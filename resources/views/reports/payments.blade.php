@extends('layouts.app')

@section('page-title', 'Báo cáo thanh toán')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Báo cáo</a></li>
<li class="breadcrumb-item active">Thanh toán</li>
{{-- Tất cả modal đã được thay thế bằng Unified Modal System --}}

@endsection

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.payments') }}" class="row align-items-center g-3">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Từ ngày</label>
                <input type="date" id="start_date" name="start_date" class="form-control" 
                       value="{{ $startDate->format('Y-m-d') }}">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">Đến ngày</label>
                <input type="date" id="end_date" name="end_date" class="form-control" 
                       value="{{ $endDate->format('Y-m-d') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block w-100">
                    <i class="fas fa-search me-2"></i>Xem báo cáo
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title mb-0">Tổng doanh thu</h5>
                        <h2 class="mt-2 mb-0">{{ number_format($totalStats['total_revenue']) }}</h2>
                    </div>
                    <div>
                        <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title mb-0">Số lượng thanh toán</h5>
                        <h2 class="mt-2 mb-0">{{ number_format($totalStats['payment_count']) }}</h2>
                    </div>
                    <div>
                        <i class="fas fa-receipt fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title mb-0">Đang xử lý</h5>
                        <h2 class="mt-2 mb-0">{{ number_format($totalStats['pending_payments']) }}</h2>
                    </div>
                    <div>
                        <i class="fas fa-clock fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title mb-0">Từ chối</h5>
                        <h2 class="mt-2 mb-0">{{ number_format($totalStats['rejected_payments']) }}</h2>
                    </div>
                    <div>
                        <i class="fas fa-times-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>Biểu đồ thanh toán theo ngày
                </h5>
            </div>
            <div class="card-body">
                <div id="paymentChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>Phương thức thanh toán
                </h5>
            </div>
            <div class="card-body">
                <div id="paymentMethodChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list-alt me-2"></i>Thanh toán theo phương thức
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Phương thức</th>
                                <th>Số lượng</th>
                                <th>Tổng tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paymentsByMethod as $method)
                            <tr>
                                <td>
                                    @if($method->payment_method == 'cash')
                                        <i class="fas fa-money-bill text-success me-1"></i> Tiền mặt
                                    @elseif($method->payment_method == 'bank_transfer')
                                        <i class="fas fa-university text-primary me-1"></i> Chuyển khoản
                                    @elseif($method->payment_method == 'card')
                                        <i class="fas fa-credit-card text-info me-1"></i> Thẻ tín dụng
                                    @elseif($method->payment_method == 'sepay')
                                        <i class="fas fa-qrcode text-danger me-1"></i> SEPAY
                                    @else
                                        <i class="fas fa-question-circle me-1"></i> {{ $method->payment_method }}
                                    @endif
                                </td>
                                <td>{{ $method->count }}</td>
                                <td>{{ number_format($method->total) }} VND</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>Thanh toán theo ngày
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th class="text-end">Tổng tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dailyPayments as $item)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($item->date)->format('d/m/Y') }}</td>
                                <td class="text-end">{{ number_format($item->total) }} VND</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-money-check-alt me-2"></i>Thanh toán gần đây
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Học viên</th>
                        <th>Khóa học</th>
                        <th>Phương thức</th>
                        <th class="text-end">Số tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td>
                            @if($payment->student())
                                {{ $payment->student()->full_name }}
                            @else
                                <span class="text-muted">Không có</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->courseItem())
                                {{ $payment->courseItem()->name }}
                            @else
                                <span class="text-muted">Không có</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $payment->payment_method == 'cash' ? 'bg-success' : 
                                ($payment->payment_method == 'bank_transfer' ? 'bg-primary' : 
                                ($payment->payment_method == 'card' ? 'bg-info' : 'bg-secondary')) }}">
                                {{ $payment->payment_method }}
                            </span>
                        </td>
                        <td class="text-end">{{ number_format($payment->amount) }} VND</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center">Không có dữ liệu</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
{{-- Tất cả modal đã được thay thế bằng Unified Modal System --}}

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
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
    
    // Màu sắc chung
    const colors = {
        primary: '#4e73df',
        success: '#1cc88a',
        info: '#36b9cc',
        warning: '#f6c23e',
        danger: '#e74a3b',
        secondary: '#858796'
    };
    
    // Biểu đồ thanh toán theo ngày
    try {
        const chartLabels = safeJsonParse('{!! json_encode($chartData["labels"] ?? []) !!}', []);
        const chartData = safeJsonParse('{!! json_encode($chartData["data"] ?? []) !!}', []);
        
        if (document.getElementById("paymentChart") && chartLabels.length > 0) {
            const options = {
                chart: {
                    type: 'area',
                    height: 300,
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    }
                },
                series: [{
                    name: 'Doanh thu',
                    data: chartData
                }],
                xaxis: {
                    categories: chartLabels,
                    labels: {
                        style: {
                            colors: '#858796'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function(value) {
                            return new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND',
                                maximumFractionDigits: 0
                            }).format(value);
                        },
                        style: {
                            colors: '#858796'
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                colors: [colors.primary],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.2,
                        stops: [0, 90, 100]
                    }
                },
                markers: {
                    size: 4,
                    colors: [colors.primary],
                    strokeColors: '#fff',
                    strokeWidth: 2,
                    hover: {
                        size: 7
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            return new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND',
                                maximumFractionDigits: 0
                            }).format(value);
                        }
                    }
                }
            };
            
            const paymentChart = new ApexCharts(document.querySelector("#paymentChart"), options);
            paymentChart.render();
        } else {
            console.warn("Không thể tạo biểu đồ thanh toán: Không có dữ liệu hoặc không tìm thấy element");
            document.querySelector("#paymentChart").innerHTML = '<div class="alert alert-info">Không có dữ liệu để hiển thị</div>';
        }
    } catch (e) {
        console.error("Lỗi khi tạo biểu đồ thanh toán:", e);
        document.querySelector("#paymentChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
    }
    
    // Biểu đồ phương thức thanh toán
    try {
        // Tạo dữ liệu từ paymentsByMethod
        const methodData = [];
        const methodLabels = [];
        
        @foreach($paymentsByMethod as $method)
            methodData.push({{ $method->total }});
            @if($method->payment_method == 'cash')
                methodLabels.push('Tiền mặt');
            @elseif($method->payment_method == 'bank_transfer')
                methodLabels.push('Chuyển khoản');
            @elseif($method->payment_method == 'card')
                methodLabels.push('Thẻ tín dụng');
            @elseif($method->payment_method == 'sepay')
                methodLabels.push('SEPAY');
            @else
                methodLabels.push('{{ $method->payment_method }}');
            @endif
        @endforeach
        
        if (document.getElementById("paymentMethodChart") && methodData.length > 0) {
            const options = {
                chart: {
                    type: 'pie',
                    height: 300,
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
                },
                series: methodData,
                labels: methodLabels,
                colors: ['#4bc0c0', '#9966ff', '#ff9f40', '#c9cccf'],
                legend: {
                    position: 'bottom',
                    fontSize: '14px'
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return Math.round(val) + "%";
                    },
                    style: {
                        fontSize: '14px',
                        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto'
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            return new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND',
                                maximumFractionDigits: 0
                            }).format(value);
                        }
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            height: 250
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };
            
            const paymentMethodChart = new ApexCharts(document.querySelector("#paymentMethodChart"), options);
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