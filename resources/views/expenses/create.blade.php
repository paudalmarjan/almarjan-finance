@extends('layouts.app')

@section('title', 'Catat Pengeluaran')

@section('content')
<div class="page-header">
    <div>
        <h5 class="page-header-title">Catat Pengeluaran Kas</h5>
        <p class="helper-text mb-0">Catat pengeluaran operasional dan belanja keperluan sekolah.</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('expenses.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card-premium p-4">
            <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="mb-3">
                    <label for="expense_category_id" class="form-label">Kategori Pengeluaran</label>
                    <select class="form-select @error('expense_category_id') is-invalid @enderror" name="expense_category_id" id="expense_category_id" required>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('expense_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('expense_category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="expense_date" class="form-label">Tanggal Pengeluaran</label>
                        <input type="date" class="form-control @error('date') is-invalid @enderror" name="date" id="expense_date" value="{{ old('date', date('Y-m-d')) }}" required>
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="amount" class="form-label">Nominal Pengeluaran (Rupiah)</label>
                        <div class="input-group @error('amount') is-invalid @enderror">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" name="amount" id="amount" value="{{ old('amount') }}" placeholder="150000" required>
                        </div>
                        @error('amount')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Keterangan / Catatan Tambahan (Opsional)</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" id="notes" rows="3" placeholder="Tulis rincian pembelian atau keperluan operasional sekolah...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="attachment" class="form-label">Unggah File Bukti / Nota Belanja <span class="text-danger">*</span></label>
                    <input type="file" class="form-control @error('attachment') is-invalid @enderror" name="attachment" id="attachment" accept=".jpg, .jpeg, .png, .pdf, .doc, .docx" required>
                    <span class="helper-text">Format didukung: JPG, PNG, PDF, Word (doc/docx) (Maks. 10MB)</span>
                    @error('attachment')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid gap-2 border-top pt-3 mt-4">
                    <button type="submit" class="btn btn-danger-custom py-2">
                        <i class="bi bi-save me-1"></i> Simpan Catatan Pengeluaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
