<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Portal PAUD Hub | {{ config('app.name', 'PAUD Al Marjan') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background-color: #f1f5f9;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .portal-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 15px 30px;
        }
        .app-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            text-decoration: none;
            color: #334155;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        .app-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 5px;
            background: var(--card-color, #cbd5e1);
            transition: height 0.3s;
        }
        .app-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            color: #0f172a;
        }
        .app-card:hover::before {
            height: 100%;
            opacity: 0.05;
        }
        .app-icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: var(--card-color, #cbd5e1);
            background-color: var(--card-bg, #f8fafc);
        }
        .app-card h4 {
            font-weight: 700;
            margin-bottom: 10px;
        }
        .app-card p {
            font-size: 0.85rem;
            color: #64748b;
            margin: 0;
        }
    </style>
</head>
<body>
    <header class="portal-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height: 36px;">
            <div class="lh-sm d-none d-sm-block">
                <span class="d-block fw-bold fs-6 text-dark">PAUD HUB</span>
                <span class="d-block text-muted" style="font-size: 0.65rem;">Central Application Portal</span>
            </div>
        </div>
        
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-weight: 600;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <span class="fw-semibold d-none d-sm-inline">{{ auth()->user()->name }}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser">
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i> Profil Anda</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i> Keluar</button>
                    </form>
                </li>
            </ul>
        </div>
    </header>

    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark">Selamat Datang di PAUD Hub</h2>
            <p class="text-muted">Pilih aplikasi atau modul yang ingin Anda gunakan di bawah ini.</p>
        </div>

        <div class="row g-4 justify-content-center" style="max-width: 900px; margin: 0 auto;">
            
            <!-- Keuangan App -->
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isHeadmaster() || auth()->user()->isFinanceAdmin() || auth()->user()->isTeacher())
            <div class="col-md-4 col-sm-6">
                <a href="{{ route('dashboard') }}" class="app-card" style="--card-color: #059669; --card-bg: #ecfdf5;">
                    <div class="app-icon-wrapper">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <h4>App Keuangan</h4>
                    <p>Pencatatan SPP, tagihan tahunan, kas masuk, dan pengeluaran.</p>
                </a>
            </div>
            @endif

            <!-- Tabungan App -->
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isHeadmaster() || auth()->user()->isSavingsAdmin() || auth()->user()->isTeacher())
            <div class="col-md-4 col-sm-6">
                <a href="{{ route('savings.index') }}" class="app-card" style="--card-color: #0284c7; --card-bg: #e0f2fe;">
                    <div class="app-icon-wrapper">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <h4>App Tabungan</h4>
                    <p>Sistem pencatatan mutasi setor dan tarik tabungan siswa.</p>
                </a>
            </div>
            @endif

            <!-- Master Data App -->
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isHeadmaster())
            <div class="col-md-4 col-sm-6">
                <a href="{{ route('settings.index') }}" class="app-card" style="--card-color: #7c3aed; --card-bg: #f5f3ff;">
                    <div class="app-icon-wrapper">
                        <i class="bi bi-database-gear"></i>
                    </div>
                    <h4>Master Data</h4>
                    <p>Pengaturan tahun ajaran, komponen biaya, dan manajemen user.</p>
                </a>
            </div>
            @endif

        </div>
    </div>
</body>
</html>
