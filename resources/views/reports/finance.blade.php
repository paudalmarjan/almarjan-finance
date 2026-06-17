@extends('layouts.app')

@section('title', 'Laporan Keuangan')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h5 class="mb-1 font-weight-600">Laporan Arus Kas</h5>
        <div class="ay-badge">
            <i class="bi bi-calendar3"></i> Tahun Ajaran: {{ $selectedYear ? $selectedYear->name : 'N/A' }}
        </div>
    </div>
</div>

<!-- Date Range Filters Card -->
<div class="card-premium p-3 mb-4">
    <form method="GET" action="{{ route('reports.finance') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label for="start_date" class="form-label small font-weight-500 text-muted">Dari Tanggal</label>
            <input type="date" class="form-control" name="start_date" id="start_date" value="{{ $startDate }}">
        </div>
        <div class="col-md-4">
            <label for="end_date" class="form-label small font-weight-500 text-muted">Sampai Tanggal</label>
            <input type="date" class="form-control" name="end_date" id="end_date" value="{{ $endDate }}">
        </div>
        <div class="col-md-4 d-grid">
            <button type="submit" class="btn btn-primary-custom">Saring Rentang Laporan</button>
        </div>
    </form>
</div>

<!-- Ledger Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3 mb-md-0">
        <div class="card-premium p-3 border-start border-secondary border-4 h-100">
            <small class="text-muted font-weight-500">Saldo Awal Kas</small>
            <h4 class="mb-0 font-weight-700 text-secondary mt-1">Rp {{ number_format($selectedYear->initial_cash_balance, 0, ',', '.') }}</h4>
        </div>
    </div>
    
    <div class="col-md-3 mb-3 mb-md-0">
        <div class="card-premium p-3 border-start border-success border-4 h-100">
            <small class="text-muted font-weight-500">Total Kas Masuk (Pemasukan)</small>
            <h4 class="mb-0 font-weight-700 text-success mt-1">Rp {{ number_format($totalIncome, 0, ',', '.') }}</h4>
        </div>
    </div>
    
    <div class="col-md-3 mb-3 mb-md-0">
        <div class="card-premium p-3 border-start border-danger border-4 h-100">
            <small class="text-muted font-weight-500">Total Kas Keluar (Pengeluaran)</small>
            <h4 class="mb-0 font-weight-700 text-danger mt-1">Rp {{ number_format($totalOutcome, 0, ',', '.') }}</h4>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card-premium p-3 border-start border-primary border-4 h-100">
            <small class="text-muted font-weight-500">Saldo Kas Akhir</small>
            <h4 class="mb-0 font-weight-700 text-primary mt-1">Rp {{ number_format($endingBalance, 0, ',', '.') }}</h4>
        </div>
    </div>
</div>

<!-- Ledger Details Card -->
<div class="card-premium p-4">
    <h6 class="font-weight-600 mb-3 text-teal border-bottom pb-2"><i class="bi bi-list-columns-reverse"></i> Jurnal Arus Kas Kronologis</h6>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle small">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>No. Referensi</th>
                    <th>Tipe Kas</th>
                    <th>Keterangan Transaksi</th>
                    <th class="text-end" style="width: 200px;">Jumlah Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ledger as $item)
                <tr>
                    <td>{{ $item['date']->format('d M Y') }}</td>
                    <td class="font-weight-600 text-muted">{{ $item['reference'] }}</td>
                    <td>
                        @if($item['type'] === 'Pemasukan')
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2">Masuk</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2">Keluar</span>
                        @endif
                    </td>
                    <td>{{ $item['description'] }}</td>
                    <td class="text-end font-weight-600 {{ $item['type'] === 'Pemasukan' ? 'text-success' : 'text-danger' }}">
                        {{ $item['type'] === 'Pemasukan' ? '+' : '-' }} Rp {{ number_format($item['amount'], 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">Tidak ada data transaksi keuangan dalam rentang tanggal tersebut.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
