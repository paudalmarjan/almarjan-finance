<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\StudentGroup;
use App\Models\AnnualFeeComponent;
use App\Models\Student;
use App\Models\StudentEnrollment;

class TargetedFeeAllocationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AcademicYear $year25;
    private AcademicYear $year26;
    private Level $kb;
    private Level $tka;
    private StudentGroup $kbGroup;
    private StudentGroup $tkaGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);

        // Set up years
        $this->year25 = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $this->year26 = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => false,
        ]);

        $this->kb = Level::create(['name' => 'KB']);
        $this->tka = Level::create(['name' => 'TKA']);

        $this->kbGroup = StudentGroup::create(['level_id' => $this->kb->id, 'name' => 'KB-A']);
        $this->tkaGroup = StudentGroup::create(['level_id' => $this->tka->id, 'name' => 'TKA-A']);

        // Set up components for TKA in year 25
        AnnualFeeComponent::create([
            'academic_year_id' => $this->year25->id,
            'level_id' => $this->tka->id,
            'name' => 'Uang Pendaftaran (Siswa Baru)',
            'amount' => 100000.00,
            'target_type' => 'New',
        ]);

        AnnualFeeComponent::create([
            'academic_year_id' => $this->year25->id,
            'level_id' => $this->tka->id,
            'name' => 'Uang Gedung Daftar Ulang (Siswa Lama)',
            'amount' => 400000.00,
            'target_type' => 'Returning',
        ]);

        AnnualFeeComponent::create([
            'academic_year_id' => $this->year25->id,
            'level_id' => $this->tka->id,
            'name' => 'Uang Seragam (Semua Siswa)',
            'amount' => 300000.00,
            'target_type' => 'All',
        ]);
    }

    public function test_new_student_enrollment_allocates_only_all_and_new_components(): void
    {
        // Register new student in 2025/2026 TKA
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->year25->id])
            ->post(route('students.store'), [
                'name' => 'Siswa Baru TKA',
                'parent_name' => 'Bapak Baru',
                'phone_number' => '081299998888',
                'enrollment_type' => 'New',
                'level_id' => $this->tka->id,
                'student_group_id' => $this->tkaGroup->id,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('students.index'));

        // Check enrollment
        $enrollment = StudentEnrollment::where('academic_year_id', $this->year25->id)->first();
        $this->assertNotNull($enrollment);
        $this->assertEquals('New', $enrollment->enrollment_type);

        // Should have Uang Pendaftaran (100k) + Uang Seragam (300k). Total = 400k.
        // Should NOT have Uang Gedung Daftar Ulang (Returning/Lama component).
        $fees = $enrollment->studentAnnualFees;
        $this->assertCount(2, $fees);
        $this->assertEquals(400000.00, $fees->sum('amount'));

        $this->assertTrue($fees->contains(function ($fee) {
            return $fee->annualFeeComponent->name === 'Uang Pendaftaran (Siswa Baru)';
        }));
        $this->assertTrue($fees->contains(function ($fee) {
            return $fee->annualFeeComponent->name === 'Uang Seragam (Semua Siswa)';
        }));
        $this->assertFalse($fees->contains(function ($fee) {
            return $fee->annualFeeComponent->name === 'Uang Gedung Daftar Ulang (Siswa Lama)';
        }));
    }

    public function test_promoted_student_enrollment_allocates_only_all_and_returning_components(): void
    {
        // 1. Create a student enrolled in KB in year 25
        $student = Student::create([
            'name' => 'Siswa Lama KB',
            'parent_name' => 'Bapak Lama',
            'phone_number' => '081277776666',
            'status' => 'Active',
            'nis' => 'AM250099',
        ]);

        $sourceEnrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->year25->id,
            'student_group_id' => $this->kbGroup->id,
            'enrollment_type' => 'New',
        ]);

        // Setup TKA components for year 26 (Target Year)
        AnnualFeeComponent::create([
            'academic_year_id' => $this->year26->id,
            'level_id' => $this->tka->id,
            'name' => 'Uang Pendaftaran (Siswa Baru)',
            'amount' => 100000.00,
            'target_type' => 'New',
        ]);

        AnnualFeeComponent::create([
            'academic_year_id' => $this->year26->id,
            'level_id' => $this->tka->id,
            'name' => 'Uang Gedung Daftar Ulang (Siswa Lama)',
            'amount' => 400000.00,
            'target_type' => 'Returning',
        ]);

        AnnualFeeComponent::create([
            'academic_year_id' => $this->year26->id,
            'level_id' => $this->tka->id,
            'name' => 'Uang Seragam (Semua Siswa)',
            'amount' => 300000.00,
            'target_type' => 'All',
        ]);

        // 2. Promote the student from KB to TKA in year 26
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->year25->id])
            ->post(route('promotions.store'), [
                'source_group_id' => $this->kbGroup->id,
                'target_academic_year_id' => $this->year26->id,
                'promotions' => [
                    $sourceEnrollment->id => $this->tkaGroup->id
                ]
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('students.index'));

        // Check target enrollment
        $targetEnrollment = StudentEnrollment::where('academic_year_id', $this->year26->id)
            ->where('student_id', $student->id)
            ->first();
        
        $this->assertNotNull($targetEnrollment);
        $this->assertEquals('Returning', $targetEnrollment->enrollment_type);

        // Should have Uang Gedung Daftar Ulang (400k) + Uang Seragam (300k). Total = 700k.
        // Should NOT have Uang Pendaftaran (New component).
        $fees = $targetEnrollment->studentAnnualFees;
        $this->assertCount(2, $fees);
        $this->assertEquals(700000.00, $fees->sum('amount'));

        $this->assertTrue($fees->contains(function ($fee) {
            return $fee->annualFeeComponent->name === 'Uang Gedung Daftar Ulang (Siswa Lama)';
        }));
        $this->assertTrue($fees->contains(function ($fee) {
            return $fee->annualFeeComponent->name === 'Uang Seragam (Semua Siswa)';
        }));
        $this->assertFalse($fees->contains(function ($fee) {
            return $fee->annualFeeComponent->name === 'Uang Pendaftaran (Siswa Baru)';
        }));
    }

    public function test_admin_can_bulk_create_annual_fee_components_for_multiple_levels(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->year25->id])
            ->post(route('settings.fees.store'), [
                'level_ids' => [$this->kb->id, $this->tka->id],
                'name' => 'Buku Kegiatan Kelas',
                'amount' => 150000.00,
                'target_type' => 'All',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('settings.index'));

        // Assert database has the components for both levels
        $this->assertDatabaseHas('annual_fee_components', [
            'academic_year_id' => $this->year25->id,
            'level_id' => $this->kb->id,
            'name' => 'Buku Kegiatan Kelas',
            'amount' => 150000.00,
            'target_type' => 'All',
        ]);

        $this->assertDatabaseHas('annual_fee_components', [
            'academic_year_id' => $this->year25->id,
            'level_id' => $this->tka->id,
            'name' => 'Buku Kegiatan Kelas',
            'amount' => 150000.00,
            'target_type' => 'All',
        ]);
    }

    public function test_admin_cannot_create_annual_fee_component_without_levels(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->year25->id])
            ->post(route('settings.fees.store'), [
                'level_ids' => [],
                'name' => 'Buku Tanpa Level',
                'amount' => 50000.00,
                'target_type' => 'All',
            ]);

        $response->assertSessionHasErrors(['level_ids']);
    }

    public function test_admin_cannot_create_annual_fee_component_with_invalid_levels(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->year25->id])
            ->post(route('settings.fees.store'), [
                'level_ids' => [9999], // invalid level ID
                'name' => 'Buku Level Salah',
                'amount' => 50000.00,
                'target_type' => 'All',
            ]);

        $response->assertSessionHasErrors(['level_ids.0']);
    }
}
