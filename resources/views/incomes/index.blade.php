@extends('layouts.app')

@section('title', 'Riwayat Pemasukan Lain-lain')

@section('content')
<div class="page-header">
    <div>
        <h5 class="page-header-title">Pemasukan Kas (Non-Siswa)</h5>
        <p class="helper-text mb-0">Riwayat penerimaan kas dari Dana BOP PAUD, Donasi/Infaq, Penjualan Formulir, dll.</p>
    </div>
    
    <div class="page-header-actions">
        <a href="{{ route('incomes.create') }}" class="btn btn-success btn-sm px-3">
            <i class="bi bi-journal-plus me-1"></i> Catat Pemasukan Baru
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="filter-card">
    <form method="GET" action="{{ route('incomes.index') }}" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label for="filter_category" class="form-label small font-weight-500 text-muted">Kategori Pemasukan</label>
            <select class="form-select form-select-sm" name="income_category_id" id="filter_category">
                <option value="">-- Semua Kategori --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('income_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
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
            <label for="filter_search" class="form-label small font-weight-500 text-muted">Cari Sumber / Catatan</label>
            <input type="text" class="form-control form-control-sm" name="search" id="filter_search" value="{{ request('search') }}" placeholder="Cari sumber / catatan...">
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary-custom btn-sm">Saring Data</button>
        </div>
    </form>
</div>

<!-- Incomes List Table -->
<div class="card-premium p-4">
    <div class="table-responsive">
        <table class="table table-clean">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Sumber / Dari</th>
                    <th>Catatan / Keterangan</th>
                    <th>Pencatat</th>
                    <th class="text-center">Lampiran</th>
                    <th class="text-end">Nominal Pemasukan</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($incomes as $inc)
                <tr>
                    <td>{{ $inc->date->format('d M Y') }}</td>
                    <td><span class="badge badge-soft-success font-weight-500">{{ $inc->incomeCategory->name }}</span></td>
                    <td><strong class="text-dark">{{ $inc->source }}</strong></td>
                    <td>{{ $inc->notes ?? '-' }}</td>
                    <td>{{ $inc->user ? $inc->user->name : '-' }}</td>
                    <td class="text-center">
                        @if($inc->attachment_path)
                            <a href="{{ $inc->attachment_url }}" target="_blank" class="btn btn-sm btn-outline-info p-1 px-2">
                                <i class="bi bi-file-earmark-image"></i> Lihat Bukti
                            </a>
                        @else
                            <span class="text-muted small">Tidak ada</span>
                        @endif
                    </td>
                    <td class="text-end font-weight-600 text-success col-amount">
                        + Rp {{ number_format($inc->amount, 0, ',', '.') }}
                    </td>
                    <td class="text-end">
                        <form action="{{ route('incomes.destroy', $inc->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan pemasukan ini?')" class="d-inline">
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
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="bi bi-journal-x empty-state-icon"></i>
                            <p class="empty-state-title">Tidak Ada Data</p>
                            <p class="empty-state-text mb-0">Belum ada catatan transaksi pemasukan lain-lain untuk pencarian ini.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-3">
        {{ $incomes->withQueryString()->links() }}
    </div>
</div>
@endsection
