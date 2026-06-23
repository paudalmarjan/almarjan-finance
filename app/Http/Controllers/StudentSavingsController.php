<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\StudentSaving;
use App\Models\StudentSavingsTransaction;
use App\Models\AcademicYear;
use App\Models\Level;
use Carbon\Carbon;

class StudentSavingsController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        $today          = Carbon::today();
        $startOfWeek    = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $startOfMonth   = Carbon::now()->startOfMonth();

        // ── KPI: Balance & Participation ───────────────────────────────────
        $totalBalance       = (float) StudentSaving::sum('balance');
        $totalStudents      = Student::where('status', 'Active')->count();
        $studentsWithSaving = StudentSaving::where('balance', '>', 0)->count();
        $avgBalance         = $studentsWithSaving > 0 ? round($totalBalance / $studentsWithSaving) : 0;
        $participationRate  = $totalStudents > 0 ? round(($studentsWithSaving / $totalStudents) * 100, 1) : 0;

        // ── KPI: Today ────────────────────────────────────────────────────
        $todayDeposits   = (float) StudentSavingsTransaction::whereDate('transaction_date', $today)->where('type', 'Deposit')->sum('amount');
        $todayWithdrawals= (float) StudentSavingsTransaction::whereDate('transaction_date', $today)->where('type', 'Withdrawal')->sum('amount');
        $todayNet        = $todayDeposits - $todayWithdrawals;
        $todayTxCount    = StudentSavingsTransaction::whereDate('transaction_date', $today)->count();

        // ── KPI: Week ─────────────────────────────────────────────────────
        $weeklyDeposits    = (float) StudentSavingsTransaction::where('transaction_date', '>=', $startOfWeek)->where('type', 'Deposit')->sum('amount');
        $weeklyWithdrawals = (float) StudentSavingsTransaction::where('transaction_date', '>=', $startOfWeek)->where('type', 'Withdrawal')->sum('amount');
        $weeklyNet         = $weeklyDeposits - $weeklyWithdrawals;

        // ── KPI: Month ────────────────────────────────────────────────────
        $monthlyDeposits    = (float) StudentSavingsTransaction::where('transaction_date', '>=', $startOfMonth)->where('type', 'Deposit')->sum('amount');
        $monthlyWithdrawals = (float) StudentSavingsTransaction::where('transaction_date', '>=', $startOfMonth)->where('type', 'Withdrawal')->sum('amount');
        $monthlyNet         = $monthlyDeposits - $monthlyWithdrawals;

        // ── Chart A: Daily activity (last 14 days) ───────────────────────
        $dailyChart = ['labels' => [], 'deposits' => [], 'withdrawals' => [], 'net' => []];
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dep  = (float) StudentSavingsTransaction::whereDate('transaction_date', $date)->where('type', 'Deposit')->sum('amount');
            $wit  = (float) StudentSavingsTransaction::whereDate('transaction_date', $date)->where('type', 'Withdrawal')->sum('amount');
            $dailyChart['labels'][]      = $date->format('d/m');
            $dailyChart['deposits'][]    = $dep;
            $dailyChart['withdrawals'][] = $wit;
            $dailyChart['net'][]         = $dep - $wit;
        }

        // ── Chart B: Monthly trend (last 6 months) ────────────────────────
        $monthlyChart = ['labels' => [], 'deposits' => [], 'withdrawals' => []];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $dep   = (float) StudentSavingsTransaction::whereYear('transaction_date', $month->year)->whereMonth('transaction_date', $month->month)->where('type', 'Deposit')->sum('amount');
            $wit   = (float) StudentSavingsTransaction::whereYear('transaction_date', $month->year)->whereMonth('transaction_date', $month->month)->where('type', 'Withdrawal')->sum('amount');
            $monthlyChart['labels'][]      = $month->format('M Y');
            $monthlyChart['deposits'][]    = $dep;
            $monthlyChart['withdrawals'][] = $wit;
        }

        // ── Level Distribution ────────────────────────────────────────────
        $levelBalances = [];
        $levels = Level::all();
        foreach ($levels as $level) {
            $bal = (float) StudentSaving::whereHas('student.enrollments.studentGroup', function ($q) use ($level, $selectedYearId) {
                $q->where('level_id', $level->id)->where('academic_year_id', $selectedYearId);
            })->sum('balance');
            $cnt = StudentSaving::whereHas('student.enrollments.studentGroup', function ($q) use ($level, $selectedYearId) {
                $q->where('level_id', $level->id)->where('academic_year_id', $selectedYearId);
            })->where('balance', '>', 0)->count();
            $levelBalances[] = ['name' => $level->name, 'balance' => $bal, 'count' => $cnt];
        }

        // ── Top 5 Balances ────────────────────────────────────────────────
        $topDepositors = StudentSaving::with('student')->orderByDesc('balance')->take(5)->get();

        // ── Recent 8 Transactions ─────────────────────────────────────────
        $recentTransactions = StudentSavingsTransaction::with(['student', 'user'])
            ->orderByDesc('transaction_date')->orderByDesc('id')->take(8)->get();

        return view('savings.dashboard', compact(
            'totalBalance', 'totalStudents', 'studentsWithSaving', 'avgBalance', 'participationRate',
            'todayDeposits', 'todayWithdrawals', 'todayNet', 'todayTxCount',
            'weeklyDeposits', 'weeklyWithdrawals', 'weeklyNet',
            'monthlyDeposits', 'monthlyWithdrawals', 'monthlyNet',
            'dailyChart', 'monthlyChart',
            'levelBalances', 'topDepositors', 'recentTransactions'
        ));
    }

    public function students(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        $selectedYear = $selectedYearId ? AcademicYear::find($selectedYearId) : null;
        
        $levels = Level::with('groups')->get();
        
        $query = Student::where('status', 'Active')->with(['savings', 'enrollments' => function($q) use ($selectedYearId) {
            $q->where('academic_year_id', $selectedYearId)->with('studentGroup.level');
        }]);

        if ($request->filled('level_id')) {
            $query->whereHas('enrollments', function($q) use ($request, $selectedYearId) {
                $q->where('academic_year_id', $selectedYearId)
                  ->whereHas('studentGroup', function($sq) use ($request) {
                      $sq->where('level_id', $request->level_id);
                  });
            });
        }

        if ($request->filled('student_group_id')) {
            $query->whereHas('enrollments', function($q) use ($request, $selectedYearId) {
                $q->where('academic_year_id', $selectedYearId)
                  ->where('student_group_id', $request->student_group_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function($q) use ($search, $like) {
                $q->where('name', $like, "%{$search}%")
                  ->orWhere('nis', $like, "%{$search}%")
                  ->orWhere('nickname', $like, "%{$search}%");
            });
        }

        $students = $query->paginate(20);

        return view('savings.students', compact('students', 'levels', 'selectedYear'));
    }

    public function deposit()
    {
        $selectedYearId = session('selected_academic_year_id');
        $selectedYear = $selectedYearId ? AcademicYear::find($selectedYearId) : null;
        
        if (!$selectedYear || !$selectedYear->is_active) {
            if (!auth()->user()->isAdmin()) {
                return redirect()->route('savings.students')->with('error', 'Hanya Administrator yang dapat melakukan transaksi di Tahun Ajaran yang tidak aktif.');
            }
        }
        
        $levels = Level::with('groups')->get();
        
        // Pass students with their enrollment group for the selected year
        $students = Student::where('status', 'Active')
            ->with(['enrollments' => function($q) use ($selectedYearId) {
                $q->where('academic_year_id', $selectedYearId);
            }])
            ->orderBy('name', 'asc')
            ->get();
            
        return view('savings.deposit', compact('selectedYear', 'students', 'levels'));
    }

    public function withdraw()
    {
        $selectedYearId = session('selected_academic_year_id');
        $selectedYear = $selectedYearId ? AcademicYear::find($selectedYearId) : null;
        
        if (!$selectedYear || !$selectedYear->is_active) {
            if (!auth()->user()->isAdmin()) {
                return redirect()->route('savings.students')->with('error', 'Hanya Administrator yang dapat melakukan transaksi di Tahun Ajaran yang tidak aktif.');
            }
        }
        
        $levels = Level::with('groups')->get();
        
        $students = Student::where('status', 'Active')
            ->with(['enrollments' => function($q) use ($selectedYearId) {
                $q->where('academic_year_id', $selectedYearId);
            }])
            ->orderBy('name', 'asc')
            ->get();
            
        return view('savings.withdraw', compact('selectedYear', 'students', 'levels'));
    }

    public function storeBulk(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        $selectedYear = AcademicYear::find($selectedYearId);
        
        if (!$selectedYear || !$selectedYear->is_active) {
            if (!auth()->user()->isAdmin()) {
                return redirect()->route('savings.students')->with('error', 'Akses ditolak.');
            }
        }

        // Filter out students with empty or 0 amounts
        $studentsToProcess = collect($request->students)->filter(function ($student) {
            return !empty($student['amount']) && floatval($student['amount']) > 0;
        })->values()->all();

        if (empty($studentsToProcess)) {
            return redirect()->back()->with('warning', 'Tidak ada baris dengan nominal yang valid untuk disimpan.');
        }

        $request->merge(['filtered_students' => $studentsToProcess]);

        $request->validate([
            'type' => 'required|in:Deposit,Withdrawal',
            'transaction_date' => 'required|date',
            'filtered_students' => 'required|array|min:1',
            'filtered_students.*.id' => 'required|exists:students,id',
            'filtered_students.*.amount' => 'required|numeric|min:1',
            'filtered_students.*.notes' => 'nullable|string|max:500',
        ]);

        $type = $request->type;
        $transactionDate = $request->transaction_date;
        $userId = auth()->id();

        try {
            DB::transaction(function () use ($request, $selectedYearId, $type, $transactionDate, $userId) {
                foreach ($request->filtered_students as $studentData) {
                    $studentId = $studentData['id'];
                    $amount = (float) $studentData['amount'];
                    $notes = $studentData['notes'] ?? null;

                    $saving = StudentSaving::firstOrCreate(
                        ['student_id' => $studentId],
                        ['balance' => 0]
                    );

                    if ($type === 'Withdrawal' && $saving->balance < $amount) {
                        $student = Student::find($studentId);
                        throw new \Exception("Saldo tidak mencukupi untuk melakukan penarikan sebesar Rp " . number_format($amount, 0, ',', '.') . " pada siswa " . $student->name);
                    }

                    if ($type === 'Deposit') {
                        $saving->balance += $amount;
                    } else {
                        $saving->balance -= $amount;
                    }
                    
                    $saving->save();

                    $receiptCode = 'SAV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

                    StudentSavingsTransaction::create([
                        'student_id' => $studentId,
                        'academic_year_id' => $selectedYearId,
                        'user_id' => $userId,
                        'type' => $type,
                        'amount' => $amount,
                        'transaction_date' => $transactionDate,
                        'notes' => $notes,
                        'receipt_number' => $receiptCode,
                    ]);
                }
            });

            return redirect()->route('savings.students')->with('success', 'Transaksi tabungan massal berhasil dicatat.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function history(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        
        $students = Student::where('status', 'Active')
            ->orderBy('name', 'asc')
            ->get();
            
        $selectedStudent = null;
        $saving = null;
        $transactions = collect();
        
        if ($request->has('student_id') && $request->student_id != '') {
            $selectedStudent = Student::findOrFail($request->student_id);
            $saving = $selectedStudent->savings;
            $transactions = $selectedStudent->savingsTransactions()
                ->with(['academicYear', 'user'])
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->get();
        }
            
        return view('savings.history', compact('students', 'selectedStudent', 'saving', 'transactions'));
    }

    public function print(StudentSavingsTransaction $transaction)
    {
        $transaction->load(['student', 'user']);
        return view('savings.print', compact('transaction'));
    }

    public function destroy(StudentSavingsTransaction $transaction)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Akses Ditolak: Hanya Administrator Utama yang dapat menghapus transaksi tabungan.');
        }

        $academicYear = $transaction->academicYear;
        if (!$academicYear || !$academicYear->is_active) {
            return redirect()->back()->with('error', 'Akses Ditolak: Transaksi di luar Tahun Ajaran aktif tidak dapat dihapus/dibatalkan.');
        }

        try {
            DB::transaction(function () use ($transaction) {
                // Find student saving record
                $saving = StudentSaving::where('student_id', $transaction->student_id)->first();
                
                if ($saving) {
                    // Correct the student's balance
                    if ($transaction->type === 'Deposit') {
                        // Deleting a deposit means subtracting the amount from the balance
                        if ($saving->balance < $transaction->amount) {
                            throw new \Exception("Tidak dapat menghapus setoran. Saldo akhir siswa saat ini (Rp " . number_format($saving->balance, 0, ',', '.') . ") lebih kecil dari jumlah setoran yang ingin dibatalkan (Rp " . number_format($transaction->amount, 0, ',', '.') . ").");
                        }
                        $saving->balance -= $transaction->amount;
                    } else {
                        // Deleting a withdrawal means adding the amount back to the balance
                        $saving->balance += $transaction->amount;
                    }
                    $saving->save();
                }

                // Delete the transaction record
                $transaction->delete();
            });

            return redirect()->back()->with('success', 'Transaksi tabungan berhasil dibatalkan dan dihapus. Saldo siswa telah dikoreksi.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membatalkan transaksi: ' . $e->getMessage());
        }
    }
}
