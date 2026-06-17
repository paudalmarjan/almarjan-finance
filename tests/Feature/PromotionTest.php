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
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected AcademicYear $sourceYear;
    protected AcademicYear $targetYear;
    protected Level $kbLevel;
    protected Level $tkaLevel;
    protected StudentGroup $kbGroup;
    protected StudentGroup $tkaGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);

        // Academic Years
        $this->sourceYear = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $this->targetYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => false,
        ]);

        // Levels & Groups
        $this->kbLevel = Level::create(['name' => 'KB']);
        $this->tkaLevel = Level::create(['name' => 'TKA']);

        $this->kbGroup = StudentGroup::create([
            'level_id' => $this->kbLevel->id,
            'name' => 'KB-A',
        ]);

        $this->tkaGroup = StudentGroup::create([
            'level_id' => $this->tkaLevel->id,
            'name' => 'TKA-A',
        ]);

        // Create a basic fee component in target year
        AnnualFeeComponent::create([
            'academic_year_id' => $this->targetYear->id,
            'level_id' => $this->tkaLevel->id,
            'name' => 'Uang Gedung',
            'amount' => 1000000,
        ]);
    }

    public function test_can_promote_student_successfully(): void
    {
        $student = Student::create([
            'name' => 'John Doe',
            'parent_name' => 'Jane Doe',
            'phone_number' => '08123456789',
            'status' => 'Active',
            'nis' => 'AM250001',
        ]);

        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->sourceYear->id,
            'student_group_id' => $this->kbGroup->id,
            'discount_percentage' => 0.00,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->sourceYear->id])
            ->post(route('promotions.store'), [
                'source_group_id' => $this->kbGroup->id,
                'target_academic_year_id' => $this->targetYear->id,
                'promotions' => [
                    $enrollment->id => $this->tkaGroup->id,
                ],
            ]);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('success', 'Proses transisi kenaikan kelas berhasil dijalankan.');
        $response->assertSessionMissing('warning');

        // Check enrollment created in target year
        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $student->id,
            'academic_year_id' => $this->targetYear->id,
            'student_group_id' => $this->tkaGroup->id,
        ]);
    }

    public function test_displays_warning_alert_when_student_already_promoted(): void
    {
        $student = Student::create([
            'name' => 'John Doe',
            'parent_name' => 'Jane Doe',
            'phone_number' => '08123456789',
            'status' => 'Active',
            'nis' => 'AM250001',
        ]);

        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->sourceYear->id,
            'student_group_id' => $this->kbGroup->id,
            'discount_percentage' => 0.00,
        ]);

        // Already enrolled in the target year (e.g. earlier promotion action)
        StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->targetYear->id,
            'student_group_id' => $this->tkaGroup->id,
            'discount_percentage' => 0.00,
        ]);

        // Attempting to promote again
        $response = $this->actingAs($this->admin)
            ->withSession(['selected_academic_year_id' => $this->sourceYear->id])
            ->post(route('promotions.store'), [
                'source_group_id' => $this->kbGroup->id,
                'target_academic_year_id' => $this->targetYear->id,
                'promotions' => [
                    $enrollment->id => $this->tkaGroup->id,
                ],
            ]);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('success', 'Proses transisi kenaikan kelas selesai.');
        $response->assertSessionHas('warning', 'Beberapa siswa dilewati karena sudah terdaftar di tahun ajaran tujuan: John Doe');

        // Verify that no second student_enrollment for the target year was created
        $enrollmentCount = StudentEnrollment::where('student_id', $student->id)
            ->where('academic_year_id', $this->targetYear->id)
            ->count();
        $this->assertEquals(1, $enrollmentCount);
    }

    public function test_displays_warning_alert_when_excel_import_has_already_promoted_students(): void
    {
        $student = Student::create([
            'name' => 'Alice Smith',
            'parent_name' => 'Bob Smith',
            'phone_number' => '08123456780',
            'status' => 'Active',
            'nis' => 'AM250002',
        ]);

        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->sourceYear->id,
            'student_group_id' => $this->kbGroup->id,
            'discount_percentage' => 0.00,
        ]);

        // Pre-enroll student in target year
        StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->targetYear->id,
            'student_group_id' => $this->tkaGroup->id,
            'discount_percentage' => 0.00,
        ]);

        // Create Excel file
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'ID Pendaftaran (Jangan Ubah)');
        $sheet->setCellValue('B1', 'NIS');
        $sheet->setCellValue('C1', 'Nama Siswa');
        $sheet->setCellValue('D1', 'Kelompok Asal');
        $sheet->setCellValue('E1', 'Kelompok Baru / Status Baru');

        $sheet->setCellValue('A2', $enrollment->id);
        $sheet->setCellValue('B2', $student->nis);
        $sheet->setCellValue('C2', $student->name);
        $sheet->setCellValue('D2', $this->kbGroup->name);
        $sheet->setCellValue('E2', $this->tkaGroup->name);

        $tempPath = tempnam(sys_get_temp_dir(), 'promo_test_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'promotion_import.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($this->admin)
            ->post(route('promotions.import'), [
                'target_academic_year_id' => $this->targetYear->id,
                'file' => $uploadedFile,
            ]);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('success', 'Data transisi kenaikan kelas berhasil diproses.');
        $response->assertSessionHas('warning', 'Beberapa siswa dilewati karena sudah terdaftar di tahun ajaran tujuan: Alice Smith');

        @unlink($tempPath);
    }
}
