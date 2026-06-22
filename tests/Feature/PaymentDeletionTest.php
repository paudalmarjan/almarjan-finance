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
use App\Models\AnnualFeeComponent;
use App\Models\StudentAnnualFee;
use App\Models\PaymentTransaction;
use App\Models\PaymentDetail;

class PaymentDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $financeAdmin;
    private User $teacher;
    private AcademicYear $academicYear;
    private Level $level;
    private StudentGroup $group;
    private Student $student;
    private StudentEnrollment $enrollment;
    private StudentAnnualFee $studentAnnualFee;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Users with different roles
        $this->superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->financeAdmin = User::factory()->create(['role' => 'finance_admin']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);

        // Set up active academic year
        $this->academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $this->level = Level::create(['name' => 'TKA']);
        $this->group = StudentGroup::create(['level_id' => $this->level->id, 'name' => 'TKA-A']);

        // Create student
        $this->student = Student::create([
            'name' => 'John Doe',
            'nis' => '12345',
            'status' => 'Active',
        ]);

        // Enroll student
        $this->enrollment = StudentEnrollment::create([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->academicYear->id,
            'student_group_id' => $this->group->id,
            'enrollment_type' => 'New',
        ]);

        // Create an annual fee component and link it to the student
        $annualComponent = AnnualFeeComponent::create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'name' => 'Uang Pangkal',
            'amount' => 1000000.00,
            'target_type' => 'All',
        ]);

        $this->studentAnnualFee = StudentAnnualFee::create([
            'student_enrollment_id' => $this->enrollment->id,
            'annual_fee_component_id' => $annualComponent->id,
            'amount' => 1000000.00,
            'is_excluded' => false,
        ]);
    }

    public function test_super_admin_can_delete_payment_and_balances_are_restored(): void
    {
        // 1. Record a payment transaction for both Annual Fee and SPP
        $tx = PaymentTransaction::create([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->financeAdmin->id,
            'date' => '2025-08-01',
            'total_amount' => 600000.00, // 400,000 for annual, 200,000 for SPP (dummy amount)
            'receipt_number' => 'BYR-20250801-0001',
        ]);

        $detailAnnual = PaymentDetail::create([
            'payment_transaction_id' => $tx->id,
            'type' => 'Annual',
            'reference_id' => $this->studentAnnualFee->id,
            'amount' => 400000.00,
        ]);

        $detailSpp = PaymentDetail::create([
            'payment_transaction_id' => $tx->id,
            'type' => 'SPP',
            'month_index' => 2, // August
            'amount' => 200000.00,
        ]);

        // Verify initial state:
        // Annual fee paid amount should be 400k, balance 600k
        $this->assertEquals(400000.00, $this->studentAnnualFee->fresh()->paid_amount);
        $this->assertEquals(600000.00, $this->studentAnnualFee->fresh()->balance);

        // Verify database has transaction
        $this->assertDatabaseHas('payment_transactions', ['id' => $tx->id]);
        $this->assertDatabaseHas('payment_details', ['id' => $detailAnnual->id]);
        $this->assertDatabaseHas('payment_details', ['id' => $detailSpp->id]);

        // 2. Perform deletion request as super admin
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->delete(route('payments.destroy', $tx->id));

        // 3. Assert successful redirection and database changes
        $response->assertRedirect(route('payments.index'));
        $response->assertSessionHas('success');

        // Verify transaction and details are deleted
        $this->assertDatabaseMissing('payment_transactions', ['id' => $tx->id]);
        $this->assertDatabaseMissing('payment_details', ['id' => $detailAnnual->id]);
        $this->assertDatabaseMissing('payment_details', ['id' => $detailSpp->id]);

        // Verify student balances are fully restored (paid amount = 0, balance = 1,000,000)
        $this->assertEquals(0.00, $this->studentAnnualFee->fresh()->paid_amount);
        $this->assertEquals(1000000.00, $this->studentAnnualFee->fresh()->balance);
    }

    public function test_finance_admin_cannot_delete_payment(): void
    {
        $tx = PaymentTransaction::create([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->financeAdmin->id,
            'date' => '2025-08-01',
            'total_amount' => 400000.00,
            'receipt_number' => 'BYR-20250801-0002',
        ]);

        $response = $this->actingAs($this->financeAdmin)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->delete(route('payments.destroy', $tx->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('payment_transactions', ['id' => $tx->id]);
    }

    public function test_teacher_cannot_delete_payment(): void
    {
        $tx = PaymentTransaction::create([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->financeAdmin->id,
            'date' => '2025-08-01',
            'total_amount' => 400000.00,
            'receipt_number' => 'BYR-20250801-0003',
        ]);

        $response = $this->actingAs($this->teacher)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->delete(route('payments.destroy', $tx->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('payment_transactions', ['id' => $tx->id]);
    }
}
