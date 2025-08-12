@extends('layouts.app')

@section('title', 'Học viên chưa thanh toán đủ')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Học viên chưa thanh toán đủ học phí
                    </h5>
                </div>

                <div class="card-body">
                    @if(isset($courseEnrollments) && count($courseEnrollments) > 0)
                        <div class="row">
                            @foreach($courseEnrollments as $courseItemId => $data)
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-warning">
                                        <div class="card-header bg-warning bg-opacity-10">
                                            <h6 class="mb-0">
                                                <i class="fas fa-graduation-cap me-2"></i>
                                                {{ $data['course_name'] }}
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-muted">Tổng học viên:</span>
                                                <span class="badge bg-primary">{{ count($data['enrollments']) }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-muted">Tổng nợ:</span>
                                                <span class="text-danger fw-bold">
                                                    {{ number_format($data['total_debt']) }} VNĐ
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">Đã thu:</span>
                                                <span class="text-success">
                                                    {{ number_format($data['total_paid']) }} VNĐ
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-footer">
                                            <button type="button" class="btn btn-sm btn-outline-primary w-100" 
                                                    onclick="showCourseDetail({{ $courseItemId }})">
                                                <i class="fas fa-eye me-1"></i>Xem chi tiết
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="text-success">Tuyệt vời!</h5>
                            <p class="text-muted">Tất cả học viên đã thanh toán đủ học phí.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tất cả modal đã được thay thế bằng Unified Modal System --}}

@push('scripts')
<script>
// Unpaid Enrollments Page với Unified Modal System
document.addEventListener('app:ready', function() {
    console.log('Unpaid Enrollments page ready with Unified Modal System');
});

// Functions để hiển thị chi tiết
function showCourseDetail(courseId) {
    if (window.unifiedModals) {
        window.unifiedModals.showCourseDetail(courseId);
    } else {
        alert('Chức năng xem chi tiết đang được phát triển');
    }
}

function showPaymentHistory(enrollmentId) {
    if (window.unifiedModals) {
        window.unifiedModals.showPaymentHistory(enrollmentId);
    } else {
        alert('Chức năng lịch sử thanh toán đang được phát triển');
    }
}

function addPayment(enrollmentId) {
    if (window.unifiedModals) {
        window.unifiedModals.showPaymentForm(enrollmentId);
    } else {
        alert('Chức năng thêm thanh toán đang được phát triển');
    }
}

// Legacy functions để tương thích
function showCourseDetailsModal(courseId) {
    showCourseDetail(courseId);
}

function showPaymentHistoryModal(enrollmentId) {
    showPaymentHistory(enrollmentId);
}
</script>
@endpush

@endsection
