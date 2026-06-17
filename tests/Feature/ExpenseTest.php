<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\ExpenseCategory;
use Illuminate\Http\UploadedFile;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_expense_requires_attachment(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $category = ExpenseCategory::create(['name' => 'ATK']);
        
        $ay = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        // 1. Submit without attachment
        $response = $this->actingAs($user)
            ->withSession(['selected_academic_year_id' => $ay->id])
            ->post(route('expenses.store'), [
                'expense_category_id' => $category->id,
                'date' => '2025-08-01',
                'amount' => 150000,
                'notes' => 'Beli kertas A4',
            ]);

        $response->assertSessionHasErrors(['attachment']);
    }

    public function test_creating_expense_fails_with_invalid_format(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $category = ExpenseCategory::create(['name' => 'ATK']);
        $ay = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        // txt file is not allowed
        $invalidFile = UploadedFile::fake()->create('receipt.txt', 100);

        $response = $this->actingAs($user)
            ->withSession(['selected_academic_year_id' => $ay->id])
            ->post(route('expenses.store'), [
                'expense_category_id' => $category->id,
                'date' => '2025-08-01',
                'amount' => 150000,
                'notes' => 'Beli kertas A4',
                'attachment' => $invalidFile,
            ]);

        $response->assertSessionHasErrors(['attachment']);
    }

    public function test_creating_expense_succeeds_with_valid_formats(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $category = ExpenseCategory::create(['name' => 'ATK']);
        $ay = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $validFormats = ['receipt.jpg', 'receipt.pdf', 'receipt.docx'];

        foreach ($validFormats as $fileName) {
            $file = UploadedFile::fake()->create($fileName, 100);

            $response = $this->actingAs($user)
                ->withSession(['selected_academic_year_id' => $ay->id])
                ->post(route('expenses.store'), [
                    'expense_category_id' => $category->id,
                    'date' => '2025-08-01',
                    'amount' => 150000,
                    'notes' => 'Beli kertas A4',
                    'attachment' => $file,
                ]);

            $response->assertRedirect(route('expenses.index'));
            $response->assertSessionHasNoErrors();
            
            $this->assertDatabaseHas('expenses', [
                'expense_category_id' => $category->id,
                'amount' => 150000,
            ]);
        }
    }

    public function test_export_lpj_compiles_pdf(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $category = ExpenseCategory::create(['name' => 'ATK']);
        $ay = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        // Create an expense with an attachment (a fake JPG image)
        $file = UploadedFile::fake()->image('receipt.jpg');
        
        $this->actingAs($user)
            ->withSession(['selected_academic_year_id' => $ay->id])
            ->post(route('expenses.store'), [
                'expense_category_id' => $category->id,
                'date' => '2025-08-01',
                'amount' => 150000,
                'notes' => 'Beli spidol whiteboard',
                'attachment' => $file,
            ]);

        // Submit LPJ Export request
        $response = $this->actingAs($user)
            ->withSession(['selected_academic_year_id' => $ay->id])
            ->post(route('reports.export-lpj'), [
                'start_date' => '2025-08-01',
                'end_date' => '2025-08-31',
            ]);

        // Assert that the response is a PDF download
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
    }
}
