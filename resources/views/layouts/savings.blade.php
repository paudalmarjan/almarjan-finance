<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title') @yield('title') | @endif{{ config('app.name', 'PAUD Al Marjan') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
    <div class="layout-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar d-flex flex-column align-items-stretch" id="sidebar">
            <div class="d-flex align-items-center justify-content-between px-3 pt-3 pb-2 brand border-bottom" style="border-color: rgba(255, 255, 255, 0.1) !important;">
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Al-Marjan" class="img-fluid rounded flex-shrink-0" style="max-height: 32px; background: white; padding: 2px;">
                    <div class="d-flex flex-column lh-sm">
                        <span class="brand-text fs-6 fw-bold text-white">AL-MARJAN</span>
                        <span class="brand-tagline text-white-50" style="font-size: 0.65rem; font-weight: 500; color: #7dd3fc !important;">Tabungan Siswa</span>
                    </div>
                </div>
            </div>
            
            <div class="nav flex-column mb-auto mt-3" id="menu">
                <div class="sidebar-section-title d-none d-sm-block">Tabungan Siswa</div>
                
                <a href="{{ route('savings.index') }}" class="nav-link {{ request()->routeIs('savings.index') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span class="ms-1 d-none d-sm-inline">Dashboard</span>
                </a>
                
                <a href="{{ route('savings.deposit') }}" class="nav-link {{ request()->routeIs('savings.deposit') ? 'active' : '' }}">
                    <i class="bi bi-box-arrow-in-down"></i>
                    <span class="ms-1 d-none d-sm-inline">Setor Tabungan</span>
                </a>
                
                <a href="{{ route('savings.withdraw') }}" class="nav-link {{ request()->routeIs('savings.withdraw') ? 'active' : '' }}">
                    <i class="bi bi-box-arrow-up"></i>
                    <span class="ms-1 d-none d-sm-inline">Tarik Tabungan</span>
                </a>
                
                <a href="{{ route('savings.history') }}" class="nav-link {{ request()->routeIs('savings.history') ? 'active' : '' }}">
                    <i class="bi bi-journal-bookmark-fill"></i>
                    <span class="ms-1 d-none d-sm-inline">Buku Tabungan</span>
                </a>
                
                <div class="sidebar-section-title d-none d-sm-block">Data & Laporan</div>

                <a href="{{ route('savings.students') }}" class="nav-link {{ request()->routeIs('savings.students') || request()->routeIs('savings.show') ? 'active' : '' }}">
                    <i class="bi bi-person-lines-fill"></i>
                    <span class="ms-1 d-none d-sm-inline">Daftar Saldo Siswa</span>
                </a>
            </div>

            <!-- User Info Sidebar footer -->
            <div class="dropdown border-top border-secondary mt-auto">
                <a href="#" class="sidebar-user-footer d-flex align-items-center text-white text-decoration-none dropdown-toggle p-3" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 32px; height: 32px; font-weight: 600;">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <span class="user-name-text text-truncate me-1" style="max-width: 120px; overflow: hidden;">{{ auth()->user()->name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-menu-item dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i> Profil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i> Keluar</button>
                        </form>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Top Header Navbar -->
            <header class="top-navbar d-flex align-items-center justify-content-between no-print">
                <h4 class="mb-0 text-dark font-weight-600 d-none d-md-block">
                    @yield('title', 'Sistem Keuangan Al Marjan')
                </h4>
                
                <div class="d-flex align-items-center ms-auto">
                    <!-- App Switcher -->
                    <div class="dropdown me-3">
                        <button class="btn btn-light border dropdown-toggle" type="button" id="appSwitcherDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Pindah Aplikasi">
                            <i class="bi bi-grid-3x3-gap-fill text-primary"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="appSwitcherDropdown">
                            <li><h6 class="dropdown-header">Aplikasi PAUD Hub</h6></li>
                            <li><a class="dropdown-item" href="{{ route('portal') }}"><i class="bi bi-house me-2"></i> Portal Utama</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="bi bi-currency-dollar me-2 text-success"></i> App Keuangan</a></li>
                            <li><a class="dropdown-item active" href="{{ route('savings.index') }}"><i class="bi bi-wallet2 me-2 text-info"></i> App Tabungan</a></li>
                            <li><a class="dropdown-item" href="{{ route('settings.index') }}"><i class="bi bi-database-gear me-2 text-purple"></i> Master Data</a></li>
                        </ul>
                    </div>
                    <!-- Global Search Input -->
                    <div class="me-3 d-none d-md-block" style="width: 250px;">
                        <div class="input-group input-group-sm cursor-pointer" onclick="openGlobalSearchModal()" style="cursor: pointer;">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 ps-1 bg-light cursor-pointer" placeholder="Cari siswa... (Ctrl+K)" style="cursor: pointer;" readonly>
                        </div>
                    </div>

                    <!-- Academic Year Selector Dropdown -->
                    @if(isset($allAcademicYears) && $allAcademicYears->isNotEmpty())
                    <form method="GET" action="" class="d-flex align-items-center me-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-calendar-event text-primary"></i>
                            </span>
                            <select class="form-select border-start-0 ps-1" name="change_academic_year_id" onchange="this.form.submit()" style="font-weight: 500;">
                                @foreach($allAcademicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ $selectedAcademicYear && $selectedAcademicYear->id == $ay->id ? 'selected' : '' }}>
                                        TA: {{ $ay->name }} {{ $ay->is_active ? '(Aktif)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                    @endif

                </div>
            </header>

            <!-- Content Body -->
            <main class="content-body">
                <!-- Global Inactive Academic Year Warning Banner -->
                @if($selectedAcademicYear && !$selectedAcademicYear->is_active)
                    <div class="alert alert-warning card-premium p-3 border-start border-warning border-4 mb-4" role="alert">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-4 me-2"></i>
                            <div>
                                <strong>Tahun Ajaran Inaktif!</strong> Anda sedang mengakses data **Tahun Ajaran {{ $selectedAcademicYear->name }} (Tidak Aktif)**.
                                @if(auth()->user()->isTeacher())
                                    <span class="d-block small text-muted mt-1">Seluruh aksi penulisan data dikunci (Mode Baca Saja).</span>
                                @else
                                    <span class="d-block small text-muted mt-1">Akses menulis terbuka untuk Administrator (dibatasi hanya untuk tindakan administratif tertentu).</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Session Alerts -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show card-premium p-3 border-start border-success border-4 mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success fs-4 me-2"></i>
                            <div><strong>Berhasil!</strong> {{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show card-premium p-3 border-start border-warning border-4 mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-4 me-2"></i>
                            <div><strong>Peringatan!</strong> {{ session('warning') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show card-premium p-3 border-start border-danger border-4 mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-octagon-fill text-danger fs-4 me-2"></i>
                            <div><strong>Gagal!</strong> {{ session('error') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Tom Select JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <!-- Modal: Global Search -->
    <div class="modal fade" id="globalSearchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content card-premium border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title font-weight-600 text-primary"><i class="bi bi-search me-2"></i>Pencarian Siswa Cepat</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="input-group mb-3 shadow-sm">
                        <span class="input-group-text bg-white border-end-0 fs-5"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-1 fs-5" id="global_search_modal_input" placeholder="Ketik nama siswa atau NIS..." autofocus autocomplete="off">
                    </div>
                    <div class="list-group list-group-flush shadow-sm rounded border d-none" id="global_search_results" style="max-height: 300px; overflow-y: auto;">
                        <!-- Results will be loaded here via JS -->
                    </div>
                    <div id="global_search_placeholder" class="text-center py-4 text-muted">
                        <i class="bi bi-person-badge fs-2 text-light d-block mb-2"></i>
                        <span class="small">Ketik minimal 2 karakter untuk memulai pencarian...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Tom Select on any element with .select2-enable class
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.select2-enable').forEach(function(el) {
                if (el.tagName === 'SELECT') {
                    new TomSelect(el, {
                        create: false,
                        sortField: {
                            field: "text",
                            direction: "asc"
                        }
                    });
                }
            });
        });

        // Global Search Modal handler
        let searchModalObj = null;
        const globalSearchModalEl = document.getElementById('globalSearchModal');

        // Focus input reliably after modal animation fully completes
        globalSearchModalEl.addEventListener('shown.bs.modal', function () {
            const input = document.getElementById('global_search_modal_input');
            if (input) input.focus();
        });

        function openGlobalSearchModal() {
            if (!searchModalObj) {
                searchModalObj = new bootstrap.Modal(globalSearchModalEl);
            }
            // Reset state before showing
            const input = document.getElementById('global_search_modal_input');
            if (input) input.value = '';
            document.getElementById('global_search_results').classList.add('d-none');
            const placeholder = document.getElementById('global_search_placeholder');
            if (placeholder) {
                placeholder.classList.remove('d-none');
                placeholder.innerHTML = `
                    <i class="bi bi-person-badge fs-2 text-light d-block mb-2"></i>
                    <span class="small">Ketik minimal 2 karakter untuk memulai pencarian...</span>
                `;
            }
            searchModalObj.show();
            // focus() is handled by shown.bs.modal event above
        }

        // Shortcut Ctrl + K or Cmd + K
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                openGlobalSearchModal();
            }
        });

        // Event listener for input typing in search modal
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('global_search_modal_input');
            const resultsContainer = document.getElementById('global_search_results');
            const placeholder = document.getElementById('global_search_placeholder');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = searchInput.value.trim();
                    if (query.length < 2) {
                        resultsContainer.classList.add('d-none');
                        placeholder.classList.remove('d-none');
                        placeholder.innerHTML = `
                            <i class="bi bi-person-badge fs-2 text-light d-block mb-2"></i>
                            <span class="small">Ketik minimal 2 karakter untuk memulai pencarian...</span>
                        `;
                        return;
                    }

                    placeholder.classList.remove('d-none');
                    placeholder.innerHTML = `
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="small ms-2">Mencari siswa...</span>
                    `;

                    fetch(`{{ route('students.search-ajax') }}?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            resultsContainer.innerHTML = '';
                            if (data.length === 0) {
                                resultsContainer.classList.add('d-none');
                                placeholder.classList.remove('d-none');
                                placeholder.innerHTML = `
                                    <i class="bi bi-emoji-frown fs-2 text-warning d-block mb-2"></i>
                                    <span class="small">Tidak ada siswa yang cocok dengan "${query}"</span>
                                `;
                            } else {
                                placeholder.classList.add('d-none');
                                resultsContainer.classList.remove('d-none');
                                data.forEach(student => {
                                    const item = document.createElement('div');
                                    item.className = 'list-group-item list-group-item-action p-3 d-flex justify-content-between align-items-center';
                                    item.innerHTML = `
                                        <div>
                                            <strong class="d-block text-dark">${student.name}</strong>
                                            <span class="small text-muted">NIS: ${student.nis || '-'} | Kelas: ${student.group_name}</span>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <a href="${student.payment_url}" class="btn btn-outline-success"><i class="bi bi-cash-coin me-1"></i>Bayar</a>
                                            <a href="/savings/history?student_id=${student.id}" class="btn btn-outline-info"><i class="bi bi-journal-bookmark-fill me-1"></i>Tabungan</a>
                                            <a href="${student.edit_url}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
                                        </div>
                                    `;
                                    resultsContainer.appendChild(item);
                                });
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            placeholder.innerHTML = '<span class="text-danger small">Gagal mengambil data. Coba lagi.</span>';
                        });
                });
            }
        });
    </script>
</body>
</html>
