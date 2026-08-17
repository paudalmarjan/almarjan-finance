@extends('layouts.app')

@section('title', 'Catat Pemasukan Lain-lain')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1 font-weight-600">Catat Pemasukan Kas (Non-Siswa)</h5>
        <p class="helper-text mb-0">Pencatatan dana BOP PAUD, donasi/infaq, penjualan formulir, dan pemasukan non-siswa lainnya.</p>
    </div>
    <a href="{{ route('incomes.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card-premium p-4">
            <form action="{{ route('incomes.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="mb-3">
                    <label for="income_category_id" class="form-label small font-weight-500">Kategori Pemasukan <span class="text-danger">*</span></label>
                    <select class="form-select @error('income_category_id') is-invalid @enderror" name="income_category_id" id="income_category_id" required>
                        <option value="">-- Pilih Kategori Pemasukan --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('income_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('income_category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="income_date" class="form-label small font-weight-500">Tanggal Terima <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('date') is-invalid @enderror" name="date" id="income_date" value="{{ old('date', date('Y-m-d')) }}" required>
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="amount" class="form-label small font-weight-500">Nominal Pemasukan (Rupiah) <span class="text-danger">*</span></label>
                        <div class="input-group @error('amount') is-invalid @enderror">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" name="amount" id="amount" value="{{ old('amount') }}" placeholder="500000" required>
                        </div>
                        @error('amount')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="source" class="form-label small font-weight-500">Sumber Pemasukan / Dari Siapa <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('source') is-invalid @enderror" name="source" id="source" value="{{ old('source') }}" placeholder="Contoh: Dinas Pendidikan / H. Ahmad / Orang Tua Siswa" required>
                    @error('source')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label small font-weight-500">Keterangan / Catatan Tambahan (Opsional)</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" id="notes" rows="3" placeholder="Tulis rincian atau peruntukan dana pemasukan ini...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="attachment" class="form-label small font-weight-500">Unggah File Bukti / Kwitansi Terima (Opsional)</label>
                    <input type="file" class="form-control @error('attachment') is-invalid @enderror" name="attachment" id="attachment" accept=".jpg, .jpeg, .png, .pdf, .doc, .docx">
                    <span class="helper-text">Format didukung: JPG, PNG, PDF, Word (doc/docx) (Maks. 10MB)</span>
                    @error('attachment')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid gap-2 border-top pt-3 mt-4">
                    <button type="submit" class="btn btn-success py-2" style="background-color: #059669; border-color: #059669;">
                        <i class="bi bi-save me-1"></i> Simpan Catatan Pemasukan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
