<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Portal Wali Murid PAUD Al Marjan</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            background-color: var(--bs-gray-100);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .mobile-container {
            width: 100%;
            max-width: 480px;
            min-height: 100vh;
            background-color: #ffffff;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .mobile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: white;
            padding: 1.5rem;
            text-align: center;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            box-shadow: 0 4px 15px rgba(22, 163, 74, 0.2);
            position: relative;
        }
        .mobile-content {
            padding: 1.5rem;
            flex-grow: 1;
        }
        .school-logo {
            width: 60px;
            height: 60px;
            background-color: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .school-logo i {
            color: var(--primary-color);
            font-size: 30px;
        }
        .mobile-footer {
            text-align: center;
            padding: 1rem;
            font-size: 0.75rem;
            color: var(--bs-gray-500);
            background-color: #fafafa;
            border-top: 1px solid var(--bs-gray-200);
        }
    </style>
</head>
<body>

    <div class="mobile-container">
        <!-- Header -->
        <div class="mobile-header">
            <div class="school-logo">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <h5 class="mb-0 font-weight-700">PAUD Al Marjan</h5>
            <p class="mb-0 small opacity-75">Portal Wali Murid</p>
            
            @if(session()->has('parent_student_id') && !request()->routeIs('wali.login'))
            <form action="{{ route('wali.logout') }}" method="POST" class="position-absolute top-0 end-0 p-3">
                @csrf
                <button type="submit" class="btn btn-sm text-white border-0" style="background: transparent; font-size: 1.5rem; padding: 0;" title="Keluar">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
            @endif
        </div>

        <!-- Content -->
        <div class="mobile-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 bg-success text-white shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </div>

        <!-- Footer -->
        <div class="mobile-footer">
            &copy; {{ date('Y') }} PAUD Al Marjan.<br>Sistem Informasi Pengelolaan Keuangan
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
