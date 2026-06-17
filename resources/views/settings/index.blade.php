@extends('layouts.app')

@section('title', 'Pengaturan Sistem')

@section('content')
<div class="row">
    <!-- Settings Navigation Tabs -->
    <div class="col-md-3 mb-4">
        <div class="card-premium p-3">
            <div class="nav flex-column nav-pills" id="settingsTabs" role="tablist" aria-orientation="vertical">
                <button class="nav-link text-start active" id="tab-years-nav" data-bs-toggle="pill" data-bs-target="#tab-years" type="button" role="tab">
                    <i class="bi bi-calendar3 me-2"></i> Tahun Ajaran
                </button>
                <button class="nav-link text-start" id="tab-groups-nav" data-bs-toggle="pill" data-bs-target="#tab-groups" type="button" role="tab">
                    <i class="bi bi-building me-2"></i> Kelas & Kelompok
                </button>
                <button class="nav-link text-start" id="tab-fees-nav" data-bs-toggle="pill" data-bs-target="#tab-fees" type="button" role="tab">
                    <i class="bi bi-cash-stack me-2"></i> Komponen Biaya & SPP
                </button>
                <button class="nav-link text-start" id="tab-discounts-nav" data-bs-toggle="pill" data-bs-target="#tab-discounts" type="button" role="tab">
                    <i class="bi bi-percent me-2"></i> Kategori Diskon
                </button>
                <button class="nav-link text-start" id="tab-expense-categories-nav" data-bs-toggle="pill" data-bs-target="#tab-expense-categories" type="button" role="tab">
                    <i class="bi bi-tags me-2"></i> Kategori Pengeluaran
                </button>
                <button class="nav-link text-start" id="tab-users-nav" data-bs-toggle="pill" data-bs-target="#tab-users" type="button" role="tab">
                    <i class="bi bi-people me-2"></i> Pengguna Sistem
                </button>
            </div>
        </div>
    </div>

    <!-- Settings Content Panes -->
    <div class="col-md-9">
        <div class="tab-content" id="settingsTabsContent">
            
            <!-- Tab 1: Tahun Ajaran -->
            <div class="tab-pane fade show active" id="tab-years" role="tabpanel">
                <div class="card-premium p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 font-weight-600"><i class="bi bi-calendar3 text-primary me-2"></i> Daftar Tahun Ajaran</h5>
                        <button type="button" class="btn btn-primary-custom btn-sm" onclick="showAddYearModal()">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Tahun Ajaran
                        </button>
                    </div>
                    <p class="helper-text mb-4">Tahun Ajaran berjalan July - June. Sistem mendukung penyimpanan data multi-tahun tanpa reset.</p>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tahun Ajaran</th>
                                    <th>Mulai</th>
                                    <th>Selesai</th>
                                    <th class="text-end">Saldo Awal Kas</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($academicYears as $ay)
                                <tr>
                                    <td class="font-weight-600">{{ $ay->name }}</td>
                                    <td>{{ $ay->start_date->format('d M Y') }}</td>
                                    <td>{{ $ay->end_date->format('d M Y') }}</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($ay->initial_cash_balance, 0, ',', '.') }}</td>
                                    <td>
                                        @if($ay->is_active)
                                            <span class="badge bg-success">Berjalan (Aktif)</span>
                                        @else
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="showEditYearModal({{ json_encode($ay) }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        @if(!$ay->is_active)
                                        <form action="{{ route('settings.academic-years.toggle-active', $ay->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                Aktifkan
                                            </button>
                                        </form>
                                        @else
                                            <button class="btn btn-sm btn-light text-muted" disabled>Aktif</button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Jenjang & Kelompok -->
            <div class="tab-pane fade" id="tab-groups" role="tabpanel">
                <div class="card-premium p-4 mb-4">
                    <h5 class="mb-3 font-weight-600"><i class="bi bi-building text-primary me-2"></i> Jenjang & Kelompok Kelas</h5>
                    <p class="helper-text mb-4">Sistem mendukung pembagian jenjang PAUD standar (KB, TKA, TKB). Anda dapat menambahkan kelompok kelas secara dinamis.</p>

                    <div class="row">
                        <!-- Left Panel: Existing Levels & Groups -->
                        <div class="col-lg-7 mb-4">
                            @foreach($levels as $lvl)
                            <div class="border rounded p-3 mb-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary fs-6">Jenjang {{ $lvl->name }}</span>
                                    <span class="text-muted small">{{ $lvl->groups->count() }} Kelompok</span>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    @forelse($lvl->groups as $grp)
                                        <div class="bg-white border rounded px-3 py-2 d-flex align-items-center">
                                            <strong class="me-3">{{ $grp->name }}</strong>
                                            <form action="{{ route('settings.groups.destroy', $grp->id) }}" method="POST" onsubmit="return confirm('Hapus kelompok kelas ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-close" style="font-size: 0.7rem;" aria-label="Close"></button>
                                            </form>
                                        </div>
                                    @empty
                                        <span class="text-muted small py-2">Belum ada kelompok kelas di jenjang ini.</span>
                                    @endforelse
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- Right Panel: Add Group Form -->
                        <div class="col-lg-5">
                            <div class="border rounded p-3 bg-white">
                                <h6 class="font-weight-600 mb-3">Tambah Kelompok Baru</h6>
                                <form action="{{ route('settings.groups.store') }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="level_id" class="form-label small font-weight-500">Pilih Jenjang</label>
                                        <select class="form-select" name="level_id" id="level_id" required>
                                            @foreach($levels as $lvl)
                                                <option value="{{ $lvl->id }}">{{ $lvl->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="group_name" class="form-label small font-weight-500">Nama Kelompok</label>
                                        <input type="text" class="form-control" name="name" id="group_name" placeholder="Misal: KB-A, TKA-B" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary-custom btn-sm w-100">Simpan Kelompok</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Komponen Biaya & SPP -->
            <div class="tab-pane fade" id="tab-fees" role="tabpanel">
                <div class="card-premium p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 font-weight-600">
                            <i class="bi bi-cash-stack text-primary me-2"></i> Pengaturan Biaya
                        </h5>
                        <div class="ay-badge">
                            <i class="bi bi-calendar3"></i> TA terpilih: {{ $selectedYear ? $selectedYear->name : 'N/A' }}
                        </div>
                    </div>
                    <p class="helper-text mb-4">Pengaturan tarif di bawah ini berlaku khusus untuk Tahun Ajaran yang sedang dipilih di header atas.</p>

                    <!-- Section A: SPP Bulanan -->
                    <div class="border rounded p-3 mb-4 bg-light">
                        <h6 class="font-weight-600 mb-2">1. Tarif Dasar SPP + Komite Bulanan</h6>
                        <p class="helper-text mb-3">Tarif bulanan standar (gabungan SPP + Komite) berlaku sama untuk seluruh jenjang pendidikan di tahun ajaran ini.</p>
                        <form action="{{ route('settings.spp.store') }}" method="POST" class="row g-3 align-items-end">
                            @csrf
                            <div class="col-auto">
                                <label for="spp_amount_input" class="form-label small font-weight-500 mb-1 text-muted">Nominal SPP</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="spp_amount" id="spp_amount_input" value="{{ $sppSetting ? (int)$sppSetting->spp_amount : '' }}" placeholder="150000" required oninput="calculateSettingsSppTotal()">
                                </div>
                            </div>
                            <div class="col-auto">
                                <label for="komite_amount_input" class="form-label small font-weight-500 mb-1 text-muted">Nominal Komite</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="komite_amount" id="komite_amount_input" value="{{ $sppSetting ? (int)$sppSetting->komite_amount : '' }}" placeholder="30000" required oninput="calculateSettingsSppTotal()">
                                </div>
                            </div>
                            <div class="col-auto">
                                <span class="badge bg-success py-2 px-3 me-2" id="settings_spp_total_badge" style="font-size: 0.85rem;">
                                    Total: Rp {{ $sppSetting ? number_format($sppSetting->amount, 0, ',', '.') : '0' }}
                                </span>
                                <button type="submit" class="btn btn-primary-custom btn-sm">Perbarui Tarif</button>
                            </div>
                        </form>
                    </div>

                    <!-- Section B: Biaya Pendidikan Tahunan -->
                    <div class="border rounded p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="font-weight-600 mb-0">2. Komponen Biaya Pendidikan Tahunan</h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="openAddFeeModalGeneral()">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Komponen
                            </button>
                        </div>
                        <p class="helper-text mb-4">Biaya Tahunan dibebankan sekali di awal masuk kelas (registrasi, gedung, seragam, dll).</p>

                        <div class="row">
                            @foreach($levels as $lvl)
                                @php
                                    $lvlComponents = collect($feeComponents)->where('level_id', $lvl->id);
                                    $subtotal = $lvlComponents->sum('amount');
                                @endphp
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100 border p-3 bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="badge bg-primary fs-6 font-weight-600">Jenjang {{ $lvl->name }}</span>
                                            <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2 small" onclick="openAddFeeModalForLevel({{ $lvl->id }}, '{{ $lvl->name }}')" style="font-size: 0.75rem;">
                                                <i class="bi bi-plus-lg"></i> Tambah
                                            </button>
                                        </div>
                                        
                                        <div class="d-flex flex-column justify-content-between h-100">
                                            <div>
                                                <ul class="list-group list-group-flush mb-3">
                                                    @forelse($lvlComponents as $fc)
                                                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2">
                                                            <div style="flex: 1; min-width: 0; padding-right: 10px;">
                                                                <span class="font-weight-500 text-dark text-truncate d-block" title="{{ $fc->name }}">
                                                                    {{ $fc->name }}
                                                                    @if($fc->target_type === 'New')
                                                                        <span class="badge bg-info text-dark ms-1" style="font-size: 0.65rem; padding: 0.15em 0.35em;">Baru</span>
                                                                    @elseif($fc->target_type === 'Returning')
                                                                        <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65rem; padding: 0.15em 0.35em;">Lama</span>
                                                                    @endif
                                                                </span>
                                                                <small class="text-muted d-block">Rp {{ number_format($fc->amount, 0, ',', '.') }}</small>
                                                            </div>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <!-- Move Up -->
                                                                <form action="{{ route('settings.fees.move-up', $fc->id) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-link text-secondary p-0 border-0" style="font-size: 0.85rem;" title="Pindahkan ke atas">
                                                                        <i class="bi bi-chevron-up"></i>
                                                                    </button>
                                                                </form>
                                                                <!-- Move Down -->
                                                                <form action="{{ route('settings.fees.move-down', $fc->id) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-link text-secondary p-0 border-0" style="font-size: 0.85rem;" title="Pindahkan ke bawah">
                                                                        <i class="bi bi-chevron-down"></i>
                                                                    </button>
                                                                </form>
                                                                <!-- Delete -->
                                                                <form action="{{ route('settings.fees.destroy', $fc->id) }}" method="POST" onsubmit="return confirm('Hapus komponen biaya {{ $fc->name }} ini?')" class="d-inline ms-1">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-link text-danger p-0 border-0" style="font-size: 0.95rem;">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </li>
                                                    @empty
                                                        <li class="list-group-item text-center bg-transparent py-4 text-muted small">
                                                            Belum ada komponen biaya.
                                                        </li>
                                                    @endforelse
                                                </ul>
                                            </div>
                                            
                                            <div class="border-top pt-2 mt-auto d-flex justify-content-between align-items-center">
                                                <strong class="text-muted small">Total Biaya:</strong>
                                                <span class="fs-6 font-weight-700 text-dark">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Kategori Diskon -->
            <div class="tab-pane fade" id="tab-discounts" role="tabpanel">
                <div class="card-premium p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 font-weight-600"><i class="bi bi-percent text-primary me-2"></i> Kategori Diskon Pemasukan</h5>
                        <button type="button" class="btn btn-primary-custom btn-sm" onclick="showAddDiscountModal()">
                            <i class="bi bi-plus-lg"></i> Tambah Kategori
                        </button>
                    </div>
                    <p class="helper-text mb-4">Diskon dipasang pada pendaftaran siswa (misal: Yatim potong 100%, Anak Guru potong 50%). Perubahan nilai diskon tidak akan merusak riwayat transaksi lampau.</p>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kategori Diskon</th>
                                    <th>Persentase Potongan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($discountCategories as $dc)
                                <tr>
                                    <td class="font-weight-600">{{ $dc->name }}</td>
                                    <td><span class="badge bg-info text-dark fs-6">{{ (int)$dc->percentage }}%</span></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="showEditDiscountModal({{ json_encode($dc) }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('settings.discounts.destroy', $dc->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kategori diskon ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab: Kategori Pengeluaran -->
            <div class="tab-pane fade" id="tab-expense-categories" role="tabpanel">
                <div class="card-premium p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 font-weight-600"><i class="bi bi-tags text-primary me-2"></i> Kategori Pengeluaran</h5>
                        <button type="button" class="btn btn-primary-custom btn-sm" data-bs-toggle="modal" data-bs-target="#addExpenseCatModal">
                            <i class="bi bi-plus-lg"></i> Tambah Kategori
                        </button>
                    </div>
                    <p class="helper-text mb-4">Mendukung kategorisasi pengeluaran kas sekolah (ATK, kegiatan, operasional, dll) untuk mempermudah laporan pertanggungjawaban (LPJ).</p>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kategori Pengeluaran</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenseCategories as $ec)
                                <tr>
                                    <td class="font-weight-600">{{ $ec->name }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('settings.expense-categories.destroy', $ec->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kategori pengeluaran ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center py-3 text-muted">Belum ada kategori pengeluaran terdaftar.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 5: Pengguna Sistem -->
            <div class="tab-pane fade" id="tab-users" role="tabpanel">
                <div class="card-premium p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 font-weight-600"><i class="bi bi-people text-primary me-2"></i> Pengguna Sistem</h5>
                        <button type="button" class="btn btn-primary-custom btn-sm" onclick="showAddUserModal()">
                            <i class="bi bi-plus-lg"></i> Tambah Pengguna
                        </button>
                    </div>
                    <p class="helper-text mb-4">Admin (Kepala Sekolah/Yayasan) memiliki akses penuh. Guru (Tata Usaha) hanya dapat menginput data keuangan/siswa di tahun berjalan.</p>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Lengkap</th>
                                    <th>Email</th>
                                    <th>Hak Akses (Role)</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $usr)
                                <tr>
                                    <td class="font-weight-600">{{ $usr->name }}</td>
                                    <td>{{ $usr->email }}</td>
                                    <td>
                                        @if($usr->isAdmin())
                                            <span class="badge bg-dark">Administrator</span>
                                        @else
                                            <span class="badge bg-secondary">Guru / Staf</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="showEditUserModal({{ json_encode($usr) }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        @if($usr->id !== auth()->id())
                                        <form action="{{ route('settings.users.destroy', $usr->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus pengguna ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Add/Edit Academic Year -->
<div class="modal fade" id="academicYearModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-premium">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-600" id="ayModalTitle">Tambah Tahun Ajaran Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="ayForm" action="{{ route('settings.academic-years.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="ay_method" value="POST">
                <div class="modal-body border-0">
                    <div class="mb-3">
                        <label for="ay_name" class="form-label small font-weight-500">Nama Tahun Ajaran</label>
                        <input type="text" class="form-control" name="name" id="ay_name" placeholder="Contoh: 2026/2027" required>
                        <span class="helper-text">Format harus YYYY/YYYY.</span>
                    </div>
                    <div class="mb-3">
                        <label for="ay_start" class="form-label small font-weight-500">Tanggal Mulai</label>
                        <input type="date" class="form-control" name="start_date" id="ay_start" required>
                    </div>
                    <div class="mb-3">
                        <label for="ay_end" class="form-label small font-weight-500">Tanggal Selesai</label>
                        <input type="date" class="form-control" name="end_date" id="ay_end" required>
                    </div>
                    <div class="mb-3">
                        <label for="ay_initial_cash" class="form-label small font-weight-500">Saldo Awal Kas (Rupiah)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" name="initial_cash_balance" id="ay_initial_cash" placeholder="0" min="0" value="0" required>
                        </div>
                        <span class="helper-text d-block mb-1">Saldo kas/bank bawaan dari pembukuan lama.</span>
                        <span class="small font-weight-600 text-info" id="ay_initial_cash_recommendation"></span>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom" id="aySubmitBtn">Simpan Tahun Ajaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Fee Component -->
<div class="modal fade" id="addFeeComponentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-premium">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-600">Tambah Komponen Biaya Pendidikan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('settings.fees.store') }}" method="POST" onsubmit="return validateFeeForm()">
                @csrf
                <div class="modal-body border-0">
                    <div class="mb-3">
                        <label class="form-label small font-weight-500 d-block">Pilih Jenjang (Bisa pilih lebih dari satu)</label>
                        <div class="d-flex flex-wrap gap-3 p-3 border rounded bg-light">
                            @foreach($levels as $lvl)
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input fee-level-checkbox" type="checkbox" name="level_ids[]" value="{{ $lvl->id }}" id="fee_level_{{ $lvl->id }}">
                                    <label class="form-check-label small font-weight-500" for="fee_level_{{ $lvl->id }}">
                                        Jenjang {{ $lvl->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="fee_name" class="form-label small font-weight-500">Nama Komponen Biaya</label>
                        <input type="text" class="form-control" name="name" id="fee_name" placeholder="Misal: Uang Seragam, Bahan Pembelajaran" required>
                    </div>
                    <div class="mb-3">
                        <label for="fee_target_type" class="form-label small font-weight-500">Tipe Sasaran Siswa</label>
                        <select class="form-select" name="target_type" id="fee_target_type" required>
                            <option value="All">Semua Siswa (Baru & Lama)</option>
                            <option value="New">Siswa Baru Saja</option>
                            <option value="Returning">Siswa Lama Saja (Daftar Ulang)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="fee_amount" class="form-label small font-weight-500">Nominal Biaya (Rupiah)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" name="amount" id="fee_amount" placeholder="500000" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan Komponen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add/Edit Discount -->
<div class="modal fade" id="discountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-premium">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-600" id="discountModalTitle">Tambah Kategori Diskon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('settings.discounts.store') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="discount_id">
                <div class="modal-body border-0">
                    <div class="mb-3">
                        <label for="discount_name" class="form-label small font-weight-500">Nama Kategori</label>
                        <input type="text" class="form-control" name="name" id="discount_name" placeholder="Misal: Yatim, Anak Guru" required>
                    </div>
                    <div class="mb-3">
                        <label for="discount_percentage" class="form-label small font-weight-500">Persentase Potongan (%)</label>
                        <input type="number" class="form-control" name="percentage" id="discount_percentage" min="0" max="100" placeholder="50" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan Diskon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add/Edit User -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-premium">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-600" id="userModalTitle">Tambah Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('settings.users.store') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="user_id">
                <div class="modal-body border-0">
                    <div class="mb-3">
                        <label for="user_name" class="form-label small font-weight-500">Nama Lengkap</label>
                        <input type="text" class="form-control" name="name" id="user_name" placeholder="Masukkan nama" required>
                    </div>
                    <div class="mb-3">
                        <label for="user_email" class="form-label small font-weight-500">Alamat Email</label>
                        <input type="email" class="form-control" name="email" id="user_email" placeholder="contoh@almarjan.sch.id" required>
                    </div>
                    <div class="mb-3">
                        <label for="user_role" class="form-label small font-weight-500">Hak Akses (Role)</label>
                        <select class="form-select" name="role" id="user_role" required>
                            <option value="teacher">Guru / Tata Usaha</option>
                            <option value="admin">Admin (Kepala Sekolah / Yayasan)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="user_password" class="form-label small font-weight-500" id="userPasswordLabel">Kata Sandi</label>
                        <input type="password" class="form-control" name="password" id="user_password" placeholder="Masukkan kata sandi baru">
                        <span class="helper-text" id="userPasswordHelper">Minimal 6 karakter.</span>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Expense Category -->
<div class="modal fade" id="addExpenseCatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-premium">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-600">Tambah Kategori Pengeluaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('settings.expense-categories.store') }}" method="POST">
                @csrf
                <div class="modal-body border-0">
                    <div class="mb-3">
                        <label for="exp_cat_name" class="form-label small font-weight-500">Nama Kategori Pengeluaran</label>
                        <input type="text" class="form-control" name="name" id="exp_cat_name" placeholder="Misal: Operasional Listrik, Konsumsi Rapat" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Academic Year Modals handlers
    function showAddYearModal() {
        document.getElementById('ayModalTitle').innerText = 'Tambah Tahun Ajaran Baru';
        document.getElementById('ayForm').action = "{{ route('settings.academic-years.store') }}";
        document.getElementById('ay_method').value = 'POST';
        document.getElementById('ay_name').value = '';
        document.getElementById('ay_start').value = '';
        document.getElementById('ay_end').value = '';
        
        const recommendedNewRaw = "{{ $recommendedNewBalance }}";
        if (recommendedNewRaw !== "") {
            const recommendedNew = parseFloat(recommendedNewRaw);
            // Default pre-fill to max(0, recommendedNew) since cash input cannot be negative
            document.getElementById('ay_initial_cash').value = Math.max(0, Math.round(recommendedNew));
            document.getElementById('ay_initial_cash_recommendation').innerHTML = '<i class="bi bi-info-circle-fill"></i> Rekomendasi (Saldo Akhir TA sebelumnya): Rp ' + Math.round(recommendedNew).toLocaleString('id-ID');
        } else {
            document.getElementById('ay_initial_cash').value = 0;
            document.getElementById('ay_initial_cash_recommendation').innerText = '';
        }
        
        var modal = new bootstrap.Modal(document.getElementById('academicYearModal'));
        modal.show();
    }

    function showEditYearModal(ay) {
        document.getElementById('ayModalTitle').innerText = 'Edit Tahun Ajaran';
        let url = "{{ route('settings.academic-years.update', ':id') }}";
        url = url.replace(':id', ay.id);
        
        document.getElementById('ayForm').action = url;
        document.getElementById('ay_method').value = 'PUT';
        document.getElementById('ay_name').value = ay.name;
        document.getElementById('ay_start').value = ay.start_date.split('T')[0];
        document.getElementById('ay_end').value = ay.end_date.split('T')[0];
        document.getElementById('ay_initial_cash').value = Math.round(ay.initial_cash_balance);
        
        if (ay.recommended_initial_balance !== null && ay.recommended_initial_balance !== undefined) {
            document.getElementById('ay_initial_cash_recommendation').innerHTML = '<i class="bi bi-info-circle-fill"></i> Rekomendasi (Saldo Akhir TA sebelumnya): Rp ' + Math.round(ay.recommended_initial_balance).toLocaleString('id-ID');
        } else {
            document.getElementById('ay_initial_cash_recommendation').innerText = '';
        }
        
        var modal = new bootstrap.Modal(document.getElementById('academicYearModal'));
        modal.show();
    }

    // Discount Modals handlers
    function showAddDiscountModal() {
        document.getElementById('discountModalTitle').innerText = 'Tambah Kategori Diskon';
        document.getElementById('discount_id').value = '';
        document.getElementById('discount_name').value = '';
        document.getElementById('discount_percentage').value = '';
        
        var modal = new bootstrap.Modal(document.getElementById('discountModal'));
        modal.show();
    }

    function showEditDiscountModal(discount) {
        document.getElementById('discountModalTitle').innerText = 'Edit Kategori Diskon';
        document.getElementById('discount_id').value = discount.id;
        document.getElementById('discount_name').value = discount.name;
        document.getElementById('discount_percentage').value = Math.round(discount.percentage);
        
        var modal = new bootstrap.Modal(document.getElementById('discountModal'));
        modal.show();
    }

    // User Modals handlers
    function showAddUserModal() {
        document.getElementById('userModalTitle').innerText = 'Tambah Pengguna';
        document.getElementById('user_id').value = '';
        document.getElementById('user_name').value = '';
        document.getElementById('user_email').value = '';
        document.getElementById('user_role').value = 'teacher';
        document.getElementById('user_password').required = true;
        document.getElementById('user_password').placeholder = 'Masukkan kata sandi';
        document.getElementById('userPasswordLabel').innerText = 'Kata Sandi';
        document.getElementById('userPasswordHelper').style.display = 'block';
        
        var modal = new bootstrap.Modal(document.getElementById('userModal'));
        modal.show();
    }

    function showEditUserModal(user) {
        document.getElementById('userModalTitle').innerText = 'Edit Pengguna';
        document.getElementById('user_id').value = user.id;
        document.getElementById('user_name').value = user.name;
        document.getElementById('user_email').value = user.email;
        document.getElementById('user_role').value = user.role;
        document.getElementById('user_password').required = false;
        document.getElementById('user_password').placeholder = 'Kosongkan jika tidak ingin mengubah';
        document.getElementById('userPasswordLabel').innerText = 'Ubah Kata Sandi (Opsional)';
        document.getElementById('userPasswordHelper').style.display = 'none';
        
        var modal = new bootstrap.Modal(document.getElementById('userModal'));
        modal.show();
    }

    // Helper to open Add Fee Component Modal with specific Level pre-selected
    function openAddFeeModalForLevel(levelId, levelName) {
        const checkboxes = document.querySelectorAll('.fee-level-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = (parseInt(cb.value) === parseInt(levelId));
        });
        var modal = new bootstrap.Modal(document.getElementById('addFeeComponentModal'));
        modal.show();
    }

    // Helper to open Add Fee Component Modal with all Levels pre-selected
    function openAddFeeModalGeneral() {
        const checkboxes = document.querySelectorAll('.fee-level-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = true;
        });
        var modal = new bootstrap.Modal(document.getElementById('addFeeComponentModal'));
        modal.show();
    }

    // Validation before submitting fee component form
    function validateFeeForm() {
        const checkboxes = document.querySelectorAll('.fee-level-checkbox');
        let checked = false;
        checkboxes.forEach(cb => {
            if (cb.checked) {
                checked = true;
            }
        });
        if (!checked) {
            alert('Silakan pilih minimal satu Jenjang.');
            return false;
        }
        return true;
    }

    function calculateSettingsSppTotal() {
        var sppInput = document.getElementById('spp_amount_input');
        var komiteInput = document.getElementById('komite_amount_input');
        var sppVal = parseFloat(sppInput.value) || 0;
        var komiteVal = parseFloat(komiteInput.value) || 0;
        var total = sppVal + komiteVal;
        document.getElementById('settings_spp_total_badge').innerText = 'Total: Rp ' + total.toLocaleString('id-ID');
    }
</script>
@endsection
