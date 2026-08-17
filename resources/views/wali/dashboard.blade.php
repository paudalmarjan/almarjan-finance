@extends('layouts.parent')

@section('title', 'Dashboard')

@section('content')
<!-- Profil Singkat Anak -->
<div class="card-premium p-3 mb-4 d-flex flex-row align-items-center">
    <div class="me-3">
        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.5rem; font-weight: 700;">
            {{ substr($student->name, 0, 1) }}
        </div>
    </div>
    <div>
        <h6 class="mb-0 font-weight-700 text-dark">{{ $student->nickname ?? $student->name }}</h6>
        <p class="mb-0 small text-muted">NIS: {{ $student->nis }} &bull; {{ $enrollment ? $enrollment->studentGroup->name : 'Belum Ada Kelas' }}</p>
    </div>
</div>

<!-- Info Tabungan -->
<div class="card-premium p-4 mb-4 border-start border-success border-4" style="background: linear-gradient(to right, #f0fdf4, #ffffff);">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <small class="text-muted font-weight-600"><i class="bi bi-piggy-bank text-success me-1"></i> Saldo Tabungan Anak</small>
    </div>
    <h3 class="mb-0 font-weight-700 text-success">
        Rp {{ number_format($student->savings ? $student->savings->balance : 0, 0, ',', '.') }}
    </h3>
    <p class="small text-muted mt-2 mb-0">Tabungan dapat ditarik pada akhir tahun ajaran.</p>
</div>

<!-- Info Tunggakan -->
<div class="card-premium p-4 mb-4 {{ $totalArrears > 0 ? 'border-start border-danger border-4' : 'border-start border-info border-4' }}">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <small class="text-muted font-weight-600"><i class="bi bi-receipt text-danger me-1"></i> Total Tagihan / Tunggakan</small>
    </div>
    <h3 class="mb-0 font-weight-700 {{ $totalArrears > 0 ? 'text-danger' : 'text-info' }}">
        Rp {{ number_format($totalArrears, 0, ',', '.') }}
    </h3>
    @if($totalArrears > 0)
        <p class="small text-danger mt-2 mb-0">Terdapat tagihan yang belum dilunasi. Silakan selesaikan pembayaran di tata usaha sekolah.</p>
    @else
        <p class="small text-info mt-2 mb-0"><i class="bi bi-check-circle-fill"></i> Alhamdulillah, seluruh tagihan lunas.</p>
    @endif
</div>

<!-- Rincian Biaya Tahunan (Uang Pangkal / Daftar Ulang) -->
@if(count($annualFees) > 0)
<div class="card-premium p-3 mb-4">
    <h6 class="font-weight-600 mb-3 border-bottom pb-2 text-teal"><i class="bi bi-journal-bookmark-fill me-1"></i> Rincian Biaya Tahunan (TA {{ $activeYear->name ?? '' }})</h6>
    <div class="table-responsive">
        <table class="table table-sm table-borderless align-middle small mb-0">
            <tbody>
                @foreach($annualFees as $fee)
                    <tr class="border-bottom">
                        <td class="ps-0 py-2">
                            <i class="bi bi-record-circle text-muted me-1" style="font-size: 0.6rem;"></i> 
                            {{ $fee->annualFeeComponent->name }}
                        </td>
                        <td class="text-end pe-0 py-2">
                            @if($fee->is_excluded)
                                <span class="badge badge-soft-neutral rounded-pill px-2">Dikecualikan</span>
                            @elseif($fee->balance == 0)
                                <span class="badge badge-soft-success rounded-pill px-2"><i class="bi bi-check2"></i> Lunas</span>
                            @else
                                <span class="text-danger font-weight-600">Sisa Rp {{ number_format($fee->balance, 0, ',', '.') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Rincian SPP -->
@if(count($sppMonths) > 0)
<div class="card-premium p-3 mb-4">
    <h6 class="font-weight-600 mb-3 border-bottom pb-2 text-teal"><i class="bi bi-calendar-check me-1"></i> Status Pembayaran SPP (TA {{ $activeYear->name ?? '' }})</h6>
    <div class="table-responsive">
        <table class="table table-sm table-borderless align-middle small">
            <tbody>
                @foreach($sppMonths as $month)
                    <tr>
                        <td class="ps-0"><i class="bi bi-calendar-month text-muted me-2"></i>{{ $month['name'] }}</td>
                        <td class="text-end pe-0">
                            @if($month['status'] == 'Daftar Ulang')
                                <span class="badge badge-soft-neutral rounded-pill px-3">Masuk Daftar Ulang</span>
                            @elseif($month['status'] == 'Paid')
                                <span class="badge badge-soft-success rounded-pill px-3"><i class="bi bi-check2"></i> Lunas</span>
                            @elseif($month['status'] == 'Unpaid')
                                <span class="badge badge-soft-danger rounded-pill px-3">Belum Bayar</span>
                            @else
                                <span class="badge badge-soft-neutral rounded-pill px-3">Belum Waktunya</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Riwayat Transaksi Terakhir -->
<div class="card-premium p-3 mb-4">
    <h6 class="font-weight-600 mb-3 border-bottom pb-2 text-teal"><i class="bi bi-clock-history me-1"></i> 5 Transaksi Terakhir</h6>
    @if($recentTransactions->count() > 0)
        <div class="list-group list-group-flush small">
            @foreach($recentTransactions as $trx)
                <div class="list-group-item px-0 py-2 border-0 border-bottom">
                    <div class="d-flex w-100 justify-content-between mb-1">
                        <strong class="text-dark">{{ $trx->reference }}</strong>
                        <small class="text-muted">{{ $trx->date->format('d M y') }}</small>
                    </div>
                    <div class="d-flex w-100 justify-content-between align-items-center">
                        <span class="text-muted text-truncate" style="max-width: 60%;">
                            {{ $trx->paymentDetails->pluck('type')->implode(', ') }}
                        </span>
                        <strong class="text-success">+ Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</strong>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-3 text-muted small">
            <i class="bi bi-journal-x fs-4 d-block mb-1 opacity-50"></i>
            Belum ada transaksi di tahun ajaran ini.
        </div>
    @endif
</div>

<!-- Tombol Pengaturan -->
<div class="d-grid gap-2 mb-4">
    <a href="{{ route('wali.change-pin') }}" class="btn btn-outline-secondary py-2" style="border-radius: 10px;">
        <i class="bi bi-key me-1"></i> Ganti PIN Rahasia
    </a>
</div>
@endsection
