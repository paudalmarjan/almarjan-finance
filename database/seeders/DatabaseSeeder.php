<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\StudentGroup;
use App\Models\DiscountCategory;
use App\Models\AnnualFeeComponent;
use App\Models\GlobalSppSetting;
use App\Models\ExpenseCategory;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Users
        User::create([
            'name' => 'Kepala Sekolah (Admin)',
            'email' => 'admin@almarjan.sch.id',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Guru Tata Usaha (Staf)',
            'email' => 'guru@almarjan.sch.id',
            'password' => Hash::make('guru123'),
            'role' => 'teacher',
        ]);

        // 2. Create Academic Years
        $ay25 = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $ay26 = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => false,
        ]);

        // 3. Create Levels
        $kb = Level::create(['name' => 'KB']);
        $tka = Level::create(['name' => 'TKA']);
        $tkb = Level::create(['name' => 'TKB']);

        // 4. Create Student Groups
        StudentGroup::create(['level_id' => $kb->id, 'name' => 'KB-A']);
        StudentGroup::create(['level_id' => $kb->id, 'name' => 'KB-B']);
        StudentGroup::create(['level_id' => $tka->id, 'name' => 'TKA-A']);
        StudentGroup::create(['level_id' => $tka->id, 'name' => 'TKA-B']);
        StudentGroup::create(['level_id' => $tkb->id, 'name' => 'TKB-A']);

        // 5. Create Discount Categories
        DiscountCategory::create(['name' => 'Yatim', 'percentage' => 100.00]);
        DiscountCategory::create(['name' => 'Dhuafa', 'percentage' => 50.00]);
        DiscountCategory::create(['name' => 'Anak Guru', 'percentage' => 50.00]);

        // 6. Create Annual Fee Components for 2025/2026
        // KB components
        AnnualFeeComponent::create(['academic_year_id' => $ay25->id, 'level_id' => $kb->id, 'name' => 'Uang Pendaftaran', 'amount' => 100000]);
        AnnualFeeComponent::create(['academic_year_id' => $ay25->id, 'level_id' => $kb->id, 'name' => 'Uang Gedung', 'amount' => 1500000]);
        AnnualFeeComponent::create(['academic_year_id' => $ay25->id, 'level_id' => $kb->id, 'name' => 'Kegiatan & Bahan Ajar', 'amount' => 400000]);
        AnnualFeeComponent::create(['academic_year_id' => $ay25->id, 'level_id' => $kb->id, 'name' => 'Seragam Sekolah', 'amount' => 300000]);

        // TKA components
        AnnualFeeComponent::create(['academic_year_id' => $ay25->id, 'level_id' => $tka->id, 'name' => 'Uang Pendaftaran', 'amount' => 100000]);
        AnnualFeeComponent::create(['academic_year_id' => $ay25->id, 'level_id' => $tka->id, 'name' => 'Uang Gedung', 'amount' => 1200000]);
        AnnualFeeComponent::create(['academic_year_id' => $ay25->id, 'level_id' => $tka->id, 'name' => 'Kegiatan & Bahan Ajar', 'amount' => 500000]);
        AnnualFeeComponent::create(['academic_year_id' => $ay25->id, 'level_id' => $tka->id, 'name' => 'Seragam Sekolah', 'amount' => 300000]);

        // TKB components
        AnnualFeeComponent::create(['academic_year_id' => $ay25->id, 'level_id' => $tkb->id, 'name' => 'Uang Pendaftaran', 'amount' => 100000]);
        AnnualFeeComponent::create(['academic_year_id' => $ay25->id, 'level_id' => $tkb->id, 'name' => 'Uang Gedung', 'amount' => 800000]);
        AnnualFeeComponent::create(['academic_year_id' => $ay25->id, 'level_id' => $tkb->id, 'name' => 'Kegiatan & Bahan Ajar', 'amount' => 500000]);
        AnnualFeeComponent::create(['academic_year_id' => $ay25->id, 'level_id' => $tkb->id, 'name' => 'Seragam Sekolah', 'amount' => 300000]);

        // 7. Global SPP Settings for 2025/2026
        GlobalSppSetting::create(['academic_year_id' => $ay25->id, 'amount' => 250000]);
        
        // Setup SPP Settings for 2026/2027 too (for future transition tests)
        GlobalSppSetting::create(['academic_year_id' => $ay26->id, 'amount' => 270000]);

        // 8. Expense Categories
        ExpenseCategory::create(['name' => 'Alat Tulis Kantor (ATK)']);
        ExpenseCategory::create(['name' => 'Kegiatan Sekolah']);
        ExpenseCategory::create(['name' => 'Gaji Guru & Staf']);
        ExpenseCategory::create(['name' => 'Perbaikan & Pemeliharaan Gedung']);
        ExpenseCategory::create(['name' => 'Lain-lain']);
    }
}
