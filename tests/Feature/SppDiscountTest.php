<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\StudentGroup;
use App\Models\DiscountCategory;
use App\Models\AnnualFeeComponent;
use App\Models\GlobalSppSetting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentAnnualFee;

class SppDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_spp_fee_remains_at_normal_rate_without_discount(): void
    {
        // 1. Create an admin user
        $user = User::factory()->create(['role' => 'admin']);

        // 2. Setup Academic Year
        $ay = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        // 3. Setup Level and Group
        $level = Level::create(['name' => 'KB']);
        $group = StudentGroup::create(['level_id' => $level->id, 'name' => 'KB-A']);

        // 4. Setup Discount Category (e.g. 50% discount)
        $discount = DiscountCategory::create(['name' => 'Dhuafa', 'percentage' => 50.00]);

        // 5. Setup Annual Fee Component (e.g. Rp 1,000,000)
        $annualComponent = AnnualFeeComponent::create([
            'academic_year_id' => $ay->id,
            'level_id' => $level->id,
            'name' => 'Uang Gedung',
            'amount' => 1000000,
        ]);

        // 6. Setup Global SPP Setting (e.g. Rp 250,000)
        $sppSetting = GlobalSppSetting::create([
            'academic_year_id' => $ay->id,
            'amount' => 250000,
        ]);

        // 7. Register Student
        $student = Student::create([
            'name' => 'Budi',
            'parent_name' => 'Slamet',
            'phone_number' => '08123456789',
            'status' => 'Active',
            'nis' => 'AM250001',
        ]);

        // 8. Enroll Student with 50% discount
        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $ay->id,
            'student_group_id' => $group->id,
            'discount_category_id' => $discount->id,
            'discount_percentage' => 50.00,
        ]);

        // 9. Generate Student Annual Fee with discount (applied)
        StudentAnnualFee::create([
            'student_enrollment_id' => $enrollment->id,
            'annual_fee_component_id' => $annualComponent->id,
            'amount' => 500000.00, // 50% of 1,000,000
            'is_excluded' => false,
        ]);

        // 10. Test that SPP creation page shows normal SPP rate (Rp 250,000) instead of Rp 125,000
        $response = $this->actingAs($user)
            ->withSession(['selected_academic_year_id' => $ay->id])
            ->get(route('payments.create', ['student_id' => $student->id]));

        $response->assertOk();
        $response->assertViewHas('monthlySppAmount', 250000.00);
        $response->assertViewHas('baseSppAmount', 250000.00);

        // 11. Record payment for SPP Month index 2 (August)
        $response = $this->actingAs($user)
            ->withSession(['selected_academic_year_id' => $ay->id])
            ->post(route('payments.store'), [
                'student_id' => $student->id,
                'date' => '2025-08-01',
                'spp_months' => [2], // August
            ]);

        // Assert redirect to payment receipt
        $response->assertRedirect();

        // Assert that the transaction was recorded with Rp 250,000 (normal SPP, no discount)
        $this->assertDatabaseHas('payment_transactions', [
            'student_id' => $student->id,
            'academic_year_id' => $ay->id,
            'total_amount' => 250000.00,
        ]);

        $this->assertDatabaseHas('payment_details', [
            'type' => 'SPP',
            'month_index' => 2,
            'amount' => 250000.00,
        ]);
    }

    public function test_can_create_discount_category(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)
            ->post(route('settings.discounts.store'), [
                'id' => '',
                'name' => 'Diskon Baru',
                'percentage' => 25,
            ]);

        $response->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('discount_categories', [
            'name' => 'Diskon Baru',
            'percentage' => 25.00,
        ]);
    }

    public function test_can_update_discount_category(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $discount = DiscountCategory::create(['name' => 'Diskon Lama', 'percentage' => 10.00]);

        $response = $this->actingAs($user)
            ->post(route('settings.discounts.store'), [
                'id' => $discount->id,
                'name' => 'Diskon Diperbarui',
                'percentage' => 15,
            ]);

        $response->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('discount_categories', [
            'id' => $discount->id,
            'name' => 'Diskon Diperbarui',
            'percentage' => 15.00,
        ]);
    }

    public function test_cannot_delete_discount_category_in_use(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        
        $ay = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);
        $level = Level::create(['name' => 'KB']);
        $group = StudentGroup::create(['level_id' => $level->id, 'name' => 'KB-A']);
        $discount = DiscountCategory::create(['name' => 'Yatim', 'percentage' => 100.00]);
        $student = Student::create([
            'name' => 'Budi',
            'parent_name' => 'Slamet',
            'phone_number' => '08123456789',
            'status' => 'Active',
            'nis' => 'AM250001',
        ]);
        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $ay->id,
            'student_group_id' => $group->id,
            'discount_category_id' => $discount->id,
            'discount_percentage' => 100.00,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('settings.discounts.destroy', $discount->id));

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('discount_categories', ['id' => $discount->id]);
    }

    public function test_can_delete_discount_category_not_in_use(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $discount = DiscountCategory::create(['name' => 'Diskon Tidak Terpakai', 'percentage' => 10.00]);

        $response = $this->actingAs($user)
            ->delete(route('settings.discounts.destroy', $discount->id));

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('discount_categories', ['id' => $discount->id]);
    }

    public function test_discount_remains_locked_on_student_update_if_category_percentage_changes_in_settings(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        
        $ay = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);
        $level = Level::create(['name' => 'KB']);
        $group = StudentGroup::create(['level_id' => $level->id, 'name' => 'KB-A']);
        
        // 10% discount originally
        $discount = DiscountCategory::create(['name' => 'Anak Guru', 'percentage' => 10.00]);
        
        $annualComponent = AnnualFeeComponent::create([
            'academic_year_id' => $ay->id,
            'level_id' => $level->id,
            'name' => 'Uang Gedung',
            'amount' => 1000000,
        ]);

        $student = Student::create([
            'name' => 'Budi',
            'parent_name' => 'Slamet',
            'phone_number' => '08123456789',
            'status' => 'Active',
            'nis' => 'AM250001',
        ]);
        
        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $ay->id,
            'student_group_id' => $group->id,
            'discount_category_id' => $discount->id,
            'discount_percentage' => 10.00,
            'enrollment_type' => 'New',
        ]);

        // Student annual fee has 10% discount applied (Rp 900.000)
        $fee = StudentAnnualFee::create([
            'student_enrollment_id' => $enrollment->id,
            'annual_fee_component_id' => $annualComponent->id,
            'amount' => 900000.00,
            'is_excluded' => false,
        ]);

        // Now change the discount percentage in settings to 50%
        $discount->update(['percentage' => 50.00]);

        // Update student without changing category, only update details
        $response = $this->actingAs($user)
            ->withSession(['selected_academic_year_id' => $ay->id])
            ->put(route('students.update', $student->id), [
                'name' => 'Budi Prasetyo',
                'parent_name' => 'Slamet',
                'phone_number' => '08123456789',
                'enrollment_type' => 'New',
                'status' => 'Active',
                'student_group_id' => $group->id,
                'discount_category_id' => $discount->id, // unchanged
            ]);

        $response->assertRedirect(route('students.index'));
        
        // Verify enrollment discount percentage is STILL 10% (locked)
        $this->assertDatabaseHas('student_enrollments', [
            'id' => $enrollment->id,
            'discount_percentage' => 10.00,
        ]);

        // Verify annual fee is STILL Rp 900,000 (locked)
        $this->assertDatabaseHas('student_annual_fees', [
            'id' => $fee->id,
            'amount' => 900000.00,
        ]);
    }

    public function test_discount_recalculates_on_student_update_if_category_id_is_changed(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        
        $ay = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);
        $level = Level::create(['name' => 'KB']);
        $group = StudentGroup::create(['level_id' => $level->id, 'name' => 'KB-A']);
        
        $discount10 = DiscountCategory::create(['name' => 'Anak Guru', 'percentage' => 10.00]);
        $discount50 = DiscountCategory::create(['name' => 'Dhuafa', 'percentage' => 50.00]);
        
        $annualComponent = AnnualFeeComponent::create([
            'academic_year_id' => $ay->id,
            'level_id' => $level->id,
            'name' => 'Uang Gedung',
            'amount' => 1000000,
        ]);

        $student = Student::create([
            'name' => 'Budi',
            'parent_name' => 'Slamet',
            'phone_number' => '08123456789',
            'status' => 'Active',
            'nis' => 'AM250001',
        ]);
        
        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $ay->id,
            'student_group_id' => $group->id,
            'discount_category_id' => $discount10->id,
            'discount_percentage' => 10.00,
            'enrollment_type' => 'New',
        ]);

        $fee = StudentAnnualFee::create([
            'student_enrollment_id' => $enrollment->id,
            'annual_fee_component_id' => $annualComponent->id,
            'amount' => 900000.00,
            'is_excluded' => false,
        ]);

        // Explicitly update category ID to discount50 (50%)
        $response = $this->actingAs($user)
            ->withSession(['selected_academic_year_id' => $ay->id])
            ->put(route('students.update', $student->id), [
                'name' => 'Budi',
                'parent_name' => 'Slamet',
                'phone_number' => '08123456789',
                'enrollment_type' => 'New',
                'status' => 'Active',
                'student_group_id' => $group->id,
                'discount_category_id' => $discount50->id, // changed
            ]);

        $response->assertRedirect(route('students.index'));
        
        // Verify enrollment is updated to 50%
        $this->assertDatabaseHas('student_enrollments', [
            'id' => $enrollment->id,
            'discount_percentage' => 50.00,
        ]);

        // Verify annual fee is recalculated to Rp 500,000
        $this->assertDatabaseHas('student_annual_fees', [
            'id' => $fee->id,
            'amount' => 500000.00,
        ]);
    }
}
