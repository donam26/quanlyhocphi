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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    @yield('styles')

    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --light-bg: #f8f9fa;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, #34495e 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-header h4 {
            margin: 0;
            font-weight: 600;
            color: white;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .nav-section-title {
            padding: 0 1.5rem 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: rgba(255,255,255,0.6);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--secondary-color);
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            font-size: 1rem;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 2rem;
        }

        .page-header {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            margin: 0;
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
        }

        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0.5rem 0 0 0;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: #6c757d;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            border-radius: 10px 10px 0 0 !important;
            padding: 1.25rem;
        }

        .card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .btn {
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
        }

        .btn-danger {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .table {
            background: white;
        }

        .table th {
            background-color: var(--light-bg);
            color: var(--primary-color);
            font-weight: 600;
            border-top: none;
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .stats-card {
            background: linear-gradient(135deg, var(--secondary-color), #5dade2);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-card.success {
            background: linear-gradient(135deg, var(--success-color), #58d68d);
        }

        .stats-card.warning {
            background: linear-gradient(135deg, var(--warning-color), #f7dc6f);
        }

        .stats-card.danger {
            background: linear-gradient(135deg, var(--accent-color), #ec7063);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }

        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 0;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .mobile-menu-btn {
                position: fixed;
                top: 1rem;
                left: 1rem;
                z-index: 1001;
                background: var(--primary-color);
                color: white;
                border: none;
                padding: 0.5rem;
                border-radius: 5px;
            }
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .pagination .page-link {
            color: var(--secondary-color);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
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
            <h4><i class="fas fa-graduation-cap me-2"></i>Quản lý Học phí</h4>
        </div>
        
        <nav class="sidebar-nav">
            <!-- Dashboard -->
            <div class="nav-section">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>

            <!-- Quản lý -->
            <div class="nav-section">
                <div class="nav-section-title">Quản lý</div>
                
                <a href="{{ route('students.index') }}" class="nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}">
                    <i class="fas fa-user-graduate"></i>
                    Học viên
                </a>
                
                <a href="{{ route('course-items.index') }}" class="nav-link {{ request()->routeIs('course-items.*') && !request()->routeIs('course-items.tree') ? 'active' : '' }}">
                    <i class="fas fa-book"></i>
                    Khóa học
                </a>
                
                <a href="{{ route('course-items.tree') }}" class="nav-link {{ request()->routeIs('course-items.tree') ? 'active' : '' }}">
                    <i class="fas fa-project-diagram"></i>
                    Cây khóa học
                </a>
                
                <a href="{{ route('classes.index') }}" class="nav-link {{ request()->routeIs('classes.*') ? 'active' : '' }}">
                    <i class="fas fa-chalkboard-teacher"></i>
                    Lớp học
                </a>
                
                <a href="{{ route('enrollments.index') }}" class="nav-link {{ request()->routeIs('enrollments.index') || (request()->routeIs('enrollments.*') && !request()->is('unpaid-enrollments')) ? 'active' : '' }}">
                    <i class="fas fa-user-plus"></i>
                    Ghi danh
                </a>
                
                <a href="{{ route('waiting-lists.index') }}" class="nav-link {{ request()->routeIs('waiting-lists.*') ? 'active' : '' }}">
                    <i class="fas fa-clock"></i>
                    Danh sách chờ
                </a>
            </div>

            <!-- Thanh toán -->
            <div class="nav-section">
                <div class="nav-section-title">Thanh toán</div>
                
                <a href="{{ route('payments.index') }}" class="nav-link {{ request()->routeIs('payments.*') && !request()->routeIs('payments.send-reminder') ? 'active' : '' }}">
                    <i class="fas fa-credit-card"></i>
                    Thanh toán
                </a>
                
                <a href="{{ route('enrollments.unpaid') }}" class="nav-link {{ request()->routeIs('enrollments.unpaid') || request()->is('unpaid-enrollments') ? 'active' : '' }}">
                    <i class="fas fa-exclamation-triangle"></i>
                    Chưa thanh toán
                </a>
            </div>

            <!-- Điểm danh -->
            <div class="nav-section">
                <div class="nav-section-title">Điểm danh</div>
                
                <a href="{{ route('attendance.index') }}" class="nav-link {{ request()->routeIs('attendance.index') || request()->routeIs('attendance.show') ? 'active' : '' }}">
                    <i class="fas fa-check-square"></i>
                    Điểm danh
                </a>
                
                <a href="{{ route('attendance.create') }}" class="nav-link {{ request()->routeIs('attendance.create') ? 'active' : '' }}">
                    <i class="fas fa-plus-square"></i>
                    Điểm danh mới
                </a>
                
                <a href="{{ route('attendance.show-class') }}" class="nav-link {{ request()->routeIs('attendance.show-class') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list"></i>
                    Điểm danh lớp
                </a>
            </div>

            <!-- Tìm kiếm & Báo cáo -->
            <div class="nav-section">
                <div class="nav-section-title">Tìm kiếm & Báo cáo</div>
                
                <a href="{{ route('search.index') }}" class="nav-link {{ request()->routeIs('search.*') ? 'active' : '' }}">
                    <i class="fas fa-search"></i>
                    Tìm kiếm
                </a>
                
                <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar"></i>
                    Báo cáo
                </a>
            </div>
        </nav>
    </div>

    <!-- Main content -->
    <div class="main-content">
        <!-- Page header -->
        <div class="page-header">
            <h1>@yield('page-title', 'Dashboard')</h1>
            @if(View::hasSection('breadcrumb'))
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        @yield('breadcrumb')
                    </ol>
                </nav>
            @endif
        </div>

        <!-- Flash messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
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
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery UI -->
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <!-- Toastr -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    @stack('scripts')
</body>
</html> 