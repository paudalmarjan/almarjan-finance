<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicYear;
use App\Models\StudentEnrollment;
use App\Models\PaymentTransaction;
use App\Models\PaymentDetail;
use App\Models\Expense;
use App\Models\GlobalSppSetting;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Level;

class ReportController extends Controller
{
    public function finance(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        $selectedYear = $selectedYearId ? AcademicYear::find($selectedYearId) : null;

        if (!$selectedYear) {
            return redirect()->route('dashboard')->with('error', 'Silakan pilih Tahun Ajaran.');
        }

        // Default date range is the Academic Year start and end dates
        $startDate = $request->filled('start_date') ? $request->start_date : $selectedYear->start_date->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->end_date : $selectedYear->end_date->format('Y-m-d');

        // Fetch income (payment transactions)
        $incomes = PaymentTransaction::where('academic_year_id', $selectedYearId)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('student')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'type' => 'Pemasukan',
                    'reference' => $item->receipt_number,
                    'description' => "Pembayaran siswa: " . $item->student->name,
                    'amount' => (float) $item->total_amount,
                ];
            });

        // Fetch expenses
        $outcomes = Expense::whereBetween('date', [$startDate, $endDate])
            ->with('expenseCategory')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'type' => 'Pengeluaran',
                    'reference' => "EXP-" . str_pad($item->id, 5, '0', STR_PAD_LEFT),
                    'description' => "[{$item->expenseCategory->name}] " . ($item->notes ?? 'Tanpa catatan'),
                    'amount' => (float) $item->amount,
                ];
            });

        // Combine and sort by date
        $ledger = $incomes->concat($outcomes)->sortBy('date')->values();

        $totalIncome = $incomes->sum('amount');
        $totalOutcome = $outcomes->sum('amount');
        $netBalance = $totalIncome - $totalOutcome;
        $endingBalance = (float) $selectedYear->initial_cash_balance + $netBalance;

        return view('reports.finance', compact('ledger', 'startDate', 'endDate', 'totalIncome', 'totalOutcome', 'netBalance', 'endingBalance', 'selectedYear'));
    }

    public function arrears(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        $selectedYear = $selectedYearId ? AcademicYear::find($selectedYearId) : null;

        if (!$selectedYear) {
            return redirect()->route('dashboard')->with('error', 'Silakan pilih Tahun Ajaran.');
        }

        // Determine current SPP index based on today's date vs academic year
        $today = date('Y-m-d');
        $currentSppIndex = 12; // Default to full year (all 12 months) if year is in the past
        
        if ($selectedYear->start_date->format('Y-m-d') > $today) {
            // Selected year is in the future
            $currentSppIndex = 0;
        } elseif ($selectedYear->start_date->format('Y-m-d') <= $today && $selectedYear->end_date->format('Y-m-d') >= $today) {
            // Selected year is currently active
            $currentMonth = (int) date('n'); // 1 to 12
            // Map calendar month to SPP month index (1 = July, 12 = June)
            $monthMap = [7=>1, 8=>2, 9=>3, 10=>4, 11=>5, 12=>6, 1=>7, 2=>8, 3=>9, 4=>10, 5=>11, 6=>12];
            $currentSppIndex = $monthMap[$currentMonth] ?? 12;
        }

        // Get SPP base amount
        $sppSetting = GlobalSppSetting::where('academic_year_id', $selectedYearId)->first();
        $baseSppAmount = $sppSetting ? $sppSetting->amount : 0.00;

        $levels = Level::with('groups')->get();

        // Fetch all student enrollments for selected year
        $query = StudentEnrollment::where('academic_year_id', $selectedYearId)
            ->whereHas('student', function ($q) {
                $q->where('status', 'Active');
            });

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

        // Filter by Enrollment Type
        if ($request->filled('enrollment_type')) {
            $query->where('enrollment_type', $request->enrollment_type);
        }

        $enrollments = $query->with(['student', 'studentGroup', 'studentAnnualFees.annualFeeComponent'])
            ->get();

        $arrearsList = [];
        $totalReceivables = 0.00;

        foreach ($enrollments as $enr) {
            // 1. Calculate outstanding Annual Fee
            $unpaidAnnualComponents = [];
            $annualArrears = 0.00;

            $sortedAnnualFees = $enr->studentAnnualFees->sortBy(function ($fee) {
                return ($fee->annualFeeComponent->sort_order ?? 0) . '_' . ($fee->annualFeeComponent->id ?? 0);
            });

            foreach ($sortedAnnualFees as $fee) {
                if (!$fee->is_excluded && $fee->balance > 0) {
                    $unpaidAnnualComponents[] = $fee->annualFeeComponent->name . " (Sisa: Rp " . number_format($fee->balance, 0, ',', '.') . ")";
                    $annualArrears += $fee->balance;
                }
            }

            // 2. Calculate outstanding SPP (from month 2 to currentSppIndex, month 1 is July which is in Annual Fee)
            $paidSppMonths = PaymentDetail::where('type', 'SPP')
                ->whereHas('paymentTransaction', function ($q) use ($enr, $selectedYearId) {
                    $q->where('student_id', $enr->student_id)
                      ->where('academic_year_id', $selectedYearId);
                })
                ->pluck('month_index')
                ->toArray();

            $unpaidSppMonths = [];
            $sppArrears = 0.00;
            // SPP stays normal, no discount applied
            $discountedSppAmount = $baseSppAmount;

            $monthNames = [
                2 => 'Agt', 3 => 'Sep', 4 => 'Okt', 5 => 'Nov', 6 => 'Des', 
                7 => 'Jan', 8 => 'Feb', 9 => 'Mar', 10 => 'Apr', 11 => 'Mei', 12 => 'Jun'
            ];

            for ($m = 2; $m <= $currentSppIndex; $m++) {
                if (!in_array($m, $paidSppMonths)) {
                    $unpaidSppMonths[] = $monthNames[$m] ?? "M-{$m}";
                    $sppArrears += $discountedSppAmount;
                }
            }

            $studentTotalArrears = $annualArrears + $sppArrears;

            if ($studentTotalArrears > 0) {
                $arrearsList[] = [
                    'student_id' => $enr->student->id,
                    'nis' => $enr->student->nis,
                    'name' => $enr->student->name,
                    'group_name' => $enr->studentGroup->name,
                    'annual_details' => $unpaidAnnualComponents,
                    'spp_details' => $unpaidSppMonths,
                    'annual_arrears' => $annualArrears,
                    'spp_arrears' => $sppArrears,
                    'total_arrears' => $studentTotalArrears,
                ];

                $totalReceivables += $studentTotalArrears;
            }
        }

        // Sort by total arrears desc
        usort($arrearsList, function ($a, $b) {
            return $b['total_arrears'] <=> $a['total_arrears'];
        });

        return view('reports.arrears', compact('arrearsList', 'totalReceivables', 'levels', 'selectedYear'));
    }

    public function lpj(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        $selectedYear = $selectedYearId ? AcademicYear::find($selectedYearId) : null;

        // Default date range is the current month
        $startDate = $request->filled('start_date') ? $request->start_date : date('Y-m-01');
        $endDate = $request->filled('end_date') ? $request->end_date : date('Y-m-t');

        $expenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->with(['expenseCategory', 'user'])
            ->orderBy('date', 'asc')
            ->get();

        $totalOutcome = $expenses->sum('amount');

        return view('reports.lpj', compact('expenses', 'startDate', 'endDate', 'totalOutcome', 'selectedYear'));
    }

    public function exportLpj(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $expenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->with(['expenseCategory', 'user'])
            ->orderBy('date', 'asc')
            ->get();

        if ($expenses->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data pengeluaran kas pada rentang tanggal tersebut.');
        }

        $totalOutcome = $expenses->sum('amount');

        // Compile HTML to PDF using the dedicated lpj_pdf view
        $pdf = Pdf::loadView('reports.lpj_pdf', compact('expenses', 'startDate', 'endDate', 'totalOutcome'));

        // Generate dynamic file name
        $fileName = 'LPJ_AlMarjan_' . date('Ymd', strtotime($startDate)) . '_sd_' . date('Ymd', strtotime($endDate)) . '.pdf';

        return $pdf->download($fileName);
    }
}
