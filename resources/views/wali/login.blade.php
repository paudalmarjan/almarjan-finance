@extends('layouts.parent')

@section('title', 'Login')

@section('content')
<div class="card-premium p-4 mt-2">
    <div class="text-center mb-4">
        <h6 class="font-weight-600 mb-1">Selamat Datang</h6>
        <p class="text-muted small mb-0">Silakan masuk untuk melihat data keuangan dan tabungan putra/putri Anda.</p>
    </div>

    <form action="{{ route('wali.login.post') }}" method="POST">
        @csrf
        
        <div class="mb-3">
            <label for="nis" class="form-label font-weight-500 small">Nomor Induk Siswa (NIS)</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" id="nis" name="nis" placeholder="Contoh: AM240001" required autocomplete="off">
            </div>
        </div>

        <div class="mb-4">
            <label for="pin" class="form-label font-weight-500 small">PIN Rahasia</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                <input type="password" class="form-control border-start-0 ps-0" id="pin" name="pin" placeholder="Masukkan 6 digit PIN" required>
            </div>
            <div class="form-text small mt-1">
                <i class="bi bi-info-circle"></i> PIN standar dari sekolah adalah <strong>123456</strong>.
            </div>
        </div>

        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary-custom py-2" style="border-radius: 10px;">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Portal
            </button>
        </div>
    </form>
</div>
@endsection
