@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')
<!-- Thống kê tổng quan -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card bg-primary text-white h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-white bg-opacity-25 p-3 me-3">
                    <i class="fas fa-users fa-2x text-white"></i>
                </div>
                <div>
                    <h3 class="mb-0">{{ number_format($stats['students_count']) }}</h3>
                    <div>Học viên</div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between align-items-center">
                <a class="text-white stretched-link" href="{{ route('students.index') }}">Xem chi tiết</a>
                <i class="fas fa-arrow-circle-right"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-success text-white h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-white bg-opacity-25 p-3 me-3">
                    <i class="fas fa-graduation-cap fa-2x text-white"></i>
                </div>
                <div>
                    <h3 class="mb-0">{{ number_format($stats['courses_count']) }}</h3>
                    <div>Khóa học</div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between align-items-center">
                <a class="text-white stretched-link" href="{{ route('course-items.tree') }}">Xem chi tiết</a>
                <i class="fas fa-arrow-circle-right"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-info text-white h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-white bg-opacity-25 p-3 me-3">
                    <i class="fas fa-user-graduate fa-2x text-white"></i>
                </div>
                <div>
                    <h3 class="mb-0">{{ number_format($stats['enrollments_count']) }}</h3>
                    <div>Lượt ghi danh</div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between align-items-center">
                <a class="text-white stretched-link" href="{{ route('enrollments.index') }}">Xem chi tiết</a>
                <i class="fas fa-arrow-circle-right"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-warning text-white h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-white bg-opacity-25 p-3 me-3">
                    <i class="fas fa-user-clock fa-2x text-white"></i>
                </div>
                <div>
                    <h3 class="mb-0">{{ number_format($stats['waitings_count']) }}</h3>
                    <div>Đang chờ</div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between align-items-center">
                <a class="text-white stretched-link" href="{{ route('waiting-lists.index') }}">Xem chi tiết</a>
                <i class="fas fa-arrow-circle-right"></i>
            </div>
        </div>
    </div>
</div>

