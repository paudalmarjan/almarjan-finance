@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        
        <!-- Success Alert Banners -->
        @if (session('status') === 'profile-updated')
            <div class="alert alert-success alert-dismissible fade show card-premium p-3 border-start border-success border-4 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill text-success fs-4 me-2"></i>
                    <div><strong>Berhasil!</strong> Informasi profil Anda telah diperbarui.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('status') === 'password-updated')
            <div class="alert alert-success alert-dismissible fade show card-premium p-3 border-start border-success border-4 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill text-success fs-4 me-2"></i>
                    <div><strong>Berhasil!</strong> Kata sandi Anda telah diperbarui.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show card-premium p-3 border-start border-danger border-4 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-octagon-fill text-danger fs-4 me-2"></i>
                    <div><strong>Gagal!</strong> {{ session('error') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Card 1: Profile Information -->
        <div class="card-premium p-4 mb-4">
            <h5 class="mb-3 font-weight-600"><i class="bi bi-person-circle text-primary me-2"></i> Informasi Profil</h5>
            <p class="helper-text mb-4">Informasi profil akun dan alamat email Anda.</p>

            <form method="post" action="{{ route('profile.update') }}">
                @csrf
                @method('patch')

                <div class="mb-3">
                    <label for="name" class="form-label small font-weight-500">Nama Lengkap</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" {{ $user->isTeacher() ? 'readonly' : '' }}>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="email" class="form-label small font-weight-500">Alamat Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username" {{ $user->isTeacher() ? 'readonly' : '' }}>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="badge bg-secondary py-2 px-3">Hak Akses: {{ $user->isAdmin() ? 'Administrator' : 'Guru / Staf' }}</span>
                    </div>
                    @if (!$user->isTeacher())
                        <button type="submit" class="btn btn-primary-custom btn-sm px-4">Simpan Profil</button>
                    @else
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Nama & email dikunci. Hubungi Admin jika ingin mengubah.</small>
                    @endif
                </div>
            </form>
        </div>

        <!-- Card 2: Update Password -->
        <div class="card-premium p-4">
            <h5 class="mb-3 font-weight-600"><i class="bi bi-key-fill text-primary me-2"></i> Perbarui Kata Sandi</h5>
            <p class="helper-text mb-4">Pastikan akun Anda menggunakan kata sandi yang kuat dan acak untuk menjaga keamanan.</p>

            <form method="post" action="{{ route('password.update') }}">
                @csrf
                @method('put')

                <div class="mb-3">
                    <label for="current_password" class="form-label small font-weight-500">Kata Sandi Saat Ini</label>
                    <input type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" id="current_password" name="current_password" autocomplete="current-password" required>
                    @error('current_password', 'updatePassword')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label small font-weight-500">Kata Sandi Baru</label>
                    <input type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" id="password" name="password" autocomplete="new-password" required>
                    @error('password', 'updatePassword')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label small font-weight-500">Konfirmasi Kata Sandi Baru</label>
                    <input type="password" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                    @error('password_confirmation', 'updatePassword')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary-custom btn-sm px-4">Perbarui Password</button>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection
