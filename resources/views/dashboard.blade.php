@extends('layouts.app')

@section('title', 'Dashboard Keuangan')

@section('content')

{{-- ═══════════════════════════════════════════════════════════════
     INFO BAR — Tahun Ajaran + Quick Links
     ═══════════════════════════════════════════════════════════════ --}}
<div class="card-premium px-4 py-3 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-calendar3 text-primary"></i>
        <span class="fw-600 text-dark">{{ $selectedYear->name }}</span>
        @if($selectedYear->is_active)
            <span class="badge badge-soft-success badge-pill">Aktif</span>
        @else
            <span class="badge badge-soft-neutral badge-pill">Tidak Aktif</span>
        @endif
        <span class="text-meta d-none d-md-inline">· Data ditampilkan untuk keseluruhan sekolah</span>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('reports.arrears') }}" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-exclamation-triangle"></i><span class="d-none d-sm-inline ms-1">Lap. Tunggakan</span>
        </a>
        <a href="{{ route('incomes.create') }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-journal-plus"></i><span class="d-none d-sm-inline ms-1">Pemasukan Lain</span>
        </a>
        <a href="{{ route('payments.create') }}" class="btn btn-sm btn-primary-custom">
            <i class="bi bi-plus-lg"></i><span class="ms-1">Catat Pembayaran</span>
        </a>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     ROW 1 — PRIMARY KPIs (5 cards)
     ═══════════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">

    {{-- Saldo Bersih --}}
    <div class="col-sm-6 col-xl">
        <div class="card-premium p-4 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="icon-box icon-box-indigo">
                    <i class="bi bi-bank2"></i>
                </div>
                <span class="badge badge-soft-indigo badge-pill">Saldo Bersih TA</span>
            </div>
            <div>
                <p class="text-label mb-1">Kas Saat Ini</p>
                <h4 class="mb-0 fw-700 text-value {{ $currentBalance >= 0 ? 'text-dark' : 'text-danger' }}">
                    Rp {{ number_format($currentBalance, 0, ',', '.') }}
                </h4>
                <span class="text-meta">Saldo Awal + Masuk − Keluar</span>
            </div>
        </div>
    </div>

    {{-- Total Pemasukan --}}
    <div class="col-sm-6 col-xl">
        <div class="card-premium p-4 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="icon-box icon-box-success">
                    <i class="bi bi-arrow-down-circle-fill"></i>
                </div>
                <span class="badge badge-soft-success badge-pill">Hari ini: +Rp {{ number_format($todayIncome, 0, ',', '.') }}</span>
            </div>
            <div>
                <p class="text-label mb-1">Total Pemasukan</p>
                <h4 class="mb-0 fw-700 text-success text-value">Rp {{ number_format($totalIncome, 0, ',', '.') }}</h4>
                <div class="mt-1 d-flex flex-column">
                    <span class="text-meta">Siswa: Rp {{ number_format($totalStudentIncome, 0, ',', '.') }} | Non-Siswa: Rp {{ number_format($totalGeneralIncome, 0, ',', '.') }}</span>
                    <span class="text-meta">Bulan ini: Rp {{ number_format($thisMonthIncome, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Total Pengeluaran --}}
    <div class="col-sm-6 col-xl">
        <div class="card-premium p-4 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="icon-box icon-box-danger">
                    <i class="bi bi-arrow-up-circle-fill"></i>
                </div>
                <span class="badge badge-soft-danger badge-pill">Bulan ini: Rp {{ number_format($thisMonthOutcome, 0, ',', '.') }}</span>
            </div>
            <div>
                <p class="text-label mb-1">Total Pengeluaran</p>
                <h4 class="mb-0 fw-700 text-danger text-value">Rp {{ number_format($totalOutcome, 0, ',', '.') }}</h4>
                <span class="text-meta">Net bulan ini: <span class="{{ $thisMonthNet >= 0 ? 'text-success' : 'text-danger' }} fw-600">{{ $thisMonthNet >= 0 ? '+' : '' }}Rp {{ number_format($thisMonthNet, 0, ',', '.') }}</span></span>
            </div>
        </div>
    </div>

    {{-- Collection Rate --}}
    <div class="col-sm-6 col-xl">
        <div class="card-premium p-4 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="icon-box icon-box-warning">
                    <i class="bi bi-bullseye"></i>
                </div>
                <span class="badge badge-soft-warning badge-pill">Tagihan {{ $arrearsRatio }}%</span>
            </div>
            <div>
                <p class="text-label mb-1">Collection Rate</p>
                <h4 class="mb-1 fw-700 text-value" style="color: var(--color-warning-text);">{{ $collectionRate }}%</h4>
                <div class="progress mb-1" style="height:4px;">
                    <div class="progress-bar" style="width:{{ $collectionRate }}%; background: var(--color-warning-text);"></div>
                </div>
                <span class="text-meta">Tagihan terkumpul vs potensi</span>
            </div>
        </div>
    </div>

    {{-- SPP Rate --}}
    <div class="col-sm-6 col-xl">
        <div class="card-premium p-4 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="icon-box icon-box-purple">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <span class="badge badge-soft-purple badge-pill">{{ $currentMonthName }}</span>
            </div>
            <div>
                <p class="text-label mb-1">Kelancaran SPP</p>
                <h4 class="mb-1 fw-700 text-value" style="color: var(--color-purple-text);">{{ $sppPaymentRate }}%</h4>
                <div class="progress mb-1" style="height:4px;">
                    <div class="progress-bar" style="width:{{ $sppPaymentRate }}%; background: var(--color-purple-text);"></div>
                </div>
                <span class="text-meta">{{ $paidSppCount }}/{{ $totalStudentsCount }} siswa lunas bulan ini</span>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     ROW 2 — STUDENT QUICK STATS (3 cards horizontal)
     ═══════════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card-premium px-4 py-3 d-flex align-items-center gap-3">
            <div class="icon-box icon-box-sm icon-box-info">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <p class="text-label mb-0">Siswa Aktif Terdaftar</p>
                <h5 class="mb-0 fw-700 text-dark">{{ $totalStudentsCount }} <span class="fw-500 text-muted" style="font-size: var(--text-sm);">siswa</span></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-premium px-4 py-3 d-flex align-items-center gap-3">
            <div class="icon-box icon-box-sm icon-box-warning">
                <i class="bi bi-tags-fill"></i>
            </div>
            <div>
                <p class="text-label mb-0">Penerima Diskon/Keringanan</p>
                <h5 class="mb-0 fw-700 text-dark">{{ $discountedStudentsCount }} <span class="fw-500 text-muted" style="font-size: var(--text-sm);">siswa</span></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-premium px-4 py-3 d-flex align-items-center gap-3">
            <div class="icon-box icon-box-sm icon-box-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <p class="text-label mb-0">Total Tagihan Aktif</p>
                <h5 class="mb-0 fw-700 text-danger tabular-nums">Rp {{ number_format($totalArrears, 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     ROW 3 — CHART (8) + EXPENSE DONUT (4)
     ═══════════════════════════════════════════════════════════════ --}}
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card-premium p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h6 class="fw-700 mb-0">Arus Kas Bulanan</h6>
                    <p class="text-meta mb-0">Pemasukan, Pengeluaran &amp; Net per bulan ({{ $selectedYear->name }})</p>
                </div>
            </div>
            <div style="height:280px;">
                <canvas id="cashflowChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-premium p-4 h-100">
            <h6 class="fw-700 mb-1">Distribusi Pengeluaran</h6>
            <p class="text-meta mb-3">Komposisi per kategori kas keluar</p>
            <div style="height:180px; position:relative;" class="mb-3">
                <canvas id="expenseDonut"></canvas>
            </div>
            @php $palette = ['#065f46','#eab308','#3b82f6','#ef4444','#10b981','#6366f1','#f97316']; @endphp
            <div class="d-flex flex-column gap-2">
                @foreach($expenseLabels as $i => $label)
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span style="width:8px; height:8px; border-radius:50%; background:{{ $palette[$i % count($palette)] }}; flex-shrink:0; display:inline-block;"></span>
                        <span class="text-meta fw-600">{{ $label }}</span>
                    </div>
                    <span class="fw-700 tabular-nums" style="font-size: var(--text-xs);">Rp {{ number_format($expenseValues[$i] ?? 0, 0, ',', '.') }}</span>
                </div>
                @endforeach
                @if(count($expenseLabels) === 0)
                    <p class="text-meta text-center py-2">Belum ada data pengeluaran.</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     ROW 4 — RECENT TRANSACTIONS (8) + ARREARS LEADERBOARD (4)
     ═══════════════════════════════════════════════════════════════ --}}
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-premium p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h6 class="fw-700 mb-0"><i class="bi bi-clock-history text-primary me-1"></i> Transaksi Keuangan Terbaru</h6>
                    <p class="text-meta mb-0">Gabungan pemasukan &amp; pengeluaran terkini</p>
                </div>
                <a href="{{ route('payments.create') }}" class="btn btn-sm btn-primary-custom">
                    <i class="bi bi-plus-lg"></i> Bayar
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-clean mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Keterangan</th>
                            <th class="text-end">Nominal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $tx)
                        <tr>
                            <td class="text-muted">{{ \Carbon\Carbon::parse($tx['date'])->format('d/m/y') }}</td>
                            <td>
                                @if($tx['type'] === 'Pemasukan')
                                    <span class="badge badge-soft-success badge-pill"><i class="bi bi-arrow-down-short"></i> Masuk</span>
                                @else
                                    <span class="badge badge-soft-danger badge-pill"><i class="bi bi-arrow-up-short"></i> Keluar</span>
                                @endif
                            </td>
                            <td class="fw-600 text-dark" style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $tx['description'] }}</td>
                            <td class="col-amount {{ $tx['type'] === 'Pemasukan' ? 'text-success' : 'text-danger' }}">
                                {{ $tx['type'] === 'Pemasukan' ? '+' : '-' }}Rp {{ number_format($tx['amount'], 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <a href="{{ $tx['route'] }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state py-4">
                                    <i class="bi bi-inbox empty-state-icon" style="font-size:2rem;"></i>
                                    <p class="empty-state-text mb-0">Belum ada transaksi dalam periode ini.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-premium p-4 h-100 d-flex flex-column">
            <h6 class="fw-700 mb-1"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Tunggakan Terbesar</h6>
            <p class="text-meta mb-4">Top 5 siswa dengan tagihan tertinggi saat ini</p>

            <div class="d-flex flex-column gap-3 flex-grow-1">
                @forelse($attentionList as $i => $att)
                @php $pct = $maxArrears > 0 ? ($att['amount'] / $maxArrears) * 100 : 0; @endphp
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div>
                            <span class="fw-600 text-dark" style="font-size: var(--text-sm);">{{ $att['name'] }}</span>
                            <span class="text-meta d-block">{{ $att['group_name'] }}</span>
                        </div>
                        <div class="text-end">
                            <span class="fw-700 text-danger tabular-nums" style="font-size: var(--text-xs);">Rp {{ number_format($att['amount'], 0, ',', '.') }}</span>
                            <a href="{{ route('payments.create', ['student_id' => $att['student_id']]) }}" class="btn btn-sm badge-soft-success d-block mt-1 border-0" style="font-size: var(--text-2xs); padding: 2px 8px;">Bayar →</a>
                        </div>
                    </div>
                    <div class="progress" style="height:3px; background: var(--color-danger-bg);">
                        <div class="progress-bar" style="width:{{ $pct }}%; background: var(--color-danger-text);"></div>
                    </div>
                </div>
                @empty
                <div class="empty-state py-4 my-auto">
                    <i class="bi bi-emoji-smile empty-state-icon text-success" style="font-size:2rem;"></i>
                    <p class="empty-state-text mb-0">Semua siswa tertib membayar! 🎉</p>
                </div>
                @endforelse
            </div>

            @if(count($attentionList) > 0)
            <div class="mt-4 pt-3 border-top">
                <a href="{{ route('reports.arrears') }}" class="btn btn-sm btn-outline-warning w-100">Lihat Semua Tunggakan →</a>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     CHART.JS SCRIPTS
     ═══════════════════════════════════════════════════════════════ --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const idr = v => new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', maximumFractionDigits:0 }).format(v);
    const abbr = v => {
        if (v >= 1e6) return 'Rp ' + (v/1e6).toFixed(1) + ' Jt';
        if (v >= 1e3) return 'Rp ' + (v/1e3).toFixed(0) + 'k';
        return 'Rp ' + v;
    };

    // ── Cashflow Bar+Line Chart ───────────────────────────────────────────
    const labels  = @json($monthLabels);
    const income  = @json($monthlyIncome);
    const outcome = @json($monthlyOutcome);
    const net     = @json($monthlyNet);

    const cashCtx = document.getElementById('cashflowChart').getContext('2d');
    new Chart(cashCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Pemasukan',
                    data: income,
                    backgroundColor: 'rgba(16,185,129,0.25)',
                    borderColor: '#10b981',
                    borderWidth: 2,
                    borderRadius: 5,
                    order: 2,
                },
                {
                    label: 'Pengeluaran',
                    data: outcome,
                    backgroundColor: 'rgba(239,68,68,0.2)',
                    borderColor: '#ef4444',
                    borderWidth: 2,
                    borderRadius: 5,
                    order: 3,
                },
                {
                    label: 'Net',
                    data: net,
                    type: 'line',
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.07)',
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointBackgroundColor: net.map(v => v >= 0 ? '#10b981' : '#ef4444'),
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    tension: 0.4,
                    fill: false,
                    order: 1,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + idr(ctx.parsed.y) } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: { font: { size: 10 }, callback: abbr },
                }
            }
        }
    });

    // ── Expense Donut ─────────────────────────────────────────────────────
    const expLabels = @json($expenseLabels);
    const expValues = @json($expenseValues);
    const palette   = ['#065f46','#eab308','#3b82f6','#ef4444','#10b981','#6366f1','#f97316'];

    if (expValues.length > 0) {
        const donutCtx = document.getElementById('expenseDonut').getContext('2d');
        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: expLabels,
                datasets: [{
                    data: expValues,
                    backgroundColor: palette.slice(0, expValues.length),
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + idr(ctx.parsed) } },
                }
            }
        });
    }

});
</script>
@endsection
