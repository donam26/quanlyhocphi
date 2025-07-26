@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 mb-4">Dashboard</h1>
    
    <!-- Thống kê tổng quan -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="h5 mb-0">{{ number_format($stats['students_count']) }}</div>
                        <div class="small text-white">Học viên</div>
                    </div>
                    <i class="fas fa-users fa-2x opacity-75"></i>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('students.index') }}">Xem chi tiết</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="h5 mb-0">{{ number_format($stats['courses_count']) }}</div>
                        <div class="small text-white">Khóa học</div>
                    </div>
                    <i class="fas fa-graduation-cap fa-2x opacity-75"></i>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('course-items.tree') }}">Xem chi tiết</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="h5 mb-0">{{ number_format($stats['enrollments_count']) }}</div>
                        <div class="small text-white">Lượt ghi danh</div>
                    </div>
                    <i class="fas fa-user-graduate fa-2x opacity-75"></i>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('enrollments.index') }}">Xem chi tiết</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="h5 mb-0">{{ number_format($stats['waitings_count']) }}</div>
                        <div class="small text-white">Đang chờ</div>
                    </div>
                    <i class="fas fa-user-clock fa-2x opacity-75"></i>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('waiting-lists.index') }}">Xem chi tiết</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ chính -->
    <div class="row mb-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-chart-line me-1"></i>
                        Doanh thu 7 ngày gần đây
                    </div>
                    <div class="small text-muted">Đơn vị: VNĐ</div>
                </div>
                <div class="card-body">
                    <canvas id="revenueByDayChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Phân bố thanh toán
                </div>
                <div class="card-body">
                    <canvas id="paymentStatusChart" width="100%" height="40"></canvas>
                </div>
                <div class="card-footer small text-muted">
                                                Tỉ lệ thanh toán đầy đủ: {{ array_sum($paymentStatusData['data']) > 0 ? number_format($paymentStatusData['data'][0] / array_sum($paymentStatusData['data']) * 100, 1) : 0 }}%
                </div>
            </div>
        </div>
    </div>
    
    <!-- Biểu đồ thứ hai -->
    <div class="row mb-4">
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Phương thức thanh toán
                </div>
                <div class="card-body">
                    <canvas id="paymentMethodChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Học viên theo giới tính
                </div>
                <div class="card-body">
                    <canvas id="genderChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Ghi danh theo tháng
                </div>
                <div class="card-body">
                    <canvas id="enrollmentChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Thông tin tài chính chi tiết -->
    <div class="row">
        <div class="col-xl-5">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-money-bill-wave me-1"></i>
                    Tài chính tổng quan
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card border-left-primary h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Tổng học phí</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                {{ number_format($financialStats['total_fee']) }} VND
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card border-left-success h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Đã thanh toán</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                {{ number_format($financialStats['total_paid']) }} VND
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card border-left-warning h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Chờ thanh toán</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                {{ number_format($financialStats['total_pending']) }} VND
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card border-left-danger h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Còn thiếu</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                {{ number_format($financialStats['total_remaining']) }} VND
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h5 class="mb-2">Tỉ lệ thanh toán</h5>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $financialStats['payment_rate'] }}%;" 
                                 aria-valuenow="{{ $financialStats['payment_rate'] }}" aria-valuemin="0" aria-valuemax="100">
                                {{ $financialStats['payment_rate'] }}%
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Học viên chưa thanh toán đủ</h5>
                            <a href="{{ route('enrollments.unpaid') }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                {{ $unPaidCount }} học viên
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-7">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Top khóa học được đăng ký nhiều nhất
                </div>
                <div class="card-body">
                    <canvas id="topCoursesChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Doanh thu và Thanh toán gần đây -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-area me-1"></i>
                    Doanh thu 6 tháng gần đây
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-table me-1"></i>
                    Thanh toán gần đây
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Học viên</th>
                                    <th>Khóa học</th>
                                    <th class="text-end">Số tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($financialStats['recent_payments'] as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                    <td>{{ $payment->enrollment->student->full_name }}</td>
                                    <td>{{ $payment->enrollment->courseItem->name }}</td>
                                    <td class="text-end text-success">{{ number_format($payment->amount) }} VND</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">Chưa có thanh toán nào</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <a href="{{ route('payments.index') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-list me-1"></i>
                            Xem tất cả thanh toán
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Học viên mới -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user-plus me-1"></i>
                    Học viên mới đăng ký
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Họ tên</th>
                                    <th>Số điện thoại</th>
                                    <th>Email</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($newStudents as $student)
                                <tr>
                                    <td>{{ $student->full_name }}</td>
                                    <td>{{ $student->phone }}</td>
                                    <td>{{ $student->email }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('students.show', $student->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">Chưa có học viên mới</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <a href="{{ route('students.index') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-list me-1"></i>
                            Xem tất cả học viên
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <i class="fas fa-phone-alt me-1"></i>
                    Học viên cần liên hệ
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Họ tên</th>
                                    <th>Số điện thoại</th>
                                    <th>Khóa học</th>
                                    <th>Thời gian chờ</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($waitingContact as $waiting)
                                <tr>
                                    <td>{{ $waiting->student->full_name }}</td>
                                    <td>{{ $waiting->student->phone }}</td>
                                    <td>{{ $waiting->courseItem->name }}</td>
                                    <td>
                                        @if($waiting->last_contact_date)
                                            {{ $waiting->last_contact_date->diffForHumans() }}
                                        @else
                                            <span class="text-danger">Chưa liên hệ</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('waiting-lists.mark-contacted', $waiting->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary" title="Đánh dấu đã liên hệ">
                                                <i class="fas fa-phone"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('enrollments.from-waiting-list', $waiting->id) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-user-plus"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">Không có học viên nào cần liên hệ</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <a href="{{ route('waiting-lists.needs-contact') }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-list me-1"></i>
                            Xem tất cả học viên cần liên hệ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
// Set new default font family and font color to mimic Bootstrap's default styling
Chart.defaults.font.family = '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.color = '#858796';

// Doanh thu theo ngày (7 ngày gần đây)
var revenueByDayCtx = document.getElementById("revenueByDayChart");
var revenueByDayChart = new Chart(revenueByDayCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($revenueByDay['labels']) !!},
        datasets: [{
            label: "Doanh thu",
            lineTension: 0.3,
            backgroundColor: "rgba(78, 115, 223, 0.05)",
            borderColor: "rgba(78, 115, 223, 1)",
            pointRadius: 3,
            pointBackgroundColor: "rgba(78, 115, 223, 1)",
            pointBorderColor: "rgba(78, 115, 223, 1)",
            pointHoverRadius: 5,
            pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
            pointHoverBorderColor: "rgba(78, 115, 223, 1)",
            pointHitRadius: 10,
            pointBorderWidth: 2,
            data: {!! json_encode($revenueByDay['data']) !!},
        }],
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        var value = context.raw;
                        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
                    }
                }
            }
        },
        scales: {
            y: {
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', 
                            notation: 'compact', compactDisplay: 'short' }).format(value);
                    }
                }
            }
        }
    }
});

