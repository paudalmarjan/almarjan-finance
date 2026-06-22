@extends('layouts.app')

@section('title', 'Kuitansi Pembayaran')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h5 class="mb-0 font-weight-600">Bukti Pembayaran Resmi</h5>
    <div>
        <a href="{{ route('payments.index') }}" class="btn btn-light btn-sm me-2"><i class="bi bi-arrow-left"></i> Riwayat</a>
        
        @if(auth()->user()->isSuperAdmin())
        <form action="{{ route('payments.destroy', $transaction->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan dan menghapus transaksi pembayaran ini? Sisa tagihan siswa akan otomatis dipulihkan.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm me-2">
                <i class="bi bi-trash me-1"></i> Batalkan Pembayaran
            </button>
        </form>
        @endif

        <a href="{{ route('payments.print', $transaction->id) }}" target="_blank" class="btn btn-primary-custom btn-sm">
            <i class="bi bi-printer me-1"></i> Cetak Kuitansi
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Receipt Card Layout -->
        <div class="card-premium p-5 bg-white border">
            <!-- Header Kuitansi -->
            <div class="row mb-4 align-items-center">
                <div class="col-2 text-center">
                    <span class="fs-1 text-teal"><i class="bi bi-wallet2"></i></span>
                </div>
                <div class="col-10">
                    <h5 class="mb-0 font-weight-700 text-teal">PAUD AL MARJAN</h5>
                    <p class="helper-text mb-0" style="font-size: 0.75rem;">Permata Depok Regency Ratujaya</p>
                    <span class="small font-weight-600 text-muted">KUITANSI PEMBAYARAN RESMI</span>
                </div>
            </div>

            <hr class="border-secondary-subtle mb-4">

            <!-- Transaction Info -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3 mb-md-0">
                    <table class="table table-borderless table-sm small mb-0">
                        <tr>
                            <td class="text-muted ps-0" style="width: 120px;">No. Kuitansi</td>
                            <td>: <strong>{{ $transaction->receipt_number }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Tanggal Bayar</td>
                            <td>: {{ $transaction->date->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Penerima Kasir</td>
                            <td>: {{ $transaction->user ? $transaction->user->name : '-' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6 border-start-md">
                    <table class="table table-borderless table-sm small mb-0">
                        <tr>
                            <td class="text-muted ps-0" style="width: 120px;">Nama Siswa</td>
                            <td>: <strong>{{ $transaction->student->name }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">NIS</td>
                            <td>: {{ $transaction->student->nis ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Tahun Ajaran / Kelas</td>
                            <td>: {{ $transaction->academicYear->name }} / {{ $enrollment ? $enrollment->studentGroup->name : '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Payment Breakdown Details -->
            <h6 class="font-weight-600 mb-2 small text-teal">Rincian Pembayaran:</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th>Pos Alokasi Dana</th>
                            <th>Kategori Tagihan</th>
                            <th class="text-end" style="width: 200px;">Jumlah Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transaction->paymentDetails as $det)
                        <tr>
                            <td class="font-weight-600">
                                @if($det->type === 'Annual')
                                    {{ $det->studentAnnualFee->annualFeeComponent->name }}
                                @else
                                    SPP Bulan {{ $det->month_name }}
                                @endif
                            </td>
                            <td>
                                @if($det->type === 'Annual')
                                    <span class="badge bg-secondary">Uang Tahunan</span>
                                @else
                                    <span class="badge bg-info text-dark">SPP Bulanan</span>
                                @endif
                            </td>
                            <td class="text-end font-weight-500 text-teal">
                                Rp {{ number_format($det->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="text-end font-weight-600 fs-6">TOTAL PEMBAYARAN:</td>
                            <td class="text-end font-weight-700 text-success fs-5">
                                Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Footer Tanda Tangan -->
            <div class="row mt-5 pt-3">
                <div class="col-8">
                    <p class="helper-text small italic mb-0">Catatan: Harap simpan bukti pembayaran ini sebagai tanda bukti pembayaran yang sah.</p>
                </div>
                <div class="col-4 text-center">
                    <p class="small text-muted mb-5">Petugas Kasir/Pencatat,</p>
                    <div class="border-bottom d-inline-block px-4 pb-1">
                        <strong>{{ $transaction->user ? $transaction->user->name : '-' }}</strong>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
