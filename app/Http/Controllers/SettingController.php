<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\StudentGroup;
use App\Models\DiscountCategory;
use App\Models\AnnualFeeComponent;
use App\Models\GlobalSppSetting;
use App\Models\User;
use App\Models\ExpenseCategory;

class SettingController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        foreach ($academicYears as $ay) {
            $prevYear = AcademicYear::where('start_date', '<', $ay->start_date)
                ->orderBy('start_date', 'desc')
                ->first();
            $ay->recommended_initial_balance = $prevYear ? $prevYear->calculateEndingBalance() : null;
        }

        $latestYear = AcademicYear::orderBy('start_date', 'desc')->first();
        $recommendedNewBalance = $latestYear ? $latestYear->calculateEndingBalance() : null;

        $levels = Level::with('groups')->get();
        $discountCategories = DiscountCategory::orderBy('name')->get();
        $expenseCategories = ExpenseCategory::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        // Get context of the current global selected academic year
        $selectedYearId = session('selected_academic_year_id');
        $selectedYear = $selectedYearId ? AcademicYear::find($selectedYearId) : null;

        $feeComponents = [];
        $sppSetting = null;

        if ($selectedYear) {
            $feeComponents = AnnualFeeComponent::where('academic_year_id', $selectedYear->id)
                ->with('level')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
            $sppSetting = GlobalSppSetting::where('academic_year_id', $selectedYear->id)->first();
        }

        return view('settings.index', compact(
            'academicYears',
            'levels',
            'discountCategories',
            'expenseCategories',
            'users',
            'selectedYear',
            'feeComponents',
            'sppSetting',
            'recommendedNewBalance'
        ));
    }

    public function storeAcademicYear(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:academic_years,name|regex:/^\d{4}\/\d{4}$/',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'initial_cash_balance' => 'nullable|numeric|min:0',
        ], [
            'name.unique' => 'Tahun Ajaran ini sudah terdaftar.',
            'name.regex' => 'Format Tahun Ajaran harus YYYY/YYYY (misal: 2025/2026).',
            'end_date.after' => 'Tanggal selesai harus setelah tanggal mulai.',
            'initial_cash_balance.min' => 'Saldo awal kas tidak boleh negatif.',
        ]);

        AcademicYear::create([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => false, // Default inactive, must be toggled manually
            'initial_cash_balance' => $request->initial_cash_balance ?? 0.00,
        ]);

        return redirect()->route('settings.index')->with('success', 'Tahun Ajaran baru berhasil ditambahkan.');
    }

    public function updateAcademicYear(Request $request, AcademicYear $year)
    {
        $request->validate([
            'name' => 'required|string|regex:/^\d{4}\/\d{4}$/|unique:academic_years,name,' . $year->id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'initial_cash_balance' => 'required|numeric|min:0',
        ], [
            'name.unique' => 'Tahun Ajaran ini sudah terdaftar.',
            'name.regex' => 'Format Tahun Ajaran harus YYYY/YYYY (misal: 2025/2026).',
            'end_date.after' => 'Tanggal selesai harus setelah tanggal mulai.',
            'initial_cash_balance.min' => 'Saldo awal kas tidak boleh negatif.',
        ]);

        $year->update([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'initial_cash_balance' => $request->initial_cash_balance,
        ]);

        return redirect()->route('settings.index')->with('success', 'Data Tahun Ajaran berhasil diperbarui.');
    }

    public function toggleActiveAcademicYear(Request $request, AcademicYear $year)
    {
        // Set all others to inactive
        AcademicYear::where('id', '!=', $year->id)->update(['is_active' => false]);
        
        // Toggle the selected one to active
        $year->update(['is_active' => true]);

        // Automatically update current context in session
        session(['selected_academic_year_id' => $year->id]);

        return redirect()->route('settings.index')->with('success', "Tahun Ajaran {$year->name} sekarang menjadi Tahun Ajaran Aktif.");
    }

    public function storeGroup(Request $request)
    {
        $request->validate([
            'level_id' => 'required|exists:levels,id',
            'name' => 'required|string|max:50',
        ]);

        // Check uniqueness for this level
        $exists = StudentGroup::where('level_id', $request->level_id)
            ->where('name', $request->name)
            ->exists();

        if ($exists) {
            return redirect()->route('settings.index')->with('error', 'Kelompok dengan nama tersebut sudah ada di Jenjang ini.');
        }

        StudentGroup::create([
            'level_id' => $request->level_id,
            'name' => $request->name,
        ]);

        return redirect()->route('settings.index')->with('success', 'Kelompok kelas baru berhasil ditambahkan.');
    }

    public function destroyGroup(StudentGroup $group)
    {
        // Prevent deletion if there are enrolled students
        if ($group->enrollments()->exists()) {
            return redirect()->route('settings.index')->with('error', 'Tidak bisa menghapus kelompok ini karena sudah memiliki data siswa terdaftar.');
        }

        $group->delete();
        return redirect()->route('settings.index')->with('success', 'Kelompok kelas berhasil dihapus.');
    }

    public function storeFeeComponent(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        if (!$selectedYearId) {
            return redirect()->route('settings.index')->with('error', 'Silakan pilih Tahun Ajaran terlebih dahulu.');
        }

        $request->validate([
            'level_ids' => 'required|array',
            'level_ids.*' => 'exists:levels,id',
            'name' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'target_type' => 'required|in:All,New,Returning',
        ]);

        foreach ($request->level_ids as $levelId) {
            $maxOrder = AnnualFeeComponent::where('academic_year_id', $selectedYearId)
                ->where('level_id', $levelId)
                ->max('sort_order') ?? 0;

            AnnualFeeComponent::create([
                'academic_year_id' => $selectedYearId,
                'level_id' => $levelId,
                'name' => $request->name,
                'amount' => $request->amount,
                'target_type' => $request->target_type,
                'sort_order' => $maxOrder + 1,
            ]);
        }

        return redirect()->route('settings.index')->with('success', 'Komponen Biaya Tahunan berhasil ditambahkan.');
    }

    public function destroyFeeComponent(AnnualFeeComponent $fee)
    {
        // Check if there are any payments recorded for this component
        $hasPayments = \App\Models\PaymentDetail::where('type', 'Annual')
            ->whereIn('reference_id', $fee->studentAnnualFees()->pluck('id'))
            ->exists();

        if ($hasPayments) {
            return redirect()->route('settings.index')->with('error', 'Komponen biaya ini tidak bisa dihapus karena sudah ada transaksi pembayaran masuk dari siswa.');
        }

        // Safe delete: delete the student bills first, then delete the component
        \Illuminate\Support\Facades\DB::transaction(function () use ($fee) {
            $fee->studentAnnualFees()->delete();
            $fee->delete();
        });

        return redirect()->route('settings.index')->with('success', 'Komponen Biaya Tahunan berhasil dihapus dari pengaturan dan tagihan siswa.');
    }

    public function moveFeeComponentUp(AnnualFeeComponent $fee)
    {
        $components = AnnualFeeComponent::where('academic_year_id', $fee->academic_year_id)
            ->where('level_id', $fee->level_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $hasDuplicates = $components->pluck('sort_order')->duplicates()->isNotEmpty();
        $allZeroes = $components->pluck('sort_order')->every(fn($val) => $val === 0);

        if ($hasDuplicates || $allZeroes) {
            foreach ($components as $i => $comp) {
                $comp->update(['sort_order' => $i]);
            }
            $components = AnnualFeeComponent::where('academic_year_id', $fee->academic_year_id)
                ->where('level_id', $fee->level_id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        $index = $components->search(function ($item) use ($fee) {
            return $item->id === $fee->id;
        });

        if ($index !== false && $index > 0) {
            $prevFee = $components[$index - 1];
            
            $feeOrder = $components[$index]->sort_order;
            $prevOrder = $prevFee->sort_order;
            
            $components[$index]->update(['sort_order' => $prevOrder]);
            $prevFee->update(['sort_order' => $feeOrder]);
        }

        return redirect()->route('settings.index')->with('success', 'Urutan komponen biaya berhasil diperbarui.');
    }

    public function moveFeeComponentDown(AnnualFeeComponent $fee)
    {
        $components = AnnualFeeComponent::where('academic_year_id', $fee->academic_year_id)
            ->where('level_id', $fee->level_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $hasDuplicates = $components->pluck('sort_order')->duplicates()->isNotEmpty();
        $allZeroes = $components->pluck('sort_order')->every(fn($val) => $val === 0);

        if ($hasDuplicates || $allZeroes) {
            foreach ($components as $i => $comp) {
                $comp->update(['sort_order' => $i]);
            }
            $components = AnnualFeeComponent::where('academic_year_id', $fee->academic_year_id)
                ->where('level_id', $fee->level_id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        $index = $components->search(function ($item) use ($fee) {
            return $item->id === $fee->id;
        });

        if ($index !== false && $index < $components->count() - 1) {
            $nextFee = $components[$index + 1];
            
            $feeOrder = $components[$index]->sort_order;
            $nextOrder = $nextFee->sort_order;
            
            $components[$index]->update(['sort_order' => $nextOrder]);
            $nextFee->update(['sort_order' => $feeOrder]);
        }

        return redirect()->route('settings.index')->with('success', 'Urutan komponen biaya berhasil diperbarui.');
    }

    public function storeSppSetting(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        if (!$selectedYearId) {
            return redirect()->route('settings.index')->with('error', 'Silakan pilih Tahun Ajaran terlebih dahulu.');
        }

        $request->validate([
            'spp_amount' => 'required|numeric|min:0',
            'komite_amount' => 'required|numeric|min:0',
        ]);

        $totalAmount = $request->spp_amount + $request->komite_amount;

        GlobalSppSetting::updateOrCreate(
            ['academic_year_id' => $selectedYearId],
            [
                'spp_amount' => $request->spp_amount,
                'komite_amount' => $request->komite_amount,
                'amount' => $totalAmount,
            ]
        );

        return redirect()->route('settings.index')->with('success', 'Tarif SPP + Komite bulanan untuk Tahun Ajaran ini berhasil diperbarui.');
    }

    public function storeDiscountCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:discount_categories,name,' . $request->id,
            'percentage' => 'required|numeric|min:0|max:100',
        ]);

        DiscountCategory::updateOrCreate(
            ['id' => $request->id],
            ['name' => $request->name, 'percentage' => $request->percentage]
        );

        return redirect()->route('settings.index')->with('success', 'Kategori diskon berhasil disimpan.');
    }

    public function destroyDiscountCategory(DiscountCategory $discount)
    {
        if ($discount->enrollments()->exists()) {
            return redirect()->route('settings.index')->with('error', 'Kategori diskon tidak bisa dihapus karena sudah digunakan oleh siswa.');
        }

        $discount->delete();
        return redirect()->route('settings.index')->with('success', 'Kategori diskon berhasil dihapus.');
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $request->id,
            'role' => 'required|in:super_admin,admin,headmaster,finance_admin,savings_admin,teacher',
            'password' => $request->id ? 'nullable|min:6' : 'required|min:6',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ];

        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        User::updateOrCreate(
            ['id' => $request->id],
            $data
        );

        return redirect()->route('settings.index')->with('success', 'Pengguna sistem berhasil disimpan.');
    }

    public function destroyUser(User $user)
    {
        // Cannot self-delete
        if ($user->id === auth()->id()) {
            return redirect()->route('settings.index')->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        $user->delete();
        return redirect()->route('settings.index')->with('success', 'Pengguna sistem berhasil dihapus.');
    }

    public function storeExpenseCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:expense_categories,name',
        ]);

        ExpenseCategory::create(['name' => $request->name]);

        return redirect()->route('settings.index')->with('success', 'Kategori pengeluaran baru berhasil ditambahkan.');
    }

    public function destroyExpenseCategory(ExpenseCategory $category)
    {
        if ($category->expenses()->exists()) {
            return redirect()->route('settings.index')->with('error', 'Kategori tidak bisa dihapus karena sudah memiliki data pengeluaran terikat.');
        }

        $category->delete();
        return redirect()->route('settings.index')->with('success', 'Kategori pengeluaran berhasil dihapus.');
    }
}
