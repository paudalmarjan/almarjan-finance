@extends('layouts.app')

@section('title', 'Dashboard Keuangan')

@section('content')

{{-- ═══════════════════════════════════════════════════════════════
     INFO BAR — Tahun Ajaran + Quick Links
     ═══════════════════════════════════════════════════════════════ --}}
<div class="card-premium px-4 py-3 mb-4 d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-calendar3 text-primary"></i>
        <span class="fw-semibold text-dark" style="font-size:.88rem;">{{ $selectedYear->name }}</span>
        @if($selectedYear->is_active)
            <span class="badge rounded-pill" style="background:rgba(16,185,129,0.12); color:#10b981; font-size:.68rem;">Aktif</span>
        @else
            <span class="badge rounded-pill" style="background:rgba(107,114,128,0.12); color:#6b7280; font-size:.68rem;">Tidak Aktif</span>
        @endif
        <span class="text-muted" style="font-size:.78rem;">· Data ditampilkan untuk keseluruhan sekolah</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('reports.arrears') }}" class="btn btn-sm btn-outline-warning" style="font-size:.75rem;">
            <i class="bi bi-exclamation-triangle me-1"></i>Lap. Tunggakan
        </a>
        <a href="{{ route('payments.create') }}" class="btn btn-sm btn-success" style="font-size:.75rem;">
            <i class="bi bi-plus-lg me-1"></i>Catat Pembayaran
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
                <div style="background:rgba(99,102,241,0.12); color:#6366f1; width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="bi bi-bank2"></i>
                </div>
                <span class="badge rounded-pill" style="background:rgba(99,102,241,0.1); color:#6366f1; font-size:.68rem;">Saldo Bersih TA</span>
            </div>
            <div>
                <p class="text-muted mb-1" style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;">Kas Saat Ini</p>
                <h4 class="mb-0 fw-bold {{ $currentBalance >= 0 ? 'text-dark' : 'text-danger' }}" style="font-size:1.2rem;">
                    Rp {{ number_format($currentBalance, 0, ',', '.') }}
                </h4>
                <small class="text-muted" style="font-size:.68rem;">Saldo Awal + Masuk − Keluar</small>
            </div>
        </div>
    </div>

    {{-- Total Pemasukan --}}
    <div class="col-sm-6 col-xl">
        <div class="card-premium p-4 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div style="background:rgba(16,185,129,0.12); color:#10b981; width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="bi bi-arrow-down-circle-fill"></i>
                </div>
                <span class="badge rounded-pill" style="background:rgba(16,185,129,0.1); color:#10b981; font-size:.68rem;">Hari ini: +Rp {{ number_format($todayIncome, 0, ',', '.') }}</span>
            </div>
            <div>
                <p class="text-muted mb-1" style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;">Total Pemasukan</p>
                <h4 class="mb-0 fw-bold text-success" style="font-size:1.2rem;">Rp {{ number_format($totalIncome, 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size:.68rem;">Bulan ini: Rp {{ number_format($thisMonthIncome, 0, ',', '.') }}</small>
            </div>
        </div>
    </div>

    {{-- Total Pengeluaran --}}
    <div class="col-sm-6 col-xl">
        <div class="card-premium p-4 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div style="background:rgba(239,68,68,0.12); color:#ef4444; width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="bi bi-arrow-up-circle-fill"></i>
                </div>
                <span class="badge rounded-pill" style="background:rgba(239,68,68,0.1); color:#ef4444; font-size:.68rem;">Bulan ini: Rp {{ number_format($thisMonthOutcome, 0, ',', '.') }}</span>
            </div>
            <div>
                <p class="text-muted mb-1" style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;">Total Pengeluaran</p>
                <h4 class="mb-0 fw-bold text-danger" style="font-size:1.2rem;">Rp {{ number_format($totalOutcome, 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size:.68rem;">Net bulan ini: <span style="color:{{ $thisMonthNet >= 0 ? '#10b981' : '#ef4444' }};">{{ $thisMonthNet >= 0 ? '+' : '' }}Rp {{ number_format($thisMonthNet, 0, ',', '.') }}</span></small>
            </div>
        </div>
    </div>

    {{-- Collection Rate --}}
    <div class="col-sm-6 col-xl">
        <div class="card-premium p-4 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div style="background:rgba(245,158,11,0.12); color:#f59e0b; width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="bi bi-bullseye"></i>
                </div>
                <span class="badge rounded-pill" style="background:rgba(245,158,11,0.1); color:#f59e0b; font-size:.68rem;">Tagihan {{ $arrearsRatio }}%</span>
            </div>
            <div>
                <p class="text-muted mb-1" style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;">Collection Rate</p>
                <h4 class="mb-1 fw-bold" style="font-size:1.2rem; color:#f59e0b;">{{ $collectionRate }}%</h4>
                <div class="progress" style="height:4px; background:rgba(245,158,11,0.15);">
                    <div class="progress-bar" style="width:{{ $collectionRate }}%; background:#f59e0b;"></div>
                </div>
                <small class="text-muted" style="font-size:.68rem;">Tagihan terkumpul vs potensi</small>
            </div>
        </div>
    </div>

    {{-- SPP Rate --}}
    <div class="col-sm-6 col-xl">
        <div class="card-premium p-4 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div style="background:rgba(139,92,246,0.12); color:#8b5cf6; width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <span class="badge rounded-pill" style="background:rgba(139,92,246,0.1); color:#8b5cf6; font-size:.68rem;">{{ $currentMonthName }}</span>
            </div>
            <div>
                <p class="text-muted mb-1" style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;">Kelancaran SPP</p>
                <h4 class="mb-1 fw-bold" style="font-size:1.2rem; color:#8b5cf6;">{{ $sppPaymentRate }}%</h4>
                <div class="progress" style="height:4px; background:rgba(139,92,246,0.15);">
                    <div class="progress-bar" style="width:{{ $sppPaymentRate }}%; background:#8b5cf6;"></div>
                </div>
                <small class="text-muted" style="font-size:.68rem;">{{ $paidSppCount }}/{{ $totalStudentsCount }} siswa lunas bulan ini</small>
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
            <div style="background:rgba(6,182,212,0.12); color:#06b6d4; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <p class="text-muted mb-0" style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;">Siswa Aktif Terdaftar</p>
                <h5 class="mb-0 fw-bold text-dark">{{ $totalStudentsCount }} <span class="fw-normal text-muted" style="font-size:.85rem;">siswa</span></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-premium px-4 py-3 d-flex align-items-center gap-3">
            <div style="background:rgba(245,158,11,0.12); color:#f59e0b; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="bi bi-tags-fill"></i>
            </div>
            <div>
                <p class="text-muted mb-0" style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;">Penerima Diskon/Keringanan</p>
                <h5 class="mb-0 fw-bold text-dark">{{ $discountedStudentsCount }} <span class="fw-normal text-muted" style="font-size:.85rem;">siswa</span></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-premium px-4 py-3 d-flex align-items-center gap-3">
            <div style="background:rgba(239,68,68,0.12); color:#ef4444; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <p class="text-muted mb-0" style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;">Total Tagihan Aktif</p>
                <h5 class="mb-0 fw-bold text-danger">Rp {{ number_format($totalArrears, 0, ',', '.') }}</h5>
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
                    <h6 class="fw-bold mb-0">Arus Kas Bulanan</h6>
                    <p class="text-muted mb-0" style="font-size:.75rem;">Pemasukan, Pengeluaran &amp; Net per bulan ({{ $selectedYear->name }})</p>
                </div>
            </div>
            <div style="height:280px;">
                <canvas id="cashflowChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-premium p-4 h-100">
            <h6 class="fw-bold mb-1">Distribusi Pengeluaran</h6>
            <p class="text-muted mb-3" style="font-size:.75rem;">Komposisi per kategori kas keluar</p>
            <div style="height:180px; position:relative;" class="mb-3">
                <canvas id="expenseDonut"></canvas>
            </div>
            @php $palette = ['#065f46','#eab308','#3b82f6','#ef4444','#10b981','#6366f1','#f97316']; @endphp
            <div class="d-flex flex-column gap-2">
                @foreach($expenseLabels as $i => $label)
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span style="width:9px; height:9px; border-radius:50%; background:{{ $palette[$i % count($palette)] }}; flex-shrink:0;"></span>
                        <span style="font-size:.78rem; font-weight:600;">{{ $label }}</span>
                    </div>
                    <span class="fw-bold" style="font-size:.78rem;">Rp {{ number_format($expenseValues[$i] ?? 0, 0, ',', '.') }}</span>
                </div>
                @endforeach
                @if(count($expenseLabels) === 0)
                    <p class="text-muted text-center py-2" style="font-size:.8rem;">Belum ada data pengeluaran.</p>
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
                    <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-primary me-1"></i> Transaksi Keuangan Terbaru</h6>
                    <p class="text-muted mb-0" style="font-size:.75rem;">Gabungan pemasukan &amp; pengeluaran terkini</p>
                </div>
                <a href="{{ route('payments.create') }}" class="btn btn-sm btn-success">
                    <i class="bi bi-plus-lg me-1"></i>Bayar
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" style="font-size:.82rem;">
                    <thead>
                        <tr style="border-bottom:2px solid #f3f4f6;">
                            <th class="text-muted fw-semibold pb-2" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.05em;">Tanggal</th>
                            <th class="text-muted fw-semibold pb-2" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.05em;">Jenis</th>
                            <th class="text-muted fw-semibold pb-2" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.05em;">Keterangan</th>
                            <th class="text-muted fw-semibold pb-2 text-end" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.05em;">Nominal</th>
                            <th class="text-muted fw-semibold pb-2 text-center" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.05em;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $tx)
                        <tr style="border-bottom:1px solid #f9fafb;">
                            <td class="py-2 text-muted">{{ \Carbon\Carbon::parse($tx['date'])->format('d/m/y') }}</td>
                            <td class="py-2">
                                @if($tx['type'] === 'Pemasukan')
                                    <span class="badge rounded-pill" style="background:rgba(16,185,129,0.1); color:#10b981; font-size:.68rem; padding:.3em .7em;">
                                        <i class="bi bi-arrow-down-short"></i> Masuk
                                    </span>
                                @else
                                    <span class="badge rounded-pill" style="background:rgba(239,68,68,0.1); color:#ef4444; font-size:.68rem; padding:.3em .7em;">
                                        <i class="bi bi-arrow-up-short"></i> Keluar
                                    </span>
                                @endif
                            </td>
                            <td class="py-2 fw-semibold text-dark" style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $tx['description'] }}</td>
                            <td class="py-2 text-end fw-bold" style="color:{{ $tx['type'] === 'Pemasukan' ? '#10b981' : '#ef4444' }};">
                                {{ $tx['type'] === 'Pemasukan' ? '+' : '-' }}Rp {{ number_format($tx['amount'], 0, ',', '.') }}
                            </td>
                            <td class="py-2 text-center">
                                <a href="{{ $tx['route'] }}" class="btn btn-sm btn-light border" style="font-size:.68rem; padding:.2rem .6rem;">Detail</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4" style="font-size:.85rem;">
                                <i class="bi bi-inbox d-block fs-3 mb-1 opacity-30"></i>Belum ada transaksi dalam periode ini.
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
            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Tunggakan Terbesar</h6>
            <p class="text-muted mb-4" style="font-size:.75rem;">Top 5 siswa dengan tagihan tertinggi saat ini</p>

            <div class="d-flex flex-column gap-3 flex-grow-1">
                @forelse($attentionList as $i => $att)
                @php $pct = $maxArrears > 0 ? ($att['amount'] / $maxArrears) * 100 : 0; @endphp
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div>
                            <span class="fw-semibold text-dark" style="font-size:.83rem;">{{ $att['name'] }}</span>
                            <span class="text-muted d-block" style="font-size:.68rem;">{{ $att['group_name'] }}</span>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold text-danger" style="font-size:.8rem;">Rp {{ number_format($att['amount'], 0, ',', '.') }}</span>
                            <a href="{{ route('payments.create', ['student_id' => $att['student_id']]) }}" class="btn btn-xs d-block mt-1" style="background:rgba(16,185,129,0.1); color:#10b981; font-size:.65rem; padding:.15rem .5rem; border-radius:4px; border:none;">Bayar Sekarang →</a>
                        </div>
                    </div>
                    <div class="progress" style="height:4px; background:rgba(239,68,68,0.1);">
                        <div class="progress-bar" style="width:{{ $pct }}%; background:#ef4444;"></div>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 my-auto">
                    <i class="bi bi-emoji-smile fs-2 d-block text-success mb-2 opacity-50"></i>
                    <p class="text-muted mb-0" style="font-size:.85rem;">Semua siswa tertib membayar! 🎉</p>
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
