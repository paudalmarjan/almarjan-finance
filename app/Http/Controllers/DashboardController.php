<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\StudentGroup;
use App\Models\StudentEnrollment;
use App\Models\PaymentTransaction;
use App\Models\PaymentDetail;
use App\Models\Expense;
use App\Models\GlobalSppSetting;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        $selectedYear = $selectedYearId ? AcademicYear::find($selectedYearId) : null;

        if (!$selectedYear) {
            return redirect()->route('settings.index')->with('error', 'Silakan aktifkan Tahun Ajaran terlebih dahulu.');
        }

        $levels = Level::with('groups')->get();

        // 1. Base Query Scopes for Filters
        $studentQuery = StudentEnrollment::where('academic_year_id', $selectedYearId)
            ->whereHas('student', function ($q) {
                $q->where('status', 'Active');
            });

        $incomeQuery = PaymentTransaction::where('academic_year_id', $selectedYearId);

        // Filter by Level
        if ($request->filled('level_id')) {
            $studentQuery->whereHas('studentGroup', function ($q) use ($request) {
                $q->where('level_id', $request->level_id);
            });
            $incomeQuery->whereHas('student.enrollments', function ($q) use ($request, $selectedYearId) {
                $q->where('academic_year_id', $selectedYearId)
                  ->whereHas('studentGroup', function ($sg) use ($request) {
                      $sg->where('level_id', $request->level_id);
                  });
            });
        }

        // Filter by Group
        if ($request->filled('student_group_id')) {
            $studentQuery->where('student_group_id', $request->student_group_id);
            $incomeQuery->whereHas('student.enrollments', function ($q) use ($request, $selectedYearId) {
                $q->where('academic_year_id', $selectedYearId)
                  ->where('student_group_id', $request->student_group_id);
            });
        }

        // 2. Calculate Income
        $totalIncome = (float) $incomeQuery->sum('total_amount');

        // 3. Calculate Expenses (Expenses are scoped to the selected Academic Year)
        $totalOutcome = (float) Expense::where('academic_year_id', $selectedYearId)->sum('amount');

        // 4. Calculate Net Balance
        $currentBalance = (float) $selectedYear->initial_cash_balance + $totalIncome - $totalOutcome;

        // 5. Calculate Arrears (piutang) for filtered student list
        $activeEnrollments = $studentQuery->with(['studentAnnualFees', 'student'])->get();
        $sppSetting = GlobalSppSetting::where('academic_year_id', $selectedYearId)->first();
        $baseSppAmount = $sppSetting ? $sppSetting->amount : 0.00;

        // Determine current SPP index based on today
        $today = date('Y-m-d');
        $currentSppIndex = 12;
        if ($selectedYear->start_date->format('Y-m-d') > $today) {
            $currentSppIndex = 0;
        } elseif ($selectedYear->start_date->format('Y-m-d') <= $today && $selectedYear->end_date->format('Y-m-d') >= $today) {
            $currentMonth = (int) date('n');
            $monthMap = [7=>1, 8=>2, 9=>3, 10=>4, 11=>5, 12=>6, 1=>7, 2=>8, 3=>9, 4=>10, 5=>11, 6=>12];
            $currentSppIndex = $monthMap[$currentMonth] ?? 12;
        }

        $totalArrears = 0.00;
        $attentionList = [];

        foreach ($activeEnrollments as $enr) {
            $annualArrears = 0.00;
            foreach ($enr->studentAnnualFees as $fee) {
                if (!$fee->is_excluded) {
                    $annualArrears += $fee->balance;
                }
            }

            // Calculate paid SPP months
            $paidSppMonths = PaymentDetail::where('type', 'SPP')
                ->whereHas('paymentTransaction', function ($q) use ($enr, $selectedYearId) {
                    $q->where('student_id', $enr->student_id)
                      ->where('academic_year_id', $selectedYearId);
                })
                ->pluck('month_index')
                ->toArray();

            $sppArrears = 0.00;
            // SPP stays normal, no discount applied
            $discountedSpp = $baseSppAmount;

            for ($m = 2; $m <= $currentSppIndex; $m++) {
                if (!in_array($m, $paidSppMonths)) {
                    $sppArrears += $discountedSpp;
                }
            }

            $studentTotalArrears = $annualArrears + $sppArrears;
            $totalArrears += $studentTotalArrears;

            if ($studentTotalArrears > 0) {
                $attentionList[] = [
                    'student_id' => $enr->student->id,
                    'name' => $enr->student->name,
                    'group_name' => $enr->studentGroup->name,
                    'amount' => $studentTotalArrears,
                ];
            }
        }

        // Sort and get top 5 for "Perhatian Khusus"
        usort($attentionList, function ($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        $attentionList = array_slice($attentionList, 0, 5);

        // 6. Chart Trends: Monthly Cash Flow (July to June)
        // Array maps: July=7, Aug=8, ..., Jun=6
        $academicMonths = [7, 8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6];
        $monthlyIncome = [];
        $monthlyOutcome = [];

        $startYear = (int) $selectedYear->start_date->format('Y');
        $endYear = (int) $selectedYear->end_date->format('Y');

        foreach ($academicMonths as $month) {
            $year = ($month >= 7) ? $startYear : $endYear;
            
            // Calculate income for this month
            // Scoped to Level/Group if filtered
            $mIncome = (float) $incomeQuery->clone()
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->sum('total_amount');

            // Calculate expense for this month
            $mOutcome = (float) Expense::where('academic_year_id', $selectedYearId)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->sum('amount');

            $monthlyIncome[] = $mIncome;
            $monthlyOutcome[] = $mOutcome;
        }

        // 7. Recent Transactions (Payments & Expenses combined)
        // Load recent payments
        $recentPayments = PaymentTransaction::where('academic_year_id', $selectedYearId)
            ->with('student')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'type' => 'Pemasukan',
                    'description' => "Pembayaran siswa: " . $item->student->name,
                    'amount' => $item->total_amount,
                    'route' => route('payments.show', $item->id),
                ];
            });

        // Load recent expenses
        $recentExpenses = Expense::where('academic_year_id', $selectedYearId)
            ->with('expenseCategory')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'type' => 'Pengeluaran',
                    'description' => "[{$item->expenseCategory->name}] " . ($item->notes ?? 'Operasional'),
                    'amount' => $item->amount,
                    'route' => route('expenses.index'),
                ];
            });

        $recentTransactions = $recentPayments->concat($recentExpenses)->sortByDesc('date')->values()->all();

        // 8. Quick Metrics
        $totalStudentsCount = (int) $studentQuery->clone()->count();
        $discountedStudentsCount = (int) $studentQuery->clone()->whereNotNull('discount_category_id')->count();

        // SPP Payment Rate for the current month
        $paidSppCount = 0;
        if ($currentSppIndex >= 2) {
            $paidSppCount = StudentEnrollment::where('academic_year_id', $selectedYearId)
                ->whereHas('student', function ($q) {
                    $q->where('status', 'Active');
                })
                ->when($request->filled('level_id'), function ($q) use ($request) {
                    $q->whereHas('studentGroup', function ($sg) use ($request) {
                        $sg->where('level_id', $request->level_id);
                    });
                })
                ->when($request->filled('student_group_id'), function ($q) use ($request) {
                    $q->where('student_group_id', $request->student_group_id);
                })
                ->whereHas('student.paymentTransactions', function ($pq) use ($selectedYearId, $currentSppIndex) {
                    $pq->where('academic_year_id', $selectedYearId)
                       ->whereHas('paymentDetails', function ($dq) use ($currentSppIndex) {
                           $dq->where('type', 'SPP')
                              ->where('month_index', $currentSppIndex);
                       });
                })
                ->count();
            $sppPaymentRate = $totalStudentsCount > 0 ? round(($paidSppCount / $totalStudentsCount) * 100) : 0;
        } else {
            $sppPaymentRate = 100;
        }

        $monthNames = [
            1 => 'Juli', 2 => 'Agustus', 3 => 'September', 4 => 'Oktober', 5 => 'November', 6 => 'Desember',
            7 => 'Januari', 8 => 'Februari', 9 => 'Maret', 10 => 'April', 11 => 'Mei', 12 => 'Juni'
        ];
        $currentMonthName = $monthNames[$currentSppIndex] ?? 'Juli';

        // 9. Expense Distribution by Category
        $expenseDistribution = Expense::where('academic_year_id', $selectedYearId)
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->select('expense_categories.name as category_name', DB::raw('SUM(expenses.amount) as total_amount'))
            ->groupBy('expense_categories.name')
            ->get();

        $expenseLabels = $expenseDistribution->pluck('category_name')->toArray();
        $expenseValues = $expenseDistribution->pluck('total_amount')->map(function ($val) {
            return (float) $val;
        })->toArray();

        return view('dashboard', compact(
            'currentBalance',
            'totalIncome',
            'totalOutcome',
            'totalArrears',
            'attentionList',
            'monthlyIncome',
            'monthlyOutcome',
            'recentTransactions',
            'levels',
            'selectedYear',
            'totalStudentsCount',
            'discountedStudentsCount',
            'sppPaymentRate',
            'currentMonthName',
            'expenseLabels',
            'expenseValues'
        ));
    }
}
