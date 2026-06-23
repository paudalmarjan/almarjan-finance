@extends('layouts.savings')

@section('title', 'Buku Tabungan Siswa')

@section('content')
<div class="row justify-content-center mb-4">
    <div class="col-md-8">
        <div class="card-premium border-0 shadow-sm p-4 text-center">
            <h4 class="font-weight-700 mb-3"><i class="bi bi-search text-primary me-2"></i> Cari Buku Tabungan Siswa</h4>
            <p class="text-muted mb-4">Ketik nama atau NIS siswa untuk melihat seluruh riwayat tabungannya.</p>
            
            <form action="{{ route('savings.history') }}" method="GET" class="d-flex gap-2 justify-content-center mx-auto" style="max-width: 500px;">
                <div class="flex-grow-1 text-start">
                    <select class="form-select student-search" name="student_id" required onchange="this.form.submit()">
                        <option value="">-- Ketik Nama atau NIS Siswa --</option>
                        @foreach($students as $student)
                            @php
                                $groupName = $student->enrollments->first() ? $student->enrollments->first()->studentGroup->name : '-';
                            @endphp
                            <option value="{{ $student->id }}" {{ (isset($selectedStudent) && $selectedStudent->id == $student->id) ? 'selected' : '' }}>
                                {{ $student->name }} ({{ $student->nis }}) - {{ $groupName }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>
</div>

@if(isset($selectedStudent))
<div class="row">
    <div class="col-md-4 mb-4">
        <!-- Student Info Card -->
        <div class="card-premium p-4 sticky-top" style="top: 20px;">
            <div class="text-center mb-4">
                <div class="avatar bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem; font-weight: 700;">
                    {{ strtoupper(substr($selectedStudent->name, 0, 1)) }}
                </div>
                <h5 class="font-weight-700 mb-1">{{ $selectedStudent->name }}</h5>
                <p class="text-muted mb-2">{{ $selectedStudent->nis }}</p>
                <span class="badge bg-light text-dark border px-3 py-2">
                    @php
                        $enrollment = $selectedStudent->enrollments->first();
                        $groupName = $enrollment && $enrollment->studentGroup ? $enrollment->studentGroup->name : '-';
                    @endphp
                    Kelompok: {{ $groupName }}
                </span>
            </div>

            <hr class="my-4">

            <div class="text-center">
                <p class="text-muted font-weight-600 mb-1">Total Saldo Tabungan Saat Ini</p>
                @php
                    $balance = $saving ? $saving->balance : 0;
                @endphp
                <h2 class="font-weight-700 {{ $balance > 0 ? 'text-success' : 'text-dark' }} mb-3">
                    Rp {{ number_format($balance, 0, ',', '.') }}
                </h2>

                <div class="d-flex gap-2 justify-content-center mt-4">
                    <a href="{{ route('savings.deposit') }}" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-box-arrow-in-down me-1"></i> Setor
                    </a>
                    <a href="{{ route('savings.withdraw') }}" class="btn btn-danger btn-sm w-100">
                        <i class="bi bi-box-arrow-up me-1"></i> Tarik
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Transaction History Card -->
        <div class="card-premium p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0 font-weight-600"><i class="bi bi-clock-history text-primary me-2"></i> Riwayat Transaksi</h5>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal & No. Ref</th>
                            <th>Jenis Mutasi</th>
                            <th class="text-end">Nominal (Rp)</th>
                            <th>Oleh</th>
                            <th class="text-center" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $trx)
                        <tr>
                            <td>
                                <div class="font-weight-600">{{ $trx->transaction_date->format('d/m/Y') }}</div>
                                <small class="text-muted" style="font-family: monospace;">{{ $trx->receipt_number }}</small>
                            </td>
                            <td>
                                @if($trx->type === 'Deposit')
                                    <span class="badge bg-success-subtle text-success px-3 py-2 border border-success border-opacity-25 rounded-pill">
                                        <i class="bi bi-box-arrow-in-down me-1"></i> Setoran
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger px-3 py-2 border border-danger border-opacity-25 rounded-pill">
                                        <i class="bi bi-box-arrow-up me-1"></i> Penarikan
                                    </span>
                                @endif
                                @if($trx->notes)
                                    <small class="d-block mt-1 text-muted"><i class="bi bi-chat-left-text me-1"></i>{{ Str::limit($trx->notes, 30) }}</small>
                                @endif
                            </td>
                            <td class="text-end font-weight-600 {{ $trx->type === 'Deposit' ? 'text-success' : 'text-danger' }}">
                                {{ $trx->type === 'Deposit' ? '+' : '-' }} {{ number_format($trx->amount, 0, ',', '.') }}
                            </td>
                            <td>
                                <small class="d-block">{{ $trx->user->name ?? 'Sistem' }}</small>
                                <small class="text-muted" style="font-size: 0.65rem;">TA: {{ $trx->academicYear->name }}</small>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ route('savings.print', $trx->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Cetak Bukti">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    @if(auth()->user()->isSuperAdmin() && $trx->academicYear->is_active)
                                        <form action="{{ route('savings.destroy', $trx->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan dan menghapus transaksi ini? Tindakan ini akan mengoreksi saldo tabungan siswa.')" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Batal Transaksi">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                Belum ada riwayat transaksi tabungan untuk siswa ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const selectEl = document.querySelector('.student-search');
        if (selectEl) {
            new TomSelect(selectEl, {
                create: false,
                sortField: {
                    field: "text",
                    direction: "asc"
                },
                placeholder: "-- Ketik Nama atau NIS Siswa --"
            });
        }
    });
</script>
@endsection
