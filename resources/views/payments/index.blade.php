@extends('layouts.app')

@section('title', 'Riwayat Transaksi')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h5 class="mb-1 font-weight-600">Penerimaan Kas (Masuk)</h5>
        <div class="ay-badge">
            <i class="bi bi-calendar3"></i> Tahun Ajaran: {{ $selectedYear ? $selectedYear->name : 'N/A' }}
        </div>
    </div>
    
    <div>
        <a href="{{ route('payments.create') }}" class="btn btn-primary-custom btn-sm">
            <i class="bi bi-cash-coin me-1"></i> Catat Pembayaran Baru
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="card-premium p-3 mb-4">
    <form method="GET" action="{{ route('payments.index') }}" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label for="start_date" class="form-label small font-weight-500 text-muted">Dari Tanggal</label>
            <input type="date" class="form-control form-control-sm" name="start_date" id="start_date" value="{{ request('start_date') }}">
        </div>
        <div class="col-md-3">
            <label for="end_date" class="form-label small font-weight-500 text-muted">Sampai Tanggal</label>
            <input type="date" class="form-control form-control-sm" name="end_date" id="end_date" value="{{ request('end_date') }}">
        </div>
        <div class="col-md-6">
            <label for="filter_search" class="form-label small font-weight-500 text-muted">Cari Nama Siswa / No. Kuitansi</label>
            <input type="text" class="form-control form-control-sm" name="search" id="filter_search" value="{{ request('search') }}" placeholder="Ketik nama anak atau nomor kuitansi...">
        </div>
        
        <div class="col-md-4">
            <label for="filter_level_id" class="form-label small font-weight-500 text-muted">Jenjang</label>
            <select class="form-select form-select-sm" name="level_id" id="filter_level_id" onchange="updateGroupOptions()">
                <option value="">-- Semua Jenjang --</option>
                @foreach($levels as $lvl)
                    <option value="{{ $lvl->id }}" {{ request('level_id') == $lvl->id ? 'selected' : '' }}>{{ $lvl->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label for="filter_group_id" class="form-label small font-weight-500 text-muted">Kelompok Kelas</label>
            <select class="form-select form-select-sm" name="student_group_id" id="filter_group_id">
                <option value="">-- Semua Kelompok --</option>
                @foreach($levels as $lvl)
                    @foreach($lvl->groups as $grp)
                        <option value="{{ $grp->id }}" data-level-id="{{ $lvl->id }}" {{ request('student_group_id') == $grp->id ? 'selected' : '' }} style="display:none;">
                            {{ $grp->name }}
                        </option>
                    @endforeach
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="filter_enrollment_type" class="form-label small font-weight-500 text-muted">Tipe Pendaftaran</label>
            <select class="form-select form-select-sm" name="enrollment_type" id="filter_enrollment_type">
                <option value="">-- Semua Tipe --</option>
                <option value="New" {{ request('enrollment_type') === 'New' ? 'selected' : '' }}>Siswa Baru</option>
                <option value="Returning" {{ request('enrollment_type') === 'Returning' ? 'selected' : '' }}>Siswa Lama</option>
            </select>
        </div>
        <div class="col-md-1 d-grid">
            <button type="submit" class="btn btn-primary-custom btn-sm">Filter</button>
        </div>
    </form>
</div>

<!-- Transaction History List -->
<div class="card-premium p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>No. Kuitansi</th>
                    <th>Tanggal</th>
                    <th>Nama Siswa</th>
                    <th>Pencatat (Guru/Staf)</th>
                    <th class="text-end">Total Pembayaran</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                <tr>
                    <td class="font-weight-600 text-teal">{{ $tx->receipt_number }}</td>
                    <td>{{ $tx->date->format('d M Y') }}</td>
                    <td class="font-weight-600">{{ $tx->student->name }}</td>
                    <td>{{ $tx->user ? $tx->user->name : '-' }}</td>
                    <td class="text-end font-weight-600 text-success">
                        Rp {{ number_format($tx->total_amount, 0, ',', '.') }}
                    </td>
                    <td class="text-end">
                        <a href="{{ route('payments.show', $tx->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i> Detail Kuitansi
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-receipt fs-1 d-block mb-2 text-light"></i>
                        Belum ada riwayat transaksi pembayaran tercatat untuk kriteria pencarian ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-3">
        {{ $transactions->withQueryString()->links() }}
    </div>
</div>

<script>
    function updateGroupOptions() {
        var levelId = document.getElementById('filter_level_id').value;
        var groupSelect = document.getElementById('filter_group_id');
        var options = groupSelect.options;
        
        // Reset selected group
        groupSelect.value = "";
        
        for (var i = 0; i < options.length; i++) {
            var opt = options[i];
            if (opt.value === "") {
                opt.style.display = "block";
                continue;
            }
            
            var optLevelId = opt.getAttribute('data-level-id');
            if (levelId === "" || optLevelId === levelId) {
                opt.style.display = "block";
            } else {
                opt.style.display = "none";
            }
        }
    }
    
    // Run once on load to filter groups based on request
    document.addEventListener("DOMContentLoaded", function() {
        updateGroupOptions();
        // Restore selected group if present in request
        var reqGroup = "{{ request('student_group_id') }}";
        if (reqGroup) {
            document.getElementById('filter_group_id').value = reqGroup;
        }
    });
</script>
@endsection
