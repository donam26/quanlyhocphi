@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Báo cáo doanh thu</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Báo cáo</a></li>
        <li class="breadcrumb-item active">Doanh thu</li>
    </ol>
    
    <!-- Bộ lọc -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Bộ lọc báo cáo
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reports.revenue') }}" id="filter-form" class="row g-3">
                <div class="col-md-5">
                    <label for="start_date" class="form-label">Từ ngày</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-5">
                    <label for="end_date" class="form-label">Đến ngày</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Tìm kiếm
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tổng quan doanh thu -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-area me-1"></i>
                    Doanh thu theo ngày
                </div>
                <div class="card-body">
                    <div id="revenueChart" style="height: 300px;"></div>
                </div>
                <div class="card-footer small text-muted">
                    Dữ liệu từ {{ $startDate->format('d/m/Y') }} đến {{ $endDate->format('d/m/Y') }}
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-money-bill-wave me-1"></i>
                    Tổng quan doanh thu
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card border-left-primary h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Tổng doanh thu</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                {{ number_format($totalRevenue, 0, ',', '.') }} VND
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
                                                Trung bình mỗi ngày</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                @php
                                                    $days = max(1, $startDate->diffInDays($endDate) + 1);
                                                    $avgDaily = $totalRevenue / $days;
                                                @endphp
                                                {{ number_format($avgDaily, 0, ',', '.') }} VND
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-3">
                    
                    <h5 class="mb-3">Doanh thu theo phương thức thanh toán</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Phương thức</th>
                                <th class="text-end">Doanh thu</th>
                                <th class="text-end">Tỷ lệ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalByMethod = 0; @endphp
                            @foreach($revenueByMethod as $item)
                                @php $totalByMethod += $item->total; @endphp
                                <tr>
                                    <td>
                                        @if($item->payment_method == 'cash')
                                            <i class="fas fa-money-bill text-success me-1"></i> Tiền mặt
                                        @elseif($item->payment_method == 'bank_transfer')
                                            <i class="fas fa-university text-primary me-1"></i> Chuyển khoản
                                        @elseif($item->payment_method == 'sepay')
                                            <i class="fas fa-qrcode text-danger me-1"></i> SEPAY
                                        @else
                                            <i class="fas fa-credit-card me-1"></i> {{ $item->payment_method }}
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($item->total, 0, ',', '.') }} VND</td>
                                    <td class="text-end">{{ number_format(($item->total / $totalRevenue) * 100, 1) }}%</td>
                                </tr>
                            @endforeach
                            @if($revenueByMethod->isEmpty())
                                <tr>
                                    <td colspan="3" class="text-center">Không có dữ liệu</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Doanh thu theo khóa học -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-graduation-cap me-1"></i>
                    Doanh thu theo khóa học
                </div>
                <a href="{{ route('reports.revenue.export', request()->all()) }}" class="btn btn-sm btn-success">
                    <i class="fas fa-file-excel me-1"></i> Xuất Excel
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Khóa học</th>
                            <th class="text-end">Doanh thu</th>
                            <th class="text-end">Tỷ lệ</th>
                            <th>Biểu đồ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($revenueByCourse as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->name }}</td>
                            <td class="text-end">{{ number_format($item->total, 0, ',', '.') }} VND</td>
                            <td class="text-end">{{ number_format(($item->total / $totalRevenue) * 100, 1) }}%</td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                        style="width: {{ ($item->total / $totalRevenue) * 100 }}%;" 
                                        aria-valuenow="{{ ($item->total / $totalRevenue) * 100 }}" 
                                        aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        @if($revenueByCourse->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center">Không có dữ liệu</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Doanh thu chi tiết theo ngày -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-calendar-alt me-1"></i>
                    Chi tiết doanh thu theo ngày
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th class="text-end">Doanh thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dailyRevenue as $item)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($item->date)->format('d/m/Y') }}</td>
                            <td class="text-end">{{ number_format($item->total, 0, ',', '.') }} VND</td>
                        </tr>
                        @endforeach
                        @if($dailyRevenue->isEmpty())
                            <tr>
                                <td colspan="2" class="text-center">Không có dữ liệu</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
{{-- Tất cả modal đã được thay thế bằng Unified Modal System --}}

@endsection

@push('styles')
<style>
.border-left-primary {border-left: 4px solid #4e73df;}
.border-left-success {border-left: 4px solid #1cc88a;}
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

    // Biểu đồ doanh thu
    try {
        const chartLabels = safeJsonParse('{!! json_encode($chartData["labels"] ?? []) !!}', []);
        const chartData = safeJsonParse('{!! json_encode($chartData["data"] ?? []) !!}', []);

        if (document.getElementById("revenueChart") && chartLabels.length > 0) {
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
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                series: [{
                    name: 'Doanh thu',
                    data: chartData
                }],
                xaxis: {
                    categories: chartLabels,
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    },
                    labels: {
                        style: {
                            colors: '#858796'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function(value) {
                            return formatCurrency(value);
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
                colors: ['#4e73df'],
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
                    colors: ['#4e73df'],
                    strokeColors: '#fff',
                    strokeWidth: 2,
                    hover: {
                        size: 7
                    }
                },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function(value) {
                            return formatCurrency(value);
                        }
                    }
                },
                grid: {
                    borderColor: '#e0e0e0',
                    strokeDashArray: 4,
                    position: 'back'
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right'
                }
            };

            const revenueChart = new ApexCharts(document.querySelector("#revenueChart"), options);
            revenueChart.render();
        } else {
            console.warn("Không thể tạo biểu đồ doanh thu: Không có dữ liệu hoặc không tìm thấy element");
            document.querySelector("#revenueChart").innerHTML = '<div class="alert alert-info">Không có dữ liệu để hiển thị</div>';
        }
    } catch (e) {
        console.error("Lỗi khi tạo biểu đồ doanh thu:", e);
        document.querySelector("#revenueChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
    }
});
</script>
@endpush 