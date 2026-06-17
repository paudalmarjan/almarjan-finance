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
use App\Models\PaymentTransaction;
use App\Models\AnnualFeeComponent;
use App\Models\StudentAnnualFee;
use App\Models\GlobalSppSetting;

class FilterEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AcademicYear $ay;
    private Level $level1;
    private Level $level2;
    private StudentGroup $group1;
    private StudentGroup $group2;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup shared baseline
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->ay = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $this->level1 = Level::create(['name' => 'TK A']);
        $this->level2 = Level::create(['name' => 'TK B']);

        $this->group1 = StudentGroup::create(['level_id' => $this->level1->id, 'name' => 'TK A-1']);
        $this->group2 = StudentGroup::create(['level_id' => $this->level2->id, 'name' => 'TK B-1']);
    }

    public function test_payment_creation_page_respects_class_group_filter(): void
    {
        // Student 1 in Group 1 (TK A-1)
        $student1 = Student::create(['name' => 'Alif', 'nis' => '1001', 'status' => 'Active']);
        StudentEnrollment::create([
            'student_id' => $student1->id,
            'academic_year_id' => $this->ay->id,
            'student_group_id' => $this->group1->id,
            'enrollment_type' => 'New',
        ]);

        // Student 2 in Group 2 (TK B-1)
        $student2 = Student::create(['name' => 'Bagas', 'nis' => '1002', 'status' => 'Active']);
        StudentEnrollment::create([
            'student_id' => $student2->id,
            'academic_year_id' => $this->ay->id,
            'student_group_id' => $this->group2->id,
            'enrollment_type' => 'Returning',
        ]);

        // Access payments.create with group 1 filter
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->ay->id])
            ->get(route('payments.create', ['student_group_id' => $this->group1->id]));

        $response->assertOk();
        $students = $response->viewData('students');
        $this->assertCount(1, $students);
        $this->assertEquals($student1->id, $students->first()->student_id);

        // Access payments.create with group 2 filter
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->ay->id])
            ->get(route('payments.create', ['student_group_id' => $this->group2->id]));

        $response->assertOk();
        $students = $response->viewData('students');
        $this->assertCount(1, $students);
        $this->assertEquals($student2->id, $students->first()->student_id);
    }

    public function test_student_index_page_respects_enrollment_type_filter(): void
    {
        // Student 1: New
        $student1 = Student::create(['name' => 'Alif', 'nis' => '1001', 'status' => 'Active']);
        StudentEnrollment::create([
            'student_id' => $student1->id,
            'academic_year_id' => $this->ay->id,
            'student_group_id' => $this->group1->id,
            'enrollment_type' => 'New',
        ]);

        // Student 2: Returning
        $student2 = Student::create(['name' => 'Bagas', 'nis' => '1002', 'status' => 'Active']);
        StudentEnrollment::create([
            'student_id' => $student2->id,
            'academic_year_id' => $this->ay->id,
            'student_group_id' => $this->group2->id,
            'enrollment_type' => 'Returning',
        ]);

        // Filter by Tipe Pendaftaran: Baru (New)
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->ay->id])
            ->get(route('students.index', ['enrollment_type' => 'New']));

        $response->assertOk();
        $enrollments = $response->viewData('enrollments');
        $this->assertCount(1, $enrollments);
        $this->assertEquals($student1->id, $enrollments->first()->student_id);

        // Filter by Tipe Pendaftaran: Lama (Returning)
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->ay->id])
            ->get(route('students.index', ['enrollment_type' => 'Returning']));

        $response->assertOk();
        $enrollments = $response->viewData('enrollments');
        $this->assertCount(1, $enrollments);
        $this->assertEquals($student2->id, $enrollments->first()->student_id);
    }

    public function test_payment_history_respects_multiple_filters(): void
    {
        // Setup Students
        $student1 = Student::create(['name' => 'Alif', 'nis' => '1001', 'status' => 'Active']);
        StudentEnrollment::create([
            'student_id' => $student1->id,
            'academic_year_id' => $this->ay->id,
            'student_group_id' => $this->group1->id,
            'enrollment_type' => 'New',
        ]);

        $student2 = Student::create(['name' => 'Bagas', 'nis' => '1002', 'status' => 'Active']);
        StudentEnrollment::create([
            'student_id' => $student2->id,
            'academic_year_id' => $this->ay->id,
            'student_group_id' => $this->group2->id,
            'enrollment_type' => 'Returning',
        ]);

        // Create transaction for Student 1
        PaymentTransaction::create([
            'student_id' => $student1->id,
            'academic_year_id' => $this->ay->id,
            'user_id' => $this->admin->id,
            'date' => '2025-08-01',
            'total_amount' => 150000,
            'receipt_number' => 'BYR-20250801-0001',
        ]);

        // Create transaction for Student 2
        PaymentTransaction::create([
            'student_id' => $student2->id,
            'academic_year_id' => $this->ay->id,
            'user_id' => $this->admin->id,
            'date' => '2025-08-02',
            'total_amount' => 200000,
            'receipt_number' => 'BYR-20250802-0002',
        ]);

        // Filter by Level 1
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->ay->id])
            ->get(route('payments.index', ['level_id' => $this->level1->id]));
        $response->assertOk();
        $this->assertCount(1, $response->viewData('transactions'));
        $this->assertEquals($student1->id, $response->viewData('transactions')->first()->student_id);

        // Filter by Group 2
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->ay->id])
            ->get(route('payments.index', ['student_group_id' => $this->group2->id]));
        $response->assertOk();
        $this->assertCount(1, $response->viewData('transactions'));
        $this->assertEquals($student2->id, $response->viewData('transactions')->first()->student_id);

        // Filter by Enrollment Type "New"
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->ay->id])
            ->get(route('payments.index', ['enrollment_type' => 'New']));
        $response->assertOk();
        $this->assertCount(1, $response->viewData('transactions'));
        $this->assertEquals($student1->id, $response->viewData('transactions')->first()->student_id);
    }

    public function test_arrears_report_respects_multiple_filters(): void
    {
        // Setup Fee configurations to trigger arrears calculation
        GlobalSppSetting::create([
            'academic_year_id' => $this->ay->id,
            'amount' => 250000,
        ]);

        $comp = AnnualFeeComponent::create([
            'academic_year_id' => $this->ay->id,
            'level_id' => $this->level1->id,
            'name' => 'Uang Gedung',
            'amount' => 500000,
        ]);

        $comp2 = AnnualFeeComponent::create([
            'academic_year_id' => $this->ay->id,
            'level_id' => $this->level2->id,
            'name' => 'Uang Buku',
            'amount' => 600000,
        ]);

        // Student 1 (New, Level 1)
        $student1 = Student::create(['name' => 'Alif', 'nis' => '1001', 'status' => 'Active']);
        $enr1 = StudentEnrollment::create([
            'student_id' => $student1->id,
            'academic_year_id' => $this->ay->id,
            'student_group_id' => $this->group1->id,
            'enrollment_type' => 'New',
        ]);
        StudentAnnualFee::create([
            'student_enrollment_id' => $enr1->id,
            'annual_fee_component_id' => $comp->id,
            'amount' => 500000,
            'is_excluded' => false,
        ]);

        // Student 2 (Returning, Level 2)
        $student2 = Student::create(['name' => 'Bagas', 'nis' => '1002', 'status' => 'Active']);
        $enr2 = StudentEnrollment::create([
            'student_id' => $student2->id,
            'academic_year_id' => $this->ay->id,
            'student_group_id' => $this->group2->id,
            'enrollment_type' => 'Returning',
        ]);
        StudentAnnualFee::create([
            'student_enrollment_id' => $enr2->id,
            'annual_fee_component_id' => $comp2->id,
            'amount' => 600000,
            'is_excluded' => false,
        ]);

        // Access arrears report with Level 1 filter
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->ay->id])
            ->get(route('reports.arrears', ['level_id' => $this->level1->id]));
        $response->assertOk();
        $arrearsList = $response->viewData('arrearsList');
        $this->assertCount(1, $arrearsList);
        $this->assertEquals($student1->id, $arrearsList[0]['student_id']);

        // Access arrears report with Group 2 filter
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->ay->id])
            ->get(route('reports.arrears', ['student_group_id' => $this->group2->id]));
        $response->assertOk();
        $arrearsList = $response->viewData('arrearsList');
        $this->assertCount(1, $arrearsList);
        $this->assertEquals($student2->id, $arrearsList[0]['student_id']);

        // Access arrears report with Enrollment Type "Returning" filter
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->ay->id])
            ->get(route('reports.arrears', ['enrollment_type' => 'Returning']));
        $response->assertOk();
        $arrearsList = $response->viewData('arrearsList');
        $this->assertCount(1, $arrearsList);
        $this->assertEquals($student2->id, $arrearsList[0]['student_id']);
    }

    public function test_fee_components_can_be_sorted_up_and_down(): void
    {
        // Create 3 components
        $compA = AnnualFeeComponent::create([
            'academic_year_id' => $this->ay->id,
            'level_id' => $this->level1->id,
            'name' => 'Komponen A',
            'amount' => 100000,
            'sort_order' => 1,
        ]);
        $compB = AnnualFeeComponent::create([
            'academic_year_id' => $this->ay->id,
            'level_id' => $this->level1->id,
            'name' => 'Komponen B',
            'amount' => 200000,
            'sort_order' => 2,
        ]);
        $compC = AnnualFeeComponent::create([
            'academic_year_id' => $this->ay->id,
            'level_id' => $this->level1->id,
            'name' => 'Komponen C',
            'amount' => 300000,
            'sort_order' => 3,
        ]);

        // Move B up (should swap sort_order of B and A)
        $response = $this->actingAs($this->admin)
            ->post(route('settings.fees.move-up', $compB->id));

        $response->assertRedirect(route('settings.index'));
        
        $this->assertEquals(2, $compA->fresh()->sort_order);
        $this->assertEquals(1, $compB->fresh()->sort_order);

        // Move B down (should swap B with A now since A has order 2 and B has order 1)
        $response = $this->actingAs($this->admin)
            ->post(route('settings.fees.move-down', $compB->id));

        $response->assertRedirect(route('settings.index'));

        $this->assertEquals(1, $compA->fresh()->sort_order);
        $this->assertEquals(2, $compB->fresh()->sort_order);
    }

    public function test_payment_creation_page_loads_annual_fees_in_sort_order(): void
    {
        // Setup student
        $student = Student::create(['name' => 'Alif', 'nis' => '1001', 'status' => 'Active']);
        $enr = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->ay->id,
            'student_group_id' => $this->group1->id,
            'enrollment_type' => 'New',
        ]);

        // Create components out of order
        $compC = AnnualFeeComponent::create([
            'academic_year_id' => $this->ay->id,
            'level_id' => $this->level1->id,
            'name' => 'Komponen C',
            'amount' => 300000,
            'sort_order' => 3,
        ]);
        $compA = AnnualFeeComponent::create([
            'academic_year_id' => $this->ay->id,
            'level_id' => $this->level1->id,
            'name' => 'Komponen A',
            'amount' => 100000,
            'sort_order' => 1,
        ]);

        StudentAnnualFee::create([
            'student_enrollment_id' => $enr->id,
            'annual_fee_component_id' => $compC->id,
            'amount' => 300000,
        ]);
        StudentAnnualFee::create([
            'student_enrollment_id' => $enr->id,
            'annual_fee_component_id' => $compA->id,
            'amount' => 100000,
        ]);

        // Load payments.create
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->ay->id])
            ->get(route('payments.create', ['student_id' => $student->id]));

        $response->assertOk();
        $annualFees = $response->viewData('annualFees');
        
        // Assert Komponen A is first (order 1) and Komponen C is second (order 3)
        $this->assertEquals($compA->id, $annualFees->first()->annual_fee_component_id);
        $this->assertEquals($compC->id, $annualFees->last()->annual_fee_component_id);
    }

    public function test_annual_fee_component_safe_delete(): void
    {
        // 1. Create a component
        $comp = AnnualFeeComponent::create([
            'academic_year_id' => $this->ay->id,
            'level_id' => $this->level1->id,
            'name' => 'Komponen Test',
            'amount' => 500000,
        ]);

        // 2. Enroll a student
        $student = Student::create(['name' => 'Alif', 'nis' => '1001', 'status' => 'Active']);
        $enr = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->ay->id,
            'student_group_id' => $this->group1->id,
            'enrollment_type' => 'New',
        ]);
        StudentAnnualFee::create([
            'student_enrollment_id' => $enr->id,
            'annual_fee_component_id' => $comp->id,
            'amount' => 500000,
        ]);

        // 3. Delete component (should succeed since no payments exist)
        $response = $this->actingAs($this->admin)
            ->delete(route('settings.fees.destroy', $comp->id));

        $response->assertRedirect(route('settings.index'));
        $this->assertDatabaseMissing('annual_fee_components', ['id' => $comp->id]);
        $this->assertDatabaseMissing('student_annual_fees', ['annual_fee_component_id' => $comp->id]);
    }

    public function test_student_search_ajax_endpoint(): void
    {
        $student = Student::create(['name' => 'Carissa Putri', 'nickname' => 'Caca', 'nis' => 'AM250001', 'status' => 'Active']);
        StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->ay->id,
            'student_group_id' => $this->group1->id,
            'enrollment_type' => 'New',
        ]);

        // Search by name
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->ay->id])
            ->get(route('students.search-ajax', ['q' => 'Carissa']));

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'Carissa Putri (Caca)',
            'nis' => 'AM250001',
            'group_name' => 'TK A-1',
        ]);

        // Search by nickname
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->ay->id])
            ->get(route('students.search-ajax', ['q' => 'Caca']));

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'Carissa Putri (Caca)',
        ]);
    }

    public function test_student_index_displays_calculated_arrears(): void
    {
        // Setup Fee configurations to trigger arrears calculation
        GlobalSppSetting::create([
            'academic_year_id' => $this->ay->id,
            'amount' => 250000,
        ]);

        $comp = AnnualFeeComponent::create([
            'academic_year_id' => $this->ay->id,
            'level_id' => $this->level1->id,
            'name' => 'Uang Gedung',
            'amount' => 500000,
        ]);

        $student = Student::create(['name' => 'Danu', 'nis' => '1005', 'status' => 'Active']);
        $enr = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->ay->id,
            'student_group_id' => $this->group1->id,
            'enrollment_type' => 'New',
        ]);

        StudentAnnualFee::create([
            'student_enrollment_id' => $enr->id,
            'annual_fee_component_id' => $comp->id,
            'amount' => 500000,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->ay->id])
            ->get(route('students.index'));

        $response->assertOk();
        $enrollments = $response->viewData('enrollments');
        $this->assertCount(1, $enrollments);
        
        $this->assertGreaterThanOrEqual(500000, $enrollments->first()->arrears_amount);
    }

    public function test_student_store_and_update_with_nickname(): void
    {
        // Store
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->ay->id])
            ->post(route('students.store'), [
                'name' => 'Budi Santoso',
                'nickname' => 'Budi',
                'parent_name' => 'Slamet',
                'phone_number' => '0812345678',
                'enrollment_type' => 'New',
                'level_id' => $this->level1->id,
                'student_group_id' => $this->group1->id,
            ]);

        $response->assertRedirect(route('students.index'));
        $student = Student::where('name', 'Budi Santoso')->first();
        $this->assertNotNull($student);
        $this->assertEquals('Budi', $student->nickname);

        // Update
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->ay->id])
            ->put(route('students.update', $student->id), [
                'name' => 'Budi Santoso',
                'nickname' => 'Bud',
                'parent_name' => 'Slamet',
                'phone_number' => '0812345678',
                'enrollment_type' => 'New',
                'status' => 'Active',
                'student_group_id' => $this->group1->id,
            ]);

        $response->assertRedirect(route('students.index'));
        $this->assertEquals('Bud', $student->fresh()->nickname);
    }
}
