@extends('layouts.app')

@section('title', 'Laporan Pertanggungjawaban (LPJ)')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h5 class="mb-1 font-weight-600">Penyusunan Berkas LPJ</h5>
        <p class="helper-text mb-0">Hasilkan dokumen Laporan Pertanggungjawaban (LPJ) terpadu dalam format PDF yang melampirkan seluruh bukti transaksi.</p>
    </div>
</div>

<div class="row">
    <!-- Left Column: Export form -->
    <div class="col-lg-5 mb-4">
        <div class="card-premium p-4 h-100 bg-light-green border border-success-subtle">
            <h6 class="font-weight-600 mb-3 text-success"><i class="bi bi-file-earmark-pdf"></i> Unduh Dokumen LPJ Terpadu</h6>
            <p class="helper-text mb-4">Sistem akan menyusun pembukuan kas keluar dan menggabungkan seluruh berkas bukti kuitansi/nota belanja yang diunggah ke dalam satu dokumen **PDF** yang rapi.</p>
            
            <form action="{{ route('reports.export-lpj') }}" method="POST" id="lpjExportForm">
                @csrf
                <div class="mb-3">
                    <label for="lpj_start" class="form-label small font-weight-500">Mulai Tanggal</label>
                    <input type="date" class="form-control" name="start_date" id="lpj_start" value="{{ $startDate }}" required>
                </div>
                <div class="mb-3">
                    <label for="lpj_end" class="form-label small font-weight-500">Sampai Tanggal</label>
                    <input type="date" class="form-control" name="end_date" id="lpj_end" value="{{ $endDate }}" required>
                </div>
                
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-success py-2">
                        <i class="bi bi-file-pdf me-1"></i> Hasilkan & Unduh Dokumen LPJ (.pdf)
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column: Preview Table -->
    <div class="col-lg-7 mb-4">
        <div class="card-premium p-4 h-100">
            <h6 class="font-weight-600 mb-3 text-teal border-bottom pb-2"><i class="bi bi-eye"></i> Pratinjau Pengeluaran Periode Ini</h6>
            
            <div class="d-flex justify-content-between mb-3">
                <span class="small text-muted">Total Pengeluaran:</span>
                <strong class="text-danger font-weight-600">Rp {{ number_format($totalOutcome, 0, ',', '.') }}</strong>
            </div>

            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                <table class="table table-hover align-middle small">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Keterangan</th>
                            <th class="text-end">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $exp)
                        <tr>
                            <td>{{ $exp->date->format('d M') }}</td>
                            <td><span class="badge bg-warning text-dark">{{ $exp->expenseCategory->name }}</span></td>
                            <td>{{ Str::limit($exp->notes ?? 'Tanpa catatan', 40) }}</td>
                            <td class="text-end text-danger font-weight-500">
                                Rp {{ number_format($exp->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Belum ada pengeluaran kas dalam rentang tanggal tersebut.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
