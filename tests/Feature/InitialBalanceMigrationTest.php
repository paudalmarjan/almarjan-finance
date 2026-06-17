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
use App\Models\PaymentDetail;

class InitialBalanceMigrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AcademicYear $academicYear;
    private Level $level;
    private StudentGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);

        // Academic year with initial cash balance
        $this->academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
            'initial_cash_balance' => 5000000.00, // 5 Million
        ]);

        $this->level = Level::create(['name' => 'TKA']);
        $this->group = StudentGroup::create(['level_id' => $this->level->id, 'name' => 'TKA-A']);
    }

    public function test_can_create_academic_year_with_initial_cash_balance(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('settings.academic-years.store'), [
                'name' => '2026/2027',
                'start_date' => '2026-07-01',
                'end_date' => '2027-06-30',
                'initial_cash_balance' => 10000000.00, // 10 Million
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('academic_years', [
            'name' => '2026/2027',
            'initial_cash_balance' => 10000000.00,
        ]);
    }

    public function test_can_update_academic_year_initial_cash_balance(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('settings.academic-years.update', $this->academicYear->id), [
                'name' => '2025/2026',
                'start_date' => '2025-07-01',
                'end_date' => '2026-06-30',
                'initial_cash_balance' => 7500000.00, // Update to 7.5 Million
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('settings.index'));

        $this->academicYear->refresh();
        $this->assertEquals(7500000.00, $this->academicYear->initial_cash_balance);
    }

    public function test_initial_cash_balance_is_included_in_dashboard_and_reports(): void
    {
        // View Dashboard
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('currentBalance', 5000000.00); // Equal to initial cash since no transactions exist

        // View Finance Report
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->academicYear->id])
            ->get(route('reports.finance'));

        $response->assertOk();
        $response->assertViewHas('endingBalance', 5000000.00);
    }

    public function test_settings_page_recommends_initial_cash_balance_based_on_predecessor_year(): void
    {
        // 1. Create a payment in 2025/2026 to increase cash balance
        $student = Student::create([
            'name' => 'Test Student',
            'parent_name' => 'Parent',
            'phone_number' => '0812345678',
            'status' => 'Active',
            'nis' => 'AM250002',
        ]);
        
        $tx = PaymentTransaction::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->admin->id,
            'date' => '2025-08-01',
            'total_amount' => 1000000.00,
            'receipt_number' => 'BYR-TEST-0001',
        ]);

        PaymentDetail::create([
            'payment_transaction_id' => $tx->id,
            'type' => 'SPP',
            'month_index' => 2,
            'amount' => 1000000.00,
        ]);

        // 2. Fetch the settings page and verify recommendedNewBalance is 6 Million (5 Million initial + 1 Million payment)
        $response = $this->actingAs($this->admin)
            ->get(route('settings.index'));

        $response->assertOk();
        $response->assertViewHas('recommendedNewBalance', 6000000.00);
    }
}
