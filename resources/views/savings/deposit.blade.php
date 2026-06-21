@extends('layouts.savings')

@section('title', 'Setor Tabungan')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        <div class="card-premium border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar bg-success bg-opacity-10 text-success rounded p-2 me-3">
                        <i class="bi bi-box-arrow-in-down fs-4"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-700">Setor Tabungan</h5>
                        <p class="text-muted mb-0 small">Masukkan setoran tabungan untuk banyak siswa sekaligus.</p>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <form action="{{ route('savings.store-bulk') }}" method="POST" id="bulkDepositForm">
                    @csrf
                    <input type="hidden" name="type" value="Deposit">

                    <div class="row mb-4 align-items-end">
                        <div class="col-md-3">
                            <label for="transaction_date" class="form-label font-weight-600">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="transaction_date" name="transaction_date" value="{{ old('transaction_date', date('Y-m-d')) }}" required>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="filter_level" class="form-label font-weight-600">Jenjang</label>
                            <select class="form-select" id="filter_level" onchange="updateGroupOptions()">
                                <option value="">-- Pilih Jenjang --</option>
                                @foreach($levels as $lvl)
                                    <option value="{{ $lvl->id }}">{{ $lvl->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter_group" class="form-label font-weight-600">Kelompok Kelas</label>
                            <select class="form-select" id="filter_group">
                                <option value="">-- Pilih Kelompok --</option>
                                @foreach($levels as $lvl)
                                    @foreach($lvl->groups as $grp)
                                        <option value="{{ $grp->id }}" data-level-id="{{ $lvl->id }}" style="display:none;">{{ $grp->name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-grid">
                            <button type="button" class="btn btn-primary" id="btn-auto-populate">
                                <i class="bi bi-list-check me-1"></i> Tampilkan Sekelas
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive mb-4" style="overflow: visible;">
                        <table class="table table-borderless align-middle" style="min-width: 800px;">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th width="5%" class="rounded-start text-center">#</th>
                                    <th width="35%">Nama Siswa <span class="text-danger">*</span></th>
                                    <th width="25%">Nominal (Rp) <span class="text-danger">*</span></th>
                                    <th width="25%">Catatan (Opsional)</th>
                                    <th width="10%" class="rounded-end text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="rows-container">
                                <!-- Rows will be appended here -->
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <button type="button" class="btn btn-outline-success border-dashed" id="btn-add-row">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Baris
                        </button>
                        
                        <div class="text-end">
                            <h6 class="text-muted mb-1">Total Setoran:</h6>
                            <h4 class="text-success font-weight-700 mb-0" id="total-amount-display">Rp 0</h4>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('savings.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-success" id="btn-submit">
                            <i class="bi bi-check-circle me-1"></i> Simpan Setoran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Template for a new row (Hidden) -->
<template id="row-template">
    <tr class="transaction-row border-bottom border-light">
        <td class="text-center text-muted row-number font-weight-600">1</td>
        <td>
            <select class="form-select student-select" name="students[{INDEX}][id]">
                <option value="">-- Cari Siswa --</option>
                @foreach($students as $student)
                    @php
                        $groupName = $student->enrollments->first() ? $student->enrollments->first()->studentGroup->name : '';
                    @endphp
                    <option value="{{ $student->id }}">{{ $student->name }} ({{ $groupName }})</option>
                @endforeach
            </select>
        </td>
        <td>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0">Rp</span>
                <input type="text" class="form-control border-start-0 amount-input" placeholder="0" oninput="formatRupiah(this); calculateTotal()">
                <input type="hidden" class="amount-hidden" name="students[{INDEX}][amount]">
            </div>
        </td>
        <td>
            <input type="text" class="form-control" name="students[{INDEX}][notes]" placeholder="Catatan singkat...">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" onclick="removeRow(this)" title="Hapus Baris">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

<style>
    /* Styling for the dashed button */
    .border-dashed {
        border-style: dashed !important;
        border-width: 2px !important;
    }
    
    /* Ensure Tom Select dropdowns show above table wrapper */
    .ts-dropdown {
        z-index: 1055 !important;
    }
</style>

<script>
    let rowIndex = 0;
    const allStudents = @json($students);

    document.addEventListener("DOMContentLoaded", function() {
        // Add initial row
        addRow();

        document.getElementById('btn-add-row').addEventListener('click', function() {
            addRow();
        });
        
        document.getElementById('btn-auto-populate').addEventListener('click', function() {
            const groupId = document.getElementById('filter_group').value;
            if(!groupId) {
                alert('Silakan pilih kelompok kelas terlebih dahulu.');
                return;
            }

            const classStudents = allStudents.filter(s => {
                if(s.enrollments && s.enrollments.length > 0) {
                    return s.enrollments[0].student_group_id == groupId;
                }
                return false;
            });

            if(classStudents.length === 0) {
                alert('Tidak ada siswa aktif di kelompok kelas ini.');
                return;
            }

            if(confirm('Aksi ini akan menghapus baris kosong saat ini dan memunculkan ' + classStudents.length + ' siswa dari kelas yang dipilih. Lanjutkan?')) {
                document.getElementById('rows-container').innerHTML = '';
                rowIndex = 0;
                
                classStudents.forEach(student => {
                    addRow(student.id);
                });
            }
        });

        document.getElementById('bulkDepositForm').addEventListener('submit', function(e) {
            // Check if there are valid rows with amounts
            let hasValidAmount = false;
            document.querySelectorAll('.amount-hidden').forEach(input => {
                if(input.value && parseFloat(input.value) > 0) hasValidAmount = true;
            });

            if(!hasValidAmount) {
                e.preventDefault();
                alert('Silakan isi nominal setidaknya pada satu siswa.');
                return false;
            }
            
            // Disable button to prevent double submit
            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...';
        });
    });

    function updateGroupOptions() {
        const levelId = document.getElementById('filter_level').value;
        const groupSelect = document.getElementById('filter_group');
        
        Array.from(groupSelect.options).forEach(opt => {
            if (opt.value === "") {
                opt.style.display = 'block';
                return;
            }
            if (opt.getAttribute('data-level-id') === levelId) {
                opt.style.display = 'block';
            } else {
                opt.style.display = 'none';
            }
        });
        groupSelect.value = "";
    }

    function addRow(preselectedStudentId = null) {
        const container = document.getElementById('rows-container');
        const template = document.getElementById('row-template').innerHTML;
        
        // Replace {INDEX} placeholder with actual index
        const html = template.replace(/\{INDEX\}/g, rowIndex);
        
        // Append HTML
        container.insertAdjacentHTML('beforeend', html);
        
        // Get the newly added row (last one)
        const newRow = container.lastElementChild;
        
        // Update row numbers
        updateRowNumbers();
        
        // Initialize TomSelect on the new select element
        const selectEl = newRow.querySelector('.student-select');
        const tom = new TomSelect(selectEl, {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            },
            placeholder: "-- Cari Siswa --"
        });
        
        if (preselectedStudentId) {
            tom.setValue(preselectedStudentId);
        }
        
        rowIndex++;
    }

    function removeRow(button) {
        const row = button.closest('tr');
        row.remove();
        updateRowNumbers();
        calculateTotal();
    }

    function updateRowNumbers() {
        const rows = document.querySelectorAll('.transaction-row');
        rows.forEach((row, index) => {
            row.querySelector('.row-number').innerText = index + 1;
        });
    }

    function formatRupiah(input) {
        // Remove non-digit chars
        let value = input.value.replace(/[^0-9]/g, '');
        
        // Update hidden input with raw value
        const hiddenInput = input.closest('.input-group').querySelector('.amount-hidden');
        hiddenInput.value = value;
        
        // Format display value
        if (value) {
            input.value = new Intl.NumberFormat('id-ID').format(value);
        } else {
            input.value = '';
        }
    }

    function calculateTotal() {
        let total = 0;
        const hiddenInputs = document.querySelectorAll('.amount-hidden');
        
        hiddenInputs.forEach(input => {
            if (input.value) {
                total += parseFloat(input.value);
            }
        });
        
        document.getElementById('total-amount-display').innerText = 
            'Rp ' + new Intl.NumberFormat('id-ID').format(total);
    }
</script>
@endsection
