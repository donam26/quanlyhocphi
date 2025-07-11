@extends('layouts.app')

@section('title', 'Chỉnh sửa điểm danh')

@section('page-title', 'Chỉnh sửa điểm danh')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('attendance.index') }}">Điểm danh</a></li>
    <li class="breadcrumb-item"><a href="{{ route('attendance.show', $attendance) }}">Chi tiết</a></li>
    <li class="breadcrumb-item active">Chỉnh sửa</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Thông tin điểm danh</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <th width="30%">Học viên:</th>
                        <td>{{ $attendance->enrollment->student->full_name }}</td>
                    </tr>
                    <tr>
                        <th>Lớp học:</th>
                        <td>{{ $attendance->enrollment->courseClass->name }}</td>
                    </tr>
                    <tr>
                        <th>Khóa học:</th>
                        <td>{{ $attendance->enrollment->courseClass->course->name }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <form action="{{ route('attendance.update', $attendance) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="class_date" class="form-label">Ngày học</label>
                        <input type="date" name="class_date" id="class_date" class="form-control" value="{{ $attendance->class_date->format('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="present" {{ $attendance->status == 'present' ? 'selected' : '' }}>Có mặt</option>
                            <option value="absent" {{ $attendance->status == 'absent' ? 'selected' : '' }}>Vắng mặt</option>
                            <option value="late" {{ $attendance->status == 'late' ? 'selected' : '' }}>Đi muộn</option>
                            <option value="excused" {{ $attendance->status == 'excused' ? 'selected' : '' }}>Có phép</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="start_time" class="form-label">Giờ vào</label>
                        <input type="time" name="start_time" id="start_time" class="form-control" value="{{ $attendance->start_time }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="end_time" class="form-label">Giờ ra</label>
                        <input type="time" name="end_time" id="end_time" class="form-control" value="{{ $attendance->end_time }}">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Ghi chú</label>
                <textarea name="notes" id="notes" class="form-control" rows="3">{{ $attendance->notes }}</textarea>
            </div>

            <div class="d-flex justify-content-between">
                <div>
                    <a href="{{ route('attendance.show', $attendance) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>
                <div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Xử lý khi thay đổi trạng thái điểm danh
    $('#status').change(function() {
        const status = $(this).val();
        
        // Tự động điền giờ vào/ra nếu có mặt
        if (status === 'present') {
            if (!$('#start_time').val()) {
                $('#start_time').val('08:00');
            }
            if (!$('#end_time').val()) {
                $('#end_time').val('12:00');
            }
        } else if (status === 'late') {
            if (!$('#start_time').val()) {
                $('#start_time').val('08:30');
            }
            if (!$('#end_time').val()) {
                $('#end_time').val('12:00');
            }
        } else if (status === 'absent' || status === 'excused') {
            // Xóa giờ nếu vắng mặt hoặc có phép
            $('#start_time').val('');
            $('#end_time').val('');
        }
    });
});
</script>
@endpush 