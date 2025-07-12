@extends('layouts.app')

@section('title', 'Trang chủ')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-0 text-gray-800">Tổng quan</h1>
        </div>
    </div>

    <!-- Thống kê nhanh -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="stats-number">{{ number_format($stats['total_students']) }}</p>
                    <p class="stats-label">Tổng học viên</p>
                    <i class="fas fa-users fa-2x opacity-75"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stats-card warning">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="stats-number">{{ $stats['unpaid_count'] ?? 0 }}</p>
                    <p class="stats-label">Chưa thanh toán</p>
                    <i class="fas fa-clock fa-2x opacity-75"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stats-card danger">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="stats-number">{{ number_format($totalRevenue) }}</p>
                    <p class="stats-label">Doanh thu (VND)</p>
                    <i class="fas fa-money-bill-wave fa-2x opacity-75"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="stats-number">{{ $stats['total_classes'] }}</p>
                    <p class="stats-label">Tổng lớp học</p>
                    <i class="fas fa-chalkboard-teacher fa-2x opacity-75"></i>
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
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Học viên chưa thanh toán -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Học viên chưa đóng phí</h6>
                    <a href="{{ route('enrollments.unpaid') }}" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
                </div>
                <div class="card-body p-0">
                    @if(isset($unpaidStudents) && $unpaidStudents->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($unpaidStudents as $enrollment)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-medium">{{ $enrollment->student->full_name }}</div>
                                        <small class="text-muted">{{ $enrollment->class->name }}</small>
                                    </div>
                                    <a href="{{ route('payments.create', $enrollment) }}" class="btn btn-sm btn-outline-primary">Thu phí</a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center p-3">
                            <p class="text-muted">Không có học viên chưa thanh toán</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Thanh toán gần đây -->
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Thanh toán gần đây</h6>
                    <a href="{{ route('payments.index') }}" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
                </div>
                <div class="card-body p-0">
                    @if(isset($recentPayments) && $recentPayments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Học viên</th>
                                        <th>Khóa học</th>
                                        <th>Số tiền</th>
                                        <th>Ngày thanh toán</th>
                                        <th>Phương thức</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentPayments as $payment)
                                        <tr>
                                            <td>{{ $payment->enrollment->student->full_name }}</td>
                                            <td>{{ $payment->enrollment->class->name }}</td>
                                            <td>{{ number_format($payment->amount) }} VND</td>
                                            <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                            <td>{{ $payment->payment_method }}</td>
                                            <td>
                                                <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center p-3">
                            <p class="text-muted">Không có thanh toán gần đây</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Cây khóa học</h6>
                    <a href="{{ route('course-items.index') }}" class="btn btn-primary btn-sm">Quản lý cây khóa học</a>
                </div>
                <div class="card-body">
                    <div id="treeContent">
                        <div class="text-center text-muted">Đang tải dữ liệu...</div>
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
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const chartData = @json($chartData);
    const labels = [];
    const data = [];
    for (let i = 1; i <= 12; i++) {
        const monthName = new Date(2023, i-1, 1).toLocaleString('vi', { month: 'long' });
        labels.push(monthName);
        data.push(chartData[i] || 0);
    }
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Doanh thu (VND)',
                data: data,
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

    // Fetch tree data
    fetch('/tree')
        .then(res => res.json())
        .then(data => {
            if (!data || data.length === 0) {
                $('#treeContent').html('<div class="text-center text-muted">Không có dữ liệu cây khóa học</div>');
                return;
            }
            
            // Render cây khóa học
            let html = renderTree(data);
            $('#treeContent').html(html);
        });
});

function renderTree(items) {
    if (!items || items.length === 0) return '<div class="text-muted">Không có dữ liệu</div>';
    
    let html = '<ul class="tree-list">';
    items.forEach(function(item) {
        html += '<li>' +
            '<b>' + item.name + '</b> ' +
            '<a href="' + item.url + '" class="btn btn-xs btn-outline-primary">Chi tiết</a>';
        
        if (item.is_leaf) {
            if (item.has_online) html += ' <span class="badge bg-info">Online</span>';
            if (item.has_offline) html += ' <span class="badge bg-secondary">Offline</span>';
            if (item.fee > 0) html += ' <span class="badge bg-success">' + formatFee(item.fee) + '</span>';
        }
        
        if (item.children && item.children.length > 0) {
            html += renderTree(item.children);
        }
        
        html += '</li>';
    });
    html += '</ul>';
    return html;
}

function formatFee(fee) {
    if (!fee || fee == 0) return '';
    return Number(fee).toLocaleString('vi-VN') + 'đ';
}
</script>
@endpush 
 