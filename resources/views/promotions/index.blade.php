@extends('layouts.master_data')

@section('title', 'Kenaikan Kelas & Kelulusan')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h5 class="mb-1 font-weight-600">Transisi Tahun Ajaran Baru</h5>
        <p class="helper-text mb-0">Kelola kenaikan kelas dan kelulusan siswa secara massal di akhir tahun ajaran.</p>
    </div>
</div>

<!-- Selection Filter Card -->
<div class="card-premium p-4 mb-4">
    <h6 class="font-weight-600 mb-3 text-teal"><i class="bi bi-funnel"></i> Langkah 1: Pilih Kelas Asal & Tahun Ajaran Tujuan</h6>
    <form method="GET" action="{{ route('promotions.index') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label small font-weight-500 text-muted">Tahun Ajaran Asal (Context)</label>
            <input type="text" class="form-control bg-light" value="{{ $selectedYear ? $selectedYear->name : 'N/A' }}" readonly>
        </div>
        <div class="col-md-4">
            <label for="source_group_id" class="form-label small font-weight-500 text-muted">Kelompok Kelas Asal</label>
            <select class="form-select" name="source_group_id" id="source_group_id" required>
                <option value="">-- Pilih Kelas Asal --</option>
                @foreach($levels as $lvl)
                    <optgroup label="Jenjang {{ $lvl->name }}">
                        @foreach($lvl->groups as $grp)
                            <option value="{{ $grp->id }}" {{ request('source_group_id') == $grp->id ? 'selected' : '' }}>
                                {{ $grp->name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="target_academic_year_id" class="form-label small font-weight-500 text-muted">Tahun Ajaran Baru (Tujuan)</label>
            <select class="form-select" name="target_academic_year_id" id="target_academic_year_id" required>
                <option value="">-- Pilih Tahun Baru --</option>
                @foreach($targetYears as $ty)
                    <option value="{{ $ty->id }}" {{ request('target_academic_year_id') == $ty->id ? 'selected' : '' }}>
                        {{ $ty->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1 d-grid">
            <button type="submit" class="btn btn-primary-custom">Muat</button>
        </div>
    </form>
</div>

@if($sourceGroup && $targetYear)
<!-- Validation Warnings -->
@if ($errors->any())
    <div class="alert alert-danger card-premium p-3 border-start border-danger border-4 mb-4" role="alert">
        <div class="d-flex mb-2">
            <i class="bi bi-exclamation-octagon-fill text-danger fs-4 me-2"></i>
            <div><strong>Gagal Memproses Transisi:</strong></div>
        </div>
        <ul class="mb-0 text-danger small ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <!-- Option A: Web UI -->
    <div class="col-lg-8 mb-4">
        <div class="card-premium p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="font-weight-600 mb-0 text-teal"><i class="bi bi-laptop"></i> Opsi A: Proses Langsung di Halaman Web</h6>
            </div>
            
            @if(count($students) > 0)
                <!-- Bulk setting bar -->
                <div class="bg-light p-3 rounded mb-3 d-flex flex-wrap align-items-center gap-2">
                    <span class="small font-weight-600 text-muted">Setel massal kelas tujuan untuk semua siswa di bawah:</span>
                    <select class="form-select form-select-sm d-inline-block w-auto" id="bulk_target_select" onchange="applyBulkTarget()">
                        <option value="">-- Pilih Tujuan Massal --</option>
                        @foreach($targetGroups as $tg)
                            <option value="{{ $tg->id }}">{{ $tg->name }}</option>
                        @endforeach
                        <option value="LULUS">LULUS (Keluar Sekolah / Graduated)</option>
                        <option value="TIDAK_LANJUT">TIDAK LANJUT (Putus Sekolah)</option>
                    </select>
                </div>

                <form action="{{ route('promotions.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="source_group_id" value="{{ $sourceGroup->id }}">
                    <input type="hidden" name="target_academic_year_id" value="{{ $targetYear->id }}">

                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover align-middle">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th style="width: 250px;">Pilih Kelas / Status Baru</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $index => $enr)
                                <tr>
                                    <td class="font-weight-600 text-teal">{{ $enr->student->nis ?? '-' }}</td>
                                    <td class="font-weight-600">{{ $enr->student->name }}</td>
                                    <td>
                                        <select class="form-select form-select-sm target-group-dropdown" name="promotions[{{ $enr->id }}]" required>
                                            <option value="">-- Pilih Tujuan --</option>
                                            @foreach($targetGroups as $tg)
                                                <option value="{{ $tg->id }}" {{ $sourceGroup->level->name === 'KB' && str_ends_with($tg->name, substr($sourceGroup->name, -1)) ? 'selected' : '' }}
                                                                             {{ $sourceGroup->level->name === 'TKA' && str_ends_with($tg->name, substr($sourceGroup->name, -1)) ? 'selected' : '' }}>
                                                    {{ $tg->name }}
                                                </option>
                                            @endforeach
                                            <option value="LULUS" {{ $sourceGroup->level->name === 'TKB' ? 'selected' : '' }}>LULUS</option>
                                            <option value="TIDAK_LANJUT">TIDAK LANJUT</option>
                                        </select>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-grid mt-3 border-top pt-3">
                        <button type="submit" class="btn btn-primary-custom" onsubmit="return confirm('Jalankan kenaikan kelas untuk siswa yang dipilih?')">
                            <i class="bi bi-save me-1"></i> Jalankan Transisi Kelas Baru
                        </button>
                    </div>
                </form>
            @else
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-check-circle fs-1 d-block mb-2 text-success"></i>
                    Semua siswa aktif di kelas asal ini sudah diproses transisinya atau kelas kosong.
                </div>
            @endif
        </div>
    </div>

    <!-- Option B: Excel Upload -->
    <div class="col-lg-4 mb-4">
        <div class="card-premium p-4 h-100 bg-light-green border border-success-subtle">
            <h6 class="font-weight-600 mb-3 text-success"><i class="bi bi-file-earmark-excel"></i> Opsi B: Menggunakan File Excel</h6>
            <p class="helper-text mb-4">Metode ini cocok untuk pengacakan siswa (rolling kelas) massal. Unduh daftar nama siswa, atur secara offline di Excel, lalu unggah kembali.</p>

            <!-- 1. Download template populated with students -->
            <div class="border rounded p-3 mb-4 bg-white shadow-sm text-center">
                <h6 class="font-weight-600 mb-2 small">Unduh File Transisi Siswa</h6>
                <form action="{{ route('promotions.export-template') }}" method="GET">
                    <input type="hidden" name="source_group_id" value="{{ $sourceGroup->id }}">
                    <input type="hidden" name="target_academic_year_id" value="{{ $targetYear->id }}">
                    <button type="submit" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-download me-1"></i> Unduh File Pemetaan (.xlsx)
                    </button>
                </form>
            </div>

            <!-- 2. Import Excel -->
            <div class="border rounded p-3 bg-white shadow-sm">
                <h6 class="font-weight-600 mb-3 small">Unggah Hasil Pemetaan Excel</h6>
                <form action="{{ route('promotions.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="target_academic_year_id" value="{{ $targetYear->id }}">
                    
                    <div class="mb-3">
                        <input type="file" class="form-control form-control-sm" name="file" accept=".xlsx, .xls" required>
                    </div>
                    
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-upload me-1"></i> Proses Unggahan Excel
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

<script>
    function applyBulkTarget() {
        var val = document.getElementById('bulk_target_select').value;
        if (val === "") return;
        
        var dropdowns = document.getElementsByClassName('target-group-dropdown');
        for (var i = 0; i < dropdowns.length; i++) {
            dropdowns[i].value = val;
        }
    }
</script>
@endsection
