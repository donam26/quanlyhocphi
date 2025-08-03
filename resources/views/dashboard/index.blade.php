@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')
<!-- Bộ lọc thời gian -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <h5 class="mb-0">Thống kê theo khoảng thời gian</h5>
                    <div class="btn-group" role="group" aria-label="Khoảng thời gian">
                        <a href="{{ route('dashboard', ['time_range' => 'day']) }}" class="btn {{ $timeRange === 'day' ? 'btn-primary' : 'btn-outline-primary' }}">Ngày</a>
                        <a href="{{ route('dashboard', ['time_range' => 'month']) }}" class="btn {{ $timeRange === 'month' ? 'btn-primary' : 'btn-outline-primary' }}">Tháng</a>
                        <a href="{{ route('dashboard', ['time_range' => 'quarter']) }}" class="btn {{ $timeRange === 'quarter' ? 'btn-primary' : 'btn-outline-primary' }}">Quý</a>
                        <a href="{{ route('dashboard', ['time_range' => 'year']) }}" class="btn {{ $timeRange === 'year' ? 'btn-primary' : 'btn-outline-primary' }}">Năm</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Thống kê tổng quan -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
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
    <div class="col-xl-3 col-md-6 mb-4">
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
    <div class="col-xl-3 col-md-6 mb-4">
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
    <div class="col-xl-3 col-md-6 mb-4">
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
                <a class="text-white stretched-link" href="{{ route('enrollments.index') }}">Xem chi tiết</a>
                <i class="fas fa-arrow-circle-right"></i>
            </div>
        </div>
    </div>
</div>

<!-- Phần nội dung biểu đồ -->
<div id="dashboard-content">
    <!-- Tài chính tổng quan và biểu đồ doanh thu -->
<div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow h-100">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>Doanh thu theo thời gian
                </h5>
            </div>
            <div class="card-body">
                    <div id="revenueTimeChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow h-100">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="fas fa-money-bill-wave me-2"></i>Tổng quan tài chính
                </h5>
            </div>
            <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="border-start border-4 border-primary px-3 py-2">
                                <div class="text-uppercase small fw-bold text-muted mb-1">Tổng doanh thu</div>
                                <div class="h5 mb-0 fw-bold">{{ number_format($financialStats['total_revenue']) }} đ</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border-start border-4 border-success px-3 py-2">
                                <div class="text-uppercase small fw-bold text-muted mb-1">Doanh thu {{ $timeRange === 'day' ? 'hôm nay' : ($timeRange === 'month' ? 'tháng này' : ($timeRange === 'quarter' ? 'quý này' : 'năm nay')) }}</div>
                                <div class="h5 mb-0 fw-bold">{{ number_format($financialStats['current_period_revenue']) }} đ</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border-start border-4 border-warning px-3 py-2">
                                <div class="text-uppercase small fw-bold text-muted mb-1">Chờ thanh toán</div>
                                <div class="h5 mb-0 fw-bold">{{ number_format($financialStats['total_pending']) }} đ</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border-start border-4 border-danger px-3 py-2">
                                <div class="text-uppercase small fw-bold text-muted mb-1">Còn thiếu</div>
                                <div class="h5 mb-0 fw-bold">{{ number_format($financialStats['total_remaining']) }} đ</div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                        <a href="{{ route('enrollments.unpaid') }}" class="btn btn-sm btn-warning w-100">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        {{ $unPaidCount }} học viên chưa thanh toán đủ
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Doanh thu và tỉ trọng theo khóa học -->
<div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Doanh thu theo khóa học
                    </h5>
                </div>
                <div class="card-body">
                    <div id="revenueByCoursesChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
        <div class="card shadow h-100">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Tỉ trọng doanh thu
                </h5>
            </div>
            <div class="card-body">
                    <div id="revenueRatioChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Học viên theo khóa học và tỉ trọng -->
    <div class="row mb-4">
        <div class="col-lg-8">
        <div class="card shadow h-100">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                        <i class="fas fa-user-graduate me-2"></i>Học viên theo khóa học
                </h5>
            </div>
            <div class="card-body">
                    <div id="studentsByCoursesChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow h-100">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Tỉ trọng học viên
                    </h5>
                </div>
                <div class="card-body">
                    <div id="studentsRatioChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ giới tính và độ tuổi -->
    <div class="row mb-4">
        <div class="col-lg-6">
        <div class="card shadow h-100">
            <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-venus-mars me-2"></i>Phân bố giới tính
                </h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <div id="genderChart" style="height: 250px;"></div>
                    <div class="mt-2">
                        <div class="d-flex justify-content-center">
                            <div class="px-3">
                                <span class="badge bg-primary">Nam</span>: {{ $studentsByGender['data'][0] }} 
                                ({{ $studentsByGender['ratio'][0] }}%)
                            </div>
                            <div class="px-3">
                                <span class="badge bg-danger">Nữ</span>: {{ $studentsByGender['data'][1] }} 
                                ({{ $studentsByGender['ratio'][1] }}%)
                            </div>
                            <div class="px-3">
                                <span class="badge bg-secondary">Khác</span>: {{ $studentsByGender['data'][2] }} 
                                ({{ $studentsByGender['ratio'][2] }}%)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow h-100">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-clock me-2"></i>Phân bố độ tuổi
                    </h5>
                </div>
                <div class="card-body">
                    <div id="ageGroupChart" style="height: 280px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ phương thức học và khu vực địa lý -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow h-100">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-laptop me-2"></i>Phương thức học
                    </h5>
                </div>
                <div class="card-body">
                    <div id="learningModeChart" style="height: 280px;"></div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-center flex-wrap">
                            <div class="px-3 mb-2">
                                <span class="badge bg-success">Trực tuyến</span>: {{ $studentsByLearningMode['data'][0] }} 
                                ({{ $studentsByLearningMode['ratio'][0] }}%)
                            </div>
                            <div class="px-3 mb-2">
                                <span class="badge bg-info">Trực tiếp</span>: {{ $studentsByLearningMode['data'][1] }} 
                                ({{ $studentsByLearningMode['ratio'][1] }}%)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow h-100">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>Phân bố khu vực
                    </h5>
                </div>
                <div class="card-body">
                    <div id="regionChart" style="height: 280px;"></div>
            </div>
        </div>
    </div>
