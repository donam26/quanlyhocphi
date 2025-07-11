@extends('layouts.app')

@section('title', 'Điểm danh mới')

@section('page-title', 'Điểm danh mới')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('attendance.index') }}">Điểm danh</a></li>
    <li class="breadcrumb-item active">Tạo mới</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Chọn lớp học và ngày điểm danh</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('attendance.create') }}" method="GET" class="row g-3">
            <div class="col-md-5">
                <label for="course_class_id" class="form-label">Lớp học</label>
                <select name="course_class_id" id="course_class_id" class="form-select" required>
                    <option value="">-- Chọn lớp học --</option>
                    @foreach($courseClasses as $class)
                        <option value="{{ $class->id }}" {{ request('course_class_id') == $class->id ? 'selected' : '' }}>
                            {{ $class->name }} ({{ $class->course->name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label for="class_date" class="form-label">Ngày học</label>
                <input type="date" name="class_date" id="class_date" class="form-control" value="{{ $classDate }}" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Hiển thị danh sách
                </button>
            </div>
        </form>
    </div>
</div>

@if($courseClass)
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Điểm danh lớp: {{ $courseClass->name }} - {{ $courseClass->course->name }}</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Điểm danh ngày: <strong>{{ \Carbon\Carbon::parse($classDate)->format('d/m/Y') }}</strong>
        </div>

        <form action="{{ route('attendance.store') }}" method="POST">
            @csrf
            <input type="hidden" name="course_class_id" value="{{ $courseClass->id }}">
            <input type="hidden" name="class_date" value="{{ $classDate }}">

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Học viên</th>
                            <th>Trạng thái</th>
                            <th>Giờ vào</th>
                            <th>Giờ ra</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($courseClass->enrollments->where('status', 'enrolled') as $enrollment)
                            <tr>
                                <td>
                                    <input type="hidden" name="attendances[{{ $loop->index }}][enrollment_id]" value="{{ $enrollment->id }}">
                                    <strong>{{ $enrollment->student->full_name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $enrollment->student->phone }}</small>
                                </td>
                                <td>
                                    <select name="attendances[{{ $loop->index }}][status]" class="form-select attendance-status">
                                        <option value="present">Có mặt</option>
                                        <option value="absent">Vắng mặt</option>
                                        <option value="late">Đi muộn</option>
                                        <option value="excused">Có phép</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="time" name="attendances[{{ $loop->index }}][start_time]" class="form-control time-field">
                                </td>
                                <td>
                                    <input type="time" name="attendances[{{ $loop->index }}][end_time]" class="form-control time-field">
                                </td>
                                <td>
                                    <input type="text" name="attendances[{{ $loop->index }}][notes]" class="form-control" placeholder="Ghi chú">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Không có học viên nào trong lớp này</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between">
                        <div>
                            <button type="button" class="btn btn-secondary" id="markAllPresent">
                                <i class="fas fa-check"></i> Đánh dấu tất cả có mặt
                            </button>
                            <button type="button" class="btn btn-danger" id="markAllAbsent">
                                <i class="fas fa-times"></i> Đánh dấu tất cả vắng mặt
                            </button>
                        </div>
                        <div>
                            <a href="{{ route('attendance.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Quay lại
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Lưu điểm danh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Xử lý khi thay đổi trạng thái điểm danh
    $('.attendance-status').change(function() {
        const row = $(this).closest('tr');
        const status = $(this).val();
        
        // Tự động điền giờ vào/ra nếu có mặt
        if (status === 'present') {
            if (!row.find('input[name$="[start_time]"]').val()) {
                row.find('input[name$="[start_time]"]').val('08:00');
            }
            if (!row.find('input[name$="[end_time]"]').val()) {
                row.find('input[name$="[end_time]"]').val('12:00');
            }
        } else if (status === 'late') {
            if (!row.find('input[name$="[start_time]"]').val()) {
                row.find('input[name$="[start_time]"]').val('08:30');
            }
            if (!row.find('input[name$="[end_time]"]').val()) {
                row.find('input[name$="[end_time]"]').val('12:00');
            }
        } else {
            // Xóa giờ nếu vắng mặt hoặc có phép
            row.find('.time-field').val('');
        }
    });

    // Đánh dấu tất cả có mặt
    $('#markAllPresent').click(function() {
        $('.attendance-status').val('present').trigger('change');
    });

    // Đánh dấu tất cả vắng mặt
    $('#markAllAbsent').click(function() {
        $('.attendance-status').val('absent').trigger('change');
    });
});
</script>
@endpush 