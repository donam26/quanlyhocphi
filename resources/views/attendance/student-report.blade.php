@extends('layouts.app')

@section('title', 'Báo cáo điểm danh học viên')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-0 text-gray-800">Báo cáo điểm danh: {{ $student->full_name }}</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Thông tin học viên</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Họ tên:</th>
                            <td>{{ $student->full_name }}</td>
                        </tr>
                        <tr>
                            <th>Số điện thoại:</th>
                            <td>{{ $student->phone }}</td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>{{ $student->email }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">Thống kê điểm danh</h6>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1">Tổng số buổi học: <strong>{{ $stats['total_classes'] }}</strong></p>
                                <p class="mb-1">Có mặt: <strong class="text-success">{{ $stats['present'] }}</strong></p>
                                <p class="mb-1">Đi muộn: <strong class="text-warning">{{ $stats['late'] }}</strong></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">Vắng mặt: <strong class="text-danger">{{ $stats['absent'] }}</strong></p>
                                <p class="mb-1">Có phép: <strong class="text-info">{{ $stats['excused'] }}</strong></p>
                                <p class="mb-1">Tỷ lệ đi học: <strong>{{ number_format($stats['attendance_rate'], 1) }}%</strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Biểu đồ điểm danh</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <canvas id="attendanceChart" height="300"></canvas>
                </div>
                <div class="col-md-6">
                    <canvas id="attendanceRateChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Lịch sử điểm danh</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Ngày học</th>
                            <th>Lớp học</th>
                            <th>Trạng thái</th>
                            <th>Giờ vào</th>
                            <th>Giờ ra</th>
                            <th>Ghi chú</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $attendance)
                            <tr>
                                <td>{{ $attendance->class_date->format('d/m/Y') }}</td>
                                <td>
                                    {{ $attendance->enrollment->courseClass->name }}
                                    <br>
                                    <small class="text-muted">{{ $attendance->enrollment->courseClass->course->name }}</small>
                                </td>
                                <td>
                                    @if($attendance->status == 'present')
                                        <span class="badge bg-success">Có mặt</span>
                                    @elseif($attendance->status == 'absent')
                                        <span class="badge bg-danger">Vắng mặt</span>
                                    @elseif($attendance->status == 'late')
                                        <span class="badge bg-warning">Đi muộn</span>
                                    @elseif($attendance->status == 'excused')
                                        <span class="badge bg-info">Có phép</span>
                                    @endif
                                </td>
                                <td>{{ $attendance->start_time ?? '--' }}</td>
                                <td>{{ $attendance->end_time ?? '--' }}</td>
                                <td>{{ $attendance->notes ?? '--' }}</td>
                                <td>
                                    <a href="{{ route('attendance.show', $attendance) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('attendance.edit', $attendance) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Không có dữ liệu điểm danh</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Biểu đồ phân bố trạng thái điểm danh
    const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
    const attendanceChart = new Chart(attendanceCtx, {
        type: 'pie',
        data: {
            labels: ['Có mặt', 'Đi muộn', 'Vắng mặt', 'Có phép'],
            datasets: [{
                data: [
                    {{ $stats['present'] }},
                    {{ $stats['late'] }},
                    {{ $stats['absent'] }},
                    {{ $stats['excused'] }}
                ],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(220, 53, 69, 0.7)',
                    'rgba(23, 162, 184, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Phân bố trạng thái điểm danh'
                }
            }
        }
    });

    // Biểu đồ tỷ lệ đi học
    const rateCtx = document.getElementById('attendanceRateChart').getContext('2d');
    const rateChart = new Chart(rateCtx, {
        type: 'doughnut',
        data: {
            labels: ['Đi học', 'Vắng mặt'],
            datasets: [{
                data: [
                    {{ $stats['present'] + $stats['late'] }},
                    {{ $stats['absent'] + $stats['excused'] }}
                ],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.7)',
                    'rgba(220, 53, 69, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Tỷ lệ đi học'
                }
            }
        }
    });
});
</script>
@endpush 