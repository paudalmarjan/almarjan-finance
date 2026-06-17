<x-guest-layout>
    <!-- Session Status -->
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show card-premium p-3 border-start border-success border-4 mb-4" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label font-weight-500">Alamat Email</label>
            <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus placeholder="nama@email.com">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label font-weight-500">Kata Sandi</label>
            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required placeholder="Masukkan kata sandi">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
            <label class="form-check-label text-muted small" for="remember_me">
                Ingat Saya
            </label>
        </div>

        <!-- Action Buttons -->
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary-custom py-2">
                Masuk <i class="bi bi-box-arrow-in-right ms-1"></i>
            </button>
        </div>

        @if (Route::has('password.request'))
            <div class="text-center mt-3">
                <a class="text-muted text-decoration-none small" href="{{ route('password.request') }}">
                    Lupa kata sandi?
                </a>
            </div>
        @endif
    </form>
</x-guest-layout>
