@extends('layouts.app')

@section('page-title', 'Báo cáo ghi danh')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Báo cáo</a></li>
<li class="breadcrumb-item active">Ghi danh</li>
{{-- Tất cả modal đã được thay thế bằng Unified Modal System --}}

@endsection

@section('content')
@php
    // Tính totalEnrollments từ statusStats
    $totalEnrollments = [
        'total' => $statusStats->sum('count'),
        'active' => $statusStats->where('status', 'active')->first()->count ?? 0,
        'waiting' => $statusStats->where('status', 'waiting')->first()->count ?? 0,
        'completed' => $statusStats->where('status', 'completed')->first()->count ?? 0,
        'cancelled' => $statusStats->where('status', 'cancelled')->first()->count ?? 0,
    ];
@endphp

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.enrollments') }}" class="row align-items-center g-3">
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
                        <h5 class="card-title mb-0">Tổng số ghi danh</h5>
                        <h2 class="mt-2 mb-0">{{ number_format($totalEnrollments['total']) }}</h2>
                    </div>
                    <div>
                        <i class="fas fa-user-plus fa-3x opacity-50"></i>
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
                        <h5 class="card-title mb-0">Đang học</h5>
                        <h2 class="mt-2 mb-0">{{ number_format($totalEnrollments['active'] ?? 0) }}</h2>
                    </div>
                    <div>
                        <i class="fas fa-graduation-cap fa-3x opacity-50"></i>
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
                        <h5 class="card-title mb-0">Đã thanh toán</h5>
                        <h2 class="mt-2 mb-0">{{ number_format($paymentStats['paid']) }}</h2>
                    </div>
                    <div>
                        <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
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
                        <h5 class="card-title mb-0">Chưa thanh toán</h5>
                        <h2 class="mt-2 mb-0">{{ number_format($paymentStats['not_paid']) }}</h2>
                    </div>
                    <div>
                        <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
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
                    <i class="fas fa-chart-line me-2"></i>Biểu đồ ghi danh theo ngày
                </h5>
            </div>
            <div class="card-body">
                <div id="enrollmentChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>Top 5 khóa học
                </h5>
            </div>
            <div class="card-body">
                <div id="topCoursesChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list-alt me-2"></i>Ghi danh theo khóa học
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Khóa học</th>
                                <th>Số ghi danh</th>
                                <th>Tỷ lệ (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($enrollmentsByCourse as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->count }}</td>
                                <td>
                                    @php
                                        $percentage = $totalEnrollments['total'] > 0 ? 
                                            round(($item->count / $totalEnrollments['total']) * 100, 1) : 0;
                                    @endphp
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" role="progressbar" 
                                             style="width: {{ $percentage }}%" 
                                             aria-valuenow="{{ $percentage }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">{{ $percentage }}%</div>
                                    </div>
                                </td>
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
                    <i class="fas fa-calendar-alt me-2"></i>Ghi danh theo ngày
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Số ghi danh</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dailyEnrollments as $item)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($item->date)->format('d/m/Y') }}</td>
                                <td>{{ $item->total }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
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
        danger: '#e74a3b'
    };
    
    // Biểu đồ ghi danh
    try {
        const enrollmentLabels = safeJsonParse('{!! json_encode($chartData["labels"] ?? []) !!}', []);
        const enrollmentData = safeJsonParse('{!! json_encode($chartData["data"] ?? []) !!}', []);

        if (document.getElementById("enrollmentChart") && enrollmentLabels.length > 0) {
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
                    name: 'Số ghi danh',
                    data: enrollmentData
                }],
                xaxis: {
                    categories: enrollmentLabels,
                    labels: {
                        style: {
                            colors: '#858796'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function(value) {
                            return Math.round(value);
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
                    theme: 'light',
                    y: {
                        formatter: function(value) {
                            return Math.round(value) + ' học viên';
                        }
                    }
                },
                grid: {
                    borderColor: '#e0e0e0',
                    strokeDashArray: 4
                }
            };

            const enrollmentChart = new ApexCharts(document.querySelector("#enrollmentChart"), options);
            enrollmentChart.render();
        } else {
            console.warn("Không thể tạo biểu đồ ghi danh: Không có dữ liệu hoặc không tìm thấy element");
            document.querySelector("#enrollmentChart").innerHTML = '<div class="alert alert-info">Không có dữ liệu để hiển thị</div>';
        }
    } catch (e) {
        console.error("Lỗi khi tạo biểu đồ ghi danh:", e);
        document.querySelector("#enrollmentChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
    }

    // Biểu đồ top khóa học
    try {
        const courseNames = safeJsonParse('{!! json_encode($topCourses->pluck("name")->toArray() ?? []) !!}', []);
        const courseCounts = safeJsonParse('{!! json_encode($topCourses->pluck("count")->toArray() ?? []) !!}', []);

        if (document.getElementById("topCoursesChart") && courseNames.length > 0) {
            const options = {
                chart: {
                    type: 'pie',
                    height: 300,
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                    toolbar: {
                        show: false
                    }
                },
                labels: courseNames,
                series: courseCounts,
                colors: [colors.primary, colors.success, colors.info, colors.warning, colors.danger],
                legend: {
                    position: 'bottom',
                    fontSize: '14px'
                },
                dataLabels: {
                    enabled: false
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            return value + ' học viên';
                        }
                    }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '0%'
                        },
                        expandOnClick: true
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

            const topCoursesChart = new ApexCharts(document.querySelector("#topCoursesChart"), options);
            topCoursesChart.render();
        } else {
            console.warn("Không thể tạo biểu đồ top khóa học: Không có dữ liệu hoặc không tìm thấy element");
            document.querySelector("#topCoursesChart").innerHTML = '<div class="alert alert-info">Không có dữ liệu để hiển thị</div>';
        }
    } catch (e) {
        console.error("Lỗi khi tạo biểu đồ top khóa học:", e);
        document.querySelector("#topCoursesChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
    }
});
</script>
@endpush 