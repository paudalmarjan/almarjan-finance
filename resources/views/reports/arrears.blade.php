@extends('layouts.app')

@section('title', 'Data Tunggakan')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h5 class="mb-1 font-weight-600">Laporan Tunggakan & Tagihan Siswa</h5>
        <div class="ay-badge">
            <i class="bi bi-calendar3"></i> Tahun Ajaran: {{ $selectedYear ? $selectedYear->name : 'N/A' }}
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card-premium p-3 mb-4">
    <form method="GET" action="{{ route('reports.arrears') }}" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label for="filter_level_id" class="form-label small font-weight-500 text-muted">Jenjang</label>
            <select class="form-select form-select-sm" name="level_id" id="filter_level_id" onchange="updateGroupOptions()">
                <option value="">-- Semua Jenjang --</option>
                @foreach($levels as $lvl)
                    <option value="{{ $lvl->id }}" {{ request('level_id') == $lvl->id ? 'selected' : '' }}>{{ $lvl->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
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
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-primary-custom btn-sm">Filter</button>
        </div>
    </form>
</div>

<!-- Total Receivables Card -->
<div class="card-premium p-4 mb-4 border-start border-danger border-5">
    <div class="row align-items-center">
        <div class="col-md-8 mb-2 mb-md-0">
            <h6 class="text-muted font-weight-500 mb-1">Total Tagihan Belum Tertagih (Seluruh Siswa)</h6>
            <p class="helper-text mb-0">Tagihan dihitung berdasarkan kewajiban bayar Uang Tahunan serta tagihan SPP bulanan yang sudah jatuh tempo hingga bulan ini.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <h2 class="mb-0 font-weight-700 text-danger">Rp {{ number_format($totalReceivables, 0, ',', '.') }}</h2>
        </div>
    </div>
</div>

<!-- Arrears List Table Card -->
<div class="card-premium p-4">
    <h6 class="font-weight-600 mb-3 text-teal border-bottom pb-2"><i class="bi bi-person-x"></i> Daftar Siswa dengan Tunggakan Aktif</h6>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle small">
            <thead class="table-light">
                <tr>
                    <th>NIS</th>
                    <th>Nama Siswa</th>
                    <th>Kelompok</th>
                    <th>Tunggakan Uang Tahunan</th>
                    <th>Tunggakan SPP Bulanan</th>
                    <th class="text-end" style="width: 150px;">Total Tunggakan</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($arrearsList as $arr)
                <tr>
                    <td class="font-weight-600 text-muted">{{ $arr['nis'] ?? '-' }}</td>
                    <td class="font-weight-600">{{ $arr['name'] }}</td>
                    <td><span class="badge bg-secondary">{{ $arr['group_name'] }}</span></td>
                    <td>
                        @if(count($arr['annual_details']) > 0)
                            <ul class="mb-0 ps-3 small text-danger">
                                @foreach($arr['annual_details'] as $det)
                                    <li>{{ $det }}</li>
                                @endforeach
                            </ul>
                        @else
                            <span class="text-success small"><i class="bi bi-check-circle-fill"></i> Lunas</span>
                        @endif
                    </td>
                    <td>
                        @if(count($arr['spp_details']) > 0)
                            <div class="text-danger font-weight-500">
                                Unpaid: {{ implode(', ', $arr['spp_details']) }}
                            </div>
                            <small class="text-muted">(Sisa: Rp {{ number_format($arr['spp_arrears'], 0, ',', '.') }})</small>
                        @else
                            <span class="text-success small"><i class="bi bi-check-circle-fill"></i> Lunas</span>
                        @endif
                    </td>
                    <td class="text-end font-weight-600 text-danger">
                        Rp {{ number_format($arr['total_arrears'], 0, ',', '.') }}
                    </td>
                    <td class="text-end">
                        <a href="{{ route('payments.create', ['student_id' => $arr['student_id']]) }}" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-cash-coin me-1"></i> Catat Bayar
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="bi bi-emoji-smile fs-1 d-block mb-2 text-success"></i>
                        Luar biasa! Tidak ada siswa yang memiliki tunggakan di Tahun Ajaran ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
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
