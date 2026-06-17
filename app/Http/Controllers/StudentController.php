<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\StudentGroup;
use App\Models\DiscountCategory;
use App\Models\AnnualFeeComponent;
use App\Models\StudentAnnualFee;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentController extends Controller
{
    public function searchAjax(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        $q = $request->input('q');

        $enrollments = StudentEnrollment::where('academic_year_id', $selectedYearId)
            ->whereHas('student', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('nis', 'like', "%{$q}%")
                      ->orWhere('nickname', 'like', "%{$q}%");
            })
            ->with(['student', 'studentGroup'])
            ->limit(10)
            ->get();

        return response()->json($enrollments->map(function ($se) {
            $displayName = $se->student->name;
            if ($se->student->nickname) {
                $displayName .= ' (' . $se->student->nickname . ')';
            }
            return [
                'id' => $se->student->id,
                'name' => $displayName,
                'nis' => $se->student->nis,
                'group_name' => $se->studentGroup->name,
                'payment_url' => route('payments.create', ['student_id' => $se->student->id]),
                'edit_url' => route('students.edit', $se->student->id),
            ];
        }));
    }

    public function index(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        $selectedYear = $selectedYearId ? AcademicYear::find($selectedYearId) : null;

        $levels = Level::with('groups')->get();
        $discountCategories = DiscountCategory::all();

        // Query students enrolled in the currently selected academic year
        $query = StudentEnrollment::where('academic_year_id', $selectedYearId)
            ->with(['student', 'studentGroup.level', 'discountCategory', 'studentAnnualFees.annualFeeComponent']);

        // Filter by Level
        if ($request->filled('level_id')) {
            $query->whereHas('studentGroup', function ($q) use ($request) {
                $q->where('level_id', $request->level_id);
            });
        }

        // Filter by Group
        if ($request->filled('student_group_id')) {
            $query->where('student_group_id', $request->student_group_id);
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        // Filter by Enrollment Type
        if ($request->filled('enrollment_type')) {
            $query->where('enrollment_type', $request->enrollment_type);
        }

        // Filter by Search Name/NIS/Nickname
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nickname', 'like', "%{$search}%");
            });
        }

        $enrollments = $query->get();

        // Calculate arrears for each student for visual badges
        $today = date('Y-m-d');
        $currentSppIndex = 12; // Default to 12 months if year in the past
        
        if ($selectedYear) {
            if ($selectedYear->start_date->format('Y-m-d') > $today) {
                $currentSppIndex = 0;
            } elseif ($selectedYear->start_date->format('Y-m-d') <= $today && $selectedYear->end_date->format('Y-m-d') >= $today) {
                $currentMonth = (int) date('n');
                $monthMap = [7=>1, 8=>2, 9=>3, 10=>4, 11=>5, 12=>6, 1=>7, 2=>8, 3=>9, 4=>10, 5=>11, 6=>12];
                $currentSppIndex = $monthMap[$currentMonth] ?? 12;
            }
        }

        $sppSetting = \App\Models\GlobalSppSetting::where('academic_year_id', $selectedYearId)->first();
        $baseSppAmount = $sppSetting ? $sppSetting->amount : 0.00;

        $studentIds = $enrollments->pluck('student_id')->toArray();
        $paidSppDetails = \App\Models\PaymentDetail::where('type', 'SPP')
            ->whereHas('paymentTransaction', function ($q) use ($selectedYearId, $studentIds) {
                $q->where('academic_year_id', $selectedYearId)
                  ->whereIn('student_id', $studentIds);
            })
            ->join('payment_transactions', 'payment_details.payment_transaction_id', '=', 'payment_transactions.id')
            ->select('payment_transactions.student_id', 'payment_details.month_index')
            ->get()
            ->groupBy('student_id')
            ->map(function ($items) {
                return $items->pluck('month_index')->toArray();
            });

        foreach ($enrollments as $enr) {
            $annualArrears = 0.00;
            foreach ($enr->studentAnnualFees as $fee) {
                if (!$fee->is_excluded) {
                    $annualArrears += $fee->balance;
                }
            }

            $sppArrears = 0.00;
            $paidMonths = $paidSppDetails->get($enr->student_id) ?? [];
            for ($m = 2; $m <= $currentSppIndex; $m++) {
                if (!in_array($m, $paidMonths)) {
                    $sppArrears += $baseSppAmount;
                }
            }

            $enr->arrears_amount = $annualArrears + $sppArrears;
        }

        return view('students.index', compact('enrollments', 'levels', 'discountCategories', 'selectedYear'));
    }

    public function create()
    {
        $selectedYearId = session('selected_academic_year_id');
        $selectedYear = $selectedYearId ? AcademicYear::find($selectedYearId) : null;

        if (!$selectedYear) {
            return redirect()->route('students.index')->with('error', 'Silakan pilih atau tambahkan Tahun Ajaran terlebih dahulu.');
        }

        $levels = Level::with('groups')->get();
        $discountCategories = DiscountCategory::all();

        return view('students.create', compact('levels', 'discountCategories', 'selectedYear'));
    }

    public function store(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        
        $request->validate([
            'name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'parent_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'enrollment_type' => 'required|in:New,Returning',
            'level_id' => 'required|exists:levels,id',
            'student_group_id' => [
                'required',
                'exists:student_groups,id',
                function ($attribute, $value, $fail) use ($request) {
                    $group = \App\Models\StudentGroup::find($value);
                    if ($group && $group->level_id != $request->level_id) {
                        $fail('Kelompok kelas harus berada dalam jenjang yang dipilih.');
                    }
                }
            ],
            'discount_category_id' => 'nullable|exists:discount_categories,id',
        ]);

        DB::transaction(function () use ($request, $selectedYearId) {
            // 1. Create student
            $student = Student::create([
                'name' => $request->name,
                'nickname' => $request->nickname,
                'parent_name' => $request->parent_name,
                'phone_number' => $request->phone_number,
                'status' => 'Active',
            ]);

            // Auto generate NIS
            $yearPrefix = date('y');
            $student->update([
                'nis' => 'AM' . $yearPrefix . str_pad($student->id, 4, '0', STR_PAD_LEFT)
            ]);

            // 2. Fetch discount category if present
            $discountPct = 0.00;
            if ($request->discount_category_id) {
                $discount = DiscountCategory::find($request->discount_category_id);
                $discountPct = $discount ? $discount->percentage : 0.00;
            }

            // 3. Create enrollment
            $enrollment = StudentEnrollment::create([
                'student_id' => $student->id,
                'academic_year_id' => $selectedYearId,
                'student_group_id' => $request->student_group_id,
                'discount_category_id' => $request->discount_category_id,
                'discount_percentage' => $discountPct,
                'enrollment_type' => $request->enrollment_type,
            ]);

            // 4. Generate annual fee components
            $components = AnnualFeeComponent::where('academic_year_id', $selectedYearId)
                ->where('level_id', $request->level_id)
                ->whereIn('target_type', ['All', $request->enrollment_type])
                ->get();

            foreach ($components as $comp) {
                // Apply discount to component amount
                $discountedAmount = $comp->amount * (1 - ($discountPct / 100));

                StudentAnnualFee::create([
                    'student_enrollment_id' => $enrollment->id,
                    'annual_fee_component_id' => $comp->id,
                    'amount' => $discountedAmount,
                    'is_excluded' => false,
                ]);
            }
        });

        return redirect()->route('students.index')->with('success', 'Siswa baru berhasil didaftarkan.');
    }

    public function edit(Student $student)
    {
        $selectedYearId = session('selected_academic_year_id');
        $enrollment = $student->enrollmentForYear($selectedYearId);
        
        if (!$enrollment) {
            return redirect()->route('students.index')->with('error', 'Siswa tidak memiliki data pendaftaran di Tahun Ajaran ini.');
        }

        $levels = Level::with('groups')->get();
        $discountCategories = DiscountCategory::all();
        
        // Load student annual fees to configure exclusions
        $studentAnnualFees = StudentAnnualFee::where('student_enrollment_id', $enrollment->id)
            ->with('annualFeeComponent')
            ->get();

        $academicYear = AcademicYear::find($selectedYearId);
        $hasPayments = \App\Models\PaymentTransaction::where('student_id', $student->id)
            ->where('academic_year_id', $selectedYearId)
            ->exists();

        $canCancelEnrollment = auth()->user()->role === 'admin' 
            && $academicYear 
            && !$academicYear->is_active 
            && !$hasPayments;

        return view('students.edit', compact('student', 'enrollment', 'levels', 'discountCategories', 'studentAnnualFees', 'canCancelEnrollment', 'hasPayments'));
    }

    public function update(Request $request, Student $student)
    {
        $selectedYearId = session('selected_academic_year_id');
        $enrollment = $student->enrollmentForYear($selectedYearId);
        
        $hasPayments = \App\Models\PaymentTransaction::where('student_id', $student->id)
            ->where('academic_year_id', $selectedYearId)
            ->exists();

        $request->validate([
            'name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'parent_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'enrollment_type' => 'required|in:New,Returning',
            'status' => 'required|in:Active,Graduated,Not Continuing,Transferred',
            'student_group_id' => [
                'required',
                'exists:student_groups,id',
                function ($attribute, $value, $fail) use ($enrollment) {
                    if ($enrollment && $enrollment->studentGroup) {
                        $newGroup = \App\Models\StudentGroup::find($value);
                        if ($newGroup && $newGroup->level_id !== $enrollment->studentGroup->level_id) {
                            $fail('Kelompok kelas harus berada dalam jenjang yang sama.');
                        }
                    }
                }
            ],
            'discount_category_id' => 'nullable|exists:discount_categories,id',
        ]);

        DB::transaction(function () use ($request, $student, $enrollment, $selectedYearId, $hasPayments) {
            // 1. Update basic info
            $student->update([
                'name' => $request->name,
                'nickname' => $request->nickname,
                'parent_name' => $request->parent_name,
                'phone_number' => $request->phone_number,
                'status' => $request->status,
            ]);

            // 2. Process discount adjustment (only change if discount category ID is changed)
            $discountPct = $enrollment->discount_percentage;
            $discountChanged = ($enrollment->discount_category_id != $request->discount_category_id);

            if ($discountChanged) {
                if ($request->discount_category_id) {
                    $discount = DiscountCategory::find($request->discount_category_id);
                    $discountPct = $discount ? $discount->percentage : 0.00;
                } else {
                    $discountPct = 0.00;
                }
            }

            $enrollmentTypeChanged = ($enrollment->enrollment_type !== $request->enrollment_type);

            $enrollmentData = [
                'student_group_id' => $request->student_group_id,
                'discount_category_id' => $request->discount_category_id,
                'discount_percentage' => $discountPct,
            ];

            if (!$hasPayments) {
                $enrollmentData['enrollment_type'] = $request->enrollment_type;
            }

            $enrollment->update($enrollmentData);

            if ($enrollmentTypeChanged && !$hasPayments) {
                // Delete old ones
                StudentAnnualFee::where('student_enrollment_id', $enrollment->id)->delete();

                // Generate new ones
                $components = AnnualFeeComponent::where('academic_year_id', $selectedYearId)
                    ->where('level_id', $enrollment->studentGroup->level_id)
                    ->whereIn('target_type', ['All', $request->enrollment_type])
                    ->get();

                foreach ($components as $comp) {
                    $discountedAmount = $comp->amount * (1 - ($discountPct / 100));

                    StudentAnnualFee::create([
                        'student_enrollment_id' => $enrollment->id,
                        'annual_fee_component_id' => $comp->id,
                        'amount' => $discountedAmount,
                        'is_excluded' => false,
                    ]);
                }
            } else {
                // 3. Exclusions & fee recalculations
                $exclusions = $request->input('exclusions', []); // Array of student_annual_fee.id that are excluded
                
                $fees = StudentAnnualFee::where('student_enrollment_id', $enrollment->id)->get();
                
                foreach ($fees as $fee) {
                    $isExcluded = in_array($fee->id, $exclusions);
                    
                    // Recalculate amount if discount changed
                    $amount = $fee->amount;
                    if ($discountChanged) {
                        $originalComponent = AnnualFeeComponent::find($fee->annual_fee_component_id);
                        if ($originalComponent) {
                            $amount = $originalComponent->amount * (1 - ($discountPct / 100));
                        }
                    }
                    
                    $fee->update([
                        'is_excluded' => $isExcluded,
                        'amount' => $amount,
                    ]);
                }
            }
        });

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set header
        $sheet->setCellValue('A1', 'Nama Siswa');
        $sheet->setCellValue('B1', 'Nama Panggilan');
        $sheet->setCellValue('C1', 'Nama Orang Tua');
        $sheet->setCellValue('D1', 'No Telepon');
        $sheet->setCellValue('E1', 'Jenjang');
        $sheet->setCellValue('F1', 'Kelompok');
        $sheet->setCellValue('G1', 'Kategori Diskon');
        $sheet->setCellValue('H1', 'Tipe Pendaftaran');
        
        // Add sample row
        $sheet->setCellValue('A2', 'Ahmad Fathoni');
        $sheet->setCellValue('B2', 'Toni');
        $sheet->setCellValue('C2', 'Subhan');
        $sheet->setCellValue('D2', '085299998888');
        $sheet->setCellValue('E2', 'TKA');
        $sheet->setCellValue('F2', 'TKA-A');
        $sheet->setCellValue('G2', 'Yatim');
        $sheet->setCellValue('H2', 'Baru'); // 'Baru' or 'Lama'
        
        // Style Header
        $styleArray = [
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFE0F2F1', // Light teal
                ],
            ],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($styleArray);
        
        // Auto size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $writer = new Xlsx($spreadsheet);
        $fileName = 'template_impor_siswa_almarjan.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($fileName) .'"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:5120',
        ], [
            'file.required' => 'Pilih file terlebih dahulu.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx atau .xls).',
            'file.max' => 'Ukuran file maksimal adalah 5MB.',
        ]);

        $file = $request->file('file');
        
        $selectedYearId = session('selected_academic_year_id');
        if (!$selectedYearId) {
            return redirect()->back()->with('error', 'Pilih Tahun Ajaran terlebih dahulu di header.');
        }
        
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            // Map header names to column indices
            $firstRow = array_map('trim', $rows[0] ?? []);
            
            $nameIdx = array_search('Nama Siswa', $firstRow);
            $nicknameIdx = array_search('Nama Panggilan', $firstRow);
            $parentIdx = array_search('Nama Orang Tua', $firstRow);
            $phoneIdx = array_search('No Telepon', $firstRow);
            $levelIdx = array_search('Jenjang', $firstRow);
            $groupIdx = array_search('Kelompok', $firstRow);
            $discountIdx = array_search('Kategori Diskon', $firstRow);
            $typeIdx = array_search('Tipe Pendaftaran', $firstRow);
            
            if ($nameIdx === false || $levelIdx === false || $groupIdx === false) {
                return redirect()->back()->with('error', 'Format header file Excel tidak sesuai. Pastikan memiliki kolom "Nama Siswa", "Jenjang", dan "Kelompok".');
            }
            
            $errors = [];
            $studentsToImport = [];
            
            // Loop rows (skip index 0 as header)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                // Skip completely empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $rowNum = $i + 1;
                $studentName = trim($row[$nameIdx] ?? '');
                $nickname = ($nicknameIdx !== false) ? trim($row[$nicknameIdx] ?? '') : null;
                $parentName = ($parentIdx !== false) ? trim($row[$parentIdx] ?? '') : null;
                $phoneNumber = ($phoneIdx !== false) ? trim($row[$phoneIdx] ?? '') : null;
                $levelName = trim($row[$levelIdx] ?? '');
                $groupName = trim($row[$groupIdx] ?? '');
                $discountName = ($discountIdx !== false) ? trim($row[$discountIdx] ?? '') : '';
                $enrollmentTypeRaw = ($typeIdx !== false) ? trim($row[$typeIdx] ?? '') : 'Baru';
                
                if (empty($studentName)) {
                    $errors[] = "Baris {$rowNum}: Nama Siswa tidak boleh kosong.";
                    continue;
                }
                
                // Validate level
                $level = Level::where('name', $levelName)->first();
                if (!$level) {
                    $errors[] = "Baris {$rowNum}: Jenjang '{$levelName}' tidak terdaftar di sistem.";
                    continue;
                }
                
                // Validate group
                $group = StudentGroup::where('level_id', $level->id)
                    ->where('name', $groupName)
                    ->first();
                if (!$group) {
                    $errors[] = "Baris {$rowNum}: Kelompok '{$groupName}' tidak terdaftar di sistem untuk Jenjang {$levelName}.";
                    continue;
                }
                
                // Validate discount if filled
                $discount = null;
                if (!empty($discountName)) {
                    $discount = DiscountCategory::where('name', $discountName)->first();
                    if (!$discount) {
                        $errors[] = "Baris {$rowNum}: Kategori Diskon '{$discountName}' tidak terdaftar di sistem.";
                        continue;
                    }
                }
                
                // Parse enrollment type
                $enrollmentType = 'New';
                if (!empty($enrollmentTypeRaw)) {
                    $lowerType = strtolower($enrollmentTypeRaw);
                    if ($lowerType === 'lama' || $lowerType === 'returning') {
                        $enrollmentType = 'Returning';
                    }
                }
                
                $studentsToImport[] = [
                    'name' => $studentName,
                    'nickname' => $nickname !== '' ? $nickname : null,
                    'parent_name' => $parentName !== '' ? $parentName : null,
                    'phone_number' => $phoneNumber !== '' ? $phoneNumber : null,
                    'group_id' => $group->id,
                    'discount_id' => $discount ? $discount->id : null,
                    'discount_percentage' => $discount ? $discount->percentage : 0.00,
                    'level_id' => $level->id,
                    'enrollment_type' => $enrollmentType,
                ];
            }
            
            if (count($errors) > 0) {
                return redirect()->back()
                    ->withErrors($errors)
                    ->with('error', 'Impor gagal karena terdapat kesalahan data di dalam Excel.');
            }
            
            if (count($studentsToImport) === 0) {
                return redirect()->back()->with('error', 'Tidak ada data siswa yang ditemukan untuk diimpor.');
            }
            
            // Run database transaction to insert all
            DB::transaction(function () use ($studentsToImport, $selectedYearId) {
                foreach ($studentsToImport as $sData) {
                    // Create student record
                    $student = Student::create([
                        'name' => $sData['name'],
                        'nickname' => $sData['nickname'],
                        'parent_name' => $sData['parent_name'],
                        'phone_number' => $sData['phone_number'],
                        'status' => 'Active',
                    ]);
                    
                    // Generate NIS automatically
                    $yearPrefix = date('y');
                    $student->update([
                        'nis' => 'AM' . $yearPrefix . str_pad($student->id, 4, '0', STR_PAD_LEFT)
                    ]);
                    
                    // Create enrollment
                    $enrollment = StudentEnrollment::create([
                        'student_id' => $student->id,
                        'academic_year_id' => $selectedYearId,
                        'student_group_id' => $sData['group_id'],
                        'discount_category_id' => $sData['discount_id'],
                        'discount_percentage' => $sData['discount_percentage'],
                        'enrollment_type' => $sData['enrollment_type'],
                    ]);
                    
                    // Generate student annual fee obligations
                    $feeComponents = AnnualFeeComponent::where('academic_year_id', $selectedYearId)
                        ->where('level_id', $sData['level_id'])
                        ->whereIn('target_type', ['All', $sData['enrollment_type']])
                        ->get();
                        
                    foreach ($feeComponents as $component) {
                        $discountedAmount = $component->amount * (1 - ($sData['discount_percentage'] / 100));
                        
                        StudentAnnualFee::create([
                            'student_enrollment_id' => $enrollment->id,
                            'annual_fee_component_id' => $component->id,
                            'amount' => $discountedAmount,
                            'is_excluded' => false,
                        ]);
                    }
                }
            });
            
            return redirect()->route('students.index')->with('success', count($studentsToImport) . ' data siswa berhasil diimpor.');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat membaca file Excel: ' . $e->getMessage());
        }
    }

    public function cancelEnrollment(Student $student)
    {
        $selectedYearId = session('selected_academic_year_id');
        $academicYear = AcademicYear::find($selectedYearId);
        
        if (!$academicYear || $academicYear->is_active) {
            return redirect()->back()->with('error', 'Pembatalan pendaftaran hanya diperbolehkan pada Tahun Ajaran yang tidak aktif.');
        }

        if (auth()->user()->role !== 'admin') {
            abort(403, 'Akses Ditolak: Hanya Administrator yang dapat membatalkan pendaftaran.');
        }

        $enrollment = $student->enrollmentForYear($selectedYearId);
        if (!$enrollment) {
            return redirect()->route('students.index')->with('error', 'Data pendaftaran siswa tidak ditemukan untuk Tahun Ajaran ini.');
        }

        $hasPayments = \App\Models\PaymentTransaction::where('student_id', $student->id)
            ->where('academic_year_id', $selectedYearId)
            ->exists();

        if ($hasPayments) {
            return redirect()->back()->with('error', 'Pendaftaran tidak dapat dibatalkan karena siswa sudah memiliki transaksi pembayaran di Tahun Ajaran ini.');
        }

        DB::transaction(function () use ($enrollment) {
            // Delete associated annual fee records
            $enrollment->studentAnnualFees()->delete();
            
            // Delete enrollment record
            $enrollment->delete();
        });

        return redirect()->route('students.index')->with('success', 'Pendaftaran siswa untuk Tahun Ajaran ' . $academicYear->name . ' berhasil dibatalkan.');
    }
}