<!-- Tài chính tổng quan -->
<div class="row mb-4">
    <div class="col-lg-6">
        <!-- Biểu đồ doanh thu -->
        <div class="card h-100 shadow">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>Doanh thu 7 ngày gần đây
                </h5>
            </div>
            <div class="card-body">
                <div id="revenueChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <!-- Thông tin tài chính -->
        <div class="card h-100 shadow">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="fas fa-money-bill-wave me-2"></i>Tổng quan tài chính
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card border-left-primary h-100">
                            <div class="card-body py-2">
                                <div class="text-uppercase small fw-bold text-primary mb-1">Tổng học phí</div>
                                <div class="h5 mb-0 fw-bold">{{ number_format($financialStats['total_fee']) }} VND</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card border-left-success h-100">
                            <div class="card-body py-2">
                                <div class="text-uppercase small fw-bold text-success mb-1">Đã thanh toán</div>
                                <div class="h5 mb-0 fw-bold">{{ number_format($financialStats['total_paid']) }} VND</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card border-left-warning h-100">
                            <div class="card-body py-2">
                                <div class="text-uppercase small fw-bold text-warning mb-1">Chờ thanh toán</div>
                                <div class="h5 mb-0 fw-bold">{{ number_format($financialStats['total_pending']) }} VND</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card border-left-danger h-100">
                            <div class="card-body py-2">
                                <div class="text-uppercase small fw-bold text-danger mb-1">Còn thiếu</div>
                                <div class="h5 mb-0 fw-bold">{{ number_format($financialStats['total_remaining']) }} VND</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <h6 class="fw-bold">Tỉ lệ thanh toán</h6>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $financialStats['payment_rate'] }}%;" 
                            aria-valuenow="{{ $financialStats['payment_rate'] }}" aria-valuemin="0" aria-valuemax="100">
                            {{ $financialStats['payment_rate'] }}%
                        </div>
                    </div>
                </div>
                
                <div class="mt-3 text-end">
                    <a href="{{ route('enrollments.unpaid') }}" class="btn btn-sm btn-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        {{ $unPaidCount }} học viên chưa thanh toán đủ
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Biểu đồ và thông tin chi tiết -->
<div class="row mb-4">
    <div class="col-md-4">
        <!-- Phương thức thanh toán -->
        <div class="card shadow h-100">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>Phương thức thanh toán
                </h5>
            </div>
            <div class="card-body">
                <div id="paymentMethodChart" style="height: 220px;"></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <!-- Biểu đồ ghi danh -->
        <div class="card shadow h-100">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Ghi danh theo tháng
                </h5>
            </div>
            <div class="card-body">
                <div id="enrollmentChart" style="height: 220px;"></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <!-- Học viên chưa thanh toán -->
        <div class="card shadow h-100">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-exclamation-circle me-2"></i>Cần quan tâm</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <a href="{{ route('enrollments.unpaid') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-clock me-2 text-warning"></i>Học viên chưa thanh toán</span>
                        <span class="badge bg-warning rounded-pill">{{ $unPaidCount }}</span>
                    </a>
                    <a href="{{ route('waiting-lists.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-plus me-2 text-primary"></i>Đăng ký mới chờ xử lý</span>
                        <span class="badge bg-primary rounded-pill">{{ $stats['waitings_count'] }}</span>
                    </a>
                    <a href="{{ route('waiting-lists.needs-contact') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-phone-alt me-2 text-info"></i>Cần liên hệ lại
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Thanh toán gần đây -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-receipt me-2"></i>Thanh toán gần đây
                </h5>
                <a href="{{ route('payments.index') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-list me-1"></i>Xem tất cả
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ngày thanh toán</th>
                                <th>Học viên</th>
                                <th>Khóa học</th>
                                <th>Phương thức</th>
                                <th class="text-end">Số tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($financialStats['recent_payments'] as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                <td>{{ $payment->enrollment->student->full_name }}</td>
                                <td>{{ $payment->enrollment->courseItem->name }}</td>
                                <td>
                                    <span class="badge bg-{{ $payment->payment_method == 'cash' ? 'success' : 
                                        ($payment->payment_method == 'transfer' ? 'info' : 
                                        ($payment->payment_method == 'card' ? 'primary' : 'secondary')) }}">
                                        {{ $payment->payment_method == 'cash' ? 'Tiền mặt' : 
                                          ($payment->payment_method == 'transfer' ? 'Chuyển khoản' : 
                                          ($payment->payment_method == 'card' ? 'Thẻ' : $payment->payment_method)) }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-success">{{ number_format($payment->amount) }} VND</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-3">Chưa có thanh toán nào</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.border-left-primary {border-left: 4px solid #4e73df;}
.border-left-success {border-left: 4px solid #1cc88a;}
.border-left-warning {border-left: 4px solid #f6c23e;}
.border-left-danger {border-left: 4px solid #e74a3b;}

/* ApexCharts Customization */
.apexcharts-tooltip {
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    padding: 10px;
    border: none;
}

.apexcharts-legend-series {
    margin-right: 10px !important;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Định dạng tiền tệ Việt Nam
    function formatCurrency(value) {
        return new Intl.NumberFormat('vi-VN', { 
            style: 'currency', 
            currency: 'VND',
            maximumFractionDigits: 0 
        }).format(value);
    }

    // Màu sắc chung
    const colors = {
        primary: '#4e73df',
        success: '#1cc88a',
        info: '#36b9cc',
        warning: '#f6c23e',
        danger: '#e74a3b',
        secondary: '#858796'
    };

    // Tùy chọn chung cho biểu đồ
    const commonOptions = {
        chart: {
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            toolbar: {
                show: false
            },
            zoom: {
                enabled: false
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        tooltip: {
            theme: 'light',
            x: {
                show: true
            },
            shared: true,
            intersect: false
        },
        legend: {
            position: 'bottom'
        },
        states: {
            hover: {
                filter: {
                    type: 'darken',
                    value: 0.9
                }
            },
            active: {
                filter: {
                    type: 'darken',
                    value: 0.9
                }
            }
        }
    };

    // Kiểm tra và đảm bảo dữ liệu hợp lệ
    const safeJsonParse = function(jsonString, defaultValue = []) {
        try {
            const parsed = JSON.parse(jsonString);
            return parsed || defaultValue;
        } catch (e) {
            console.error("Error parsing JSON:", e);
            return defaultValue;
        }
    };

    // 1. Biểu đồ doanh thu 7 ngày gần đây
    try {
        const revenueLabels = safeJsonParse('{!! json_encode($revenueByDay["labels"] ?? []) !!}', []);
        const revenueData = safeJsonParse('{!! json_encode($revenueByDay["data"] ?? []) !!}', []);

        if (document.getElementById("revenueChart") && revenueLabels.length > 0) {
            const revenueOptions = {
                ...commonOptions,
                chart: {
                    ...commonOptions.chart,
                    height: 300,
                    type: 'bar'
                },
                colors: [colors.primary],
                plotOptions: {
                    bar: {
                        columnWidth: '50%',
                        borderRadius: 4,
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: 1,
                    colors: [colors.primary]
                },
                xaxis: {
                    categories: revenueLabels,
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function(value) {
                            return formatCurrency(value);
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            return formatCurrency(value);
                        }
                    }
                },
                series: [{
                    name: 'Doanh thu',
                    data: revenueData
                }]
            };

            const revenueChart = new ApexCharts(document.querySelector("#revenueChart"), revenueOptions);
            revenueChart.render();
        } else {
            console.warn("Không thể tạo biểu đồ doanh thu: Không có dữ liệu hoặc không tìm thấy element");
            document.querySelector("#revenueChart").innerHTML = '<div class="alert alert-info">Không có dữ liệu để hiển thị</div>';
        }
    } catch (e) {
        console.error("Lỗi khi tạo biểu đồ doanh thu:", e);
        document.querySelector("#revenueChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
    }

    // 2. Biểu đồ ghi danh theo tháng
    try {
        const enrollmentLabels = safeJsonParse('{!! json_encode($enrollmentChartData["labels"] ?? []) !!}', []);
        const enrollmentData = safeJsonParse('{!! json_encode($enrollmentChartData["data"] ?? []) !!}', []);

        if (document.getElementById("enrollmentChart") && enrollmentLabels.length > 0) {
            const enrollmentOptions = {
                ...commonOptions,
                chart: {
                    ...commonOptions.chart,
                    height: 220,
                    type: 'bar'
                },
                colors: [colors.info],
                plotOptions: {
                    bar: {
                        columnWidth: '60%',
                        borderRadius: 4,
                        horizontal: false
                    }
                },
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    categories: enrollmentLabels
                },
                yaxis: {
                    labels: {
                        formatter: function(value) {
                            return Math.round(value);
                        }
                    }
                },
                series: [{
                    name: 'Lượt ghi danh',
                    data: enrollmentData
                }]
            };

            const enrollmentChart = new ApexCharts(document.querySelector("#enrollmentChart"), enrollmentOptions);
            enrollmentChart.render();
        } else {
            console.warn("Không thể tạo biểu đồ ghi danh: Không có dữ liệu hoặc không tìm thấy element");
            document.querySelector("#enrollmentChart").innerHTML = '<div class="alert alert-info">Không có dữ liệu để hiển thị</div>';
        }
    } catch (e) {
        console.error("Lỗi khi tạo biểu đồ ghi danh:", e);
        document.querySelector("#enrollmentChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
    }

    // 3. Biểu đồ phương thức thanh toán
    try {
        const paymentLabels = safeJsonParse('{!! json_encode($paymentMethodData["labels"] ?? []) !!}', []);
        const paymentData = safeJsonParse('{!! json_encode($paymentMethodData["data"] ?? []) !!}', []);

        if (document.getElementById("paymentMethodChart") && paymentLabels.length > 0 && paymentData.length > 0) {
            const paymentMethodOptions = {
                ...commonOptions,
                chart: {
                    ...commonOptions.chart,
                    height: 220,
                    type: 'donut'
                },
                colors: ['#4bc0c0', '#9966ff', '#ff9f40', '#c9cccf'],
                labels: paymentLabels,
                dataLabels: {
                    enabled: false
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%'
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            const total = paymentData.reduce((a, b) => a + b, 0);
                            const percent = total > 0 ? Math.round((value / total) * 100) : 0;
                            return `${formatCurrency(value)} (${percent}%)`;
                        }
                    }
                },
                legend: {
                    position: 'bottom',
                    formatter: function(seriesName, opts) {
                        const value = opts.w.globals.series[opts.seriesIndex];
                        const total = opts.w.globals.series.reduce((a, b) => a + b, 0);
                        const percent = total > 0 ? Math.round((value / total) * 100) : 0;
                        return `${seriesName}: ${percent}%`;
                    }
                },
                series: paymentData
            };

            const paymentMethodChart = new ApexCharts(document.querySelector("#paymentMethodChart"), paymentMethodOptions);
            paymentMethodChart.render();
        } else {
            console.warn("Không thể tạo biểu đồ phương thức thanh toán: Không có dữ liệu hoặc không tìm thấy element");
            document.querySelector("#paymentMethodChart").innerHTML = '<div class="alert alert-info">Không có dữ liệu để hiển thị</div>';
        }
    } catch (e) {
        console.error("Lỗi khi tạo biểu đồ phương thức thanh toán:", e);
        document.querySelector("#paymentMethodChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
    }
});
</script>
@endpush
