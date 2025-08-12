@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Báo cáo</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Báo cáo</li>
    </ol>
    
    <!-- Thống kê nhanh -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-3">
                            <div class="text-white-75 small">Doanh thu hôm nay</div>
                            <div class="text-lg fw-bold">{{ number_format($recentStats['revenue_today'], 0, ',', '.') }} đ</div>
                        </div>
                        <i class="fas fa-calendar-day fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-3">
                            <div class="text-white-75 small">Doanh thu tháng</div>
                            <div class="text-lg fw-bold">{{ number_format($recentStats['revenue_month'], 0, ',', '.') }} đ</div>
                        </div>
                        <i class="fas fa-calendar-alt fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-3">
                            <div class="text-white-75 small">Học viên mới hôm nay</div>
                            <div class="text-lg fw-bold">{{ $recentStats['new_students'] }}</div>
                        </div>
                        <i class="fas fa-user-plus fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-3">
                            <div class="text-white-75 small">Ghi danh mới hôm nay</div>
                            <div class="text-lg fw-bold">{{ $recentStats['new_enrollments'] }}</div>
                        </div>
                        <i class="fas fa-user-graduate fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Danh sách các báo cáo -->
    <div class="row">
        @foreach ($availableReports as $report)
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header {{ $report['color'] }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $report['title'] }}</h5>
                        <i class="fas {{ $report['icon'] }} fa-2x"></i>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text">{{ $report['description'] }}</p>
                </div>
                <div class="card-footer bg-transparent border-0 d-flex justify-content-between">
                    <a href="{{ route($report['route']) }}" class="btn btn-primary">
                        <i class="fas fa-chart-line me-1"></i> Xem báo cáo
                    </a>
                    <a href="#" class="btn btn-outline-secondary">
                        <i class="fas fa-file-export me-1"></i> Xuất Excel
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    
</div>
{{-- Tất cả modal đã được thay thế bằng Unified Modal System --}}


@push('scripts')
<script>
// Page ready với Unified Modal System
document.addEventListener('app:ready', function() {
    console.log('Page ready with Unified Modal System');
});
</script>
@endpush

@endsection
