<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\StudentGroup;
use App\Models\Student;
use App\Models\StudentEnrollment;

class StudentGroupLevelValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AcademicYear $academicYear;
    private Level $levelTka;
    private Level $levelTkb;
    private StudentGroup $groupTkaA;
    private StudentGroup $groupTkaB;
    private StudentGroup $groupTkbA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);

        $this->academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $this->levelTka = Level::create(['name' => 'TKA']);
        $this->levelTkb = Level::create(['name' => 'TKB']);

        $this->groupTkaA = StudentGroup::create(['level_id' => $this->levelTka->id, 'name' => 'TKA-A']);
        $this->groupTkaB = StudentGroup::create(['level_id' => $this->levelTka->id, 'name' => 'TKA-B']);
        $this->groupTkbA = StudentGroup::create(['level_id' => $this->levelTkb->id, 'name' => 'TKB-A']);
    }

    public function test_store_requires_student_group_to_belong_to_selected_level(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->post(route('students.store'), [
                'name' => 'Budi',
                'parent_name' => 'Slamet',
                'phone_number' => '08123456789',
                'enrollment_type' => 'New',
                'level_id' => $this->levelTka->id,
                'student_group_id' => $this->groupTkbA->id, // Mismatched group (TKB-A under level TKA)
            ]);

        $response->assertSessionHasErrors(['student_group_id']);
        
        // Assert no student was created
        $this->assertDatabaseMissing('students', [
            'name' => 'Budi',
        ]);
    }

    public function test_store_succeeds_with_correct_level_and_group(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->post(route('students.store'), [
                'name' => 'Budi',
                'parent_name' => 'Slamet',
                'phone_number' => '08123456789',
                'enrollment_type' => 'New',
                'level_id' => $this->levelTka->id,
                'student_group_id' => $this->groupTkaA->id, // Matching group (TKA-A under level TKA)
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('students.index'));
        
        $this->assertDatabaseHas('students', [
            'name' => 'Budi',
        ]);
    }

    public function test_update_requires_student_group_to_belong_to_same_level(): void
    {
        // 1. Create a student enrolled in TKA-A
        $student = Student::create([
            'name' => 'Budi',
            'parent_name' => 'Slamet',
            'phone_number' => '08123456789',
            'status' => 'Active',
            'nis' => 'AM250001',
        ]);

        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'student_group_id' => $this->groupTkaA->id, // Level: TKA
        ]);

        // 2. Try to update student to TKB-A (Level: TKB, which is different)
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->put(route('students.update', $student->id), [
                'name' => 'Budi Updated',
                'parent_name' => 'Slamet',
                'phone_number' => '08123456789',
                'status' => 'Active',
                'student_group_id' => $this->groupTkbA->id, // Mismatched level group
            ]);

        $response->assertSessionHasErrors(['student_group_id']);
        
        // Assert DB was not updated with the mismatched group
        $enrollment->refresh();
        $this->assertEquals($this->groupTkaA->id, $enrollment->student_group_id);
    }

    public function test_update_succeeds_with_group_in_same_level(): void
    {
        // 1. Create a student enrolled in TKA-A
        $student = Student::create([
            'name' => 'Budi',
            'parent_name' => 'Slamet',
            'phone_number' => '08123456789',
            'status' => 'Active',
            'nis' => 'AM250001',
        ]);

        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'student_group_id' => $this->groupTkaA->id, // Level: TKA
            'enrollment_type' => 'New',
        ]);

        // 2. Update student to TKA-B (Level: TKA, same level)
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->put(route('students.update', $student->id), [
                'name' => 'Budi Updated',
                'parent_name' => 'Slamet',
                'phone_number' => '08123456789',
                'enrollment_type' => 'New',
                'status' => 'Active',
                'student_group_id' => $this->groupTkaB->id, // Matching level group
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('students.index'));
        
        $enrollment->refresh();
        $this->assertEquals($this->groupTkaB->id, $enrollment->student_group_id);
    }

    public function test_admin_can_cancel_enrollment_on_inactive_academic_year_without_payments(): void
    {
        // 1. Set academic year to inactive
        $this->academicYear->update(['is_active' => false]);

        // 2. Create student & enrollment
        $student = Student::create([
            'name' => 'Budi',
            'parent_name' => 'Slamet',
            'phone_number' => '08123456789',
            'status' => 'Active',
            'nis' => 'AM250001',
        ]);

        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'student_group_id' => $this->groupTkaA->id,
        ]);

        // Create an annual fee component
        $component = \App\Models\AnnualFeeComponent::create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->levelTka->id,
            'name' => 'Uang Pangkal',
            'amount' => 100000,
        ]);

        // Create some student annual fees
        $fee = \App\Models\StudentAnnualFee::create([
            'student_enrollment_id' => $enrollment->id,
            'annual_fee_component_id' => $component->id,
            'amount' => 100000,
            'is_excluded' => false,
        ]);

        // 3. Admin deletes enrollment
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->delete(route('students.cancel-enrollment', $student->id));

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('success');

        // Assert enrollment and annual fees are deleted
        $this->assertDatabaseMissing('student_enrollments', ['id' => $enrollment->id]);
        $this->assertDatabaseMissing('student_annual_fees', ['id' => $fee->id]);
        
        // Basic student record should still exist
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_cannot_cancel_enrollment_on_active_academic_year(): void
    {
        // 1. Academic year is active (default)
        $this->assertTrue($this->academicYear->is_active);

        // 2. Create student & enrollment
        $student = Student::create([
            'name' => 'Budi',
            'parent_name' => 'Slamet',
            'phone_number' => '08123456789',
            'status' => 'Active',
            'nis' => 'AM250001',
        ]);

        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'student_group_id' => $this->groupTkaA->id,
        ]);

        // 3. Admin deletes enrollment (fails because active year)
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->delete(route('students.cancel-enrollment', $student->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Pembatalan pendaftaran hanya diperbolehkan pada Tahun Ajaran yang tidak aktif.');

        $this->assertDatabaseHas('student_enrollments', ['id' => $enrollment->id]);
    }

    public function test_cannot_cancel_enrollment_with_existing_payments(): void
    {
        // 1. Set academic year to inactive
        $this->academicYear->update(['is_active' => false]);

        // 2. Create student & enrollment
        $student = Student::create([
            'name' => 'Budi',
            'parent_name' => 'Slamet',
            'phone_number' => '08123456789',
            'status' => 'Active',
            'nis' => 'AM250001',
        ]);

        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'student_group_id' => $this->groupTkaA->id,
        ]);

        // 3. Create a payment transaction
        \App\Models\PaymentTransaction::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->admin->id,
            'date' => '2025-08-01',
            'total_amount' => 100000.00,
            'receipt_number' => 'REC-0001',
        ]);

        // 4. Admin deletes enrollment (fails because of existing payments)
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->delete(route('students.cancel-enrollment', $student->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Pendaftaran tidak dapat dibatalkan karena siswa sudah memiliki transaksi pembayaran di Tahun Ajaran ini.');

        $this->assertDatabaseHas('student_enrollments', ['id' => $enrollment->id]);
    }

    public function test_teacher_cannot_cancel_enrollment(): void
    {
        $this->academicYear->update(['is_active' => false]);
        $teacher = User::factory()->create(['role' => 'teacher']);

        $student = Student::create([
            'name' => 'Budi',
            'parent_name' => 'Slamet',
            'phone_number' => '08123456789',
            'status' => 'Active',
            'nis' => 'AM250001',
        ]);

        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'student_group_id' => $this->groupTkaA->id,
        ]);

        // Teacher deletes enrollment (fails with 302 redirect back by PreventTeacherWriteOnPastYears middleware)
        $response = $this->actingAs($teacher)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->delete(route('students.cancel-enrollment', $student->id));

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('student_enrollments', ['id' => $enrollment->id]);
    }

    public function test_store_student_succeeds_without_parent_name_and_phone_number(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->post(route('students.store'), [
                'name' => 'Siswa Mandiri',
                'parent_name' => '',
                'phone_number' => null,
                'enrollment_type' => 'New',
                'level_id' => $this->levelTka->id,
                'student_group_id' => $this->groupTkaA->id,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('students', [
            'name' => 'Siswa Mandiri',
            'parent_name' => null,
            'phone_number' => null,
        ]);
    }

    public function test_update_student_succeeds_without_parent_name_and_phone_number(): void
    {
        $student = Student::create([
            'name' => 'Siswa Mandiri',
            'parent_name' => 'Sebelumnya',
            'phone_number' => '0812',
            'status' => 'Active',
            'nis' => 'AM250001',
        ]);

        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'student_group_id' => $this->groupTkaA->id,
            'enrollment_type' => 'New',
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->put(route('students.update', $student->id), [
                'name' => 'Siswa Mandiri Baru',
                'parent_name' => '',
                'phone_number' => null,
                'enrollment_type' => 'New',
                'status' => 'Active',
                'student_group_id' => $this->groupTkaA->id,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Siswa Mandiri Baru',
            'parent_name' => null,
            'phone_number' => null,
        ]);
    }

    public function test_store_student_as_returning_allocates_returning_fees(): void
    {
        // 1. Setup components
        $compNew = \App\Models\AnnualFeeComponent::create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->levelTka->id,
            'name' => 'Uang Pangkal Baru',
            'amount' => 500000.00,
            'target_type' => 'New',
        ]);

        $compRet = \App\Models\AnnualFeeComponent::create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->levelTka->id,
            'name' => 'Uang Daftar Ulang Lama',
            'amount' => 300000.00,
            'target_type' => 'Returning',
        ]);

        $compAll = \App\Models\AnnualFeeComponent::create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->levelTka->id,
            'name' => 'Uang Kegiatan Semua',
            'amount' => 200000.00,
            'target_type' => 'All',
        ]);

        // 2. Register as Returning
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->post(route('students.store'), [
                'name' => 'Siswa Lama Pindahan',
                'parent_name' => 'Orang Tua',
                'phone_number' => '0812',
                'enrollment_type' => 'Returning',
                'level_id' => $this->levelTka->id,
                'student_group_id' => $this->groupTkaA->id,
            ]);

        $response->assertSessionHasNoErrors();
        
        $enrollment = StudentEnrollment::where('academic_year_id', $this->academicYear->id)
            ->whereHas('student', function($q) { $q->where('name', 'Siswa Lama Pindahan'); })
            ->first();

        $this->assertNotNull($enrollment);
        $this->assertEquals('Returning', $enrollment->enrollment_type);

        // Should have compRet (300k) + compAll (200k) = 500k total
        // Should NOT have compNew (500k)
        $fees = $enrollment->studentAnnualFees;
        $this->assertCount(2, $fees);
        $this->assertEquals(500000.00, $fees->sum('amount'));
        $this->assertTrue($fees->contains('annual_fee_component_id', $compRet->id));
        $this->assertTrue($fees->contains('annual_fee_component_id', $compAll->id));
        $this->assertFalse($fees->contains('annual_fee_component_id', $compNew->id));
    }

    public function test_update_student_enrollment_type_regenerates_fees(): void
    {
        // 1. Setup components
        $compNew = \App\Models\AnnualFeeComponent::create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->levelTka->id,
            'name' => 'Uang Pangkal Baru',
            'amount' => 500000.00,
            'target_type' => 'New',
        ]);

        $compRet = \App\Models\AnnualFeeComponent::create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->levelTka->id,
            'name' => 'Uang Daftar Ulang Lama',
            'amount' => 300000.00,
            'target_type' => 'Returning',
        ]);

        // 2. Create student enrolled as New
        $student = Student::create([
            'name' => 'Siswa Labil',
            'status' => 'Active',
            'nis' => 'AM259999',
        ]);

        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'student_group_id' => $this->groupTkaA->id,
            'enrollment_type' => 'New',
        ]);

        \App\Models\StudentAnnualFee::create([
            'student_enrollment_id' => $enrollment->id,
            'annual_fee_component_id' => $compNew->id,
            'amount' => $compNew->amount,
        ]);

        // 3. Update to Returning
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->put(route('students.update', $student->id), [
                'name' => 'Siswa Labil',
                'enrollment_type' => 'Returning',
                'status' => 'Active',
                'student_group_id' => $this->groupTkaA->id,
            ]);

        $response->assertSessionHasNoErrors();
        
        $enrollment->refresh();
        $this->assertEquals('Returning', $enrollment->enrollment_type);

        // Old fee (compNew) should be deleted, new fee (compRet) should be created
        $fees = $enrollment->studentAnnualFees;
        $this->assertCount(1, $fees);
        $this->assertEquals($compRet->id, $fees->first()->annual_fee_component_id);
    }

    public function test_cannot_update_student_enrollment_type_with_payments(): void
    {
        // 1. Setup components
        $compNew = \App\Models\AnnualFeeComponent::create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->levelTka->id,
            'name' => 'Uang Pangkal Baru',
            'amount' => 500000.00,
            'target_type' => 'New',
        ]);

        // 2. Create student enrolled as New
        $student = Student::create([
            'name' => 'Siswa Labil',
            'status' => 'Active',
            'nis' => 'AM259999',
        ]);

        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'student_group_id' => $this->groupTkaA->id,
            'enrollment_type' => 'New',
        ]);

        $fee = \App\Models\StudentAnnualFee::create([
            'student_enrollment_id' => $enrollment->id,
            'annual_fee_component_id' => $compNew->id,
            'amount' => $compNew->amount,
        ]);

        // 3. Record a payment
        $transaction = \App\Models\PaymentTransaction::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'receipt_number' => 'REC250001',
            'date' => '2025-07-15',
            'total_amount' => 100000.00,
            'payment_method' => 'Cash',
        ]);

        \App\Models\PaymentDetail::create([
            'payment_transaction_id' => $transaction->id,
            'type' => 'AnnualFee',
            'student_annual_fee_id' => $fee->id,
            'amount' => 100000.00,
        ]);

        // 4. Try updating to Returning (should be blocked in controller from changing the type)
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->put(route('students.update', $student->id), [
                'name' => 'Siswa Labil',
                'enrollment_type' => 'Returning',
                'status' => 'Active',
                'student_group_id' => $this->groupTkaA->id,
            ]);

        $response->assertSessionHasNoErrors();
        
        $enrollment->refresh();
        // Enrollment type should STILL be 'New' because payments exist
        $this->assertEquals('New', $enrollment->enrollment_type);

        $fees = $enrollment->studentAnnualFees;
        $this->assertCount(1, $fees);
        $this->assertEquals($compNew->id, $fees->first()->annual_fee_component_id);
    }
}
