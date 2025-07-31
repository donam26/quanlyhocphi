@extends('layouts.app')

@section('page-title', 'Quản lý tiến độ học tập')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Quản lý tiến độ học tập</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('learning-progress.index') }}" method="GET" id="course-select-form">
                        <div class="row align-items-end">
                            <div class="col-md-6">
                                <label for="course_id" class="form-label">Chọn khóa học</label>
                                <select name="course_id" id="course_id" class="form-select select2">
                                    <option value="">-- Chọn khóa học --</option>
                                    @foreach($courseItems as $course)
                                        <option value="{{ $course->id }}" {{ $selectedCourse && $selectedCourse->id == $course->id ? 'selected' : '' }}>
                                            {{ $course->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                @if($selectedCourse)
                                    <a href="{{ route('learning-paths.edit', $selectedCourse->id) }}" class="btn btn-outline-primary">
                                        <i class="fas fa-edit"></i> Chỉnh sửa lộ trình học tập
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($selectedCourse)
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h4>Thông tin khóa học</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Cấp độ trong cây:</strong> {{ $selectedCourse->level }}</p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p><strong>Học phí:</strong> {{ number_format($selectedCourse->fee) }} đồng</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Lộ trình học tập</h5>
                <div class="d-flex align-items-center">
                    <span class="me-3">Tổng số lộ trình: {{ $learningPaths->count() }}</span>
                    <a href="{{ route('learning-paths.edit', $selectedCourse->id) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> Chỉnh sửa lộ trình
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($learningPaths->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Khóa học này chưa có lộ trình học tập nào.
                        <a href="{{ route('learning-paths.create', $selectedCourse->id) }}" class="alert-link">Thêm lộ trình học tập</a>.
                    </div>
                @else
                    <div id="success-message" class="alert alert-success" style="display: none;">
                        Đã cập nhật tiến độ học tập thành công!
                    </div>
                    
                    @foreach($learningPaths as $index => $path)
                        @php
                            $stats = $pathCompletionStats[$path->id] ?? null;
                            $isCompleted = $stats && $stats['completed_count'] > 0;
                        @endphp
                        <div class="learning-path-item {{ $isCompleted ? 'completed' : '' }}" id="path-item-{{ $path->id }}">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input progress-checkbox" 
                                    id="path-{{ $path->id }}" 
                                    data-path-id="{{ $path->id }}"
                                    data-course-id="{{ $selectedCourse->id }}"
                                    {{ $isCompleted ? 'checked' : '' }}>
                                <label class="form-check-label" for="path-{{ $path->id }}">
                                    <div class="fw-bold">Buổi {{ $index + 1 }}</div>
                                    <div class="text-muted">{{ $path->id }}</div>
                                    <div class="text-success completion-status" id="status-{{ $path->id }}" style="{{ $isCompleted ? '' : 'display: none;' }}">
                                        <i class="fas fa-check-circle"></i> Đã hoàn thành
                                    </div>
                                </label>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @endif
</div>

<style>
.learning-path-item {
    margin-bottom: 10px;
    padding: 15px;
    border-radius: 5px;
    background-color: #f8f9fa;
    transition: background-color 0.3s ease;
}

.learning-path-item.completed {
    background-color: #e8f5e9;
}

.form-check-input {
    width: 20px;
    height: 20px;
    margin-top: 2px;
    cursor: pointer;
}

.form-check-label {
    margin-left: 8px;
}
</style>

@push('scripts')
<script>
$(document).ready(function() {
    // Khởi tạo Select2
    $('#course_id').select2({
        theme: 'bootstrap-5',
        placeholder: "Chọn khóa học...",
        allowClear: true
    });
    
    // Tự động submit form khi thay đổi khóa học
    $('#course_id').on('change', function() {
        $('#course-select-form').submit();
    });
    
    // Xử lý khi checkbox thay đổi
    $('.progress-checkbox').change(function() {
        const pathId = $(this).data('path-id');
        const courseId = $(this).data('course-id');
        const isCompleted = $(this).is(':checked');
        const pathItem = $('#path-item-' + pathId);
        const completionStatus = $('#status-' + pathId);
        
        // Hiển thị loading
        $(this).prop('disabled', true);
        
        // Gửi AJAX request để cập nhật trạng thái
        $.ajax({
            url: '{{ route("learning-progress.update-path-status") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                path_id: pathId,
                course_id: courseId,
                is_completed: isCompleted ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    // Cập nhật giao diện
                    if (isCompleted) {
                        pathItem.addClass('completed');
                        completionStatus.show();
                    } else {
                        pathItem.removeClass('completed');
                        completionStatus.hide();
                    }
                    
                    // Hiển thị thông báo thành công
                    $('#success-message').fadeIn().delay(2000).fadeOut();
                } else {
                    alert('Có lỗi xảy ra: ' + response.message);
                    // Khôi phục trạng thái checkbox nếu có lỗi
                    $('.progress-checkbox[data-path-id="' + pathId + '"]').prop('checked', !isCompleted);
                }
            },
            error: function() {
                alert('Có lỗi xảy ra khi cập nhật tiến độ học tập');
                // Khôi phục trạng thái checkbox nếu có lỗi
                $('.progress-checkbox[data-path-id="' + pathId + '"]').prop('checked', !isCompleted);
            },
            complete: function() {
                // Bỏ disabled cho checkbox
                $('.progress-checkbox[data-path-id="' + pathId + '"]').prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
@endsection 