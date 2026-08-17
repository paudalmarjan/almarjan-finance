<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\Hash;
use App\Models\GlobalSppSetting;
use App\Models\PaymentDetail;

class ParentPortalController extends Controller
{
    public function index()
    {
        $studentId = session('parent_student_id');
        $student = Student::with(['savings', 'paymentTransactions.paymentDetails'])->findOrFail($studentId);

        $activeYear = AcademicYear::where('is_active', true)->first();
        $enrollment = null;
        $totalArrears = 0;
        $sppArrears = 0;
        $annualArrears = 0;
        $sppMonths = [];
        $annualFees = [];
        $baseSppAmount = 0;

        if ($activeYear) {
            $enrollment = $student->enrollmentForYear($activeYear->id);
            if ($enrollment) {
                // Calculate Arrears
                $today = date('Y-m-d');
                $currentSppIndex = 12;
                if ($activeYear->start_date->format('Y-m-d') > $today) {
                    $currentSppIndex = 0;
                } elseif ($activeYear->start_date->format('Y-m-d') <= $today && $activeYear->end_date->format('Y-m-d') >= $today) {
                    $currentMonth = (int) date('n');
                    $monthMap = [7=>1, 8=>2, 9=>3, 10=>4, 11=>5, 12=>6, 1=>7, 2=>8, 3=>9, 4=>10, 5=>11, 6=>12];
                    $currentSppIndex = $monthMap[$currentMonth] ?? 12;
                }

                $sppSetting = GlobalSppSetting::where('academic_year_id', $activeYear->id)->first();
                $baseSppAmount = $sppSetting ? $sppSetting->amount : 0.00;

                // Paid SPP Months
                $paidMonths = PaymentDetail::where('type', 'SPP')
                    ->whereHas('paymentTransaction', function ($q) use ($activeYear, $student) {
                        $q->where('academic_year_id', $activeYear->id)
                          ->where('student_id', $student->id);
                    })
                    ->pluck('month_index')->toArray();

                for ($m = 1; $m <= 12; $m++) {
                    // Month 1 is July. We consider it part of Annual Fees (Daftar Ulang)
                    if ($m === 1) {
                        $sppMonths[$m] = [
                            'index' => 1,
                            'name' => 'Juli',
                            'status' => 'Daftar Ulang'
                        ];
                        continue;
                    }

                    $isPaid = in_array($m, $paidMonths);
                    $isDue = ($m <= $currentSppIndex);
                    
                    $status = 'Not Due';
                    if ($isPaid) $status = 'Paid';
                    elseif ($isDue) {
                        $status = 'Unpaid';
                        $sppArrears += $baseSppAmount;
                    }

                    $sppMonths[$m] = [
                        'index' => $m,
                        'name' => \Carbon\Carbon::create()->month($m <= 6 ? $m + 6 : $m - 6)->translatedFormat('F'),
                        'status' => $status
                    ];
                }

                // Annual Arrears
                // We need the relation to annualFeeComponent for the name
                $enrollment->load('studentAnnualFees.annualFeeComponent');
                foreach ($enrollment->studentAnnualFees as $fee) {
                    if (!$fee->is_excluded) {
                        $annualArrears += $fee->balance;
                    }
                    $annualFees[] = $fee;
                }

                $totalArrears = $sppArrears + $annualArrears;
            }
        }

        // Recent Transactions
        $recentTransactions = $student->paymentTransactions()->latest()->take(5)->get();

        return view('wali.dashboard', compact(
            'student', 'activeYear', 'enrollment', 'totalArrears', 'sppArrears', 'annualArrears', 'sppMonths', 'annualFees', 'baseSppAmount', 'recentTransactions'
        ));
    }

    public function showChangePin()
    {
        return view('wali.change_pin');
    }

    public function updatePin(Request $request)
    {
        $request->validate([
            'old_pin' => 'required',
            'new_pin' => 'required|min:4|max:10|confirmed',
        ]);

        $studentId = session('parent_student_id');
        $student = Student::findOrFail($studentId);

        if (!Hash::check($request->old_pin, $student->pin)) {
            return back()->with('error', 'PIN lama yang Anda masukkan tidak sesuai.');
        }

        $student->update([
            'pin' => Hash::make($request->new_pin)
        ]);

        return redirect()->route('wali.dashboard')->with('success', 'PIN berhasil diubah. Gunakan PIN baru Anda untuk login berikutnya.');
    }
}
