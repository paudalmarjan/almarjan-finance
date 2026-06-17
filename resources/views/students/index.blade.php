@extends('layouts.app')

@section('title', 'Daftar Siswa')

@section('content')
<!-- Actions Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h5 class="mb-1 font-weight-600">Manajemen Siswa</h5>
        <div class="ay-badge">
            <i class="bi bi-calendar3"></i> Tahun Ajaran: {{ $selectedYear ? $selectedYear->name : 'N/A' }}
        </div>
    </div>
    
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-success btn-sm px-3" data-bs-toggle="modal" data-bs-target="#importExcelModal">
            <i class="bi bi-file-earmark-excel me-1"></i> Impor Excel
        </button>
        <a href="{{ route('students.create') }}" class="btn btn-primary-custom btn-sm px-3">
            <i class="bi bi-plus-lg me-1"></i> Tambah Siswa Baru
        </a>
    </div>
</div>

<!-- Display Validation Errors from Import Excel -->
@if ($errors->any())
    <div class="alert alert-danger card-premium p-3 border-start border-danger border-4 mb-4" role="alert">
        <div class="d-flex mb-2">
            <i class="bi bi-exclamation-octagon-fill text-danger fs-4 me-2"></i>
            <div><strong>Terdapat Kesalahan Impor Data:</strong></div>
        </div>
        <ul class="mb-0 text-danger small ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Filters Card -->
<div class="card-premium p-3 mb-4">
    <form method="GET" action="{{ route('students.index') }}" class="row g-3 align-items-end">
        <div class="col-md-2">
            <label for="filter_level_id" class="form-label small font-weight-500 text-muted">Jenjang</label>
            <select class="form-select form-select-sm" name="level_id" id="filter_level_id" onchange="updateGroupOptions()">
                <option value="">-- Semua Jenjang --</option>
                @foreach($levels as $lvl)
                    <option value="{{ $lvl->id }}" {{ request('level_id') == $lvl->id ? 'selected' : '' }}>{{ $lvl->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
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
        <div class="col-md-2">
            <label for="filter_enrollment_type" class="form-label small font-weight-500 text-muted">Tipe Pendaftaran</label>
            <select class="form-select form-select-sm" name="enrollment_type" id="filter_enrollment_type">
                <option value="">-- Semua Tipe --</option>
                <option value="New" {{ request('enrollment_type') === 'New' ? 'selected' : '' }}>Siswa Baru</option>
                <option value="Returning" {{ request('enrollment_type') === 'Returning' ? 'selected' : '' }}>Siswa Lama</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="filter_status" class="form-label small font-weight-500 text-muted">Status</label>
            <select class="form-select form-select-sm" name="status" id="filter_status">
                <option value="">-- Semua Status --</option>
                <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Aktif</option>
                <option value="Graduated" {{ request('status') === 'Graduated' ? 'selected' : '' }}>Lulus</option>
                <option value="Not Continuing" {{ request('status') === 'Not Continuing' ? 'selected' : '' }}>Tidak Melanjutkan</option>
                <option value="Transferred" {{ request('status') === 'Transferred' ? 'selected' : '' }}>Mutasi Keluar</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="filter_search" class="form-label small font-weight-500 text-muted">Cari Nama / NIS</label>
            <input type="text" class="form-control form-control-sm" name="search" id="filter_search" value="{{ request('search') }}" placeholder="Ketik nama atau nomor induk...">
        </div>
        <div class="col-md-1 d-grid">
            <button type="submit" class="btn btn-primary-custom btn-sm">Filter</button>
        </div>
    </form>
</div>

<!-- Student List Card -->
<div class="card-premium p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>NIS</th>
                    <th>Nama Lengkap</th>
                    <th>Orang Tua</th>
                    <th>No. Telepon</th>
                    <th>Kelompok</th>
                    <th>Diskon</th>
                    <th>Tunggakan</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($enrollments as $enr)
                <tr>
                    <td class="font-weight-600 text-teal">{{ $enr->student->nis ?? '-' }}</td>
                    <td class="font-weight-600">
                        {{ $enr->student->name }}
                        @if($enr->student->nickname)
                            <div class="small text-muted font-weight-500" style="font-size: 0.75rem;">({{ $enr->student->nickname }})</div>
                        @endif
                    </td>
                    <td>{{ $enr->student->parent_name ?? '-' }}</td>
                    <td>{{ $enr->student->phone_number ?? '-' }}</td>
                    <td>
                        <span class="badge bg-secondary">{{ $enr->studentGroup->name }}</span>
                        <div class="mt-1">
                            @if($enr->enrollment_type === 'New')
                                <span class="badge bg-primary" style="font-size: 0.7rem;">Baru</span>
                            @else
                                <span class="badge bg-dark" style="font-size: 0.7rem;">Lama</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if($enr->discountCategory)
                            <span class="badge bg-info text-dark">{{ $enr->discountCategory->name }} ({{ (int)$enr->discount_percentage }}%)</span>
                        @else
                            <span class="text-muted small">-</span>
                        @endif
                    </td>
                    <td>
                        @if($enr->arrears_amount > 0)
                            <span class="badge bg-danger">Rp {{ number_format($enr->arrears_amount, 0, ',', '.') }}</span>
                        @else
                            <span class="badge bg-success">Lunas</span>
                        @endif
                    </td>
                    <td>
                        @if($enr->student->status === 'Active')
                            <span class="badge bg-success">Aktif</span>
                        @elseif($enr->student->status === 'Graduated')
                            <span class="badge bg-dark">Lulus</span>
                        @elseif($enr->student->status === 'Not Continuing')
                            <span class="badge bg-danger">Tidak Lanjut</span>
                        @else
                            <span class="badge bg-warning text-dark">Mutasi</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('students.edit', $enr->student->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil-square me-1"></i> Edit / Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">
                        <i class="bi bi-people fs-1 d-block mb-2 text-light"></i>
                        Belum ada data siswa terdaftar untuk Tahun Ajaran ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Import Excel -->
<div class="modal fade" id="importExcelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-premium">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-600">Impor Siswa massal via Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body border-0">
                    <p class="helper-text mb-3">Gunakan template resmi kami untuk mengimpor data agar struktur kolom tidak mengalami kesalahan sistem.</p>
                    
                    <div class="mb-4 text-center border rounded p-3 bg-light">
                        <h6 class="font-weight-600 mb-2">Belum punya template Excel?</h6>
                        <a href="{{ route('students.import-template') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download me-1"></i> Unduh Template Excel (.xlsx)
                        </a>
                    </div>
                    
                    <div class="mb-3">
                        <label for="excel_file" class="form-label small font-weight-500">Pilih Berkas Excel</label>
                        <input type="file" class="form-control" name="file" id="excel_file" accept=".xlsx, .xls" required>
                        <span class="helper-text">Format didukung: .xlsx, .xls (Maks. 5MB)</span>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Mulai Impor Data</button>
                </div>
            </form>
        </div>
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
