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
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        $selectedYear   = $selectedYearId ? AcademicYear::find($selectedYearId) : null;

        if (!$selectedYear) {
            return redirect()->route('settings.index')->with('error', 'Silakan aktifkan Tahun Ajaran terlebih dahulu.');
        }

        $levels = Level::with('groups')->get();
        $today  = Carbon::today();

        // ── Base Query Scopes ─────────────────────────────────────────────
        $studentQuery = StudentEnrollment::where('academic_year_id', $selectedYearId)
            ->whereHas('student', fn($q) => $q->where('status', 'Active'));

        $incomeQuery = PaymentTransaction::where('academic_year_id', $selectedYearId);

        // ── Core Financials ───────────────────────────────────────────────
        $totalIncome    = (float) $incomeQuery->sum('total_amount');
        $totalOutcome   = (float) Expense::where('academic_year_id', $selectedYearId)->sum('amount');
        $currentBalance = (float) $selectedYear->initial_cash_balance + $totalIncome - $totalOutcome;

        $todayIncome      = (float) $incomeQuery->clone()->whereDate('date', $today)->sum('total_amount');
        $thisMonthIncome  = (float) $incomeQuery->clone()->whereYear('date', $today->year)->whereMonth('date', $today->month)->sum('total_amount');
        $thisMonthOutcome = (float) Expense::where('academic_year_id', $selectedYearId)->whereYear('date', $today->year)->whereMonth('date', $today->month)->sum('amount');
        $thisMonthNet     = $thisMonthIncome - $thisMonthOutcome;

        // ── Student Counts ────────────────────────────────────────────────
        $totalStudentsCount      = (int) $studentQuery->clone()->count();
        $discountedStudentsCount = (int) $studentQuery->clone()->whereNotNull('discount_category_id')->count();

        // ── SPP Rate ──────────────────────────────────────────────────────
        $sppSetting    = GlobalSppSetting::where('academic_year_id', $selectedYearId)->first();
        $baseSppAmount = $sppSetting ? $sppSetting->amount : 0.00;

        $currentSppIndex = 12;
        $todayStr = $today->format('Y-m-d');
        if ($selectedYear->start_date->format('Y-m-d') > $todayStr) {
            $currentSppIndex = 0;
        } elseif ($selectedYear->start_date->format('Y-m-d') <= $todayStr && $selectedYear->end_date->format('Y-m-d') >= $todayStr) {
            $monthMap        = [7=>1, 8=>2, 9=>3, 10=>4, 11=>5, 12=>6, 1=>7, 2=>8, 3=>9, 4=>10, 5=>11, 6=>12];
            $currentSppIndex = $monthMap[$today->month] ?? 12;
        }

        $monthNames       = [1=>'Juli', 2=>'Agustus', 3=>'September', 4=>'Oktober', 5=>'November', 6=>'Desember', 7=>'Januari', 8=>'Februari', 9=>'Maret', 10=>'April', 11=>'Mei', 12=>'Juni'];
        $currentMonthName = $monthNames[$currentSppIndex] ?? 'Juli';

        $paidSppCount   = 0;
        $sppPaymentRate = 100;
        if ($currentSppIndex >= 2) {
            $paidSppCount = StudentEnrollment::where('academic_year_id', $selectedYearId)
                ->whereHas('student', fn($q) => $q->where('status', 'Active'))
                ->whereHas('student.paymentTransactions', function ($pq) use ($selectedYearId, $currentSppIndex) {
                    $pq->where('academic_year_id', $selectedYearId)
                       ->whereHas('paymentDetails', fn($dq) => $dq->where('type', 'SPP')->where('month_index', $currentSppIndex));
                })->count();
            $sppPaymentRate = $totalStudentsCount > 0 ? round(($paidSppCount / $totalStudentsCount) * 100) : 0;
        }

        // ── Arrears Calculation ───────────────────────────────────────────
        $activeEnrollments = $studentQuery->with(['studentAnnualFees', 'student', 'studentGroup'])->get();
        $totalArrears  = 0.00;
        $attentionList = [];

        foreach ($activeEnrollments as $enr) {
            $annualArrears = collect($enr->studentAnnualFees)->where('is_excluded', false)->sum('balance');

            $paidSppMonths = PaymentDetail::where('type', 'SPP')
                ->whereHas('paymentTransaction', fn($q) => $q->where('student_id', $enr->student_id)->where('academic_year_id', $selectedYearId))
                ->pluck('month_index')->toArray();

            $sppArrears = 0.00;
            for ($m = 2; $m <= $currentSppIndex; $m++) {
                if (!in_array($m, $paidSppMonths)) $sppArrears += $baseSppAmount;
            }

            $studentArrears = $annualArrears + $sppArrears;
            $totalArrears  += $studentArrears;

            if ($studentArrears > 0) {
                $attentionList[] = [
                    'student_id' => $enr->student->id,
                    'name'       => $enr->student->name,
                    'group_name' => optional($enr->studentGroup)->name ?? '-',
                    'amount'     => $studentArrears,
                ];
            }
        }

        usort($attentionList, fn($a, $b) => $b['amount'] <=> $a['amount']);
        $attentionList = array_slice($attentionList, 0, 5);
        $maxArrears    = count($attentionList) > 0 ? $attentionList[0]['amount'] : 1;

        // Collection Rate
        $totalPotentialIncome = $totalIncome + $totalArrears;
        $collectionRate       = $totalPotentialIncome > 0 ? round(($totalIncome / $totalPotentialIncome) * 100, 1) : 100;
        $arrearsRatio         = $totalPotentialIncome > 0 ? round(($totalArrears / $totalPotentialIncome) * 100, 1) : 0;

        // ── Monthly Chart (Academic Year: Jul–Jun) ─────────────────────────
        $academicMonths = [7, 8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6];
        $monthLabels    = ['Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'];
        $monthlyIncome  = [];
        $monthlyOutcome = [];
        $monthlyNet     = [];

        $startYear = (int) $selectedYear->start_date->format('Y');
        $endYear   = (int) $selectedYear->end_date->format('Y');

        foreach ($academicMonths as $month) {
            $year  = ($month >= 7) ? $startYear : $endYear;
            $mInc  = (float) $incomeQuery->clone()->whereYear('date', $year)->whereMonth('date', $month)->sum('total_amount');
            $mExp  = (float) Expense::where('academic_year_id', $selectedYearId)->whereYear('date', $year)->whereMonth('date', $month)->sum('amount');
            $monthlyIncome[]  = $mInc;
            $monthlyOutcome[] = $mExp;
            $monthlyNet[]     = $mInc - $mExp;
        }

        // ── Recent Transactions ───────────────────────────────────────────
        $recentPayments = PaymentTransaction::where('academic_year_id', $selectedYearId)
            ->with('student')->orderByDesc('date')->orderByDesc('created_at')->limit(6)->get()
            ->map(fn($item) => [
                'date'        => $item->date,
                'type'        => 'Pemasukan',
                'description' => $item->student->name,
                'amount'      => $item->total_amount,
                'route'       => route('payments.show', $item->id),
            ]);

        $recentExpenses = Expense::where('academic_year_id', $selectedYearId)
            ->with('expenseCategory')->orderByDesc('date')->orderByDesc('created_at')->limit(6)->get()
            ->map(fn($item) => [
                'date'        => $item->date,
                'type'        => 'Pengeluaran',
                'description' => optional($item->expenseCategory)->name . ': ' . ($item->notes ?? 'Operasional'),
                'amount'      => $item->amount,
                'route'       => route('expenses.index'),
            ]);

        $recentTransactions = $recentPayments->concat($recentExpenses)->sortByDesc('date')->values()->all();

        // ── Expense Distribution ──────────────────────────────────────────
        $expenseDistribution = Expense::where('academic_year_id', $selectedYearId)
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->select('expense_categories.name as category_name', DB::raw('SUM(expenses.amount) as total_amount'))
            ->groupBy('expense_categories.name')->get();

        $expenseLabels = $expenseDistribution->pluck('category_name')->toArray();
        $expenseValues = $expenseDistribution->pluck('total_amount')->map(fn($v) => (float) $v)->toArray();

        return view('dashboard', compact(
            'currentBalance', 'totalIncome', 'totalOutcome', 'totalArrears',
            'todayIncome', 'thisMonthIncome', 'thisMonthOutcome', 'thisMonthNet',
            'collectionRate', 'arrearsRatio', 'totalPotentialIncome',
            'attentionList', 'maxArrears',
            'monthlyIncome', 'monthlyOutcome', 'monthlyNet', 'monthLabels',
            'recentTransactions',
            'selectedYear',
            'totalStudentsCount', 'discountedStudentsCount',
            'sppPaymentRate', 'currentMonthName',
            'expenseLabels', 'expenseValues',
            'paidSppCount'
        ));
    }
}
