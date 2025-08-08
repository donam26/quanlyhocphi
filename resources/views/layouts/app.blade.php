<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản lý Học phí')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    @yield('styles')

    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
            --primary-color: #1e293b;
            --primary-light: #334155;
            --secondary-color: #3b82f6;
            --accent-color: #ef4444;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --light-bg: #f8fafc;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
        }

        /* ===== SIDEBAR STYLES ===== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            overflow: hidden;
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-lg);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        /* ===== SIDEBAR HEADER ===== */
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            transition: all 0.4s ease;
            overflow: hidden;
        }

        .sidebar-brand-icon {
            width: 40px;
            height: 40px;
            background: var(--secondary-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .sidebar-brand-text {
            margin-left: 12px;
            font-size: 1.125rem;
            font-weight: 700;
            color: white;
            white-space: nowrap;
            transition: all 0.4s ease;
        }

        .sidebar.collapsed .sidebar-brand-text {
            opacity: 0;
            transform: translateX(-20px);
        }

        /* ===== TOGGLE BUTTON ===== */
        .sidebar-toggle {
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 8px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.875rem;
            backdrop-filter: blur(10px);
        }

        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .sidebar-toggle:active {
            transform: scale(0.95);
        }

        .sidebar.collapsed .sidebar-toggle {
            margin-left: auto;
            margin-right: auto;
        }

        /* ===== NAVIGATION ===== */
        .sidebar-nav {
            padding: 1.5rem 0;
            height: calc(100vh - 80px);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .nav-section-title {
            padding: 0 1.5rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.4s ease;
            position: relative;
        }

        .sidebar.collapsed .nav-section {
            margin-bottom: 1rem;
        }

        .sidebar.collapsed .nav-section-title {
            opacity: 0;
            transform: translateX(-20px);
            pointer-events: none;
            height: 0;
            padding: 0;
            margin: 0;
            overflow: hidden;
        }

        /* ===== COLLAPSED SECTION DIVIDER ===== */
        .sidebar.collapsed .nav-section:not(:first-child)::before {
            content: '';
            display: block;
            width: 32px;
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
            margin: 1rem auto 1rem;
        }

        /* ===== NAV LINKS ===== */
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            margin: 0 0.75rem 0.5rem;
            border-radius: 12px;
            overflow: hidden;
            min-height: 48px;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--secondary-color);
            transform: scaleY(0);
            transition: transform 0.3s ease;
            border-radius: 0 2px 2px 0;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(4px);
        }

        .nav-link:hover::before,
        .nav-link.active::before {
            transform: scaleY(1);
        }

        .nav-link.active {
            background: rgba(59, 130, 246, 0.2);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        .nav-link-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .nav-link-text {
            margin-left: 12px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.4s ease;
            white-space: nowrap;
            opacity: 1;
        }

        /* ===== COLLAPSED SIDEBAR STYLES ===== */
        .sidebar.collapsed .nav-link {
            padding: 0.75rem;
            justify-content: center;
            margin: 0 0.5rem 0.5rem;
            transform: none;
            width: calc(100% - 1rem);
            min-height: 48px;
        }

        .sidebar.collapsed .nav-link:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.15);
        }

        .sidebar.collapsed .nav-link.active {
            background: rgba(59, 130, 246, 0.3);
        }

        .sidebar.collapsed .nav-link-text {
            opacity: 0;
            transform: translateX(-20px);
            pointer-events: none;
            position: absolute;
            width: 0;
            overflow: hidden;
        }

        .sidebar.collapsed .nav-link-icon {
            margin: 0;
            width: 24px;
            height: 24px;
            font-size: 1.25rem;
        }

        /* ===== COLLAPSED HOVER EFFECTS ===== */
        .sidebar.collapsed .nav-link::before {
            display: none;
        }

        /* ===== TOOLTIP ===== */
        .nav-tooltip {
            position: absolute;
            left: calc(100% + 16px);
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.95);
            color: white;
            padding: 0.625rem 0.875rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1001;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-tooltip::before {
            content: '';
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            border: 7px solid transparent;
            border-right-color: rgba(0, 0, 0, 0.95);
        }

        /* Chỉ hiển thị tooltip khi sidebar collapsed */
        .sidebar:not(.collapsed) .nav-tooltip {
            display: none;
        }

        .sidebar.collapsed .nav-link:hover .nav-tooltip {
            opacity: 1;
            visibility: visible;
            transform: translateY(-50%) translateX(8px);
        }

        /* Delay tooltip để tránh flicker */
        .sidebar.collapsed .nav-link .nav-tooltip {
            transition-delay: 0.5s;
        }

        .sidebar.collapsed .nav-link:hover .nav-tooltip {
            transition-delay: 0s;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--light-bg);
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* ===== PAGE HEADER ===== */
        .page-header {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .page-header h1 {
            margin: 0;
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0.75rem 0 0 0;
            font-size: 0.875rem;
        }

        .breadcrumb-item {
            color: var(--text-muted);
        }

        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: var(--text-muted);
            margin: 0 0.5rem;
        }

        .breadcrumb-item a {
            color: var(--secondary-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb-item a:hover {
            color: var(--primary-color);
        }

        /* ===== CARDS ===== */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
        }

        .card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        /* ===== BUTTONS ===== */
        .btn {
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-danger {
            background: var(--accent-color);
            color: white;
        }

        .btn-outline-secondary {
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            background: white;
        }

        .btn-outline-secondary:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* ===== ALERTS ===== */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width) !important;
                z-index: 1050;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar.collapsed {
                width: var(--sidebar-width) !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 1rem;
            }

            .mobile-menu-btn {
                position: fixed;
                top: 1rem;
                left: 1rem;
                z-index: 1051;
                background: var(--primary-color);
                color: white;
                border: none;
                padding: 0.75rem;
                border-radius: 12px;
                box-shadow: var(--shadow);
                transition: all 0.3s ease;
            }

            .mobile-menu-btn:hover {
                transform: scale(1.05);
            }

            .sidebar-toggle {
                display: none;
            }

            .page-header {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }
        }

        /* ===== FORM CONTROLS ===== */
        .form-control:focus,
        .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        /* ===== PAGINATION ===== */
        .pagination .page-link {
            color: var(--secondary-color);
            border-radius: 8px;
            margin: 0 2px;
            border: 1px solid var(--border-color);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        /* ===== ANIMATIONS ===== */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .nav-link {
            animation: slideIn 0.3s ease forwards;
        }

        /* ===== UTILITIES ===== */
        .shadow-sm {
            box-shadow: var(--shadow) !important;
        }

        .shadow {
            box-shadow: var(--shadow-lg) !important;
        }
    </style>
</head>
<body>
    <!-- Mobile menu button -->
    <button class="mobile-menu-btn d-md-none" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="sidebar-brand-text">Quản lý Học phí</div>
            </div>
            <button class="sidebar-toggle d-none d-md-block" onclick="toggleSidebarCollapse()">
                <i class="fas fa-chevron-left" id="toggle-icon"></i>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <!-- Dashboard -->
            <div class="nav-section">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <div class="nav-link-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <span class="nav-link-text">Dashboard</span>
                    <div class="nav-tooltip">Dashboard</div>
                </a>
            </div>

            <!-- Quản lý -->
            <div class="nav-section">
                <div class="nav-section-title">Quản lý</div>
                
                <a href="{{ route('students.index') }}" class="nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}">
                    <div class="nav-link-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <span class="nav-link-text">Học viên</span>
                    <div class="nav-tooltip">Học viên</div>
                </a>
                
                <a href="{{ route('course-items.tree') }}" class="nav-link {{ request()->routeIs('course-items.tree') ? 'active' : '' }}">
                    <div class="nav-link-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <span class="nav-link-text">Khóa học</span>
                    <div class="nav-tooltip">Khóa học</div>
                </a>
                
                <a href="{{ route('course-items.waiting-tree') }}" class="nav-link {{ request()->routeIs('course-items.waiting-tree') ? 'active' : '' }}">
                    <div class="nav-link-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <span class="nav-link-text">Danh sách chờ</span>
                    <div class="nav-tooltip">Danh sách chờ</div>
                </a>
                
                <a href="{{ route('attendance.tree') }}" class="nav-link {{ request()->routeIs('attendance.tree') ? 'active' : '' }}">
                    <div class="nav-link-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <span class="nav-link-text">Điểm danh</span>
                    <div class="nav-tooltip">Điểm danh</div>
                </a>
                
                <a href="{{ route('schedules.index') }}" class="nav-link {{ request()->routeIs('schedules.*') || request()->routeIs('*.schedules') ? 'active' : '' }}">
                    <div class="nav-link-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <span class="nav-link-text">Lịch học</span>
                    <div class="nav-tooltip">Lịch học</div>
                </a>
                
                <a href="{{ route('learning-progress.index') }}" class="nav-link {{ request()->routeIs('learning-progress.*') ? 'active' : '' }}">
                    <div class="nav-link-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <span class="nav-link-text">Tiến độ học tập</span>
                    <div class="nav-tooltip">Tiến độ học tập</div>
                </a>
                
                <a href="{{ route('enrollments.index') }}" class="nav-link {{ request()->routeIs('enrollments.*') && !request()->routeIs('course-items.waiting-tree') ? 'active' : '' }}">
                    <div class="nav-link-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <span class="nav-link-text">Ghi danh</span>
                    <div class="nav-tooltip">Ghi danh</div>
                </a>
                
            </div>
            
            <!-- THANH TOÁN -->
            <div class="nav-section">
                <div class="nav-section-title">Thanh toán</div>
                
                <a href="{{ route('enrollments.unpaid') }}" class="nav-link {{ request()->routeIs('enrollments.unpaid') || request()->is('unpaid-enrollments') ? 'active' : '' }}">
                    <div class="nav-link-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <span class="nav-link-text">Chưa thanh toán</span>
                    <div class="nav-tooltip">Chưa thanh toán</div>
                </a>
                
                <a href="{{ route('payments.index') }}" class="nav-link {{ request()->routeIs('payments.*') && !request()->routeIs('payments.send-reminder') ? 'active' : '' }}">
                    <div class="nav-link-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <span class="nav-link-text">Lịch sử</span>
                    <div class="nav-tooltip">Lịch sử thanh toán</div>
                </a>
            </div>

            <!-- Tìm kiếm & Báo cáo -->
            <div class="nav-section">
                <div class="nav-section-title">Báo cáo</div>
                
                <a href="{{ route('search.index') }}" class="nav-link {{ request()->routeIs('search.*') ? 'active' : '' }}">
                    <div class="nav-link-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <span class="nav-link-text">Tìm kiếm</span>
                    <div class="nav-tooltip">Tìm kiếm</div>
                </a>
                
                <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <div class="nav-link-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <span class="nav-link-text">Báo cáo</span>
                    <div class="nav-tooltip">Báo cáo</div>
                </a>
            </div>
        </nav>
    </div>

    <!-- Main content -->
    <div class="main-content">
        <!-- Page header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>@yield('page-title', 'Dashboard')</h1>
                    @if(View::hasSection('breadcrumb'))
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                @yield('breadcrumb')
                            </ol>
                        </nav>
                    @endif
                </div>
                <div>
                    <button onclick="window.history.back()" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Quay lại
                    </button>
                    @yield('page-actions')
                </div>
            </div>
        </div>

        <!-- Flash messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show">
                <i class="fas fa-info-circle me-2"></i>
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Có lỗi xảy ra:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Page content -->
        @yield('content')
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery UI -->
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <!-- Toastr -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <style>
        /* Fix cho Select2 AJAX */
        .select2-ajax-container {
            width: 100% !important;
        }
        
        .select2-container--bootstrap-5 {
            width: 100% !important;
        }
        
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
            border-radius: 0.375rem;
            border: 1px solid #dee2e6;
            padding: 0.375rem 0.75rem;
        }
        
        .select2-container--bootstrap-5 .select2-selection--single {
            padding: 0.375rem 2.25rem 0.375rem 0.75rem;
        }
        
        /* Fix cho row gap */
        .gap-2 {
            gap: 0.5rem !important;
        }
    </style>
    
    <script>
        // Cấu hình Select2 mặc định cho toàn bộ ứng dụng
        $(document).ready(function() {
            // Áp dụng Select2 cho tất cả các select có class .select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
            
            // Cấu hình đặc biệt cho select2-ajax
            $('.select2-ajax').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Tìm kiếm...',
                allowClear: true,
                containerCssClass: 'select2-ajax-container'
            });
            
            // Format tiền tệ
            function formatCurrency(number) {
                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(number);
            }
            
            // Khôi phục trạng thái sidebar từ localStorage
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed) {
                document.getElementById('sidebar').classList.add('collapsed');
                document.getElementById('toggle-icon').classList.remove('fa-chevron-left');
                document.getElementById('toggle-icon').classList.add('fa-chevron-right');
            }
        });
        
        // Hàm toggle sidebar collapse với animation mượt
        function toggleSidebarCollapse() {
            const sidebar = document.getElementById('sidebar');
            const toggleIcon = document.getElementById('toggle-icon');
            
            sidebar.classList.toggle('collapsed');
            
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.classList.remove('fa-chevron-left');
                toggleIcon.classList.add('fa-chevron-right');
                localStorage.setItem('sidebarCollapsed', 'true');
            } else {
                toggleIcon.classList.remove('fa-chevron-right');
                toggleIcon.classList.add('fa-chevron-left');
                localStorage.setItem('sidebarCollapsed', 'false');
            }
        }
        
        // Hàm mở mobile sidebar (giữ nguyên cho mobile)
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // Smooth scroll cho navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                // Add loading state
                this.style.opacity = '0.7';
                setTimeout(() => {
                    this.style.opacity = '1';
                }, 300);
            });
        });
    </script>
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">

    <!-- Các scripts cần thiết -->
    <script src="{{ asset('js/app.js') }}"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

    @stack('scripts')
</body>
</html> 