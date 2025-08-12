@extends('layouts.app')

@section('title', 'Quản lý tiến độ học tập')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks me-2"></i>Quản lý tiến độ học tập
                    </h5>
                </div>
                <div class="card-body">
                    <h6 class="text-muted mb-3">Danh sách các khóa học đang có lộ trình chưa hoàn thành</h6>

                    @if(isset($incompleteCoursesData) && count($incompleteCoursesData) === 0)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Không có khóa học nào chưa hoàn thành lộ trình.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(isset($incompleteCoursesData) && count($incompleteCoursesData) > 0)
        <div class="row">
            @foreach($incompleteCoursesData as $courseData)
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">{{ $courseData['course']->name }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Tiến độ hoàn thành</span>
                                    <span>{{ $courseData['progress_percentage'] }}%</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: {{ $courseData['progress_percentage'] }}%;" 
                                         aria-valuenow="{{ $courseData['progress_percentage'] }}" 
                                         aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-users text-primary me-2"></i>
                                        <div>
                                            <div class="small text-muted">Học viên</div>
                                            <div class="fw-bold">{{ $courseData['total_students'] }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-list-check text-warning me-2"></i>
                                        <div>
                                            <div class="small text-muted">Lộ trình</div>
                                            <div class="fw-bold">{{ $courseData['completed_pathways'] }}/{{ $courseData['total_pathways'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-primary" onclick="showCourseProgress({{ $courseData['course']->id }})">
                                    <i class="fas fa-eye me-1"></i> Xem chi tiết
                                </button>
                                <button type="button" class="btn btn-outline-secondary" 
                                        onclick="showLearningPathForm({{ $courseData['course']->id }})">
                                    <i class="fas fa-edit me-1"></i> Chỉnh sửa lộ trình
                                </button>
                            </div>
                        </div>
                        <div class="card-footer text-muted">
                            <small>
                                <i class="fas fa-money-bill-wave me-1"></i> 
                                Học phí: {{ number_format($courseData['course']->fee) }} VNĐ
                            </small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Tất cả modal đã được thay thế bằng Unified Modal System --}}

@push('styles')
<style>
    .learning-path-item {
        margin-bottom: 10px;
        padding: 15px;
        border-radius: 8px;
        background-color: #f8f9fa;
        border: 2px solid transparent;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .learning-path-item.completed {
        background-color: #e8f5e9;
        border-color: #28a745;
        transform: scale(1.02);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.1);
    }
    
    .learning-path-item.completed::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 5px;
        height: 100%;
        background: linear-gradient(to bottom, #28a745, #20c997);
    }
    
    .learning-path-item.completed .learning-path-title {
        color: #28a745;
        font-weight: 600;
    }
    
    .learning-path-item.loading {
        opacity: 0.7;
        pointer-events: none;
    }
    
    .learning-path-item.loading::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        animation: shimmer 1.5s infinite;
    }
    
    @keyframes shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    .form-check-input {
        width: 22px;
        height: 22px;
        margin-top: 2px;
        cursor: pointer;
        border: 2px solid #dee2e6;
        transition: all 0.3s ease;
    }
    
    .form-check-input:checked {
        background-color: #28a745;
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
    }
    
    .form-check-input:hover {
        border-color: #28a745;
        box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.1);
    }
    
    .form-check-input:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .form-check-label {
        margin-left: 8px;
        cursor: pointer;
        user-select: none;
    }
    
    .completion-status {
        transition: all 0.3s ease;
        animation: fadeIn 0.5s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .progress {
        border-radius: 0.5rem;
    }
    
    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
    }
</style>
@endpush

@push('scripts')
<script>
// Learning Progress Page với Unified Modal System
document.addEventListener('app:ready', function() {
    console.log('Learning Progress page ready with Unified Modal System');
});

// Functions để sử dụng Unified Modal System
function showCourseProgress(courseId) {
    if (window.unifiedModals) {
        window.unifiedModals.showCourseProgressModal(courseId);
    } else {
        alert('Chức năng xem tiến độ khóa học đang được phát triển');
    }
}

function showStudentProgress(studentId, enrollmentId = null) {
    if (window.unifiedModals) {
        window.unifiedModals.showStudentProgressModal(studentId, enrollmentId);
    } else {
        alert('Chức năng xem tiến độ học viên đang được phát triển');
    }
}

function showLearningPathForm(courseId) {
    if (window.unifiedModals) {
        window.unifiedModals.showLearningPathForm(courseId);
    } else {
        alert('Chức năng chỉnh sửa lộ trình đang được phát triển');
    }
}

function updateLearningProgress(pathId, enrollmentId, isCompleted) {
    if (window.unifiedModals) {
        return window.unifiedModals.updateLearningProgress(pathId, enrollmentId, isCompleted);
    } else {
        alert('Chức năng cập nhật tiến độ đang được phát triển');
        return Promise.reject('Function not available');
    }
}

// Legacy functions để tương thích
function openLearningPathModal(courseId) {
    showLearningPathForm(courseId);
}

function showCourseProgressModal(courseId) {
    showCourseProgress(courseId);
}

function showStudentProgressModal(studentId, enrollmentId) {
    showStudentProgress(studentId, enrollmentId);
}
</script>
@endpush

@endsection
