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
                    <canvas id="revenueChart" width="100%" height="40"></canvas>
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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
// Set new default font family and font color
Chart.defaults.font.family = '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.color = '#858796';

// Area Chart - Revenue
var ctx = document.getElementById("revenueChart");
var myLineChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: {!! json_encode($chartData['labels']) !!},
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
            data: {!! json_encode($chartData['data']) !!},
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('vi-VN').format(value) + ' đ';
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        var label = context.dataset.label || '';
                        var value = context.raw;
                        return label + ': ' + new Intl.NumberFormat('vi-VN').format(value) + ' đ';
                    }
                }
            }
        }
    }
});
</script>
@endpush 