@extends('layouts.app')

@section('title', 'Báo cáo & Thống kê')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-0 text-gray-800">Báo cáo & Thống kê</h1>
        </div>
    </div>

    <!-- Thống kê nhanh -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="stats-number">{{ number_format($totalStudents ?? 0) }}</p>
                    <p class="stats-label">Tổng học viên</p>
                    <i class="fas fa-users fa-2x opacity-75"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stats-card warning">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="stats-number">{{ $activeClasses ?? 0 }}</p>
                    <p class="stats-label">Lớp đang mở</p>
                    <i class="fas fa-chalkboard-teacher fa-2x opacity-75"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stats-card danger">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="stats-number">{{ number_format($totalRevenue ?? 0) }}</p>
                    <p class="stats-label">Doanh thu (VND)</p>
                    <i class="fas fa-money-bill-wave fa-2x opacity-75"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="stats-number">{{ number_format($totalUnpaid ?? 0) }}</p>
                    <p class="stats-label">Công nợ (VND)</p>
                    <i class="fas fa-exclamation-circle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Biểu đồ doanh thu -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Doanh thu theo tháng</h6>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary active" id="thisYear">Năm nay</button>
                        <button class="btn btn-sm btn-outline-primary" id="lastYear">Năm trước</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Biểu đồ phân bổ học viên -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">Phân bổ học viên theo khóa</h6>
                </div>
                <div class="card-body">
                    <canvas id="studentDistributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Báo cáo công nợ -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Báo cáo công nợ</h6>
                    <a href="{{ route('enrollments.unpaid') }}" class="btn btn-sm btn-outline-primary">Xem chi tiết</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <td width="60%">Tổng học phí chưa thu</td>
                                <td class="text-end text-danger fw-bold">{{ number_format($totalUnpaid ?? 0) }} VND</td>
                            </tr>
                            <tr>
                                <td>Số học viên chưa thanh toán</td>
                                <td class="text-end">{{ $unpaidCount ?? 0 }} học viên</td>
                            </tr>
                            <tr>
                                <td>Số học viên thanh toán một phần</td>
                                <td class="text-end">{{ $partialPaidCount ?? 0 }} học viên</td>
                            </tr>
                            <tr>
                                <td>Công nợ trung bình mỗi học viên</td>
                                <td class="text-end">{{ number_format($averageDebt ?? 0) }} VND</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Báo cáo lớp học -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Báo cáo lớp học</h6>
                    <a href="{{ route('course-classes.index') }}" class="btn btn-sm btn-outline-primary">Xem chi tiết</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <td width="60%">Tổng số lớp</td>
                                <td class="text-end">{{ $totalClasses ?? 0 }} lớp</td>
                            </tr>
                            <tr>
                                <td>Lớp đang mở</td>
                                <td class="text-end">{{ $activeClasses ?? 0 }} lớp</td>
                            </tr>
                            <tr>
                                <td>Lớp đã kết thúc</td>
                                <td class="text-end">{{ $completedClasses ?? 0 }} lớp</td>
                            </tr>
                            <tr>
                                <td>Tỷ lệ lấp đầy trung bình</td>
                                <td class="text-end">{{ number_format($averageOccupancy ?? 0, 1) }}%</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Biểu đồ doanh thu
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    
    // Giả lập dữ liệu doanh thu
    const revenueData = @json($revenueData ?? []);
    
    const revenueChart = new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
            datasets: [{
                label: 'Doanh thu (VND)',
                data: revenueData,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('vi-VN');
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.raw.toLocaleString('vi-VN') + ' VND';
                        }
                    }
                }
            }
        }
    });
    
    // Biểu đồ phân bổ học viên
    const distributionCtx = document.getElementById('studentDistributionChart').getContext('2d');
    
    // Giả lập dữ liệu phân bổ học viên
    const distributionData = @json($distributionData ?? []);
    const distributionLabels = @json($distributionLabels ?? []);
    
    const distributionChart = new Chart(distributionCtx, {
        type: 'pie',
        data: {
            labels: distributionLabels,
            datasets: [{
                data: distributionData,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Chuyển đổi giữa năm nay và năm trước
    $('#thisYear').click(function() {
        $(this).addClass('active');
        $('#lastYear').removeClass('active');
        // Ở đây sẽ gọi AJAX để lấy dữ liệu năm nay
    });
    
    $('#lastYear').click(function() {
        $(this).addClass('active');
        $('#thisYear').removeClass('active');
        // Ở đây sẽ gọi AJAX để lấy dữ liệu năm trước
    });
});
</script>
@endpush 
 