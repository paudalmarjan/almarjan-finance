<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentSaving;
use App\Models\StudentSavingsTransaction;

class StudentSavingsDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $savingsAdmin;
    private User $teacher;
    private AcademicYear $activeYear;
    private AcademicYear $inactiveYear;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->savingsAdmin = User::factory()->create(['role' => 'savings_admin']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);

        $this->activeYear = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $this->inactiveYear = AcademicYear::create([
            'name' => '2024/2025',
            'start_date' => '2024-07-01',
            'end_date' => '2025-06-30',
            'is_active' => false,
        ]);

        $this->student = Student::create([
            'name' => 'Jane Doe',
            'nis' => '54321',
            'status' => 'Active',
        ]);
    }

    public function test_super_admin_can_delete_deposit_and_balance_is_decreased(): void
    {
        $saving = StudentSaving::create([
            'student_id' => $this->student->id,
            'balance' => 100000.00,
        ]);

        $trx = StudentSavingsTransaction::create([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->activeYear->id,
            'user_id' => $this->savingsAdmin->id,
            'type' => 'Deposit',
            'amount' => 40000.00,
            'transaction_date' => '2026-01-01',
            'receipt_number' => 'SAV-20260101-00001',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('savings.destroy', $trx->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('student_savings_transactions', ['id' => $trx->id]);
        
        $saving->refresh();
        $this->assertEquals(60000.00, $saving->balance);
    }

    public function test_cannot_delete_deposit_if_resulting_balance_is_negative(): void
    {
        $saving = StudentSaving::create([
            'student_id' => $this->student->id,
            'balance' => 20000.00,
        ]);

        $trx = StudentSavingsTransaction::create([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->activeYear->id,
            'user_id' => $this->savingsAdmin->id,
            'type' => 'Deposit',
            'amount' => 50000.00,
            'transaction_date' => '2026-01-01',
            'receipt_number' => 'SAV-20260101-00001',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('savings.destroy', $trx->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('student_savings_transactions', ['id' => $trx->id]);
        
        $saving->refresh();
        $this->assertEquals(20000.00, $saving->balance);
    }

    public function test_super_admin_can_delete_withdrawal_and_balance_is_restored(): void
    {
        $saving = StudentSaving::create([
            'student_id' => $this->student->id,
            'balance' => 50000.00,
        ]);

        $trx = StudentSavingsTransaction::create([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->activeYear->id,
            'user_id' => $this->savingsAdmin->id,
            'type' => 'Withdrawal',
            'amount' => 30000.00,
            'transaction_date' => '2026-01-01',
            'receipt_number' => 'SAV-20260101-00002',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('savings.destroy', $trx->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('student_savings_transactions', ['id' => $trx->id]);
        
        $saving->refresh();
        $this->assertEquals(80000.00, $saving->balance);
    }

    public function test_non_super_admin_cannot_delete_savings_transaction(): void
    {
        $saving = StudentSaving::create([
            'student_id' => $this->student->id,
            'balance' => 100000.00,
        ]);

        $trx = StudentSavingsTransaction::create([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->activeYear->id,
            'user_id' => $this->savingsAdmin->id,
            'type' => 'Deposit',
            'amount' => 40000.00,
            'transaction_date' => '2026-01-01',
            'receipt_number' => 'SAV-20260101-00001',
        ]);

        $response = $this->actingAs($this->savingsAdmin)
            ->delete(route('savings.destroy', $trx->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('student_savings_transactions', ['id' => $trx->id]);

        $response = $this->actingAs($this->teacher)
            ->delete(route('savings.destroy', $trx->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('student_savings_transactions', ['id' => $trx->id]);
    }

    public function test_cannot_delete_transaction_from_inactive_academic_year(): void
    {
        $saving = StudentSaving::create([
            'student_id' => $this->student->id,
            'balance' => 100000.00,
        ]);

        $trx = StudentSavingsTransaction::create([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->inactiveYear->id,
            'user_id' => $this->savingsAdmin->id,
            'type' => 'Deposit',
            'amount' => 40000.00,
            'transaction_date' => '2024-10-01',
            'receipt_number' => 'SAV-20241001-00001',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('savings.destroy', $trx->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('student_savings_transactions', ['id' => $trx->id]);
        
        $saving->refresh();
        $this->assertEquals(100000.00, $saving->balance);
    }
}
