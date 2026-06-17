<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\StudentGroup;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\AnnualFeeComponent;
use App\Models\StudentAnnualFee;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        $selectedYear = $selectedYearId ? AcademicYear::find($selectedYearId) : null;

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $levels = Level::with('groups')->get();
        
        // Target academic years (restricted to the immediate next consecutive year)
        $targetYears = [];
        if ($selectedYear) {
            $targetYears = AcademicYear::where('start_date', '>=', $selectedYear->end_date)
                ->orderBy('start_date', 'asc')
                ->limit(1)
                ->get();
        }

        $students = [];
        $sourceGroup = null;
        $targetYear = null;
        $targetGroups = [];

        if ($request->filled('source_group_id') && $request->filled('target_academic_year_id')) {
            $sourceGroup = StudentGroup::with('level')->find($request->source_group_id);
            $targetYear = AcademicYear::find($request->target_academic_year_id);
            
            if ($sourceGroup && $targetYear) {
                // Get all active students enrolled in this group during the source year
                $students = StudentEnrollment::where('academic_year_id', $selectedYearId)
                    ->where('student_group_id', $sourceGroup->id)
                    ->whereHas('student', function ($q) {
                        $q->where('status', 'Active');
                    })
                    ->with('student')
                    ->get();

                // Determine target level (KB -> TKA, TKA -> TKB, TKB -> Graduated)
                $sourceLevelName = $sourceGroup->level->name;
                $targetLevelName = null;
                
                if ($sourceLevelName === 'KB') {
                    $targetLevelName = 'TKA';
                } elseif ($sourceLevelName === 'TKA') {
                    $targetLevelName = 'TKB';
                }

                if ($targetLevelName) {
                    $targetGroups = StudentGroup::whereHas('level', function($q) use ($targetLevelName) {
                        $q->where('name', $targetLevelName);
                    })->get();
                }
            }
        }

        return view('promotions.index', compact(
            'academicYears', 
            'levels', 
            'targetYears', 
            'students', 
            'sourceGroup', 
            'targetYear', 
            'targetGroups',
            'selectedYear'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'source_group_id' => 'required|exists:student_groups,id',
            'target_academic_year_id' => 'required|exists:academic_years,id',
            'promotions' => 'required|array', // Keys are student_enrollment_id, values are group_id or 'LULUS' or 'TIDAK_LANJUT'
        ]);

        $targetYearId = $request->target_academic_year_id;
        $promotions = $request->promotions;
        
        try {
            $skippedStudents = [];
            DB::transaction(function () use ($promotions, $targetYearId, &$skippedStudents) {
                foreach ($promotions as $sourceEnrollmentId => $action) {
                    $sourceEnrollment = StudentEnrollment::with('student')->find($sourceEnrollmentId);
                    if (!$sourceEnrollment) continue;

                    $student = $sourceEnrollment->student;

                    if ($action === 'LULUS') {
                        // Mark student as Graduated
                        $student->update(['status' => 'Graduated']);
                    } elseif ($action === 'TIDAK_LANJUT') {
                        // Mark student as Not Continuing
                        $student->update(['status' => 'Not Continuing']);
                    } elseif (is_numeric($action)) {
                        // Normal Promotion
                        $targetGroupId = (int) $action;
                        $targetGroup = StudentGroup::find($targetGroupId);
                        if (!$targetGroup) continue;

                        // Check if student already enrolled in target year
                        $alreadyEnrolled = StudentEnrollment::where('student_id', $student->id)
                            ->where('academic_year_id', $targetYearId)
                            ->exists();
                            
                        if ($alreadyEnrolled) {
                            $skippedStudents[] = $student->name;
                            continue;
                        }

                        // Create new enrollment
                        $newEnrollment = StudentEnrollment::create([
                            'student_id' => $student->id,
                            'academic_year_id' => $targetYearId,
                            'student_group_id' => $targetGroupId,
                            'discount_category_id' => $sourceEnrollment->discount_category_id,
                            'discount_percentage' => $sourceEnrollment->discount_percentage,
                            'enrollment_type' => 'Returning',
                        ]);

                        // Generate new fee components
                        $components = AnnualFeeComponent::where('academic_year_id', $targetYearId)
                            ->where('level_id', $targetGroup->level_id)
                            ->whereIn('target_type', ['All', 'Returning'])
                            ->get();

                        foreach ($components as $comp) {
                            $discountedAmount = $comp->amount * (1 - ($sourceEnrollment->discount_percentage / 100));

                            StudentAnnualFee::create([
                                'student_enrollment_id' => $newEnrollment->id,
                                'annual_fee_component_id' => $comp->id,
                                'amount' => $discountedAmount,
                                'is_excluded' => false,
                            ]);
                        }
                    }
                }
            });

            if (!empty($skippedStudents)) {
                $names = implode(', ', $skippedStudents);
                return redirect()->route('students.index')
                    ->with('success', 'Proses transisi kenaikan kelas selesai.')
                    ->with('warning', 'Beberapa siswa dilewati karena sudah terdaftar di tahun ajaran tujuan: ' . $names);
            }

            return redirect()->route('students.index')->with('success', 'Proses transisi kenaikan kelas berhasil dijalankan.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses kenaikan kelas: ' . $e->getMessage());
        }
    }

    public function exportTemplate(Request $request)
    {
        $request->validate([
            'source_group_id' => 'required|exists:student_groups,id',
            'target_academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $sourceYearId = session('selected_academic_year_id');
        $sourceGroupId = $request->source_group_id;
        $targetYearId = $request->target_academic_year_id;

        $sourceGroup = StudentGroup::with('level')->find($sourceGroupId);
        $targetYear = AcademicYear::find($targetYearId);

        $enrollments = StudentEnrollment::where('academic_year_id', $sourceYearId)
            ->where('student_group_id', $sourceGroupId)
            ->whereHas('student', function ($q) {
                $q->where('status', 'Active');
            })
            ->with('student')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'ID Pendaftaran (Jangan Ubah)');
        $sheet->setCellValue('B1', 'NIS');
        $sheet->setCellValue('C1', 'Nama Siswa');
        $sheet->setCellValue('D1', 'Kelompok Asal');
        $sheet->setCellValue('E1', 'Kelompok Baru / Status Baru');

        $rowNum = 2;
        foreach ($enrollments as $enr) {
            $sheet->setCellValue('A' . $rowNum, $enr->id);
            $sheet->setCellValue('B' . $rowNum, $enr->student->nis);
            $sheet->setCellValue('C' . $rowNum, $enr->student->name);
            $sheet->setCellValue('D' . $rowNum, $sourceGroup->name);
            
            // Suggest target group
            $sourceLevelName = $sourceGroup->level->name;
            if ($sourceLevelName === 'TKB') {
                $sheet->setCellValue('E' . $rowNum, 'LULUS');
            } else {
                $sheet->setCellValue('E' . $rowNum, '');
            }
            $rowNum++;
        }

        // Style headers
        $styleArray = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE8F5E9'] // Light green
            ]
        ];
        $sheet->getStyle('A1:E1')->applyFromArray($styleArray);

        // Auto size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Hidden instruction info
        $sheet->setCellValue('G1', 'PANDUAN PENGISIAN KOLOM E:');
        $sheet->setCellValue('G2', '1. Tulis nama kelompok baru yang terdaftar di tahun ajaran baru (misal: TKA-A, TKA-B, TKB-A).');
        $sheet->setCellValue('G3', '2. Tulis "LULUS" jika anak lulus (khusus kelas TKB).');
        $sheet->setCellValue('G4', '3. Tulis "TIDAK LANJUT" jika anak tidak melanjutkan sekolah di tahun berikutnya.');
        $sheet->getStyle('G1:G4')->getFont()->setItalic(true);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'transisi_kelas_' . str_replace('/', '_', $sourceGroup->name) . '_ke_TA_' . str_replace('/', '_', $targetYear->name) . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($fileName) .'"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function import(Request $request)
    {
        $request->validate([
            'target_academic_year_id' => 'required|exists:academic_years,id',
            'file' => 'required|mimes:xlsx,xls|max:5120',
        ], [
            'file.required' => 'Pilih file terlebih dahulu.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx atau .xls).',
            'file.max' => 'Ukuran file maksimal adalah 5MB.',
        ]);

        $file = $request->file('file');
        $targetYearId = $request->target_academic_year_id;

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Validate headers
            $headers = array_map('trim', array_slice($rows[0], 0, 5));
            $expectedHeaders = ['ID Pendaftaran (Jangan Ubah)', 'NIS', 'Nama Siswa', 'Kelompok Asal', 'Kelompok Baru / Status Baru'];

            if ($headers !== $expectedHeaders) {
                return redirect()->back()->with('error', 'Header berkas Excel tidak sesuai. Harap gunakan template transisi kelas.');
            }

            $errors = [];
            $updates = [];

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row))) {
                    continue;
                }

                $rowNum = $i + 1;
                $enrollmentId = trim($row[0] ?? '');
                $studentName = trim($row[2] ?? '');
                $action = strtoupper(trim($row[4] ?? ''));

                if (empty($enrollmentId)) {
                    $errors[] = "Baris {$rowNum}: ID Pendaftaran kosong.";
                    continue;
                }

                if (empty($action)) {
                    $errors[] = "Baris {$rowNum}: Pilihan kelompok baru atau status di Kolom E masih kosong.";
                    continue;
                }

                $sourceEnrollment = StudentEnrollment::with('student')->find($enrollmentId);
                if (!$sourceEnrollment) {
                    $errors[] = "Baris {$rowNum}: Data pendaftaran siswa ID '{$enrollmentId}' tidak ditemukan di sistem.";
                    continue;
                }

                // Parse action
                if ($action === 'LULUS') {
                    $updates[] = [
                        'enrollment' => $sourceEnrollment,
                        'action' => 'LULUS'
                    ];
                } elseif ($action === 'TIDAK LANJUT' || $action === 'TIDAK_LANJUT') {
                    $updates[] = [
                        'enrollment' => $sourceEnrollment,
                        'action' => 'TIDAK_LANJUT'
                    ];
                } else {
                    // Try to find the group matching name
                    $group = StudentGroup::where('name', $action)->first();
                    if (!$group) {
                        $errors[] = "Baris {$rowNum}: Kelompok kelas '{$action}' tidak terdaftar di sistem. Periksa ejaan di menu pengaturan.";
                        continue;
                    }

                    $updates[] = [
                        'enrollment' => $sourceEnrollment,
                        'action' => $group->id,
                        'group' => $group
                    ];
                }
            }

            if (count($errors) > 0) {
                return redirect()->back()
                    ->withErrors($errors)
                    ->with('error', 'Gagal memproses impor transisi karena terdapat kesalahan data.');
            }

            $skippedStudents = [];
            // Execute promotions
            DB::transaction(function () use ($updates, $targetYearId, &$skippedStudents) {
                foreach ($updates as $up) {
                    $sourceEnrollment = $up['enrollment'];
                    $student = $sourceEnrollment->student;
                    $action = $up['action'];

                    if ($action === 'LULUS') {
                        $student->update(['status' => 'Graduated']);
                    } elseif ($action === 'TIDAK_LANJUT') {
                        $student->update(['status' => 'Not Continuing']);
                    } elseif (is_numeric($action)) {
                        $targetGroupId = (int) $action;
                        $targetGroup = $up['group'];

                        // Avoid double promotion
                        $alreadyEnrolled = StudentEnrollment::where('student_id', $student->id)
                            ->where('academic_year_id', $targetYearId)
                            ->exists();

                        if ($alreadyEnrolled) {
                            $skippedStudents[] = $student->name;
                            continue;
                        }

                        // Create enrollment in target year
                        $newEnrollment = StudentEnrollment::create([
                            'student_id' => $student->id,
                            'academic_year_id' => $targetYearId,
                            'student_group_id' => $targetGroupId,
                            'discount_category_id' => $sourceEnrollment->discount_category_id,
                            'discount_percentage' => $sourceEnrollment->discount_percentage,
                            'enrollment_type' => 'Returning',
                        ]);

                        // Generate fees
                        $components = AnnualFeeComponent::where('academic_year_id', $targetYearId)
                            ->where('level_id', $targetGroup->level_id)
                            ->whereIn('target_type', ['All', 'Returning'])
                            ->get();

                        foreach ($components as $comp) {
                            $discountedAmount = $comp->amount * (1 - ($sourceEnrollment->discount_percentage / 100));

                            StudentAnnualFee::create([
                                'student_enrollment_id' => $newEnrollment->id,
                                'annual_fee_component_id' => $comp->id,
                                'amount' => $discountedAmount,
                                'is_excluded' => false,
                            ]);
                        }
                    }
                }
            });

            if (!empty($skippedStudents)) {
                $names = implode(', ', $skippedStudents);
                return redirect()->route('students.index')
                    ->with('success', 'Data transisi kenaikan kelas berhasil diproses.')
                    ->with('warning', 'Beberapa siswa dilewati karena sudah terdaftar di tahun ajaran tujuan: ' . $names);
            }

            return redirect()->route('students.index')->with('success', count($updates) . ' data transisi kenaikan kelas berhasil diproses.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat memproses impor transisi: ' . $e->getMessage());
        }
    }
}
