@extends('layouts.app')

@section('title', 'Tambah Siswa Baru')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1 font-weight-600">Registrasi Siswa Baru</h5>
        <div class="ay-badge">
            <i class="bi bi-calendar3"></i> Pendaftaran untuk TA: {{ $selectedYear->name }}
        </div>
    </div>
    <a href="{{ route('students.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-premium p-4">
            <form action="{{ route('students.store') }}" method="POST">
                @csrf
                
                <h6 class="font-weight-600 mb-3 border-bottom pb-2 text-teal"><i class="bi bi-person-fill"></i> Biodata Siswa</h6>
                
                <div class="row mb-3">
                    <div class="col-md-8 mb-3 mb-md-0">
                        <label for="name" class="form-label small font-weight-500">Nama Lengkap Siswa</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name') }}" placeholder="Masukkan nama lengkap anak" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="nickname" class="form-label small font-weight-500">Nama Panggilan (Opsional)</label>
                        <input type="text" class="form-control @error('nickname') is-invalid @enderror" name="nickname" id="nickname" value="{{ old('nickname') }}" placeholder="Panggilan akrab">
                        @error('nickname')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="parent_name" class="form-label small font-weight-500">Nama Wali / Orang Tua (Opsional)</label>
                        <input type="text" class="form-control @error('parent_name') is-invalid @enderror" name="parent_name" id="parent_name" value="{{ old('parent_name') }}" placeholder="Nama ibu atau ayah">
                        @error('parent_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="phone_number" class="form-label small font-weight-500">Nomor Telepon (WhatsApp) (Opsional)</label>
                        <input type="text" class="form-control @error('phone_number') is-invalid @enderror" name="phone_number" id="phone_number" value="{{ old('phone_number') }}" placeholder="Contoh: 0812xxxxxxxx">
                        <span class="helper-text">Digunakan untuk keperluan konfirmasi pembayaran.</span>
                        @error('phone_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <h6 class="font-weight-600 mb-3 mt-4 border-bottom pb-2 text-teal"><i class="bi bi-building"></i> Penempatan Kelas & Potongan</h6>

                <div class="row mb-4">
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label for="enrollment_type" class="form-label small font-weight-500">Tipe Pendaftaran</label>
                        <select class="form-select @error('enrollment_type') is-invalid @enderror" name="enrollment_type" id="enrollment_type" required>
                            <option value="New" {{ old('enrollment_type', 'New') === 'New' ? 'selected' : '' }}>Siswa Baru</option>
                            <option value="Returning" {{ old('enrollment_type') === 'Returning' ? 'selected' : '' }}>Siswa Lama (Daftar Ulang)</option>
                        </select>
                        <span class="helper-text">Menentukan alokasi tagihan tahunan.</span>
                        @error('enrollment_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 mb-3 mb-md-0">
                        <label for="level_id" class="form-label small font-weight-500">Pilih Jenjang</label>
                        <select class="form-select @error('level_id') is-invalid @enderror" name="level_id" id="level_id" onchange="updateGroupOptions()" required>
                            <option value="">-- Pilih Jenjang --</option>
                            @foreach($levels as $lvl)
                                <option value="{{ $lvl->id }}" {{ old('level_id') == $lvl->id ? 'selected' : '' }}>{{ $lvl->name }}</option>
                            @endforeach
                        </select>
                        @error('level_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label for="student_group_id" class="form-label small font-weight-500">Pilih Kelompok Kelas</label>
                        <select class="form-select @error('student_group_id') is-invalid @enderror" name="student_group_id" id="student_group_id" required>
                            <option value="">-- Pilih Kelompok --</option>
                            @foreach($levels as $lvl)
                                @foreach($lvl->groups as $grp)
                                    <option value="{{ $grp->id }}" data-level-id="{{ $lvl->id }}" {{ old('student_group_id') == $grp->id ? 'selected' : '' }} style="display:none;">
                                        {{ $grp->name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                        @error('student_group_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="discount_category_id" class="form-label small font-weight-500">Kategori Diskon (Opsional)</label>
                        <select class="form-select @error('discount_category_id') is-invalid @enderror" name="discount_category_id" id="discount_category_id">
                            <option value="">-- Tanpa Potongan --</option>
                            @foreach($discountCategories as $dc)
                                <option value="{{ $dc->id }}" {{ old('discount_category_id') == $dc->id ? 'selected' : '' }}>
                                    {{ $dc->name }} (Potong {{ (int)$dc->percentage }}%)
                                </option>
                            @endforeach
                        </select>
                        <span class="helper-text">Diskon akan memotong biaya tahunan secara otomatis.</span>
                        @error('discount_category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>



                <div class="d-grid gap-2 border-top pt-3 mt-4">
                    <button type="submit" class="btn btn-primary-custom py-2">
                        <i class="bi bi-save me-1"></i> Daftarkan Siswa & Buat Tagihan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function updateGroupOptions() {
        var levelId = document.getElementById('level_id').value;
        var groupSelect = document.getElementById('student_group_id');
        var options = groupSelect.options;
        
        // Reset selected group
        groupSelect.value = "";
        
        for (var i = 0; i < options.length; i++) {
            var opt = options[i];
            if (opt.value === "") {
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
    
    // Run once on load if there's old input
    document.addEventListener("DOMContentLoaded", function() {
        if (document.getElementById('level_id').value !== "") {
            updateGroupOptions();
            // Restore group selection
            var oldGroup = "{{ old('student_group_id') }}";
            if (oldGroup) {
                document.getElementById('student_group_id').value = oldGroup;
            }
        }
    });
</script>
@endsection
