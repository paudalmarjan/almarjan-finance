@extends('layouts.parent')

@section('title', 'Ganti PIN')

@section('content')
<div class="mb-3">
    <a href="{{ route('wali.dashboard') }}" class="btn btn-sm btn-light rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card-premium p-4">
    <div class="mb-4">
        <h6 class="font-weight-600 mb-1"><i class="bi bi-shield-lock text-teal me-2"></i> Ganti PIN Rahasia</h6>
        <p class="text-muted small mb-0">Pastikan Anda menggunakan PIN yang mudah diingat namun sulit ditebak oleh orang lain.</p>
    </div>

    <form action="{{ route('wali.change-pin.post') }}" method="POST">
        @csrf
        
        <div class="mb-3">
            <label for="old_pin" class="form-label font-weight-500 small">PIN Lama</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                <input type="password" class="form-control border-start-0 ps-0 @error('old_pin') is-invalid @enderror" id="old_pin" name="old_pin" required>
            </div>
            @error('old_pin')
                <div class="small text-danger mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="new_pin" class="form-label font-weight-500 small">PIN Baru</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-key text-muted"></i></span>
                <input type="password" class="form-control border-start-0 ps-0 @error('new_pin') is-invalid @enderror" id="new_pin" name="new_pin" placeholder="Minimal 4 digit" required>
            </div>
            @error('new_pin')
                <div class="small text-danger mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="new_pin_confirmation" class="form-label font-weight-500 small">Ulangi PIN Baru</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-key-fill text-muted"></i></span>
                <input type="password" class="form-control border-start-0 ps-0" id="new_pin_confirmation" name="new_pin_confirmation" placeholder="Ketik ulang PIN baru" required>
            </div>
        </div>

        <div class="d-grid mt-2">
            <button type="submit" class="btn btn-primary-custom py-2" style="border-radius: 10px;">
                <i class="bi bi-check-circle me-1"></i> Simpan PIN Baru
            </button>
        </div>
    </form>
</div>
@endsection