// Doanh thu 6 tháng
var revenueCtx = document.getElementById("revenueChart");
var revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($revenueChartData['labels']) !!},
        datasets: [{
            label: "Doanh thu",
            lineTension: 0.3,
            backgroundColor: "rgba(78, 115, 223, 0.05)",
            borderColor: "rgba(78, 115, 223, 1)",
            pointRadius: 3,
            pointBackgroundColor: "rgba(78, 115, 223, 1)",
            pointBorderColor: "rgba(78, 115, 223, 1)",
            pointHoverRadius: 5,
            pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
            pointHoverBorderColor: "rgba(78, 115, 223, 1)",
            pointHitRadius: 10,
            pointBorderWidth: 2,
            data: {!! json_encode($revenueChartData['data']) !!},
        }],
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        var value = context.raw;
                        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
                    }
                }
            }
        },
        scales: {
            y: {
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', 
                            notation: 'compact', compactDisplay: 'short' }).format(value);
                    }
                }
            }
        }
    }
});

// Enrollment Chart
var enrollmentCtx = document.getElementById("enrollmentChart");
var enrollmentChart = new Chart(enrollmentCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($enrollmentChartData['labels']) !!},
        datasets: [{
            label: "Số lượt ghi danh",
            backgroundColor: "#4e73df",
            hoverBackgroundColor: "#2e59d9",
            borderColor: "#4e73df",
            data: {!! json_encode($enrollmentChartData['data']) !!},
        }],
    },
    options: {
        maintainAspectRatio: false,
    }
});

// Payment Method Chart
var paymentMethodCtx = document.getElementById("paymentMethodChart");
var paymentMethodChart = new Chart(paymentMethodCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($paymentMethodData['labels']) !!},
        datasets: [{
            data: {!! json_encode($paymentMethodData['data']) !!},
            backgroundColor: {!! json_encode($paymentMethodData['backgroundColor']) !!},
            hoverBackgroundColor: {!! json_encode($paymentMethodData['backgroundColor']) !!},
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        var label = context.label || '';
                        var value = context.raw;
                        var total = context.dataset.data.reduce((a, b) => a + b, 0);
                        var percentage = Math.round((value / total) * 100);
                        return label + ': ' + new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value) + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Gender Chart
var genderCtx = document.getElementById("genderChart");
var genderChart = new Chart(genderCtx, {
    type: 'pie',
    data: {
        labels: {!! json_encode($genderData['labels']) !!},
        datasets: [{
            data: {!! json_encode($genderData['data']) !!},
            backgroundColor: {!! json_encode($genderData['backgroundColor']) !!},
            hoverBackgroundColor: {!! json_encode($genderData['backgroundColor']) !!},
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
    },
    options: {
        maintainAspectRatio: false,
    }
});

// Payment Status Chart
var paymentStatusCtx = document.getElementById("paymentStatusChart");
var paymentStatusChart = new Chart(paymentStatusCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($paymentStatusData['labels']) !!},
        datasets: [{
            data: {!! json_encode($paymentStatusData['data']) !!},
            backgroundColor: {!! json_encode($paymentStatusData['backgroundColor']) !!},
            hoverBackgroundColor: {!! json_encode($paymentStatusData['backgroundColor']) !!},
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        var label = context.label || '';
                        var value = context.raw;
                        var total = context.dataset.data.reduce((a, b) => a + b, 0);
                        var percentage = Math.round((value / total) * 100);
                        return label + ': ' + value + ' học viên (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Top Courses Chart
var topCoursesCtx = document.getElementById("topCoursesChart");
var topCoursesChart = new Chart(topCoursesCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($enrollmentsByCourse['labels']) !!},
        datasets: [{
            label: "Số lượt ghi danh",
            backgroundColor: "#36b9cc",
            hoverBackgroundColor: "#2c9faf",
            borderColor: "#36b9cc",
            data: {!! json_encode($enrollmentsByCourse['data']) !!},
        }],
    },
    options: {
        maintainAspectRatio: false,
        indexAxis: 'y',
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});
</script>
@endpush
