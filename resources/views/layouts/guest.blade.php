<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'PAUD Al Marjan') }}</title>

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body.login-bg {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--sidebar-bg) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }
        .login-card {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            max-width: 420px;
            width: 100%;
            padding: 40px 30px;
        }
        .school-logo-img {
            max-height: 80px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body class="login-bg">
    <div class="login-card">
        <div class="text-center mb-4">
            <img src="{{ asset('images/logo.png') }}" alt="Logo Al-Marjan" class="school-logo-img img-fluid">
            <h4 class="font-weight-700 text-dark mb-1">Keuangan Al Marjan</h4>
            <p class="text-muted small">Silakan masuk menggunakan akun Anda</p>
        </div>

        {{ $slot }}
    </div>
</body>
</html>
