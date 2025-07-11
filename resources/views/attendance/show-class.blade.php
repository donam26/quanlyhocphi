@extends('layouts.app')

@section('title', 'Điểm danh lớp')

@section('page-title', 'Điểm danh lớp')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('attendance.index') }}">Điểm danh</a></li>
    <li class="breadcrumb-item active">{{ $courseClass->name }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Thông tin lớp học</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <th width="30%">Tên lớp:</th>
                        <td>{{ $courseClass->name }}</td>
                    </tr>
                    <tr>
                        <th>Khóa học:</th>
                        <td>{{ $courseClass->course->name }}</td>
                    </tr>
                    <tr>
                        <th>Ngày bắt đầu:</th>
                        <td>{{ $courseClass->start_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <th>Ngày kết thúc:</th>
                        <td>{{ $courseClass->end_date->format('d/m/Y') }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <div class="alert alert-info">
                    <h6 class="alert-heading">Thống kê điểm danh ngày {{ \Carbon\Carbon::parse($classDate)->format('d/m/Y') }}</h6>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1">Tổng học viên: <strong>{{ $stats['total_students'] }}</strong></p>
                            <p class="mb-1">Có mặt: <strong class="text-success">{{ $stats['present'] }}</strong></p>
                            <p class="mb-1">Đi muộn: <strong class="text-warning">{{ $stats['late'] }}</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1">Vắng mặt: <strong class="text-danger">{{ $stats['absent'] }}</strong></p>
                            <p class="mb-1">Có phép: <strong class="text-info">{{ $stats['excused'] }}</strong></p>
                            <p class="mb-1">Tỷ lệ đi học: <strong>{{ round((($stats['present'] + $stats['late']) / $stats['total_students']) * 100, 1) }}%</strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Danh sách điểm danh ngày {{ \Carbon\Carbon::parse($classDate)->format('d/m/Y') }}</h5>
        <div>
            <a href="{{ route('attendance.create', ['course_class_id' => $courseClass->id, 'class_date' => $classDate]) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Chỉnh sửa điểm danh
            </a>
            <a href="{{ route('attendance.class-report', $courseClass) }}" class="btn btn-info">
                <i class="fas fa-chart-bar"></i> Báo cáo điểm danh
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>Học viên</th>
                        <th>Trạng thái</th>
                        <th>Giờ vào</th>
                        <th>Giờ ra</th>
                        <th>Ghi chú</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($courseClass->enrollments->where('status', 'enrolled') as $enrollment)
                        @php
                            $attendance = $attendances[$enrollment->id] ?? null;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $enrollment->student->full_name }}</strong>
                                <br>
                                <small class="text-muted">{{ $enrollment->student->phone }}</small>
                            </td>
                            <td>
                                @if($attendance)
                                    @if($attendance->status == 'present')
                                        <span class="badge bg-success">Có mặt</span>
                                    @elseif($attendance->status == 'absent')
                                        <span class="badge bg-danger">Vắng mặt</span>
                                    @elseif($attendance->status == 'late')
                                        <span class="badge bg-warning">Đi muộn</span>
                                    @elseif($attendance->status == 'excused')
                                        <span class="badge bg-info">Có phép</span>
                                    @endif
                                @else
                                    <span class="badge bg-secondary">Chưa điểm danh</span>
                                @endif
                            </td>
                            <td>{{ $attendance->start_time ?? '--' }}</td>
                            <td>{{ $attendance->end_time ?? '--' }}</td>
                            <td>{{ $attendance->notes ?? '--' }}</td>
                            <td>
                                @if($attendance)
                                    <a href="{{ route('attendance.edit', $attendance) }}" class="btn btn-sm btn-primary" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @else
                                    <span class="text-muted">--</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Không có học viên nào trong lớp này</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection 