<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\AcademicYear;
use App\Models\GlobalSppSetting;
use App\Models\StudentAnnualFee;
use App\Models\PaymentTransaction;
use App\Models\PaymentDetail;
use App\Models\Level;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        $selectedYear = $selectedYearId ? AcademicYear::find($selectedYearId) : null;

        $levels = Level::with('groups')->get();

        $query = PaymentTransaction::where('academic_year_id', $selectedYearId)
            ->with(['student', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by Date Range
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        // Filter by Search (Student Name or Invoice Number)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by Level
        if ($request->filled('level_id')) {
            $query->whereHas('student.enrollments', function ($q) use ($selectedYearId, $request) {
                $q->where('academic_year_id', $selectedYearId)
                  ->whereHas('studentGroup', function ($sq) use ($request) {
                      $sq->where('level_id', $request->level_id);
                  });
            });
        }

        // Filter by Group
        if ($request->filled('student_group_id')) {
            $query->whereHas('student.enrollments', function ($q) use ($selectedYearId, $request) {
                $q->where('academic_year_id', $selectedYearId)
                  ->where('student_group_id', $request->student_group_id);
            });
        }

        // Filter by Enrollment Type
        if ($request->filled('enrollment_type')) {
            $query->whereHas('student.enrollments', function ($q) use ($selectedYearId, $request) {
                $q->where('academic_year_id', $selectedYearId)
                  ->where('enrollment_type', $request->enrollment_type);
            });
        }

        $transactions = $query->paginate(20);

        return view('payments.index', compact('transactions', 'levels', 'selectedYear'));
    }

    public function create(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        $selectedYear = $selectedYearId ? AcademicYear::find($selectedYearId) : null;

        if (!$selectedYear) {
            return redirect()->route('payments.index')->with('error', 'Silakan tentukan Tahun Ajaran aktif terlebih dahulu.');
        }

        // Load levels with groups for the group filter dropdown
        $levels = Level::with('groups')->get();

        // Load enrolled students in selected year, applying student group filter if present
        $studentsQuery = StudentEnrollment::where('academic_year_id', $selectedYearId)
            ->whereHas('student', function ($q) {
                $q->where('status', 'Active');
            });

        if ($request->filled('student_group_id')) {
            $studentsQuery->where('student_group_id', $request->student_group_id);
        }

        $students = $studentsQuery->with(['student', 'studentGroup'])
            ->get()
            ->sortBy('student.name');

        $selectedStudent = null;
        $enrollment = null;
        $annualFees = [];
        $paidSppMonths = [];
        $monthlySppAmount = 0.00;
        $baseSppAmount = 0.00;
        $baseSppAmount = 0.00;

        if ($request->filled('student_id')) {
            $enrollmentQuery = StudentEnrollment::where('student_id', $request->student_id)
                ->where('academic_year_id', $selectedYearId);

            if ($request->filled('student_group_id')) {
                $enrollmentQuery->where('student_group_id', $request->student_group_id);
            }

            $enrollment = $enrollmentQuery->with(['discountCategory', 'studentGroup'])->first();

            if ($enrollment) {
                $selectedStudent = Student::find($request->student_id);
                // Load annual fees
                $annualFees = StudentAnnualFee::select('student_annual_fees.*')
                    ->join('annual_fee_components', 'student_annual_fees.annual_fee_component_id', '=', 'annual_fee_components.id')
                    ->where('student_annual_fees.student_enrollment_id', $enrollment->id)
                    ->orderBy('annual_fee_components.sort_order')
                    ->orderBy('annual_fee_components.id')
                    ->with('annualFeeComponent')
                    ->get();

                // Calculate paid SPP months
                $paidSppMonths = PaymentDetail::where('type', 'SPP')
                    ->whereHas('paymentTransaction', function ($q) use ($selectedStudent, $selectedYearId) {
                        $q->where('student_id', $selectedStudent->id)
                          ->where('academic_year_id', $selectedYearId);
                    })
                    ->pluck('month_index')
                    ->toArray();

                // Load base SPP amount for this year
                $sppSetting = GlobalSppSetting::where('academic_year_id', $selectedYearId)->first();
                $baseSppAmount = $sppSetting ? $sppSetting->amount : 0.00;

                // SPP stays normal, no discount applied
                $monthlySppAmount = $baseSppAmount;
            }
        }

        return view('payments.create', compact(
            'levels',
            'students',
            'selectedStudent',
            'enrollment',
            'annualFees',
            'paidSppMonths',
            'monthlySppAmount',
            'baseSppAmount',
            'selectedYear'
        ));
    }

    public function store(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'annual_payments' => 'nullable|array',
            'spp_months' => 'nullable|array',
        ]);

        $studentId = $request->student_id;
        $enrollment = StudentEnrollment::where('student_id', $studentId)
            ->where('academic_year_id', $selectedYearId)
            ->first();

        if (!$enrollment) {
            return redirect()->back()->with('error', 'Siswa tidak terdaftar di Tahun Ajaran terpilih.');
        }

        // SPP stays normal, no discount applied
        $sppSetting = GlobalSppSetting::where('academic_year_id', $selectedYearId)->first();
        $baseSppAmount = $sppSetting ? $sppSetting->amount : 0.00;
        $monthlySppAmount = $baseSppAmount;

        try {
            $transaction = DB::transaction(function () use ($request, $studentId, $selectedYearId, $enrollment, $monthlySppAmount) {
                // Generate Invoice Number: BYR-YYYYMMDD-XXXX
                $dateStr = date('Ymd', strtotime($request->date));
                $randomNumber = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $receiptNumber = "BYR-{$dateStr}-{$randomNumber}";

                // 1. Create Transaction header
                $tx = PaymentTransaction::create([
                    'student_id' => $studentId,
                    'academic_year_id' => $selectedYearId,
                    'user_id' => auth()->id(),
                    'date' => $request->date,
                    'total_amount' => 0.00, // Will update shortly
                    'receipt_number' => $receiptNumber,
                ]);

                $totalAmount = 0.00;

                // 2. Process Annual Fee payments
                if ($request->filled('annual_payments')) {
                    foreach ($request->annual_payments as $feeId => $payAmt) {
                        if ($payAmt <= 0) continue;

                        $annualFee = StudentAnnualFee::find($feeId);
                        if (!$annualFee || $annualFee->is_excluded) continue;

                        // Validate payment amount against balance
                        $balance = $annualFee->balance;
                        if ($payAmt > $balance + 0.01) { // Adding small delta for float precision
                            throw new \Exception("Jumlah bayar Rp " . number_format($payAmt, 0, ',', '.') . " melebihi sisa tagihan untuk komponen " . $annualFee->annualFeeComponent->name . " (Sisa: Rp " . number_format($balance, 0, ',', '.') . ").");
                        }

                        // Save detail
                        PaymentDetail::create([
                            'payment_transaction_id' => $tx->id,
                            'type' => 'Annual',
                            'reference_id' => $feeId,
                            'month_index' => null,
                            'amount' => $payAmt,
                        ]);

                        $totalAmount += $payAmt;
                    }
                }

                // 3. Process SPP payments
                if ($request->filled('spp_months')) {
                    // Filter out duplicate or already paid months in database
                    $existingSpp = PaymentDetail::where('type', 'SPP')
                        ->whereHas('paymentTransaction', function ($q) use ($studentId, $selectedYearId) {
                            $q->where('student_id', $studentId)
                              ->where('academic_year_id', $selectedYearId);
                        })
                        ->pluck('month_index')
                        ->toArray();

                    foreach ($request->spp_months as $mIndex) {
                        $mIndex = (int) $mIndex;
                        
                        // July (1) is bundled in annual, prevent manual payment
                        if ($mIndex === 1) {
                            throw new \Exception("Pembayaran SPP Juli harus dilakukan melalui komponen Biaya Tahunan.");
                        }

                        if (in_array($mIndex, $existingSpp)) {
                            throw new \Exception("SPP untuk Bulan ke-{$mIndex} sudah pernah dibayar sebelumnya.");
                        }

                        PaymentDetail::create([
                            'payment_transaction_id' => $tx->id,
                            'type' => 'SPP',
                            'reference_id' => null,
                            'month_index' => $mIndex,
                            'amount' => $monthlySppAmount,
                        ]);

                        $totalAmount += $monthlySppAmount;
                    }
                }



                if ($totalAmount <= 0) {
                    throw new \Exception("Silakan isi nominal pembayaran atau pilih bulan SPP terlebih dahulu.");
                }

                // Update Transaction total
                $tx->update(['total_amount' => $totalAmount]);

                return $tx;
            });

            return redirect()->route('payments.show', $transaction->id)->with('success', 'Transaksi pembayaran berhasil dicatat.');

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(PaymentTransaction $transaction)
    {
        $transaction->load(['student.enrollments' => function ($q) use ($transaction) {
            $q->where('academic_year_id', $transaction->academic_year_id);
        }, 'paymentDetails.studentAnnualFee.annualFeeComponent', 'user']);

        $enrollment = $transaction->student->enrollments->first();

        return view('payments.show', compact('transaction', 'enrollment'));
    }

    public function print(PaymentTransaction $transaction)
    {
        $transaction->load(['student.enrollments' => function ($q) use ($transaction) {
            $q->where('academic_year_id', $transaction->academic_year_id);
        }, 'paymentDetails.studentAnnualFee.annualFeeComponent', 'user']);

        $enrollment = $transaction->student->enrollments->first();

        return view('payments.print', compact('transaction', 'enrollment'));
    }
}
