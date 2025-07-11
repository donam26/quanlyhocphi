@extends('layouts.app')

@section('title', 'Quản lý Điểm danh')

@section('page-title', 'Quản lý Điểm danh')

@section('breadcrumb')
    <li class="breadcrumb-item active">Điểm danh</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Danh sách điểm danh</h5>
        <a href="{{ route('attendance.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Điểm danh mới
        </a>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-12">
                <form action="{{ route('attendance.index') }}" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="course_class_id" class="form-label">Lớp học</label>
                        <select name="course_class_id" id="course_class_id" class="form-select">
                            <option value="">Tất cả lớp</option>
                            @foreach($courseClasses as $class)
                                <option value="{{ $class->id }}" {{ request('course_class_id') == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }} ({{ $class->course->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="class_date" class="form-label">Ngày học</label>
                        <input type="date" name="class_date" id="class_date" class="form-control" value="{{ request('class_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Tất cả trạng thái</option>
                            <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Có mặt</option>
                            <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Vắng mặt</option>
                            <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Đi muộn</option>
                            <option value="excused" {{ request('status') == 'excused' ? 'selected' : '' }}>Có phép</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Lọc
                        </button>
                        <a href="{{ route('attendance.index') }}" class="btn btn-secondary ms-2">
                            <i class="fas fa-sync"></i> Đặt lại
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Học viên</th>
                        <th>Lớp học</th>
                        <th>Ngày học</th>
                        <th>Giờ vào</th>
                        <th>Giờ ra</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                        <tr>
                            <td>{{ $attendance->id }}</td>
                            <td>
                                <strong>{{ $attendance->enrollment->student->full_name }}</strong>
                                <br>
                                <small class="text-muted">{{ $attendance->enrollment->student->phone }}</small>
                            </td>
                            <td>
                                {{ $attendance->enrollment->courseClass->name }}
                                <br>
                                <small class="text-muted">{{ $attendance->enrollment->courseClass->course->name }}</small>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($attendance->class_date)->format('d/m/Y') }}</td>
                            <td>{{ $attendance->start_time ?? '--' }}</td>
                            <td>{{ $attendance->end_time ?? '--' }}</td>
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
                            <td>{{ $attendance->notes ?? '--' }}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('attendance.edit', $attendance) }}" class="btn btn-sm btn-primary" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('attendance.show', $attendance) }}" class="btn btn-sm btn-info" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form action="{{ route('attendance.destroy', $attendance) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa điểm danh này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">Không có dữ liệu điểm danh</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $attendances->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection 