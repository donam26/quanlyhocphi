@extends('layouts.app')

@section('page-title', 'Tiến độ học tập: ' . $student->full_name)

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Tiến độ học tập: {{ $student->full_name }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('learning-progress.index') }}">Quản lý tiến độ học tập</a></li>
                    <li class="breadcrumb-item active">{{ $student->full_name }}</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('learning-progress.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
            <a href="{{ route('students.show', $student->id) }}" class="btn btn-outline-info">
                <i class="fas fa-user"></i> Hồ sơ học viên
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Thông tin học viên</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 text-center">
                            <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                                {{ substr($student->full_name, 0, 1) }}
                            </div>
                        </div>
                        <div class="col-md-5">
                            <p><strong>Họ và tên:</strong> {{ $student->full_name }}</p>
                            <p><strong>Số điện thoại:</strong> {{ $student->phone }}</p>
                            <p><strong>Email:</strong> {{ $student->email ?: 'Không có' }}</p>
                        </div>
                        <div class="col-md-5">
                            <p><strong>Ngày sinh:</strong> {{ $student->birthday ? $student->birthday->format('d/m/Y') : 'Không có' }}</p>
                            <p><strong>Địa chỉ:</strong> {{ $student->address ?: 'Không có' }}</p>
                            <p><strong>Số khóa học đã đăng ký:</strong> {{ $enrollments->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Khóa học đã đăng ký</h5>
                </div>
                <div class="card-body">
                    @if($enrollments->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Học viên này chưa đăng ký khóa học nào.
                        </div>
                    @else
                        <form action="{{ route('learning-progress.student', $student->id) }}" method="GET" id="enrollment-select-form">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="enrollment_id" class="form-label">Chọn khóa học</label>
                                    <select name="enrollment_id" id="enrollment_id" class="form-select">
                                        <option value="">-- Chọn khóa học --</option>
                                        @foreach($enrollments as $enrollment)
                                            <option value="{{ $enrollment->id }}" {{ $selectedEnrollment && $selectedEnrollment->id == $enrollment->id ? 'selected' : '' }}>
                                                {{ $enrollment->courseItem->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($selectedEnrollment)
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Tiến độ học tập: {{ $selectedEnrollment->courseItem->name }}</h5>
                        <div>
                            <button class="btn btn-success" id="save-progress">
                                <i class="fas fa-save"></i> Lưu thay đổi
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($learningPaths->isEmpty())
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Khóa học này chưa có lộ trình học tập nào.
                            </div>
                        @else
                            <div class="progress mb-4" style="height: 25px;">
                                @php
                                    $totalPaths = $learningPaths->count();
                                    $completedPaths = $progress->where('is_completed', true)->count();
                                    $progressPercent = $totalPaths > 0 ? round(($completedPaths / $totalPaths) * 100) : 0;
                                @endphp
                                <div class="progress-bar {{ $progressPercent >= 100 ? 'bg-success' : ($progressPercent >= 50 ? 'bg-info' : 'bg-warning') }}" role="progressbar" style="width: {{ $progressPercent }}%;" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100">{{ $progressPercent }}% hoàn thành</div>
                            </div>

                            <div class="learning-paths-list">
                                @foreach($learningPaths as $index => $path)
                                    @php
                                        $pathProgress = $progress->get($path->id);
                                        $isCompleted = $pathProgress ? $pathProgress->is_completed : false;
                                        $completedDate = $pathProgress && $pathProgress->completed_at ? $pathProgress->completed_at->format('d/m/Y H:i') : null;
                                    @endphp
                                    <div class="learning-path-item {{ $isCompleted ? 'completed' : '' }} p-3 mb-3 border rounded">
                                        <div class="row align-items-center">
                                            <div class="col-md-1">
                                                <span class="badge bg-primary rounded-pill">{{ $index + 1 }}</span>
                                            </div>
                                            <div class="col-md-7">
                                                <h5 class="mb-1">{{ $path->title }}</h5>
                                                @if($path->description)
                                                    <p class="text-muted mb-0">{{ $path->description }}</p>
                                                @endif
                                            </div>
                                            <div class="col-md-2 text-center">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input progress-toggle" type="checkbox" id="progress-{{ $path->id }}" 
                                                        data-path-id="{{ $path->id }}" 
                                                        data-enrollment-id="{{ $selectedEnrollment->id }}" 
                                                        {{ $isCompleted ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="progress-{{ $path->id }}">
                                                        {{ $isCompleted ? 'Đã hoàn thành' : 'Chưa hoàn thành' }}
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-2 text-end">
                                                @if($completedDate)
                                                    <small class="text-muted">Hoàn thành: {{ $completedDate }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Tự động submit form khi thay đổi khóa học
        $('#enrollment_id').change(function() {
            $('#enrollment-select-form').submit();
        });
        
        // Mảng lưu trữ các thay đổi
        let changedProgress = [];
        
        // Xử lý khi toggle thay đổi
        $('.progress-toggle').change(function() {
            const pathId = $(this).data('path-id');
            const enrollmentId = $(this).data('enrollment-id');
            const isCompleted = $(this).is(':checked');
            const pathItem = $(this).closest('.learning-path-item');
            const statusLabel = $(this).next('label');
            
            // Cập nhật trạng thái hiển thị
            if (isCompleted) {
                pathItem.addClass('completed');
                statusLabel.text('Đã hoàn thành');
            } else {
                pathItem.removeClass('completed');
                statusLabel.text('Chưa hoàn thành');
            }
            
            // Thêm vào mảng thay đổi
            const existingIndex = changedProgress.findIndex(item => 
                item.learning_path_id === pathId && item.enrollment_id === enrollmentId
            );
            
            if (existingIndex !== -1) {
                changedProgress[existingIndex].is_completed = isCompleted;
            } else {
                changedProgress.push({
                    learning_path_id: pathId,
                    enrollment_id: enrollmentId,
                    is_completed: isCompleted
                });
            }
            
            // Cập nhật thanh tiến độ
            updateProgressBar();
        });
        
        // Cập nhật thanh tiến độ
        function updateProgressBar() {
            const totalPaths = $('.progress-toggle').length;
            const completedPaths = $('.progress-toggle:checked').length;
            const progressPercent = Math.round((completedPaths / totalPaths) * 100);
            
            const progressBar = $('.progress-bar');
            progressBar.css('width', `${progressPercent}%`);
            progressBar.attr('aria-valuenow', progressPercent);
            progressBar.text(`${progressPercent}% hoàn thành`);
            
            // Cập nhật màu sắc
            progressBar.removeClass('bg-success bg-info bg-warning');
            if (progressPercent >= 100) {
                progressBar.addClass('bg-success');
            } else if (progressPercent >= 50) {
                progressBar.addClass('bg-info');
            } else {
                progressBar.addClass('bg-warning');
            }
        }
        
        // Lưu thay đổi
        $('#save-progress').click(function() {
            if (changedProgress.length === 0) {
                alert('Không có thay đổi nào để lưu');
                return;
            }
            
            // Hiển thị nút loading
            const button = $(this);
            const originalText = button.html();
            button.html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...');
            button.prop('disabled', true);
            
            // Gửi request AJAX để cập nhật
            $.ajax({
                url: '{{ route("learning-progress.update-bulk") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    progress_data: changedProgress
                },
                success: function(response) {
                    if (response.status === 'success') {
                        // Hiển thị thông báo thành công
                        alert('Đã cập nhật tiến độ học tập thành công');
                        // Xóa mảng thay đổi
                        changedProgress = [];
                        // Reload trang để cập nhật thông tin mới
                        location.reload();
                    } else {
                        alert('Có lỗi xảy ra: ' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('Có lỗi xảy ra khi cập nhật tiến độ học tập');
                    console.error(xhr);
                },
                complete: function() {
                    // Khôi phục nút
                    button.html(originalText);
                    button.prop('disabled', false);
                }
            });
        });
    });
</script>
@endpush

<style>
    .avatar-lg {
        width: 80px;
        height: 80px;
        font-size: 32px;
    }
    .learning-path-item {
        transition: all 0.3s ease;
    }
    .learning-path-item.completed {
        background-color: #e8f5e9;
    }
    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
    }
    .progress {
        border-radius: 0.5rem;
    }
</style>
@endsection 