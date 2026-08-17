@extends('layouts.app')

@section('title', 'Riwayat Pengeluaran')

@section('content')
<div class="page-header">
    <div>
        <h5 class="page-header-title">Pengeluaran Kas (Keluar)</h5>
        <p class="helper-text mb-0">Riwayat pengeluaran operasional dan belanja keperluan sekolah.</p>
    </div>
    
    <div class="page-header-actions">
        <a href="{{ route('expenses.create') }}" class="btn btn-danger-custom btn-sm px-3">
            <i class="bi bi-journal-arrow-down me-1"></i> Catat Pengeluaran Baru
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="filter-card">
    <form method="GET" action="{{ route('expenses.index') }}" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label for="filter_category" class="form-label small font-weight-500 text-muted">Kategori</label>
            <select class="form-select form-select-sm" name="expense_category_id" id="filter_category">
                <option value="">-- Semua Kategori --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('expense_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="start_date" class="form-label small font-weight-500 text-muted">Dari Tanggal</label>
            <input type="date" class="form-control form-control-sm" name="start_date" id="start_date" value="{{ request('start_date') }}">
        </div>
        <div class="col-md-2">
            <label for="end_date" class="form-label small font-weight-500 text-muted">Sampai Tanggal</label>
            <input type="date" class="form-control form-control-sm" name="end_date" id="end_date" value="{{ request('end_date') }}">
        </div>
        <div class="col-md-3">
            <label for="filter_search" class="form-label small font-weight-500 text-muted">Cari Keterangan Catatan</label>
            <input type="text" class="form-control form-control-sm" name="search" id="filter_search" value="{{ request('search') }}" placeholder="Cari isi catatan...">
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary-custom btn-sm">Saring Data</button>
        </div>
    </form>
</div>

<!-- Expenses List Table -->
<div class="card-premium p-4">
    <div class="table-responsive">
        <table class="table table-clean">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori Pengeluaran</th>
                    <th>Catatan/Keterangan</th>
                    <th>Pencatat</th>
                    <th class="text-center">Lampiran</th>
                    <th class="text-end">Nominal Pengeluaran</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $exp)
                <tr>
                    <td>{{ $exp->date->format('d M Y') }}</td>
                    <td><span class="badge badge-soft-warning font-weight-500">{{ $exp->expenseCategory->name }}</span></td>
                    <td>{{ $exp->notes ?? '-' }}</td>
                    <td>{{ $exp->user ? $exp->user->name : '-' }}</td>
                    <td class="text-center">
                        @if($exp->attachment_path)
                            <a href="{{ $exp->attachment_url }}" target="_blank" class="btn btn-sm btn-outline-info p-1 px-2">
                                <i class="bi bi-file-earmark-image"></i> Lihat Bukti
                            </a>
                        @else
                            <span class="text-muted small">Tidak ada</span>
                        @endif
                    </td>
                    <td class="text-end font-weight-600 text-danger col-amount">
                        Rp {{ number_format($exp->amount, 0, ',', '.') }}
                    </td>
                    <td class="text-end">
                        <form action="{{ route('expenses.destroy', $exp->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan pengeluaran kas ini?')" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="bi bi-journal-x empty-state-icon"></i>
                            <p class="empty-state-title">Tidak Ada Data</p>
                            <p class="empty-state-text mb-0">Belum ada catatan transaksi pengeluaran untuk pencarian ini.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-3">
        {{ $expenses->withQueryString()->links() }}
    </div>
</div>
@endsection
