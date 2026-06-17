@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
<!-- Filter Panel Header -->
<div class="card-premium p-3 mb-4">
    <form method="GET" action="{{ route('dashboard') }}" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small font-weight-600 text-muted mb-1"><i class="bi bi-calendar3"></i> Tahun Ajaran Context</label>
            <input type="text" class="form-control form-control-sm bg-light" value="{{ $selectedYear->name }} {{ $selectedYear->is_active ? '(Berjalan/Aktif)' : '' }}" readonly>
        </div>
        <div class="col-md-3">
            <label for="filter_level_id" class="form-label small font-weight-600 text-muted mb-1">Filter Jenjang</label>
            <select class="form-select form-select-sm" name="level_id" id="filter_level_id" onchange="updateGroupOptions()">
                <option value="">-- Semua Jenjang --</option>
                @foreach($levels as $lvl)
                    <option value="{{ $lvl->id }}" {{ request('level_id') == $lvl->id ? 'selected' : '' }}>{{ $lvl->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="filter_group_id" class="form-label small font-weight-600 text-muted mb-1">Filter Kelompok Kelas</label>
            <select class="form-select form-select-sm" name="student_group_id" id="filter_group_id">
                <option value="">-- Semua Kelompok --</option>
                @foreach($levels as $lvl)
                    @foreach($lvl->groups as $grp)
                        <option value="{{ $grp->id }}" data-level-id="{{ $lvl->id }}" {{ request('student_group_id') == $grp->id ? 'selected' : '' }} style="display:none;">
                            {{ $grp->name }}
                        </option>
                    @endforeach
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary-custom btn-sm">Terapkan Filter</button>
        </div>
    </form>
</div>

<!-- Quick Stats Metrics -->
<div class="row mb-4">
    <!-- Metric 1: Total Active Students -->
    <div class="col-md-4 mb-3 mb-md-0">
        <div class="card-premium p-3 bg-light border border-light-subtle h-100 d-flex align-items-center justify-content-between">
            <div>
                <small class="text-muted font-weight-500">Siswa Aktif Terdaftar</small>
                <h4 class="mb-0 font-weight-700 mt-1 text-dark">{{ $totalStudentsCount }} Siswa</h4>
            </div>
            <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                <i class="bi bi-people-fill fs-4"></i>
            </div>
        </div>
    </div>

    <!-- Metric 2: Discount Recipients -->
    <div class="col-md-4 mb-3 mb-md-0">
        <div class="card-premium p-3 bg-light border border-light-subtle h-100 d-flex align-items-center justify-content-between">
            <div>
                <small class="text-muted font-weight-500">Siswa Penerima Diskon</small>
                <h4 class="mb-0 font-weight-700 mt-1 text-dark">{{ $discountedStudentsCount }} Siswa</h4>
            </div>
            <div class="bg-warning-subtle text-warning-emphasis rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                <i class="bi bi-tags-fill fs-4"></i>
            </div>
        </div>
    </div>

    <!-- Metric 3: SPP Payment Completion -->
    <div class="col-md-4">
        <div class="card-premium p-3 bg-light border border-light-subtle h-100">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <small class="text-muted font-weight-500">Kelancaran SPP ({{ $currentMonthName }})</small>
                <span class="font-weight-700 text-success">{{ $sppPaymentRate }}%</span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $sppPaymentRate }}%;" aria-valuenow="{{ $sppPaymentRate }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <small class="helper-text mt-1 d-block text-muted" style="font-size: 0.65rem;">Siswa yang sudah melunasi SPP bulan ini</small>
        </div>
    </div>
</div>

<!-- Financial Summary Cards -->
<div class="row mb-4">
    <!-- Card 1: Balance -->
    <div class="col-md-3 mb-3 mb-md-0">
        <div class="card-premium p-3 border-start border-primary border-4 h-100">
            <small class="text-muted font-weight-500">Saldo Akhir Bersih</small>
            <h3 class="mb-0 font-weight-700 mt-1 {{ $currentBalance >= 0 ? 'text-primary' : 'text-danger' }}">
                Rp {{ number_format($currentBalance, 0, ',', '.') }}
            </h3>
            <span class="helper-text mt-1 d-block" style="font-size: 0.7rem;">Pemasukan - Pengeluaran TA</span>
        </div>
    </div>
    
    <!-- Card 2: Income -->
    <div class="col-md-3 mb-3 mb-md-0">
        <div class="card-premium p-3 border-start border-success border-4 h-100">
            <small class="text-muted font-weight-500">Total Kas Masuk</small>
            <h3 class="mb-0 font-weight-700 text-success mt-1">
                Rp {{ number_format($totalIncome, 0, ',', '.') }}
            </h3>
            <span class="helper-text mt-1 d-block" style="font-size: 0.7rem;">Penerimaan pembayaran siswa</span>
        </div>
    </div>
    
    <!-- Card 3: Outcome -->
    <div class="col-md-3 mb-3 mb-md-0">
        <div class="card-premium p-3 border-start border-danger border-4 h-100">
            <small class="text-muted font-weight-500">Total Kas Keluar</small>
            <h3 class="mb-0 font-weight-700 text-danger mt-1">
                Rp {{ number_format($totalOutcome, 0, ',', '.') }}
            </h3>
            <span class="helper-text mt-1 d-block" style="font-size: 0.7rem;">Pengeluaran operasional sekolah</span>
        </div>
    </div>
    
    <!-- Card 4: Arrears -->
    <div class="col-md-3">
        <div class="card-premium p-3 border-start border-warning border-4 h-100">
            <small class="text-muted font-weight-500">Total Tunggakan Aktif</small>
            <h3 class="mb-0 font-weight-700 text-warning mt-1">
                Rp {{ number_format($totalArrears, 0, ',', '.') }}
            </h3>
            <span class="helper-text mt-1 d-block" style="font-size: 0.7rem;">Piutang tagihan siswa saat ini</span>
        </div>
    </div>
</div>

<!-- Graphs & Actionables Row -->
<div class="row mb-4">
    <!-- Cash Flow Trend Chart -->
    <div class="col-lg-8 mb-4 mb-lg-0">
        <div class="card-premium p-4 h-100">
            <h6 class="font-weight-600 mb-3 text-teal"><i class="bi bi-bar-chart-line"></i> Tren Arus Kas Bulanan (Juli - Juni)</h6>
            <div id="cashflow-chart" style="min-height: 280px;"></div>
        </div>
    </div>

    <!-- Expense Distribution Donut Chart -->
    <div class="col-lg-4">
        <div class="card-premium p-4 h-100">
            <h6 class="font-weight-600 mb-3 text-teal"><i class="bi bi-pie-chart"></i> Distribusi Pengeluaran</h6>
            <div id="expense-donut-chart" style="min-height: 280px;"></div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Activities Table -->
    <div class="col-lg-8 mb-4 mb-lg-0">
        <div class="card-premium p-4 h-100">
            <h6 class="font-weight-600 mb-3 text-teal border-bottom pb-2"><i class="bi bi-clock-history"></i> Transaksi Keuangan Terakhir</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle small mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Tipe Kas</th>
                            <th>Keterangan / Transaksi</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $tx)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($tx['date'])->format('d M Y') }}</td>
                            <td>
                                @if($tx['type'] === 'Pemasukan')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Masuk</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Keluar</span>
                                @endif
                            </td>
                            <td>{{ $tx['description'] }}</td>
                            <td class="text-end font-weight-600 {{ $tx['type'] === 'Pemasukan' ? 'text-success' : 'text-danger' }}">
                                {{ $tx['type'] === 'Pemasukan' ? '+' : '-' }} Rp {{ number_format($tx['amount'], 0, ',', '.') }}
                            </td>
                            <td class="text-end">
                                <a href="{{ $tx['route'] }}" class="btn btn-light btn-sm" style="font-size: 0.75rem;">
                                    Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-3 text-muted">Belum ada catatan aktivitas transaksi dalam periode ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Special Attention / High Arrears Students -->
    <div class="col-lg-4">
        <div class="card-premium p-4 h-100 d-flex flex-column">
            <h6 class="font-weight-600 mb-3 text-warning"><i class="bi bi-exclamation-triangle"></i> Perhatian Khusus (Tunggakan Terbesar)</h6>
            
            <div class="list-group list-group-flush flex-grow-1">
                @forelse($attentionList as $att)
                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="d-block small">{{ $att['name'] }}</strong>
                        <span class="badge bg-secondary" style="font-size: 0.65rem;">Kelompok {{ $att['group_name'] }}</span>
                    </div>
                    <div class="text-end">
                        <span class="text-danger font-weight-600 d-block small">Rp {{ number_format($att['amount'], 0, ',', '.') }}</span>
                        <a href="{{ route('payments.create', ['student_id' => $att['student_id']]) }}" class="btn btn-sm btn-outline-success p-0 px-2 mt-1" style="font-size: 0.65rem;">
                            Bayar
                        </a>
                    </div>
                </div>
                @empty
                <div class="text-center py-5 text-muted my-auto">
                    <i class="bi bi-emoji-smile fs-2 text-success d-block mb-2"></i>
                    Semua siswa tertib membayar SPP & Biaya Tahunan.
                </div>
                @endforelse
            </div>
            
            @if(count($attentionList) > 0)
            <div class="text-center mt-3 pt-2 border-top">
                <a href="{{ route('reports.arrears') }}" class="small text-decoration-none">Lihat Semua Tunggakan <i class="bi bi-arrow-right"></i></a>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Load ApexCharts from CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    // 1. Chart Rendering Config (Bar Chart: Income vs Expense)
    var incomeData = @json($monthlyIncome);
    var outcomeData = @json($monthlyOutcome);

    var options = {
        series: [{
            name: 'Pemasukan Kas',
            data: incomeData
        }, {
            name: 'Pengeluaran Kas',
            data: outcomeData
        }],
        chart: {
            type: 'bar',
            height: 280,
            fontFamily: 'Inter, sans-serif',
            toolbar: {
                show: false
            }
        },
        colors: ['#065f46', '#ef4444'], // Brand Green & Red
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 4,
            },
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: ['Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
        },
        yaxis: {
            labels: {
                formatter: function (val) {
                    return "Rp " + val.toLocaleString('id-ID');
                }
            }
        },
        fill: {
            opacity: 1
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return "Rp " + val.toLocaleString('id-ID');
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
        }
    };

    var chart = new ApexCharts(document.querySelector("#cashflow-chart"), options);
    chart.render();

    // Donut Chart for Expense Distribution by Category
    var expenseLabels = @json($expenseLabels);
    var expenseValues = @json($expenseValues);

    var donutOptions = {
        series: expenseValues,
        labels: expenseLabels,
        chart: {
            type: 'donut',
            height: 280,
            fontFamily: 'Inter, sans-serif'
        },
        noData: {
            text: 'Belum ada data pengeluaran kas',
            align: 'center',
            verticalAlign: 'middle',
            style: {
                color: '#64748b',
                fontSize: '12px',
                fontFamily: 'Inter, sans-serif'
            }
        },
        colors: ['#065f46', '#eab308', '#3b82f6', '#ef4444', '#10b981', '#6366f1', '#f97316'],
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }],
        legend: {
            position: 'bottom'
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return "Rp " + val.toLocaleString('id-ID');
                }
            }
        }
    };

    var donutChart = new ApexCharts(document.querySelector("#expense-donut-chart"), donutOptions);
    donutChart.render();

    // 2. Filter options handling
    function updateGroupOptions() {
        var levelId = document.getElementById('filter_level_id').value;
        var groupSelect = document.getElementById('filter_group_id');
        var options = groupSelect.options;
        
        groupSelect.value = "";
        
        for (var i = 0; i < options.length; i++) {
            var opt = options[i];
            if (opt.value === "") {
                opt.style.display = "block";
                continue;
            }
            
            var optLevelId = opt.getAttribute('data-level-id');
            if (levelId === "" || optLevelId === levelId) {
                opt.style.display = "block";
            } else {
                opt.style.display = "none";
            }
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        updateGroupOptions();
        var reqGroup = "{{ request('student_group_id') }}";
        if (reqGroup) {
            document.getElementById('filter_group_id').value = reqGroup;
        }
    });
</script>
@endsection
