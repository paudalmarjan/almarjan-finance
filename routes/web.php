<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

use App\Http\Controllers\PortalController;

// Redirect / to portal
Route::redirect('/', '/portal');

// Public ping route for uptime monitoring
Route::get('/ping', function () {
    return 'pong';
});

// Auth protected routes
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Portal Hub
    Route::get('/portal', [PortalController::class, 'index'])->name('portal');

    // Dashboard (App Keuangan)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Payments (Catat & Riwayat)
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{transaction}', [PaymentController::class, 'show'])->name('payments.show');
    Route::get('/payments/{transaction}/print', [PaymentController::class, 'print'])->name('payments.print');

    // Expenses (Catat & Riwayat)
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    // Students (Daftar Siswa)
    Route::get('/students/search-ajax', [StudentController::class, 'searchAjax'])->name('students.search-ajax');
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
    Route::get('/students/import-template', [StudentController::class, 'downloadTemplate'])->name('students.import-template');

    // Reports (Laporan Keuangan, Tunggakan, LPJ)
    Route::get('/reports/finance', [ReportController::class, 'finance'])->name('reports.finance');
    Route::get('/reports/arrears', [ReportController::class, 'arrears'])->name('reports.arrears');
    Route::get('/reports/lpj', [ReportController::class, 'lpj'])->name('reports.lpj');
    Route::post('/reports/lpj/export', [ReportController::class, 'exportLpj'])->name('reports.export-lpj');

    // Savings (Tabungan Siswa)
    Route::get('/savings', [App\Http\Controllers\StudentSavingsController::class, 'index'])->name('savings.index');
    Route::get('/savings/students', [App\Http\Controllers\StudentSavingsController::class, 'students'])->name('savings.students');
    Route::get('/savings/deposit', [App\Http\Controllers\StudentSavingsController::class, 'deposit'])->name('savings.deposit');
    Route::get('/savings/withdraw', [App\Http\Controllers\StudentSavingsController::class, 'withdraw'])->name('savings.withdraw');
    Route::post('/savings/bulk', [App\Http\Controllers\StudentSavingsController::class, 'storeBulk'])->name('savings.store-bulk');
    Route::get('/savings/history', [App\Http\Controllers\StudentSavingsController::class, 'history'])->name('savings.history');
    Route::get('/savings/transaction/{transaction}/print', [App\Http\Controllers\StudentSavingsController::class, 'print'])->name('savings.print');

    // Admin Only Routes
    Route::middleware('admin')->group(function () {
        // Cancel / Delete Payment Transaction (Hanya Admin Utama)
        Route::delete('/payments/{transaction}', [PaymentController::class, 'destroy'])->name('payments.destroy');

        // Cancel / Delete Savings Transaction (Hanya Admin Utama)
        Route::delete('/savings/transaction/{transaction}', [App\Http\Controllers\StudentSavingsController::class, 'destroy'])->name('savings.destroy');

        // Cancel Student Enrollment (Tahun Ajaran Inaktif)
        Route::delete('/students/{student}/cancel-enrollment', [StudentController::class, 'cancelEnrollment'])->name('students.cancel-enrollment');

        // Promotions (Kenaikan Kelas)
        Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');
        Route::post('/promotions', [PromotionController::class, 'store'])->name('promotions.store');
        Route::get('/promotions/export-template', [PromotionController::class, 'exportTemplate'])->name('promotions.export-template');
        Route::post('/promotions/import', [PromotionController::class, 'import'])->name('promotions.import');

        // Settings (Pengaturan Sistem)
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/academic-years', [SettingController::class, 'storeAcademicYear'])->name('settings.academic-years.store');
        Route::put('/settings/academic-years/{year}', [SettingController::class, 'updateAcademicYear'])->name('settings.academic-years.update');
        Route::put('/settings/academic-years/{year}/toggle-active', [SettingController::class, 'toggleActiveAcademicYear'])->name('settings.academic-years.toggle-active');
        
        Route::post('/settings/groups', [SettingController::class, 'storeGroup'])->name('settings.groups.store');
        Route::delete('/settings/groups/{group}', [SettingController::class, 'destroyGroup'])->name('settings.groups.destroy');
        
        Route::post('/settings/fees', [SettingController::class, 'storeFeeComponent'])->name('settings.fees.store');
        Route::delete('/settings/fees/{fee}', [SettingController::class, 'destroyFeeComponent'])->name('settings.fees.destroy');
        Route::post('/settings/fees/{fee}/move-up', [SettingController::class, 'moveFeeComponentUp'])->name('settings.fees.move-up');
        Route::post('/settings/fees/{fee}/move-down', [SettingController::class, 'moveFeeComponentDown'])->name('settings.fees.move-down');
        
        Route::post('/settings/spp', [SettingController::class, 'storeSppSetting'])->name('settings.spp.store');
        
        Route::post('/settings/discounts', [SettingController::class, 'storeDiscountCategory'])->name('settings.discounts.store');
        Route::delete('/settings/discounts/{discount}', [SettingController::class, 'destroyDiscountCategory'])->name('settings.discounts.destroy');
        
        Route::post('/settings/expense-categories', [SettingController::class, 'storeExpenseCategory'])->name('settings.expense-categories.store');
        Route::delete('/settings/expense-categories/{category}', [SettingController::class, 'destroyExpenseCategory'])->name('settings.expense-categories.destroy');
        
        Route::post('/settings/users', [SettingController::class, 'storeUser'])->name('settings.users.store');
        Route::delete('/settings/users/{user}', [SettingController::class, 'destroyUser'])->name('settings.users.destroy');
    });
});

Route::get('/run-migrations', function () {
    Artisan::call('migrate', ['--force' => true]);
    return 'Database berhasil dimigrasi: <pre>' . Artisan::output() . '</pre>';
});

require __DIR__.'/auth.php';
