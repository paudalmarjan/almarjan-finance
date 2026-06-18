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

    public function test_uploaded_image_is_resized_and_compressed(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $category = ExpenseCategory::create(['name' => 'ATK']);
        $ay = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        // Buat file gambar JPEG asli berukuran 2000x2000 piksel menggunakan GD
        $image = imagecreatetruecolor(2000, 2000);
        $bg = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bg);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'test_large_');
        imagejpeg($image, $tempFile, 100);
        imagedestroy($image);

        $file = new UploadedFile(
            $tempFile,
            'large_photo.jpg',
            'image/jpeg',
            null,
            true
        );

        $response = $this->actingAs($user)
            ->withSession(['selected_academic_year_id' => $ay->id])
            ->post(route('expenses.store'), [
                'expense_category_id' => $category->id,
                'date' => '2025-08-01',
                'amount' => 150000,
                'notes' => 'Beli spidol',
                'attachment' => $file,
            ]);

        $response->assertRedirect(route('expenses.index'));

        $expense = \App\Models\Expense::first();
        $this->assertNotNull($expense->attachment_path);

        $disk = config('filesystems.default') === 'local' ? 'public' : config('filesystems.default');
        $storedContents = \Illuminate\Support\Facades\Storage::disk($disk)->get($expense->attachment_path);
        
        // Simpan sementara konten yang tersimpan di storage untuk memeriksa dimensinya
        $tempStoredFile = tempnam(sys_get_temp_dir(), 'stored_');
        file_put_contents($tempStoredFile, $storedContents);
        
        $info = getimagesize($tempStoredFile);
        $this->assertEquals(1200, $info[0]); // Pastikan lebar resized ke 1200px
        
        // Bersihkan file
        unlink($tempStoredFile);
        if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($expense->attachment_path)) {
            \Illuminate\Support\Facades\Storage::disk($disk)->delete($expense->attachment_path);
        }
    }

    public function test_non_image_file_is_uploaded_without_modification(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $category = ExpenseCategory::create(['name' => 'ATK']);
        $ay = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $pdfContent = "%PDF-1.4 Fake PDF Content for LPJ Verification";
        $tempFile = tempnam(sys_get_temp_dir(), 'test_pdf_');
        file_put_contents($tempFile, $pdfContent);

        $file = new UploadedFile(
            $tempFile,
            'document.pdf',
            'application/pdf',
            null,
            true
        );

        $response = $this->actingAs($user)
            ->withSession(['selected_academic_year_id' => $ay->id])
            ->post(route('expenses.store'), [
                'expense_category_id' => $category->id,
                'date' => '2025-08-01',
                'amount' => 150000,
                'notes' => 'Beli spidol',
                'attachment' => $file,
            ]);

        $response->assertRedirect(route('expenses.index'));

        $expense = \App\Models\Expense::first();
        $disk = config('filesystems.default') === 'local' ? 'public' : config('filesystems.default');
        
        // Pastikan konten file PDF sama persis tanpa mengalami modifikasi/kompresi
        $this->assertEquals($pdfContent, \Illuminate\Support\Facades\Storage::disk($disk)->get($expense->attachment_path));

        // Bersihkan
        if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($expense->attachment_path)) {
            \Illuminate\Support\Facades\Storage::disk($disk)->delete($expense->attachment_path);
        }
    }

    public function test_upload_exceeding_10mb_fails_validation(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $category = ExpenseCategory::create(['name' => 'ATK']);
        $ay = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        // File 11 MB (11 * 1024 KB)
        $largeFile = UploadedFile::fake()->create('receipt.pdf', 11 * 1024);

        $response = $this->actingAs($user)
            ->withSession(['selected_academic_year_id' => $ay->id])
            ->post(route('expenses.store'), [
                'expense_category_id' => $category->id,
                'date' => '2025-08-01',
                'amount' => 150000,
                'notes' => 'Beli kertas',
                'attachment' => $largeFile,
            ]);

        $response->assertSessionHasErrors(['attachment']);
        $errors = session('errors')->get('attachment');
        $this->assertStringContainsString('maksimal adalah 10MB', $errors[0]);
    }

    public function test_upload_within_10mb_limit_succeeds(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $category = ExpenseCategory::create(['name' => 'ATK']);
        $ay = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        // File 9.5 MB (9.5 * 1024 KB)
        $file = UploadedFile::fake()->create('receipt.pdf', 9.5 * 1024);

        $response = $this->actingAs($user)
            ->withSession(['selected_academic_year_id' => $ay->id])
            ->post(route('expenses.store'), [
                'expense_category_id' => $category->id,
                'date' => '2025-08-01',
                'amount' => 150000,
                'notes' => 'Beli kertas',
                'attachment' => $file,
            ]);

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHasNoErrors();

        $expense = \App\Models\Expense::first();
        $disk = config('filesystems.default') === 'local' ? 'public' : config('filesystems.default');
        
        // Bersihkan
        if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($expense->attachment_path)) {
            \Illuminate\Support\Facades\Storage::disk($disk)->delete($expense->attachment_path);
        }
    }
}
