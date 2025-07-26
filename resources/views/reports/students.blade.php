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
                    <canvas id="genderChart" width="100%" height="250"></canvas>
                    
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
                    <canvas id="ageChart" width="100%" height="250"></canvas>
                    
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
                    <canvas id="statusChart" width="100%" height="250"></canvas>
                    
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
// Set new default font family and font color to mimic Bootstrap's default styling
Chart.defaults.font.family = '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.color = '#858796';

// Biểu đồ giới tính học viên
var genderCtx = document.getElementById("genderChart");
var genderData = @json($studentsByGender->pluck('total', 'gender')->toArray());
var genderChart = new Chart(genderCtx, {
    type: 'pie',
    data: {
        labels: Object.keys(genderData).map(gender => {
            if (gender === 'male') return 'Nam';
            if (gender === 'female') return 'Nữ';
            return 'Khác';
        }),
        datasets: [{
            data: Object.values(genderData),
            backgroundColor: ['#4e73df', '#e74a3b', '#858796'],
            hoverBackgroundColor: ['#2e59d9', '#d52a1a', '#6e707e'],
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Biểu đồ độ tuổi học viên
var ageCtx = document.getElementById("ageChart");
var ageGroups = @json($ageGroups);
var ageChart = new Chart(ageCtx, {
    type: 'bar',
    data: {
        labels: ['<18', '18-25', '26-35', '36-50', '>50'],
        datasets: [{
            data: [ageGroups.under_18, ageGroups['18_25'], ageGroups['26_35'], ageGroups['36_50'], ageGroups.above_50],
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
            maxBarThickness: 25,
        }],
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                }
            },
            y: {
                beginAtZero: true
            }
        }
    }
});

// Biểu đồ trạng thái ghi danh
var statusCtx = document.getElementById("statusChart");
var statusData = @json($enrollmentCounts->pluck('total', 'status')->toArray());
var statusLabels = Object.keys(statusData).map(status => {
    if (status === 'enrolled') return 'Đang học';
    if (status === 'completed') return 'Hoàn thành';
    if (status === 'cancelled') return 'Đã hủy';
    if (status === 'on_hold') return 'Tạm dừng';
    return status;
});
var statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: Object.values(statusData),
            backgroundColor: ['#1cc88a', '#4e73df', '#e74a3b', '#f6c23e'],
            hoverBackgroundColor: ['#17a673', '#2e59d9', '#d52a1a', '#e3af0e'],
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>
@endpush 