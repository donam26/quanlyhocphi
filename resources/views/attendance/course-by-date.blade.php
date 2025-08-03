@extends('layouts.app')

@section('title', 'Chi tiết điểm danh ngày: ' . \Carbon\Carbon::parse($attendanceDate)->format('d/m/Y'))

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2>Chi tiết điểm danh: {{ \Carbon\Carbon::parse($attendanceDate)->format('d/m/Y') }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('course-items.tree') }}">{{ $courseItem->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('course-items.attendance', $courseItem->id) }}">Điểm danh</a></li>
                    <li class="breadcrumb-item active">{{ \Carbon\Carbon::parse($attendanceDate)->format('d/m/Y') }}</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('course-items.attendance', $courseItem->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <!-- Thống kê điểm danh -->
    <div class="row mb-4">
        <div class="col-md">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Tổng học viên</h5>
                    <h2>{{ $stats['total_students'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Có mặt</h5>
                    <h2>{{ $stats['present'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Vắng mặt</h5>
                    <h2>{{ $stats['absent'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Đi muộn</h5>
                    <h2>{{ $stats['late'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Có phép</h5>
                    <h2>{{ $stats['excused'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách điểm danh -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Danh sách điểm danh - {{ \Carbon\Carbon::parse($attendanceDate)->format('d/m/Y') }}</h5>
            <a href="{{ route('course-items.attendance', ['courseItem' => $courseItem->id, 'attendance_date' => $attendanceDate]) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Chỉnh sửa điểm danh
            </a>
        </div>
        <div class="card-body">
            @if($enrollments->isEmpty())
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Không có học viên nào đăng ký các khóa học này.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>STT</th>
                                <th>Học viên</th>
                                <th>Khóa học</th>
                                <th>Trạng thái</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($enrollments as $index => $enrollment)
                                @php
                                    $existingAttendance = $existingAttendances[$enrollment->id] ?? null;
                                    $status = $existingAttendance ? $existingAttendance->status : 'Chưa điểm danh';
                                    $notes = $existingAttendance ? $existingAttendance->notes : '';
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $enrollment->student->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $enrollment->student->phone }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $enrollment->courseItem->name }}</span>
                                    </td>
                                    <td>
                                        @if($status === 'present')
                                            <span class="badge bg-success">Có mặt</span>
                                        @elseif($status === 'absent')
                                            <span class="badge bg-danger">Vắng mặt</span>
                                        @elseif($status === 'late')
                                            <span class="badge bg-warning">Đi muộn</span>
                                        @elseif($status === 'excused')
                                            <span class="badge bg-info">Có phép</span>
                                        @else
                                            <span class="badge bg-secondary">Chưa điểm danh</span>
                                        @endif
                                    </td>
                                    <td>{{ $notes ?: '--' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if(!$courseItem->is_leaf)
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Phân bổ theo khóa học</h5>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Khóa học</th>
                        <th>Tổng học viên</th>
                        <th>Có mặt</th>
                        <th>Vắng mặt</th>
                        <th>Đi muộn</th>
                        <th>Có phép</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($childCourseItems as $childItem)
                        @php
                            $courseEnrollments = $enrollments->where('course_item_id', $childItem->id);
                            $courseEnrollmentIds = $courseEnrollments->pluck('id')->toArray();
                            
                            $courseAttendances = $existingAttendances->filter(function($attendance) use ($courseEnrollmentIds) {
                                return in_array($attendance->enrollment_id, $courseEnrollmentIds);
                            });
                            
                            $courseStats = [
                                'total' => $courseEnrollments->count(),
                                'present' => $courseAttendances->where('status', 'present')->count(),
                                'absent' => $courseAttendances->where('status', 'absent')->count(),
                                'late' => $courseAttendances->where('status', 'late')->count(),
                                'excused' => $courseAttendances->where('status', 'excused')->count(),
                            ];
                        @endphp
                        <tr>
                            <td>{{ $childItem->name }}</td>
                            <td>{{ $courseStats['total'] }}</td>
                            <td>
                                <span class="badge bg-success">{{ $courseStats['present'] }}</span>
                                {{ $courseStats['total'] > 0 ? round(($courseStats['present'] / $courseStats['total']) * 100) . '%' : '0%' }}
                            </td>
                            <td>
                                <span class="badge bg-danger">{{ $courseStats['absent'] }}</span>
                                {{ $courseStats['total'] > 0 ? round(($courseStats['absent'] / $courseStats['total']) * 100) . '%' : '0%' }}
                            </td>
                            <td>
                                <span class="badge bg-warning">{{ $courseStats['late'] }}</span>
                                {{ $courseStats['total'] > 0 ? round(($courseStats['late'] / $courseStats['total']) * 100) . '%' : '0%' }}
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $courseStats['excused'] }}</span>
                                {{ $courseStats['total'] > 0 ? round(($courseStats['excused'] / $courseStats['total']) * 100) . '%' : '0%' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection 