@extends('layouts.savings')

@section('title', 'Dashboard Tabungan')

@section('content')

{{-- ═══════════════════════════════════════════════════════════════
     ROW 1 — PRIMARY KPIs (5 cards): Total Saldo, Partisipasi, Rata-rata,
              Hari Ini (Net Flow), Transaksi Hari Ini
     ═══════════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">

    {{-- Total Saldo --}}
    <div class="col-sm-6 col-xl">
        <div class="card-premium p-4 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="kpi-icon-wrap" style="background: rgba(99,102,241,0.12); color:#6366f1; width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="bi bi-bank2"></i>
                </div>
                <span class="badge rounded-pill" style="background:rgba(99,102,241,0.1); color:#6366f1; font-size:0.68rem;">Semua Siswa</span>
            </div>
            <div>
                <p class="text-muted mb-1" style="font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Total Saldo Sekolah</p>
                <h4 class="mb-0 fw-bold text-dark" style="font-size:1.25rem;">Rp {{ number_format($totalBalance, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>

    {{-- Partisipasi --}}
    <div class="col-sm-6 col-xl">
        <div class="card-premium p-4 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="kpi-icon-wrap" style="background: rgba(16,185,129,0.12); color:#10b981; width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="bi bi-people-fill"></i>
                </div>
                <span class="badge rounded-pill" style="background:rgba(16,185,129,0.1); color:#10b981; font-size:0.68rem;">{{ $studentsWithSaving }}/{{ $totalStudents }} siswa</span>
            </div>
            <div>
                <p class="text-muted mb-1" style="font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Partisipasi Menabung</p>
                <h4 class="mb-1 fw-bold" style="font-size:1.25rem; color:#10b981;">{{ $participationRate }}%</h4>
                <div class="progress" style="height:4px; background:rgba(16,185,129,0.15);">
                    <div class="progress-bar" style="width:{{ $participationRate }}%; background:#10b981;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Rata-rata Saldo --}}
    <div class="col-sm-6 col-xl">
        <div class="card-premium p-4 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="kpi-icon-wrap" style="background: rgba(245,158,11,0.12); color:#f59e0b; width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="bi bi-calculator-fill"></i>
                </div>
                <span class="badge rounded-pill" style="background:rgba(245,158,11,0.1); color:#f59e0b; font-size:0.68rem;">Per siswa aktif</span>
            </div>
            <div>
                <p class="text-muted mb-1" style="font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Rata-rata Saldo</p>
                <h4 class="mb-0 fw-bold text-dark" style="font-size:1.25rem;">Rp {{ number_format($avgBalance, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>

    {{-- Net Flow Hari Ini --}}
    <div class="col-sm-6 col-xl">
        <div class="card-premium p-4 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="kpi-icon-wrap" style="background: {{ $todayNet >= 0 ? 'rgba(16,185,129,0.12)' : 'rgba(239,68,68,0.12)' }}; color:{{ $todayNet >= 0 ? '#10b981' : '#ef4444' }}; width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="bi bi-{{ $todayNet >= 0 ? 'arrow-up-circle-fill' : 'arrow-down-circle-fill' }}"></i>
                </div>
                <span class="badge rounded-pill" style="background:rgba(0,0,0,0.05); color:#6b7280; font-size:0.68rem;">{{ \Carbon\Carbon::today()->translatedFormat('d M Y') }}</span>
            </div>
            <div>
                <p class="text-muted mb-1" style="font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Net Flow Hari Ini</p>
                <h4 class="mb-0 fw-bold" style="font-size:1.25rem; color:{{ $todayNet >= 0 ? '#10b981' : '#ef4444' }};">
                    {{ $todayNet >= 0 ? '+' : '' }}Rp {{ number_format($todayNet, 0, ',', '.') }}
                </h4>
                <small class="text-muted" style="font-size:.7rem;">
                    <span class="text-success">↑ {{ number_format($todayDeposits,0,',','.') }}</span>
                    &nbsp;|&nbsp;
                    <span class="text-danger">↓ {{ number_format($todayWithdrawals,0,',','.') }}</span>
                </small>
            </div>
        </div>
    </div>

    {{-- Jumlah Transaksi Hari Ini --}}
    <div class="col-sm-6 col-xl">
        <div class="card-premium p-4 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="kpi-icon-wrap" style="background: rgba(139,92,246,0.12); color:#8b5cf6; width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="bi bi-receipt"></i>
                </div>
                <span class="badge rounded-pill" style="background:rgba(139,92,246,0.1); color:#8b5cf6; font-size:0.68rem;">transaksi</span>
            </div>
            <div>
                <p class="text-muted mb-1" style="font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Aktivitas Hari Ini</p>
                <h4 class="mb-0 fw-bold text-dark" style="font-size:1.25rem;">{{ $todayTxCount }} <span style="font-size:.85rem; font-weight:500; color:#9ca3af;">transaksi</span></h4>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     ROW 2 — PERIOD SUMMARY: Minggu Ini vs Bulan Ini
     ═══════════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card-premium p-4">
            <p class="text-muted mb-3" style="font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em;"><i class="bi bi-calendar-week me-1"></i> Ringkasan Minggu Ini</p>
            <div class="row g-0 text-center">
                <div class="col border-end">
                    <p class="text-muted mb-1" style="font-size:.72rem;">Setoran</p>
                    <p class="fw-bold text-success mb-0" style="font-size:1rem;">Rp {{ number_format($weeklyDeposits, 0, ',', '.') }}</p>
                </div>
                <div class="col border-end">
                    <p class="text-muted mb-1" style="font-size:.72rem;">Penarikan</p>
                    <p class="fw-bold text-danger mb-0" style="font-size:1rem;">Rp {{ number_format($weeklyWithdrawals, 0, ',', '.') }}</p>
                </div>
                <div class="col">
                    <p class="text-muted mb-1" style="font-size:.72rem;">Net</p>
                    <p class="fw-bold mb-0" style="font-size:1rem; color:{{ $weeklyNet >= 0 ? '#10b981' : '#ef4444' }};">
                        {{ $weeklyNet >= 0 ? '+' : '' }}Rp {{ number_format($weeklyNet, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card-premium p-4">
            <p class="text-muted mb-3" style="font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em;"><i class="bi bi-calendar-month me-1"></i> Ringkasan Bulan Ini</p>
            <div class="row g-0 text-center">
                <div class="col border-end">
                    <p class="text-muted mb-1" style="font-size:.72rem;">Setoran</p>
                    <p class="fw-bold text-success mb-0" style="font-size:1rem;">Rp {{ number_format($monthlyDeposits, 0, ',', '.') }}</p>
                </div>
                <div class="col border-end">
                    <p class="text-muted mb-1" style="font-size:.72rem;">Penarikan</p>
                    <p class="fw-bold text-danger mb-0" style="font-size:1rem;">Rp {{ number_format($monthlyWithdrawals, 0, ',', '.') }}</p>
                </div>
                <div class="col">
                    <p class="text-muted mb-1" style="font-size:.72rem;">Net</p>
                    <p class="fw-bold mb-0" style="font-size:1rem; color:{{ $monthlyNet >= 0 ? '#10b981' : '#ef4444' }};">
                        {{ $monthlyNet >= 0 ? '+' : '' }}Rp {{ number_format($monthlyNet, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     ROW 3 — CHARTS (8/4 split)
     ═══════════════════════════════════════════════════════════════ --}}
<div class="row g-4 mb-4">
    {{-- Left: Dual Chart (Tab toggle: Daily / Monthly) --}}
    <div class="col-lg-8">
        <div class="card-premium p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h6 class="fw-bold mb-0">Aktivitas Mutasi Tabungan</h6>
                    <p class="text-muted mb-0" style="font-size:.75rem;">Setoran &amp; Penarikan dalam periode terpilih</p>
                </div>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-primary" id="btnDaily" onclick="switchChart('daily')">14 Hari</button>
                    <button type="button" class="btn btn-outline-secondary" id="btnMonthly" onclick="switchChart('monthly')">6 Bulan</button>
                </div>
            </div>
            <div style="height: 280px; position:relative;">
                <canvas id="dailyChart"></canvas>
                <canvas id="monthlyChart" style="display:none;"></canvas>
            </div>
        </div>
    </div>

    {{-- Right: Level Distribution (Donut) --}}
    <div class="col-lg-4">
        <div class="card-premium p-4 h-100">
            <h6 class="fw-bold mb-1">Distribusi Saldo per Jenjang</h6>
            <p class="text-muted mb-3" style="font-size:.75rem;">Komposisi total saldo sekolah</p>
            <div style="height: 180px; position:relative;" class="mb-3">
                <canvas id="donutChart"></canvas>
            </div>
            <div id="donut-legend" class="d-flex flex-column gap-2 mt-3">
                @php
                    $palette = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];
                @endphp
                @foreach($levelBalances as $i => $lb)
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span style="width:10px; height:10px; border-radius:50%; background:{{ $palette[$i % count($palette)] }}; flex-shrink:0;"></span>
                        <span style="font-size:.8rem; font-weight:600;">{{ $lb['name'] }}</span>
                    </div>
                    <div class="text-end">
                        <span class="fw-bold" style="font-size:.8rem;">Rp {{ number_format($lb['balance'], 0, ',', '.') }}</span>
                        <span class="text-muted d-block" style="font-size:.68rem;">{{ $lb['count'] }} siswa · {{ $totalBalance > 0 ? round(($lb['balance'] / $totalBalance) * 100, 1) : 0 }}%</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     ROW 4 — LEADERBOARD + RECENT TRANSACTIONS
     ═══════════════════════════════════════════════════════════════ --}}
<div class="row g-4">
    {{-- Leaderboard --}}
    <div class="col-lg-4">
        <div class="card-premium p-4 h-100">
            <h6 class="fw-bold mb-1"><i class="bi bi-trophy-fill text-warning me-1"></i> Top 5 Saldo Tertinggi</h6>
            <p class="text-muted mb-4" style="font-size:.75rem;">Siswa dengan akumulasi tabungan terbesar</p>
            <div class="d-flex flex-column gap-3">
                @forelse($topDepositors as $i => $s)
                @php
                    $medals = ['🥇','🥈','🥉','4️⃣','5️⃣'];
                    $pct = $topDepositors->first()->balance > 0 ? ($s->balance / $topDepositors->first()->balance) * 100 : 0;
                @endphp
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size:1.1rem; line-height:1;">{{ $medals[$i] }}</span>
                            <div>
                                <span class="fw-semibold text-dark" style="font-size:.85rem;">{{ $s->student->name }}</span>
                                <span class="text-muted d-block" style="font-size:.68rem;">{{ $s->student->nis }}</span>
                            </div>
                        </div>
                        <span class="fw-bold text-success" style="font-size:.82rem;">Rp {{ number_format($s->balance, 0, ',', '.') }}</span>
                    </div>
                    <div class="progress" style="height:4px; background:rgba(99,102,241,0.1);">
                        <div class="progress-bar" style="width:{{ $pct }}%; background:#6366f1;"></div>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center py-3" style="font-size:.85rem;">Belum ada data tabungan.</p>
                @endforelse
            </div>
            <div class="mt-4 pt-3 border-top">
                <a href="{{ route('savings.students') }}" class="btn btn-sm btn-outline-primary w-100">Lihat Semua Siswa →</a>
            </div>
        </div>
    </div>

    {{-- Recent Transactions --}}
    <div class="col-lg-8">
        <div class="card-premium p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-primary me-1"></i> Transaksi Terbaru</h6>
                    <p class="text-muted mb-0" style="font-size:.75rem;">8 mutasi terakhir di semua kelas</p>
                </div>
                <a href="{{ route('savings.deposit') }}" class="btn btn-sm btn-success">
                    <i class="bi bi-plus-lg me-1"></i>Setor
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" style="font-size:.82rem;">
                    <thead>
                        <tr style="border-bottom:2px solid #f3f4f6;">
                            <th class="text-muted fw-semibold pb-2" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.05em;">Tanggal</th>
                            <th class="text-muted fw-semibold pb-2" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.05em;">Siswa</th>
                            <th class="text-muted fw-semibold pb-2" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.05em;">Jenis</th>
                            <th class="text-muted fw-semibold pb-2 text-end" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.05em;">Nominal</th>
                            <th class="text-muted fw-semibold pb-2" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.05em;">Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $trx)
                        <tr style="border-bottom:1px solid #f9fafb;">
                            <td class="py-2 text-muted">{{ $trx->transaction_date->format('d/m/y') }}</td>
                            <td class="py-2 fw-semibold text-dark">{{ $trx->student->name ?? '-' }}</td>
                            <td class="py-2">
                                @if($trx->type === 'Deposit')
                                    <span class="badge rounded-pill" style="background:rgba(16,185,129,0.1); color:#10b981; font-size:.68rem; padding:.3em .7em;">
                                        <i class="bi bi-arrow-down-short"></i> Setor
                                    </span>
                                @else
                                    <span class="badge rounded-pill" style="background:rgba(239,68,68,0.1); color:#ef4444; font-size:.68rem; padding:.3em .7em;">
                                        <i class="bi bi-arrow-up-short"></i> Tarik
                                    </span>
                                @endif
                            </td>
                            <td class="py-2 text-end fw-bold" style="color:{{ $trx->type === 'Deposit' ? '#10b981' : '#ef4444' }};">
                                {{ $trx->type === 'Deposit' ? '+' : '-' }}Rp {{ number_format($trx->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-2 text-muted">{{ $trx->user->name ?? 'Sistem' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4" style="font-size:.85rem;">
                                <i class="bi bi-inbox d-block fs-3 mb-1 opacity-30"></i>Belum ada transaksi.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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

    const baseBarDataset = (label, data, color) => ({
        label, data,
        backgroundColor: color + '33',
        borderColor: color,
        borderWidth: 2,
        borderRadius: 5,
        borderSkipped: false,
    });

    // ── Daily Chart ──────────────────────────────────────────────
    const daily = @json($dailyChart);
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    const dailyInstance = new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: daily.labels,
            datasets: [
                baseBarDataset('Setoran', daily.deposits, '#10b981'),
                baseBarDataset('Penarikan', daily.withdrawals, '#ef4444'),
                {
                    label: 'Net Flow',
                    data: daily.net,
                    type: 'line',
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.08)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#6366f1',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y',
                }
            ]
        },
        options: chartOptions(abbr),
    });

    // ── Monthly Chart ─────────────────────────────────────────────
    const monthly = @json($monthlyChart);
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyInstance = new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: monthly.labels,
            datasets: [
                baseBarDataset('Setoran', monthly.deposits, '#10b981'),
                baseBarDataset('Penarikan', monthly.withdrawals, '#ef4444'),
            ]
        },
        options: chartOptions(abbr),
    });

    function chartOptions(yFormatter) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode:'index', intersect:false },
            plugins: {
                legend: { position:'top', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + idr(ctx.parsed.y) } },
            },
            scales: {
                x: { grid: { display:false }, ticks: { font:{size:10} } },
                y: { beginAtZero:true, grid:{ color:'rgba(0,0,0,0.04)' }, ticks: { font:{size:10}, callback: yFormatter } },
            }
        };
    }

    window.switchChart = function(type) {
        if (type === 'daily') {
            document.getElementById('dailyChart').style.display = '';
            document.getElementById('monthlyChart').style.display = 'none';
            document.getElementById('btnDaily').className = 'btn btn-sm btn-primary';
            document.getElementById('btnMonthly').className = 'btn btn-sm btn-outline-secondary';
        } else {
            document.getElementById('dailyChart').style.display = 'none';
            document.getElementById('monthlyChart').style.display = '';
            document.getElementById('btnDaily').className = 'btn btn-sm btn-outline-secondary';
            document.getElementById('btnMonthly').className = 'btn btn-sm btn-primary';
        }
    };

    // ── Donut Chart ───────────────────────────────────────────────
    const levelBalances = @json($levelBalances);
    const palette = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];
    const donutCtx = document.getElementById('donutChart').getContext('2d');
    new Chart(donutCtx, {
        type: 'doughnut',
        data: {
            labels: levelBalances.map(l => l.name),
            datasets: [{
                data: levelBalances.map(l => l.balance),
                backgroundColor: palette.slice(0, levelBalances.length),
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
});
</script>
@endsection
