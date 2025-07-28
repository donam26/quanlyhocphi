@extends('layouts.app')

@section('page-title', 'Báo cáo điểm danh')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Báo cáo</a></li>
<li class="breadcrumb-item active">Điểm danh</li>
@endsection

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.attendance') }}" class="row align-items-center g-3">
            <div class="col-md-8">
                <label for="course_item_id" class="form-label">Chọn khóa học</label>
                <select id="course_item_id" name="course_item_id" class="form-select select2" required>
                    <option value="">-- Chọn khóa học --</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ request('course_item_id') == $course->id ? 'selected' : '' }}>
                            {{ $course->name }}
                        </option>
                    @endforeach
                </select>
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

@if($courseItem)
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">Tổng buổi học</h5>
                            <h2 class="mt-2 mb-0">{{ array_sum($attendanceStats ?? []) }}</h2>
                        </div>
                        <div>
                            <i class="fas fa-calendar-alt fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">Có mặt</h5>
                            <h2 class="mt-2 mb-0">{{ $attendanceStats['present'] ?? 0 }}</h2>
                        </div>
                        <div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">Vắng mặt</h5>
                            <h2 class="mt-2 mb-0">{{ $attendanceStats['absent'] ?? 0 }}</h2>
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
                        <i class="fas fa-chart-line me-2"></i>Biểu đồ điểm danh theo ngày
                    </h5>
                </div>
                <div class="card-body">
                    <div id="attendanceChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Tỷ lệ điểm danh
                    </h5>
                </div>
                <div class="card-body">
                    <div id="attendanceRateChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-check me-2"></i>Điểm danh theo ngày
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th class="text-center">Có mặt</th>
                                    <th class="text-center">Vắng</th>
                                    <th class="text-center">Đi muộn</th>
                                    <th class="text-center">Tỷ lệ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendanceByDate ?? [] as $item)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($item->date)->format('d/m/Y') }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-success">{{ $item->present_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger">{{ $item->absent_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark">{{ $item->late_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $total = $item->present_count + $item->absent_count + $item->late_count;
                                            $rate = $total > 0 ? round(($item->present_count / $total) * 100, 1) : 0;
                                        @endphp
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: {{ $rate }}%;" 
                                                 aria-valuenow="{{ $rate }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">{{ $rate }}%</div>
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
                        <i class="fas fa-user-check me-2"></i>Học viên với tỷ lệ điểm danh cao nhất
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Học viên</th>
                                    <th class="text-center">Có mặt</th>
                                    <th class="text-center">Vắng</th>
                                    <th class="text-center">Đi muộn</th>
                                    <th class="text-center">Tỷ lệ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($studentsAttendance ?? [] as $student)
                                <tr>
                                    <td>{{ $student->full_name }}</td>
                                    <td class="text-center">{{ $student->present_count }}</td>
                                    <td class="text-center">{{ $student->absent_count }}</td>
                                    <td class="text-center">{{ $student->late_count }}</td>
                                    <td class="text-center">
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: {{ $student->attendance_rate }}%;" 
                                                 aria-valuenow="{{ $student->attendance_rate }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">{{ $student->attendance_rate }}%</div>
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
    </div>
@else
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Vui lòng chọn khóa học để xem báo cáo điểm danh.
    </div>
@endif
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

    @if($courseItem && isset($chartData))
    // Màu sắc chung
    const colors = {
        success: 'rgba(40, 167, 69, 0.7)',
        danger: 'rgba(220, 53, 69, 0.7)',
        warning: 'rgba(255, 193, 7, 0.7)'
    };

    // Biểu đồ điểm danh theo ngày
    try {
        const labels = safeJsonParse('{!! json_encode($chartData["labels"] ?? []) !!}', []);
        const presentData = safeJsonParse('{!! json_encode($chartData["present_data"] ?? []) !!}', []);
        const absentData = safeJsonParse('{!! json_encode($chartData["absent_data"] ?? []) !!}', []);
        const lateData = safeJsonParse('{!! json_encode($chartData["late_data"] ?? []) !!}', []);

        if (document.getElementById("attendanceChart") && labels.length > 0) {
            const options = {
                chart: {
                    type: 'bar',
                    height: 300,
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                    toolbar: {
                        show: false
                    },
                    stacked: false
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 2
                    },
                },
                series: [
                    {
                        name: 'Có mặt',
                        data: presentData
                    },
                    {
                        name: 'Vắng mặt',
                        data: absentData
                    },
                    {
                        name: 'Đi muộn',
                        data: lateData
                    }
                ],
                xaxis: {
                    categories: labels,
                    labels: {
                        style: {
                            colors: '#858796',
                            fontSize: '12px'
                        },
                        rotate: -45,
                        rotateAlways: true
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
                colors: [colors.success, colors.danger, colors.warning],
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center'
                },
                tooltip: {
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

            const attendanceChart = new ApexCharts(document.querySelector("#attendanceChart"), options);
            attendanceChart.render();
        } else {
            console.warn("Không thể tạo biểu đồ điểm danh: Không có dữ liệu hoặc không tìm thấy element");
            document.querySelector("#attendanceChart").innerHTML = '<div class="alert alert-info">Không có dữ liệu để hiển thị</div>';
        }
    } catch (e) {
        console.error("Lỗi khi tạo biểu đồ điểm danh:", e);
        document.querySelector("#attendanceChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
    }

    // Biểu đồ tỷ lệ điểm danh
    try {
        const stats = {
            present: {{ $attendanceStats['present'] ?? 0 }},
            absent: {{ $attendanceStats['absent'] ?? 0 }},
            late: {{ $attendanceStats['late'] ?? 0 }}
        };
        
        if (document.getElementById("attendanceRateChart") && (stats.present > 0 || stats.absent > 0 || stats.late > 0)) {
            const options = {
                chart: {
                    type: 'pie',
                    height: 300,
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                    toolbar: {
                        show: false
                    }
                },
                series: [stats.present, stats.absent, stats.late],
                labels: ['Có mặt', 'Vắng mặt', 'Đi muộn'],
                colors: [colors.success, colors.danger, colors.warning],
                legend: {
                    position: 'bottom',
                    fontSize: '14px'
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val, opts) {
                        const total = stats.present + stats.absent + stats.late;
                        const percent = total > 0 ? Math.round((opts.w.globals.series[opts.seriesIndex] / total) * 100) : 0;
                        return percent + '%';
                    },
                    style: {
                        fontSize: '14px',
                        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto',
                        fontWeight: 'bold'
                    },
                    dropShadow: {
                        enabled: false
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            const total = stats.present + stats.absent + stats.late;
                            const percent = total > 0 ? Math.round((value / total) * 100) : 0;
                            return value + ' học viên (' + percent + '%)';
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

            const attendanceRateChart = new ApexCharts(document.querySelector("#attendanceRateChart"), options);
            attendanceRateChart.render();
        } else {
            console.warn("Không thể tạo biểu đồ tỷ lệ điểm danh: Không có dữ liệu hoặc không tìm thấy element");
            document.querySelector("#attendanceRateChart").innerHTML = '<div class="alert alert-info">Không có dữ liệu để hiển thị</div>';
        }
    } catch (e) {
        console.error("Lỗi khi tạo biểu đồ tỷ lệ điểm danh:", e);
        document.querySelector("#attendanceRateChart").innerHTML = '<div class="alert alert-danger">Đã xảy ra lỗi khi tạo biểu đồ</div>';
    }
    @endif
});
</script>
@endpush 