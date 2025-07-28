@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Báo cáo học viên</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Báo cáo</a></li>
        <li class="breadcrumb-item active">Học viên</li>
    </ol>
    
    <!-- Thống kê tổng quan -->
    <div class="row">
        <!-- Thống kê học viên theo giới tính -->
        <div class="col-lg-6 col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-venus-mars me-1"></i>
                    Học viên theo giới tính
                </div>
                <div class="card-body">
                    <div id="genderChart" style="height: 250px;"></div>
                    
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Giới tính</th>
                                    <th class="text-end">Số lượng</th>
                                    <th class="text-end">Tỷ lệ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalStudents = $studentsByGender->sum('total');
                                @endphp
                                @foreach($studentsByGender as $gender)
                                <tr>
                                    <td>
                                        @if($gender->gender == 'male')
                                            <i class="fas fa-male text-primary me-1"></i> Nam
                                        @elseif($gender->gender == 'female')
                                            <i class="fas fa-female text-danger me-1"></i> Nữ
                                        @else
                                            <i class="fas fa-user me-1"></i> Khác
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $gender->total }}</td>
                                    <td class="text-end">{{ number_format(($gender->total / $totalStudents) * 100, 1) }}%</td>
                                </tr>
                                @endforeach
                                @if($studentsByGender->isEmpty())
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
        
        <!-- Thống kê học viên theo độ tuổi -->
        <div class="col-lg-6 col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-birthday-cake me-1"></i>
                    Học viên theo độ tuổi
                </div>
                <div class="card-body">
                    <div id="ageChart" style="height: 250px;"></div>
                    
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Độ tuổi</th>
                                    <th class="text-end">Số lượng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Dưới 18 tuổi</td>
                                    <td class="text-end">{{ $ageGroups['under_18'] }}</td>
                                </tr>
                                <tr>
                                    <td>18-25 tuổi</td>
                                    <td class="text-end">{{ $ageGroups['18_25'] }}</td>
                                </tr>
                                <tr>
                                    <td>26-35 tuổi</td>
                                    <td class="text-end">{{ $ageGroups['26_35'] }}</td>
                                </tr>
                                <tr>
                                    <td>36-50 tuổi</td>
                                    <td class="text-end">{{ $ageGroups['36_50'] }}</td>
                                </tr>
                                <tr>
                                    <td>Trên 50 tuổi</td>
                                    <td class="text-end">{{ $ageGroups['above_50'] }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Thống kê học viên theo trạng thái -->
        <div class="col-lg-6 col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-tasks me-1"></i>
                    Ghi danh theo trạng thái
                </div>
                <div class="card-body">
                    <div id="statusChart" style="height: 250px;"></div>
                    
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Trạng thái</th>
                                    <th class="text-end">Số lượng</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($enrollmentCounts as $status)
                                <tr>
                                    <td>
                                        @if($status->status == 'enrolled')
                                            <span class="badge bg-success">Đang học</span>
                                        @elseif($status->status == 'completed')
                                            <span class="badge bg-primary">Đã hoàn thành</span>
                                        @elseif($status->status == 'cancelled')
                                            <span class="badge bg-danger">Đã hủy</span>
                                        @elseif($status->status == 'on_hold')
                                            <span class="badge bg-warning text-dark">Tạm dừng</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $status->status }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $status->total }}</td>
                                </tr>
                                @endforeach
                                @if($enrollmentCounts->isEmpty())
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
    </div>
    
    <!-- Học viên mới nhất -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user-plus me-1"></i>
                    Học viên mới nhất
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Họ và tên</th>
                                    <th>Email</th>
                                    <th>Số điện thoại</th>
                                    <th>Ngày đăng ký</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentStudents as $student)
                                <tr>
                                    <td>
                                        <a href="{{ route('students.show', $student->id) }}">
                                            {{ $student->full_name }}
                                        </a>
                                    </td>
                                    <td>{{ $student->email }}</td>
                                    <td>{{ $student->phone }}</td>
                                    <td>{{ $student->created_at->format('d/m/Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">Không có dữ liệu</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Học viên có nhiều khóa học nhất -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-award me-1"></i>
                    Học viên có nhiều khóa học nhất
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Họ và tên</th>
                                    <th>Email</th>
                                    <th class="text-center">Số khóa học</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topStudents as $index => $student)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $student->full_name }}</td>
                                    <td>{{ $student->email }}</td>
                                    <td class="text-center">{{ $student->enrollments_count }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('students.enrollments', $student->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-list"></i> Xem khóa học
                                        </a>
                                    </td>
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
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra và đảm bảo dữ liệu hợp lệ
    const safeJsonParse = function(jsonString, defaultValue = {}) {
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
        danger: '#e74a3b',
        success: '#1cc88a',
        warning: '#f6c23e',
        secondary: '#858796'
    };

    // Biểu đồ giới tính học viên
    try {
        const genderData = safeJsonParse('@json($studentsByGender->pluck('total', 'gender')->toArray())', {});
        const genderLabels = Object.keys(genderData).map(gender => {
            if (gender === 'male') return 'Nam';
            if (gender === 'female') return 'Nữ';
            return 'Khác';
        });
        const genderValues = Object.values(genderData);

        if (document.getElementById("genderChart") && genderValues.length > 0) {
            const options = {
                chart: {
                    type: 'pie',
                    height: 250,
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
                },
                series: genderValues,
                labels: genderLabels,
                colors: [colors.primary, colors.danger, colors.secondary],
                legend: {
                    position: 'bottom'
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
                        formatter: function(value, { seriesIndex, dataPointIndex, w }) {
                            const total = genderValues.reduce((a, b) => a + b, 0);
                            const percent = total > 0 ? Math.round((value / total) * 100) : 0;
                            return value + ' học viên (' + percent + '%)';
                        }
                    }
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
            
            const genderChart = new ApexCharts(document.querySelector("#genderChart"), options);
            genderChart.render();
        } else {
            console.warn("Không thể tạo biểu đồ giới tính: Không có dữ liệu hoặc không tìm thấy element");
            document.querySelector("#genderChart").innerHTML = '<div class="alert alert-info">Không có dữ liệu để hiển thị</div>';
        }
    } catch (e) {
        console.error("Lỗi khi tạo biểu đồ giới tính:", e);
        document.querySelector("#genderChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
    }
    
    // Biểu đồ độ tuổi học viên
    try {
        const ageGroups = safeJsonParse('@json($ageGroups)', {});
        const ageData = [
            ageGroups.under_18 || 0, 
            ageGroups['18_25'] || 0, 
            ageGroups['26_35'] || 0, 
            ageGroups['36_50'] || 0, 
            ageGroups.above_50 || 0
        ];
        
        if (document.getElementById("ageChart") && ageData.some(value => value > 0)) {
            const options = {
                chart: {
                    type: 'bar',
                    height: 250,
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        distributed: true,
                        columnWidth: '60%',
                        borderRadius: 3
                    }
                },
                series: [{
                    name: 'Học viên',
                    data: ageData
                }],
                xaxis: {
                    categories: ['<18', '18-25', '26-35', '36-50', '>50'],
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
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                colors: [colors.primary, colors.success, colors.info, colors.warning, colors.danger],
                legend: {
                    show: false
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            return value + ' học viên';
                        }
                    }
                }
            };
            
            const ageChart = new ApexCharts(document.querySelector("#ageChart"), options);
            ageChart.render();
        } else {
            console.warn("Không thể tạo biểu đồ độ tuổi: Không có dữ liệu hoặc không tìm thấy element");
            document.querySelector("#ageChart").innerHTML = '<div class="alert alert-info">Không có dữ liệu để hiển thị</div>';
        }
    } catch (e) {
        console.error("Lỗi khi tạo biểu đồ độ tuổi:", e);
        document.querySelector("#ageChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
    }
    
    // Biểu đồ trạng thái ghi danh
    try {
        const statusData = safeJsonParse('@json($enrollmentCounts->pluck('total', 'status')->toArray())', {});
        const statusLabels = Object.keys(statusData).map(status => {
            if (status === 'enrolled') return 'Đang học';
            if (status === 'completed') return 'Hoàn thành';
            if (status === 'cancelled') return 'Đã hủy';
            if (status === 'on_hold') return 'Tạm dừng';
            return status;
        });
        const statusValues = Object.values(statusData);
        
        if (document.getElementById("statusChart") && statusValues.length > 0) {
            const options = {
                chart: {
                    type: 'donut',
                    height: 250,
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
                },
                series: statusValues,
                labels: statusLabels,
                colors: [colors.success, colors.primary, colors.danger, colors.warning],
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
                            const total = statusValues.reduce((a, b) => a + b, 0);
                            const percent = total > 0 ? Math.round((value / total) * 100) : 0;
                            return value + ' lượt (' + percent + '%)';
                        }
                    }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '50%'
                        }
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            height: 200
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };
            
            const statusChart = new ApexCharts(document.querySelector("#statusChart"), options);
            statusChart.render();
        } else {
            console.warn("Không thể tạo biểu đồ trạng thái: Không có dữ liệu hoặc không tìm thấy element");
            document.querySelector("#statusChart").innerHTML = '<div class="alert alert-info">Không có dữ liệu để hiển thị</div>';
        }
    } catch (e) {
        console.error("Lỗi khi tạo biểu đồ trạng thái:", e);
        document.querySelector("#statusChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
    }
});
</script>
@endpush 