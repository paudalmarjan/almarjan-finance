@extends('layouts.app')

@section('title', 'Detail & Edit Siswa')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1 font-weight-600">Detail & Sunting Siswa</h5>
        <div class="ay-badge">
            <i class="bi bi-calendar3"></i> Tahun Ajaran: {{ $enrollment->academicYear->name }}
        </div>
    </div>
    <a href="{{ route('students.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<form action="{{ route('students.update', $student->id) }}" method="POST">
    @csrf
    @method('PUT')
    
    <div class="row">
        <!-- Left Side: Basic Student Profile -->
        <div class="col-lg-6 mb-4">
            <div class="card-premium p-4 h-100">
                <h6 class="font-weight-600 mb-3 border-bottom pb-2 text-teal"><i class="bi bi-person-fill"></i> Data Diri Siswa</h6>
                
                <div class="mb-3">
                    <label class="form-label small font-weight-500 text-muted">Nomor Induk Siswa (NIS)</label>
                    <input type="text" class="form-control bg-light" value="{{ $student->nis }}" readonly>
                    <span class="helper-text">NIS dibuat otomatis oleh sistem.</span>
                </div>

                <div class="row mb-3">
                    <div class="col-md-8 mb-3 mb-md-0">
                        <label for="name" class="form-label small font-weight-500">Nama Lengkap Siswa</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name', $student->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="nickname" class="form-label small font-weight-500">Nama Panggilan (Opsional)</label>
                        <input type="text" class="form-control @error('nickname') is-invalid @enderror" name="nickname" id="nickname" value="{{ old('nickname', $student->nickname) }}">
                        @error('nickname')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="parent_name" class="form-label small font-weight-500">Nama Wali / Orang Tua (Opsional)</label>
                        <input type="text" class="form-control @error('parent_name') is-invalid @enderror" name="parent_name" id="parent_name" value="{{ old('parent_name', $student->parent_name) }}">
                        @error('parent_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="phone_number" class="form-label small font-weight-500">Nomor Telepon (WhatsApp) (Opsional)</label>
                        <input type="text" class="form-control @error('phone_number') is-invalid @enderror" name="phone_number" id="phone_number" value="{{ old('phone_number', $student->phone_number) }}">
                        @error('phone_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="filter_level_id" class="form-label small font-weight-500">Jenjang</label>
                        <input type="text" class="form-control bg-light" value="{{ $enrollment->studentGroup->level->name }}" readonly>
                        <span class="helper-text">Jenjang tidak dapat diubah di tengah tahun ajaran.</span>
                    </div>
                    <div class="col-md-6">
                        <label for="student_group_id" class="form-label small font-weight-500">Pilih Kelompok Kelas</label>
                        <select class="form-select @error('student_group_id') is-invalid @enderror" name="student_group_id" id="student_group_id" required>
                            @foreach($levels as $lvl)
                                @if($lvl->id == $enrollment->studentGroup->level_id)
                                    @foreach($lvl->groups as $grp)
                                        <option value="{{ $grp->id }}" {{ old('student_group_id', $enrollment->student_group_id) == $grp->id ? 'selected' : '' }}>
                                            {{ $grp->name }}
                                        </option>
                                    @endforeach
                                @endif
                            @endforeach
                        </select>
                        @error('student_group_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="enrollment_type" class="form-label small font-weight-500">Tipe Pendaftaran</label>
                        <select class="form-select @error('enrollment_type') is-invalid @enderror" name="enrollment_type" id="enrollment_type" {{ $hasPayments ? 'disabled' : '' }}>
                            <option value="New" {{ old('enrollment_type', $enrollment->enrollment_type) === 'New' ? 'selected' : '' }}>Siswa Baru</option>
                            <option value="Returning" {{ old('enrollment_type', $enrollment->enrollment_type) === 'Returning' ? 'selected' : '' }}>Siswa Lama (Daftar Ulang)</option>
                        </select>
                        @if($hasPayments)
                            <input type="hidden" name="enrollment_type" value="{{ $enrollment->enrollment_type }}">
                            <span class="helper-text text-warning"><i class="bi bi-exclamation-triangle-fill"></i> Tipe pendaftaran terkunci karena ada transaksi pembayaran.</span>
                        @else
                            <span class="helper-text">Mengubah tipe akan membuat ulang tagihan tahunan siswa.</span>
                        @endif
                        @error('enrollment_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="discount_category_id" class="form-label small font-weight-500">
                            Kategori Diskon
                            @if($enrollment->discountCategory)
                                <span class="badge bg-secondary text-white ms-1" style="font-size: 0.7rem;">Aktif: {{ (int)$enrollment->discount_percentage }}%</span>
                            @endif
                        </label>
                        <select class="form-select @error('discount_category_id') is-invalid @enderror" name="discount_category_id" id="discount_category_id">
                            <option value="">-- Tanpa Potongan --</option>
                            @foreach($discountCategories as $dc)
                                <option value="{{ $dc->id }}" {{ old('discount_category_id', $enrollment->discount_category_id) == $dc->id ? 'selected' : '' }}>
                                    {{ $dc->name }} (Tarif Baru: {{ (int)$dc->percentage }}%)
                                </option>
                            @endforeach
                        </select>
                        @error('discount_category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="status" class="form-label small font-weight-500">Status Keaktifan</label>
                        <select class="form-select @error('status') is-invalid @enderror" name="status" id="status" required>
                            <option value="Active" {{ old('status', $student->status) === 'Active' ? 'selected' : '' }}>Aktif</option>
                            <option value="Graduated" {{ old('status', $student->status) === 'Graduated' ? 'selected' : '' }}>Lulus</option>
                            <option value="Not Continuing" {{ old('status', $student->status) === 'Not Continuing' ? 'selected' : '' }}>Tidak Melanjutkan</option>
                            <option value="Transferred" {{ old('status', $student->status) === 'Transferred' ? 'selected' : '' }}>Mutasi Keluar</option>
                        </select>
                    </div>
                </div>


            </div>
        </div>

        <!-- Right Side: Annual Fee Customization Exclusions -->
        <div class="col-lg-6 mb-4">
            <div class="card-premium p-4 h-100 d-flex flex-column">
                <h6 class="font-weight-600 mb-3 border-bottom pb-2 text-teal"><i class="bi bi-shield-exclamation"></i> Kustomisasi Biaya Pendidikan Tahunan</h6>
                <p class="helper-text mb-3">Centang komponen di bawah ini untuk **mengeluarkan/mengecualikan** siswa dari kewajiban pembayaran tersebut (misal: jika orang tua sudah membeli seragam sendiri di luar sekolah).</p>
                
                <div class="table-responsive flex-grow-1">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">Kecualikan</th>
                                <th>Nama Komponen</th>
                                <th class="text-end">Jumlah Tagihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($studentAnnualFees as $fee)
                            <tr>
                                <td class="text-center">
                                    <div class="form-check d-flex justify-content-center">
                                        <input class="form-check-input" type="checkbox" name="exclusions[]" value="{{ $fee->id }}" id="exc_{{ $fee->id }}" {{ $fee->is_excluded ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <label for="exc_{{ $fee->id }}" class="form-label mb-0 font-weight-500 cursor-pointer">
                                        {{ $fee->annualFeeComponent->name }}
                                    </label>
                                </td>
                                <td class="text-end">
                                    @if($fee->is_excluded)
                                        <span class="text-muted text-decoration-line-through">Rp {{ number_format($fee->annualFeeComponent->amount * (1 - ($enrollment->discount_percentage / 100)), 0, ',', '.') }}</span>
                                        <span class="badge bg-danger ms-2">Dikecualikan (Rp 0)</span>
                                    @else
                                        Rp {{ number_format($fee->amount, 0, ',', '.') }}
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Panel -->
    <div class="card-premium p-3 d-flex justify-content-between align-items-center mb-4">
        <div>
            @if($canCancelEnrollment)
                <button type="button" class="btn btn-outline-danger px-4 py-2" onclick="confirmCancelEnrollment()">
                    <i class="bi bi-trash-fill me-1"></i> Batalkan Pendaftaran TA {{ $enrollment->academicYear->name }}
                </button>
            @endif
        </div>
        <button type="submit" class="btn btn-primary-custom px-4 py-2">
            <i class="bi bi-check-lg me-1"></i> Simpan Perubahan Data Siswa
        </button>
    </div>
</form>

@if($canCancelEnrollment)
<form id="cancel-enrollment-form" action="{{ route('students.cancel-enrollment', $student->id) }}" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>

<script>
    function confirmCancelEnrollment() {
        if (confirm("Apakah Anda yakin ingin membatalkan pendaftaran siswa ini untuk Tahun Ajaran {{ $enrollment->academicYear->name }}?\n\nTindakan ini akan menghapus data pendaftaran dan tagihan tahunan siswa untuk tahun ajaran ini. Data diri dasar siswa tidak akan terhapus.")) {
            document.getElementById('cancel-enrollment-form').submit();
        }
    }
</script>
@endif
@endsection
