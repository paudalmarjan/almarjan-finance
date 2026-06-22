@extends('layouts.app')

@section('title', 'Catat Pembayaran')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1 font-weight-600">Catat Transaksi Penerimaan</h5>
        <p class="helper-text mb-0">Pilih siswa untuk melihat rincian tagihan dan mencatat pembayaran masuk.</p>
    </div>
    <a href="{{ route('payments.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<!-- Student Selector Card -->
<div class="card-premium p-4 mb-4">
    <form method="GET" action="{{ route('payments.create') }}" id="studentSelectorForm">
        <div class="row">
            <div class="col-md-6 mb-3 mb-md-0">
                <label for="student_group_filter" class="form-label font-weight-600">Filter Kelompok Kelas</label>
                <select class="form-select" name="student_group_id" id="student_group_filter" onchange="clearStudentAndSubmit()">
                    <option value="">-- Semua Kelas --</option>
                    @foreach($levels as $level)
                        <optgroup label="{{ $level->name }}">
                            @foreach($level->groups as $group)
                                <option value="{{ $group->id }}" {{ request('student_group_id') == $group->id ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="student_id_select" class="form-label font-weight-600">Pilih Siswa Aktif</label>
                <select class="form-select select2-enable" name="student_id" id="student_id_select" onchange="this.form.submit()">
                    <option value="">-- Cari Nama Siswa atau NIS --</option>
                    @foreach($students as $se)
                        <option value="{{ $se->student->id }}" {{ $selectedStudent && $selectedStudent->id == $se->student->id ? 'selected' : '' }}>
                            {{ $se->student->name }}{{ $se->student->nickname ? ' (' . $se->student->nickname . ')' : '' }} (NIS: {{ $se->student->nis ?? '-' }}) - Kelas {{ $se->studentGroup->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
</div>

<script>
    function clearStudentAndSubmit() {
        var studentSelect = document.getElementById('student_id_select');
        if (studentSelect) {
            studentSelect.value = "";
        }
        document.getElementById('studentSelectorForm').submit();
    }
</script>

@if($selectedStudent && $enrollment)
<form action="{{ route('payments.store') }}" method="POST" id="paymentForm" onsubmit="return confirmPayment()">
    @csrf
    <input type="hidden" name="student_id" value="{{ $selectedStudent->id }}">

    <div class="row">
        <!-- Left Column: Student Profile & Annual Fee Installments -->
        <div class="col-lg-7 mb-4">
            <!-- Profile Info Mini Card -->
            <div class="card-premium p-3 mb-3 bg-light border-0">
                <div class="row">
                    <div class="col-md-6 mb-2 mb-md-0">
                        <small class="text-muted d-block">Nama Lengkap</small>
                        <strong>{{ $selectedStudent->name }}{{ $selectedStudent->nickname ? ' (' . $selectedStudent->nickname . ')' : '' }}</strong>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <small class="text-muted d-block">NIS</small>
                        <strong>{{ $selectedStudent->nis ?? '-' }}</strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Kelas</small>
                        <strong>{{ $enrollment->studentGroup->name }}</strong>
                    </div>
                </div>
                <hr class="my-2 border-secondary-subtle">
                <div class="row">
                    <div class="col-md-6 mb-2 mb-md-0">
                        <small class="text-muted d-block">Wali Murid</small>
                        <span>
                            @if($selectedStudent->parent_name && $selectedStudent->phone_number)
                                {{ $selectedStudent->parent_name }} ({{ $selectedStudent->phone_number }})
                            @elseif($selectedStudent->parent_name)
                                {{ $selectedStudent->parent_name }}
                            @elseif($selectedStudent->phone_number)
                                {{ $selectedStudent->phone_number }}
                            @else
                                -
                            @endif
                        </span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Kategori Diskon</small>
                        @if($enrollment->discountCategory)
                            <span class="badge bg-info text-dark">{{ $enrollment->discountCategory->name }} (Potongan {{ (int)$enrollment->discount_percentage }}%)</span>
                        @else
                            <span class="text-muted small">Tidak Ada</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Annual Fee Component Details -->
            <div class="card-premium p-4 h-auto">
                <h6 class="font-weight-600 mb-3 text-teal border-bottom pb-2">
                    <i class="bi bi-calendar-check"></i> Biaya Pendidikan Tahunan (Cicilan / Pelunasan)
                </h6>
                <p class="helper-text mb-3">Ketikkan jumlah uang pembayaran pada kolom input untuk komponen biaya tahunan yang ingin dicicil/dilunasi.</p>

                <!-- Auto Allocation FIFO input -->
                <div class="row mb-3 bg-light p-3 border rounded g-3 align-items-center mx-0">
                    <div class="col-md-7 px-0">
                        <label for="auto_allocation_amount" class="form-label small font-weight-600 mb-1 text-teal">Alokasi Pembayaran Cepat (FIFO)</label>
                        <p class="helper-text mb-0" style="font-size: 0.75rem;">Masukkan total nominal pembayaran di sini. Sistem akan otomatis membagi ke komponen di bawah dari atas ke bawah.</p>
                        <span class="badge bg-secondary-subtle text-secondary border mt-2 small" style="font-size: 0.72rem; font-weight: 500;">
                            Total Sisa Tagihan Tahunan: <strong class="text-teal">Rp {{ number_format($annualFees->where('is_excluded', false)->sum('balance'), 0, ',', '.') }}</strong>
                        </span>
                    </div>
                    <div class="col-md-5 px-0">
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="auto_allocation_amount" placeholder="Ketik total nominal..." oninput="distributeAmountFIFO(this.value)">
                        </div>
                        <div class="text-danger small mt-1 d-none" id="fifo_excess_warning" style="font-weight: 500;">
                            <i class="bi bi-exclamation-triangle-fill"></i> Melebihi total sisa tagihan (Rp <span id="fifo_max_amount">0</span>)! Selisih Rp <span id="fifo_excess_amount">0</span> tidak teralokasi.
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Komponen Biaya</th>
                                <th class="text-end">Tagihan Awal</th>
                                <th class="text-end">Terbayar</th>
                                <th class="text-end">Sisa</th>
                                <th style="width: 150px;" class="text-end">Jumlah Bayar (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($annualFees as $fee)
                            <tr>
                                <td>
                                    <div class="font-weight-500">{{ $fee->annualFeeComponent->name }}</div>
                                    @if($fee->is_excluded)
                                        <span class="badge bg-danger mt-1">Dikecualikan</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($fee->is_excluded)
                                        <span class="text-muted text-decoration-line-through">Rp {{ number_format($fee->annualFeeComponent->amount * (1 - ($enrollment->discount_percentage / 100)), 0, ',', '.') }}</span>
                                    @else
                                        Rp {{ number_format($fee->amount, 0, ',', '.') }}
                                    @endif
                                </td>
                                <td class="text-end text-success">
                                    Rp {{ number_format($fee->paid_amount, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-weight-500 text-danger">
                                    Rp {{ number_format($fee->balance, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    @if($fee->is_excluded || $fee->balance <= 0)
                                        <input type="number" class="form-control form-control-sm text-end" value="0" disabled>
                                    @else
                                        <input type="number" class="form-control form-control-sm text-end annual-payment-input" 
                                               name="annual_payments[{{ $fee->id }}]" 
                                               id="annual_fee_{{ $fee->id }}"
                                               data-balance="{{ $fee->balance }}"
                                               data-name="{{ $fee->annualFeeComponent->name }}"
                                               value="0" min="0" max="{{ $fee->balance }}" 
                                               oninput="clearFIFOAndCalculate()">
                                        <div class="text-danger small mt-1 text-end d-none" id="err_{{ $fee->id }}">Melebihi sisa tagihan!</div>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">Belum ada rincian komponen biaya tahunan.</td>
                            </tr>
                            @endforelse


                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column: SPP 12 Months Checklist -->
        <div class="col-lg-5 mb-4">
            <div class="card-premium p-4 h-100">
                <h6 class="font-weight-600 mb-3 text-teal border-bottom pb-2">
                    <i class="bi bi-calendar-event"></i> Pilihan SPP Bulanan (Juli - Juni)
                </h6>
                <p class="helper-text mb-3">Centang bulan SPP yang ingin dibayarkan secara bersamaan.</p>

                <div class="row g-2">
                    @php
                        $months = [
                            1 => 'Juli',
                            2 => 'Agustus',
                            3 => 'September',
                            4 => 'Oktober',
                            5 => 'November',
                            6 => 'Desember',
                            7 => 'Januari',
                            8 => 'Februari',
                            9 => 'Maret',
                            10 => 'April',
                            11 => 'Mei',
                            12 => 'Juni'
                        ];
                    @endphp

                    @foreach($months as $index => $name)
                        <div class="col-6">
                            @if($index === 1)
                                <!-- July SPP is always bundled inside Annual Fee -->
                                <div class="spp-month-card disabled">
                                    <div class="font-weight-600 small">{{ $name }}</div>
                                    <div class="small" style="font-size: 0.65rem;">Ter-bundle Biaya Tahunan</div>
                                </div>
                            @elseif(in_array($index, $paidSppMonths))
                                <!-- Already paid SPP month -->
                                <div class="spp-month-card paid">
                                    <div class="font-weight-600 small"><i class="bi bi-check-circle-fill"></i> {{ $name }}</div>
                                    <div class="small" style="font-size: 0.65rem;">Lunas</div>
                                </div>
                            @else
                                <!-- Selectable SPP month -->
                                <input class="form-check-input spp-checkbox d-none" type="checkbox" name="spp_months[]" 
                                       value="{{ $index }}" id="spp_{{ $index }}" 
                                       onchange="toggleMonthCard(this); calculateTotal();">
                                <label class="spp-month-card unpaid w-100" for="spp_{{ $index }}" id="card_spp_{{ $index }}">
                                    <div class="font-weight-600 small">{{ $name }}</div>
                                    <div class="small text-teal" style="font-size: 0.75rem; font-weight: 500;">
                                        Rp {{ number_format($monthlySppAmount, 0, ',', '.') }}
                                    </div>
                                </label>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Action & Summary Card -->
    <div class="card-premium p-4 mb-4">
        <div class="row align-items-center">
            <div class="col-md-4 mb-3 mb-md-0">
                <label for="payment_date" class="form-label small font-weight-500 text-muted">Tanggal Transaksi</label>
                <input type="date" class="form-control" name="date" id="payment_date" value="{{ date('Y-m-d') }}" required>
            </div>
            
            <div class="col-md-5 mb-3 mb-md-0 text-md-end">
                <div class="text-muted small">Total Kas Masuk (Pemasukan):</div>
                <h3 class="mb-0 font-weight-700 text-success" id="total_display">Rp 0</h3>
            </div>
            
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-primary-custom py-2" id="submitBtn">
                    <i class="bi bi-check-circle me-1"></i> Simpan Transaksi
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    var monthlySpp = parseFloat("{{ $monthlySppAmount }}");

    function toggleMonthCard(checkbox) {
        var cardId = 'card_' + checkbox.id;
        var card = document.getElementById(cardId);
        
        if (checkbox.checked) {
            card.classList.remove('unpaid');
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
            card.classList.add('unpaid');
        }
    }

    function clearFIFOAndCalculate() {
        var autoAllocInput = document.getElementById('auto_allocation_amount');
        if (autoAllocInput) {
            autoAllocInput.value = '';
        }
        var warningDiv = document.getElementById('fifo_excess_warning');
        if (warningDiv) {
            warningDiv.classList.add('d-none');
        }
        calculateTotal();
    }

    function calculateTotal() {
        var total = 0;
        var hasValidationError = false;
        
        // 1. Sum annual payments
        var annualInputs = document.getElementsByClassName('annual-payment-input');
        for (var i = 0; i < annualInputs.length; i++) {
            var input = annualInputs[i];
            var val = parseFloat(input.value) || 0;
            var maxVal = parseFloat(input.getAttribute('data-balance')) || 0;
            var feeId = input.id.replace('annual_fee_', '');
            var errDiv = document.getElementById('err_' + feeId);

            if (val < 0) {
                input.value = 0;
                val = 0;
            }

            if (val > maxVal) {
                hasValidationError = true;
                errDiv.classList.remove('d-none');
                input.classList.add('is-invalid');
            } else {
                errDiv.classList.add('d-none');
                input.classList.remove('is-invalid');
                total += val;
            }
        }

        // 2. Sum checked SPP months
        var checkedSpp = document.querySelectorAll('.spp-checkbox:checked').length;
        total += (checkedSpp * monthlySpp);

        // Check if there is an excess in FIFO allocation
        var autoAllocInput = document.getElementById('auto_allocation_amount');
        if (autoAllocInput && autoAllocInput.value !== '') {
            var autoAllocVal = parseFloat(autoAllocInput.value) || 0;
            var totalSisaAnnual = 0;
            for (var i = 0; i < annualInputs.length; i++) {
                var input = annualInputs[i];
                if (!input.disabled) {
                    totalSisaAnnual += parseFloat(input.getAttribute('data-balance')) || 0;
                }
            }
            if (autoAllocVal > totalSisaAnnual) {
                hasValidationError = true;
            }
        }

        // 3. Display formatted total
        document.getElementById('total_display').innerText = 'Rp ' + total.toLocaleString('id-ID');
        
        // Disable submit button if there are validation errors
        document.getElementById('submitBtn').disabled = hasValidationError;
    }

    function confirmPayment() {
        var totalText = document.getElementById('total_display').innerText;
        if (totalText === 'Rp 0') {
            alert('Jumlah transaksi adalah Rp 0. Silakan isi cicilan biaya tahunan atau centang bulan SPP terlebih dahulu.');
            return false;
        }
        return confirm('Apakah Anda yakin ingin mencatat pembayaran sebesar ' + totalText + ' ini ke sistem?');
    }

    function distributeAmountFIFO(totalPayable) {
        totalPayable = parseFloat(totalPayable) || 0;
        if (totalPayable < 0) {
            totalPayable = 0;
            document.getElementById('auto_allocation_amount').value = 0;
        }

        var inputs = document.getElementsByClassName('annual-payment-input');
        var remaining = totalPayable;
        var totalSisa = 0;

        // Calculate total sisa
        for (var i = 0; i < inputs.length; i++) {
            var input = inputs[i];
            if (input.disabled) continue;
            var balance = parseFloat(input.getAttribute('data-balance')) || 0;
            totalSisa += balance;
        }

        // Handle warning display
        var warningDiv = document.getElementById('fifo_excess_warning');
        var excessSpan = document.getElementById('fifo_excess_amount');
        var maxSpan = document.getElementById('fifo_max_amount');
        if (totalPayable > totalSisa) {
            var excess = totalPayable - totalSisa;
            if (warningDiv && excessSpan) {
                warningDiv.classList.remove('d-none');
                excessSpan.innerText = excess.toLocaleString('id-ID');
                if (maxSpan) {
                    maxSpan.innerText = totalSisa.toLocaleString('id-ID');
                }
            }
        } else {
            if (warningDiv) {
                warningDiv.classList.add('d-none');
            }
        }

        // Distribute up to balance
        for (var i = 0; i < inputs.length; i++) {
            var input = inputs[i];
            if (input.disabled) continue;

            var balance = parseFloat(input.getAttribute('data-balance')) || 0;
            if (balance <= 0) continue;

            if (remaining >= balance) {
                input.value = balance;
                remaining -= balance;
            } else {
                input.value = remaining;
                remaining = 0;
            }
        }

        calculateTotal();
    }
</script>
@endif

@if(!$selectedStudent)
    <div class="card-premium p-5 text-center text-muted">
        <i class="bi bi-search fs-1 mb-3 text-light d-block"></i>
        <span>Pilih siswa aktif terlebih dahulu untuk mencatat transaksi pembayaran.</span>
    </div>
@endif

@endsection
