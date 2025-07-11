@extends('layouts.app')

@section('title', 'Chi tiết điểm danh')

@section('page-title', 'Chi tiết điểm danh')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('attendance.index') }}">Điểm danh</a></li>
    <li class="breadcrumb-item active">Chi tiết</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Thông tin điểm danh</h5>
        <div>
            <a href="{{ route('attendance.edit', $attendance) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Chỉnh sửa
            </a>
            <form action="{{ route('attendance.destroy', $attendance) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa điểm danh này?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Xóa
                </button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold">Thông tin học viên</h6>
                <table class="table table-borderless">
                    <tr>
                        <th width="30%">Họ tên:</th>
                        <td>{{ $attendance->enrollment->student->full_name }}</td>
                    </tr>
                    <tr>
                        <th>Số điện thoại:</th>
                        <td>{{ $attendance->enrollment->student->phone }}</td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td>{{ $attendance->enrollment->student->email }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold">Thông tin lớp học</h6>
                <table class="table table-borderless">
                    <tr>
                        <th width="30%">Lớp học:</th>
                        <td>{{ $attendance->enrollment->courseClass->name }}</td>
                    </tr>
                    <tr>
                        <th>Khóa học:</th>
                        <td>{{ $attendance->enrollment->courseClass->course->name }}</td>
                    </tr>
                    <tr>
                        <th>Ngày bắt đầu:</th>
                        <td>{{ $attendance->enrollment->courseClass->start_date->format('d/m/Y') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-12">
                <h6 class="fw-bold">Thông tin điểm danh</h6>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%">Ngày học:</th>
                                <td>{{ $attendance->class_date->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <th>Trạng thái:</th>
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
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%">Giờ vào:</th>
                                <td>{{ $attendance->start_time ?? '--' }}</td>
                            </tr>
                            <tr>
                                <th>Giờ ra:</th>
                                <td>{{ $attendance->end_time ?? '--' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Ghi chú</h6>
                            </div>
                            <div class="card-body">
                                {{ $attendance->notes ?? 'Không có ghi chú' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between">
            <a href="{{ route('attendance.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại danh sách
            </a>
            <a href="{{ route('attendance.student-report', $attendance->enrollment->student) }}" class="btn btn-info">
                <i class="fas fa-chart-bar"></i> Xem báo cáo điểm danh của học viên
            </a>
        </div>
    </div>
</div>
@endsection 