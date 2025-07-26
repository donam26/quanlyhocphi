<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page-title', 'Trang thanh toán') - {{ config('app.name') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .payment-header {
            background-color: #1a3057;
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .payment-footer {
            background-color: #1a3057;
            color: white;
            padding: 20px 0;
            margin-top: 40px;
            font-size: 0.9em;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        .card-header {
            border-top-left-radius: 10px !important;
            border-top-right-radius: 10px !important;
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Header -->
    <header class="payment-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div>
                    <h1 class="h4 mb-0">{{ config('app.name') }}</h1>
                    <p class="mb-0">Hệ thống Quản lý Học phí</p>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="py-4">
        <div class="container">
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
            
            <!-- Page Content -->
            @yield('content')
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="payment-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tất cả các quyền được bảo lưu.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Nếu có thắc mắc, vui lòng liên hệ: <a href="mailto:support@example.com" class="text-white">support@example.com</a></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    @stack('scripts')
</body>
</html> 