@extends('layouts.savings')

@section('title', 'Tabungan Siswa')

@section('content')
<div class="filter-card">
    <form method="GET" action="{{ route('savings.students') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label for="filter_level_id" class="form-label small font-weight-600 text-muted mb-1">Filter Jenjang</label>
            <select class="form-select form-select-sm" name="level_id" id="filter_level_id" onchange="updateGroupOptions()">
                <option value="">-- Semua Jenjang --</option>
                @foreach($levels as $lvl)
                    <option value="{{ $lvl->id }}" {{ request('level_id') == $lvl->id ? 'selected' : '' }}>{{ $lvl->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="filter_group_id" class="form-label small font-weight-600 text-muted mb-1">Filter Kelompok Kelas</label>
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
        <div class="col-md-4">
            <label for="search" class="form-label small font-weight-600 text-muted mb-1">Cari Siswa</label>
            <input type="text" class="form-control form-control-sm" name="search" id="search" placeholder="Nama, Panggilan, atau NIS" value="{{ request('search') }}">
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary-custom btn-sm">Cari</button>
        </div>
    </form>
</div>

<div class="card-premium p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0 font-weight-600"><i class="bi bi-wallet2 text-primary me-2"></i> Daftar Tabungan Siswa</h5>
        <div class="btn-group">
            <a href="{{ route('savings.deposit') }}" class="btn btn-success btn-sm">
                <i class="bi bi-box-arrow-in-down"></i> Setor
            </a>
            <a href="{{ route('savings.withdraw') }}" class="btn btn-danger btn-sm">
                <i class="bi bi-box-arrow-up"></i> Tarik
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-clean">
            <thead>
                <tr>
                    <th>NIS</th>
                    <th>Nama Lengkap</th>
                    <th>Kelompok</th>
                    <th class="text-end">Saldo Tabungan</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                @php
                    $enrollment = $student->enrollments->first();
                    $groupName = $enrollment && $enrollment->studentGroup ? $enrollment->studentGroup->name : '-';
                    $balance = $student->savings ? $student->savings->balance : 0;
                @endphp
                <tr>
                    <td><span class="badge badge-soft-neutral">{{ $student->nis }}</span></td>
                    <td class="font-weight-600">
                        {{ $student->name }}
                        @if($student->nickname)
                            <small class="text-muted d-block">{{ $student->nickname }}</small>
                        @endif
                    </td>
                    <td>{{ $groupName }}</td>
                    <td class="text-end font-weight-600 col-amount {{ $balance > 0 ? 'text-success' : '' }}">
                        Rp {{ number_format($balance, 0, ',', '.') }}
                    </td>
                    <td class="text-center">
                        <a href="{{ route('savings.history', ['student_id' => $student->id]) }}" class="btn btn-sm btn-outline-primary" title="Riwayat Tabungan">
                            <i class="bi bi-clock-history"></i> Riwayat
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="bi bi-inbox empty-state-icon"></i>
                            <p class="empty-state-title">Tidak Ada Data</p>
                            <p class="empty-state-text mb-0">Belum ada data siswa aktif yang ditemukan.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-3">
        {{ $students->links('pagination::bootstrap-5') }}
    </div>
</div>

<script>
    function updateGroupOptions() {
        const levelId = document.getElementById('filter_level_id').value;
        const groupSelect = document.getElementById('filter_group_id');
        const options = groupSelect.querySelectorAll('option[data-level-id]');
        
        groupSelect.value = "";
        
        options.forEach(opt => {
            if (!levelId || opt.getAttribute('data-level-id') === levelId) {
                opt.style.display = 'block';
            } else {
                opt.style.display = 'none';
            }
        });
    }

    // Initialize group options on load
    document.addEventListener("DOMContentLoaded", function() {
        updateGroupOptions();
    });
</script>
@endsection