</div>

    <!-- Danh sách chờ và Thanh toán gần đây -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-clock me-2"></i>Danh sách chờ ({{ $waitingList['total'] }} người)
                    </h5>
                    <a href="{{ route('enrollments.waiting-list') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-list me-1"></i>Xem tất cả
                    </a>
                </div>
                <div class="card-body">
                    @if($waitingList['total'] > 0)
                        <div id="waitingListChart" style="height: 200px;"></div>
                        <div class="table-responsive mt-3">
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Khóa học</th>
                                        <th class="text-center">Số lượng</th>
                                        <th class="text-center">Tỉ lệ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($waitingList['by_course'] as $course)
                                    <tr>
                                        <td>{{ $course['name'] }}</td>
                                        <td class="text-center">{{ $course['count'] }}</td>
                                        <td class="text-center">{{ $course['ratio'] }}%</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">Hiện không có học viên nào trong danh sách chờ</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow h-100">
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
                                    <th>Ngày</th>
                                <th>Học viên</th>
                                <th>Khóa học</th>
                                <th class="text-end">Số tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                                @forelse($recentPayments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                <td>{{ $payment->enrollment->student->full_name }}</td>
                                <td>{{ $payment->enrollment->courseItem->name }}</td>
                                    <td class="text-end fw-bold text-success">{{ number_format($payment->amount) }} đ</td>
                            </tr>
                            @empty
                            <tr>
                                    <td colspan="4" class="text-center py-3">Chưa có thanh toán nào</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Custom styles cho dashboard */
.border-start.border-4 {
    border-left-width: 4px !important;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hàm định dạng tiền tệ Việt Nam
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
        secondary: '#858796',
        primaryLight: '#eaefff',
        successLight: '#e0fff5',
        infoLight: '#e3f8fa',
        warningLight: '#fff8e8'
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
            shared: true,
            intersect: false
        },
        legend: {
            position: 'bottom'
        }
    };

    // Render biểu đồ doanh thu theo thời gian
    function renderRevenueTimeChart() {
        try {
            const revenueLabels = @json($revenueByTime['labels'] ?? []);
            const revenueData = @json($revenueByTime['data'] ?? []);

            if (document.getElementById("revenueTimeChart") && revenueLabels.length > 0) {
            const revenueOptions = {
                ...commonOptions,
                chart: {
                    ...commonOptions.chart,
                    height: 300,
                        type: 'area'
                },
                colors: [colors.primary],
                dataLabels: {
                    enabled: false
                },
                stroke: {
                        curve: 'smooth',
                        width: 2
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            opacityTo: 0.2,
                            stops: [0, 90, 100]
                        }
                },
                xaxis: {
                        categories: revenueLabels
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

                const revenueTimeChart = new ApexCharts(document.querySelector("#revenueTimeChart"), revenueOptions);
                revenueTimeChart.render();
        }
    } catch (e) {
        console.error("Lỗi khi tạo biểu đồ doanh thu:", e);
            document.querySelector("#revenueTimeChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
        }
    }

    // Render biểu đồ doanh thu theo khóa học
    function renderRevenueByCoursesChart() {
        try {
            const courseLabels = @json($revenueByCourses['labels'] ?? []);
            const courseData = @json($revenueByCourses['data'] ?? []);

            if (document.getElementById("revenueByCoursesChart") && courseLabels.length > 0) {
                const revenueByCoursesOptions = {
                    ...commonOptions,
                    chart: {
                        ...commonOptions.chart,
                        height: 300,
                        type: 'bar'
                    },
                    colors: [colors.success],
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            columnWidth: '55%',
                            borderRadius: 2,
                            dataLabels: {
                                position: 'top',
                            },
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            return formatCurrency(val);
                        },
                        style: {
                            fontSize: '12px',
                            colors: ["#304758"]
                        }
                    },
                    xaxis: {
                        categories: courseLabels,
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
                        data: courseData
                    }]
                };

                const revenueByCoursesChart = new ApexCharts(document.querySelector("#revenueByCoursesChart"), revenueByCoursesOptions);
                revenueByCoursesChart.render();
            }
        } catch (e) {
            console.error("Lỗi khi tạo biểu đồ doanh thu theo khóa học:", e);
            document.querySelector("#revenueByCoursesChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
        }
    }

    // Render biểu đồ tỉ trọng doanh thu
    function renderRevenueRatioChart() {
        try {
            const courseLabels = @json($revenueByCourses['labels'] ?? []);
            const ratioData = @json($revenueByCourses['ratio'] ?? []);

            if (document.getElementById("revenueRatioChart") && courseLabels.length > 0) {
                const revenueRatioOptions = {
                    ...commonOptions,
                    chart: {
                        ...commonOptions.chart,
                        height: 300,
                        type: 'pie'
                    },
                    colors: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6610f2', '#fd7e14', '#20c9a6', '#5a5c69', '#858796'],
                    labels: courseLabels,
                    legend: {
                        position: 'bottom',
                        formatter: function(seriesName, opts) {
                            return seriesName + ": " + opts.w.globals.series[opts.seriesIndex] + '%';
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            return val.toFixed(1) + "%";
                        },
                        style: {
                            fontSize: '12px',
                        },
                        dropShadow: {
                            enabled: false
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    series: ratioData,
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                height: 300
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                };

                const revenueRatioChart = new ApexCharts(document.querySelector("#revenueRatioChart"), revenueRatioOptions);
                revenueRatioChart.render();
            }
        } catch (e) {
            console.error("Lỗi khi tạo biểu đồ tỉ trọng doanh thu:", e);
            document.querySelector("#revenueRatioChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
        }
    }

    // Render biểu đồ học viên theo khóa học
    function renderStudentsByCoursesChart() {
        try {
            const courseLabels = @json($studentsByCourses['labels'] ?? []);
            const studentsData = @json($studentsByCourses['data'] ?? []);

            if (document.getElementById("studentsByCoursesChart") && courseLabels.length > 0) {
                const studentsByCoursesOptions = {
                ...commonOptions,
                chart: {
                    ...commonOptions.chart,
                        height: 300,
                    type: 'bar'
                },
                colors: [colors.info],
                plotOptions: {
                    bar: {
                            horizontal: true,
                            columnWidth: '55%',
                            borderRadius: 2,
                            dataLabels: {
                                position: 'top',
                            },
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            return val;
                        },
                        style: {
                            fontSize: '12px',
                            colors: ["#304758"]
                        }
                    },
                    xaxis: {
                        categories: courseLabels
                    },
                    series: [{
                        name: 'Học viên',
                        data: studentsData
                    }]
                };

                const studentsByCoursesChart = new ApexCharts(document.querySelector("#studentsByCoursesChart"), studentsByCoursesOptions);
                studentsByCoursesChart.render();
            }
        } catch (e) {
            console.error("Lỗi khi tạo biểu đồ học viên theo khóa học:", e);
            document.querySelector("#studentsByCoursesChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
        }
    }

    // Render biểu đồ tỉ trọng học viên
    function renderStudentsRatioChart() {
        try {
            const courseLabels = @json($studentsByCourses['labels'] ?? []);
            const ratioData = @json($studentsByCourses['ratio'] ?? []);

            if (document.getElementById("studentsRatioChart") && courseLabels.length > 0) {
                const studentsRatioOptions = {
                    ...commonOptions,
                    chart: {
                        ...commonOptions.chart,
                        height: 300,
                        type: 'pie'
                    },
                    colors: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6610f2', '#fd7e14', '#20c9a6', '#5a5c69', '#858796'],
                    labels: courseLabels,
                    legend: {
                        position: 'bottom',
                        formatter: function(seriesName, opts) {
                            return seriesName + ": " + opts.w.globals.series[opts.seriesIndex] + '%';
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            return val.toFixed(1) + "%";
                        },
                        style: {
                            fontSize: '12px',
                        },
                        dropShadow: {
                            enabled: false
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    series: ratioData,
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                height: 300
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                };

                const studentsRatioChart = new ApexCharts(document.querySelector("#studentsRatioChart"), studentsRatioOptions);
                studentsRatioChart.render();
            }
        } catch (e) {
            console.error("Lỗi khi tạo biểu đồ tỉ trọng học viên:", e);
            document.querySelector("#studentsRatioChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
        }
    }

    // Render biểu đồ giới tính
    function renderGenderChart() {
        try {
            const genderLabels = @json($studentsByGender['labels'] ?? []);
            const genderData = @json($studentsByGender['data'] ?? []);

            if (document.getElementById("genderChart") && genderLabels.length > 0) {
                const genderOptions = {
                    ...commonOptions,
                    chart: {
                        ...commonOptions.chart,
                        height: 250,
                        type: 'donut'
                    },
                    colors: [colors.primary, colors.danger, colors.secondary],
                    labels: genderLabels,
                    series: genderData,
                    legend: {
                        show: false
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '65%',
                                labels: {
                                    show: true,
                                    name: {
                                        show: true
                                    },
                                    value: {
                                        show: true,
                                        formatter: function (val) {
                                            return val;
                                        }
                                    },
                                    total: {
                                        show: true,
                                        label: 'Tổng',
                                        formatter: function (w) {
                                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        }
                                    }
                                }
                            }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                };

                const genderChart = new ApexCharts(document.querySelector("#genderChart"), genderOptions);
                genderChart.render();
            }
        } catch (e) {
            console.error("Lỗi khi tạo biểu đồ giới tính:", e);
            document.querySelector("#genderChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
        }
    }

    // Render biểu đồ độ tuổi
    function renderAgeGroupChart() {
        try {
            const ageLabels = @json($studentsByAge['labels'] ?? []);
            const ageData = @json($studentsByAge['data'] ?? []);
            const ageRatio = @json($studentsByAge['ratio'] ?? []);

            if (document.getElementById("ageGroupChart") && ageLabels.length > 0) {
                const ageGroupOptions = {
                    ...commonOptions,
                    chart: {
                        ...commonOptions.chart,
                        height: 280,
                        type: 'bar'
                    },
                    colors: [colors.primary, colors.success, colors.info, colors.warning, colors.danger],
                    plotOptions: {
                        bar: {
                            columnWidth: '45%',
                            distributed: true,
                            dataLabels: {
                                position: 'top',
                            },
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            return val + ' (' + ageRatio[ageData.indexOf(val)].toFixed(1) + '%)';
                        },
                        style: {
                            fontSize: '12px',
                            colors: ["#304758"]
                        },
                        offsetY: -20
                    },
                xaxis: {
                        categories: ageLabels,
                        labels: {
                            style: {
                                fontSize: '12px'
                            }
                        },
                },
                yaxis: {
                    labels: {
                        formatter: function(value) {
                            return Math.round(value);
                        }
                    }
                },
                    legend: {
                        show: false
                    },
                series: [{
                        name: 'Số lượng',
                        data: ageData
                    }]
                };

                const ageGroupChart = new ApexCharts(document.querySelector("#ageGroupChart"), ageGroupOptions);
                ageGroupChart.render();
            }
        } catch (e) {
            console.error("Lỗi khi tạo biểu đồ độ tuổi:", e);
            document.querySelector("#ageGroupChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
        }
    }

    // Render biểu đồ phương thức học
    function renderLearningModeChart() {
        try {
            const modeLabels = @json($studentsByLearningMode['labels'] ?? []);
            const modeData = @json($studentsByLearningMode['data'] ?? []);

            if (document.getElementById("learningModeChart") && modeLabels.length > 0) {
                const learningModeOptions = {
                ...commonOptions,
                chart: {
                    ...commonOptions.chart,
                        height: 280,
                    type: 'donut'
                },
                    colors: [colors.success, colors.info, colors.secondary],
                    labels: modeLabels,
                    series: modeData,
                    legend: {
                        show: false
                },
                plotOptions: {
                    pie: {
                        donut: {
                                size: '65%',
                                labels: {
                                    show: true,
                                    name: {
                                        show: true
                                    },
                                    value: {
                                        show: true,
                                        formatter: function (val) {
                                            return val;
                                        }
                                    },
                                    total: {
                                        show: true,
                                        label: 'Tổng',
                                        formatter: function (w) {
                                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        }
                                    }
                                }
                            }
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                };

                const learningModeChart = new ApexCharts(document.querySelector("#learningModeChart"), learningModeOptions);
                learningModeChart.render();
            }
        } catch (e) {
            console.error("Lỗi khi tạo biểu đồ phương thức học:", e);
            document.querySelector("#learningModeChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
        }
    }

    // Render biểu đồ khu vực
    function renderRegionChart() {
        try {
            const regionLabels = @json($studentsByRegion['labels'] ?? []);
            const regionData = @json($studentsByRegion['data'] ?? []);
            const regionRatio = @json($studentsByRegion['ratio'] ?? []);

            if (document.getElementById("regionChart") && regionLabels.length > 0) {
                const regionOptions = {
                    ...commonOptions,
                    chart: {
                        ...commonOptions.chart,
                        height: 280,
                        type: 'bar'
                    },
                    colors: ['#36b9cc', '#1cc88a', '#4e73df', '#858796'],
                    plotOptions: {
                        bar: {
                            columnWidth: '45%',
                            distributed: true,
                            dataLabels: {
                                position: 'top',
                            },
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            return val + ' (' + regionRatio[regionData.indexOf(val)].toFixed(1) + '%)';
                        },
                        style: {
                            fontSize: '12px',
                            colors: ["#304758"]
                        },
                        offsetY: -20
                    },
                    xaxis: {
                        categories: regionLabels,
                        labels: {
                            style: {
                                fontSize: '12px'
                            }
                        },
                    },
                    yaxis: {
                        labels: {
                        formatter: function(value) {
                                return Math.round(value);
                            }
                        }
                    },
                    legend: {
                        show: false
                    },
                    series: [{
                        name: 'Số lượng',
                        data: regionData
                    }]
                };

                const regionChart = new ApexCharts(document.querySelector("#regionChart"), regionOptions);
                regionChart.render();
            }
        } catch (e) {
            console.error("Lỗi khi tạo biểu đồ khu vực:", e);
            document.querySelector("#regionChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
        }
    }

    // Render biểu đồ danh sách chờ
    function renderWaitingListChart() {
        try {
            const waitingLabels = @json($waitingList['labels'] ?? []);
            const waitingData = @json($waitingList['data'] ?? []);

            if (document.getElementById("waitingListChart") && waitingLabels.length > 0) {
                const waitingListOptions = {
                    ...commonOptions,
                    chart: {
                        ...commonOptions.chart,
                        height: 200,
                        type: 'bar',
                        sparkline: {
                            enabled: false
                        }
                    },
                    colors: [colors.warning],
                    plotOptions: {
                        bar: {
                            columnWidth: '60%',
                            borderRadius: 2
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val) {
                            return val;
                        },
                        style: {
                            fontSize: '10px'
                        }
                    },
                    xaxis: {
                        categories: waitingLabels.map(label => label.length > 20 ? label.substring(0, 20) + '...' : label),
                        labels: {
                            style: {
                                fontSize: '10px'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            formatter: function(val) {
                                return Math.round(val);
                            }
                        }
                    },
                    series: [{
                        name: 'Số lượng',
                        data: waitingData
                    }]
                };

                const waitingListChart = new ApexCharts(document.querySelector("#waitingListChart"), waitingListOptions);
                waitingListChart.render();
            }
        } catch (e) {
            console.error("Lỗi khi tạo biểu đồ danh sách chờ:", e);
            document.querySelector("#waitingListChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
        }
    }

    // Khởi tạo tất cả biểu đồ
    renderRevenueTimeChart();
    renderRevenueByCoursesChart();
    renderRevenueRatioChart();
    renderStudentsByCoursesChart();
    renderStudentsRatioChart();
    renderGenderChart();
    renderAgeGroupChart();
    renderLearningModeChart();
    renderRegionChart();
    if (document.getElementById("waitingListChart")) {
        renderWaitingListChart();
    }
});
</script>
@endpush

