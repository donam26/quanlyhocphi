@extends('layouts.app')

@section('title', 'Quản lý ghi danh')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-graduate me-2"></i>Quản lý ghi danh
                    </h5>
                    <button type="button" class="btn btn-primary" onclick="showEnrollmentForm()">
                        <i class="fas fa-plus me-1"></i>Tạo ghi danh mới
                    </button>
                </div>

                <!-- Search and Filter Form -->
                <div class="card-body border-bottom">
                    <form method="GET" action="{{ route('enrollments.index') }}" id="enrollmentSearchForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Tìm kiếm học viên</label>
                                <select name="student_search" id="student_search" class="form-select student-search">
                                    <option value="">Tìm theo tên, SĐT...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Khóa học</label>
                                <select name="course_item_id" class="form-select">
                                    <option value="">Tất cả khóa học</option>
                                    @foreach(\App\Models\CourseItem::whereNotNull('fee')->get() as $course)
                                        <option value="{{ $course->id }}" {{ request('course_item_id') == $course->id ? 'selected' : '' }}>
                                            {{ $course->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Trạng thái</label>
                                <select name="status" class="form-select">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang học</option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Tạm dừng</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Hủy</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search me-1"></i>Tìm kiếm
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Enrollments Table -->
                @if(isset($enrollments) && $enrollments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="20%">Học viên</th>
                                    <th width="20%">Khóa học</th>
                                    <th width="10%">Ngày ghi danh</th>
                                    <th width="10%">Học phí</th>
                                    <th width="10%">Đã thanh toán</th>
                                    <th width="10%">Trạng thái</th>
                                    <th width="15%">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($enrollments as $index => $enrollment)
                                    <tr>
                                        <td>{{ $enrollments->firstItem() + $index }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <strong>{{ $enrollment->student->full_name }}</strong>
                                                    <br><small class="text-muted">{{ $enrollment->student->phone }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong>{{ $enrollment->courseItem->name }}</strong>
                                            @if($enrollment->courseItem->parent)
                                                <br><small class="text-muted">{{ $enrollment->courseItem->parent->name }}</small>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($enrollment->enrollment_date)->format('d/m/Y') }}</td>
                                        <td class="fw-bold">{{ number_format($enrollment->final_fee) }} VNĐ</td>
                                        <td>
                                            @php
                                                $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
                                                $isFullyPaid = $totalPaid >= $enrollment->final_fee;
                                            @endphp
                                            <span class="fw-bold {{ $isFullyPaid ? 'text-success' : 'text-warning' }}">
                                                {{ number_format($totalPaid) }} VNĐ
                                            </span>
                                            @if(!$isFullyPaid)
                                                <br><small class="text-danger">
                                                    Còn nợ: {{ number_format($enrollment->final_fee - $totalPaid) }} VNĐ
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($enrollment->status === 'active')
                                                <span class="badge bg-success">Đang học</span>
                                            @elseif($enrollment->status === 'completed')
                                                <span class="badge bg-primary">Hoàn thành</span>
                                            @elseif($enrollment->status === 'suspended')
                                                <span class="badge bg-warning">Tạm dừng</span>
                                            @elseif($enrollment->status === 'cancelled')
                                                <span class="badge bg-danger">Hủy</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $enrollment->status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="showEnrollmentDetail({{ $enrollment->id }})"
                                                        title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" 
                                                        onclick="showEnrollmentForm({{ $enrollment->id }})"
                                                        title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" 
                                                        onclick="showPaymentForm({{ $enrollment->id }})"
                                                        title="Thêm thanh toán">
                                                    <i class="fas fa-money-bill"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDeleteEnrollment({{ $enrollment->id }}, '{{ $enrollment->student->full_name }}')"
                                                        title="Xóa ghi danh">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="card-footer">
                        {{ $enrollments->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Chưa có ghi danh nào</h5>
                        <p class="text-muted">Hãy tạo ghi danh đầu tiên để bắt đầu quản lý.</p>
                        <button type="button" class="btn btn-primary" onclick="showEnrollmentForm()">
                            <i class="fas fa-plus me-1"></i>Tạo ghi danh đầu tiên
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Tất cả modal đã được thay thế bằng Unified Modal System --}}

@push('scripts')
<script>
// Enrollments Page với Unified Modal System
document.addEventListener('app:ready', function() {
    console.log('Enrollments page ready with Unified Modal System');
});

// Functions để sử dụng Unified Modal System
function showEnrollmentDetail(enrollmentId) {
    if (window.unifiedModals) {
        window.unifiedModals.showEnrollmentDetail(enrollmentId);
    } else {
        alert('Chức năng xem chi tiết ghi danh đang được phát triển');
    }
}

function showEnrollmentForm(enrollmentId = null) {
    if (window.unifiedModals) {
        window.unifiedModals.showEnrollmentForm(enrollmentId);
    } else {
        alert('Chức năng form ghi danh đang được phát triển');
    }
}

function showPaymentForm(enrollmentId) {
    if (window.unifiedModals) {
        window.unifiedModals.showPaymentForm(enrollmentId);
    } else {
        alert('Chức năng thêm thanh toán đang được phát triển');
    }
}

function confirmDeleteEnrollment(enrollmentId, studentName) {
    if (window.unifiedModals) {
        window.unifiedModals.confirmDelete(enrollmentId, `Ghi danh của ${studentName}`, 'enrollment');
    } else {
        if (confirm(`Bạn có chắc chắn muốn xóa ghi danh của "${studentName}"?`)) {
            alert('Chức năng xóa ghi danh đang được phát triển');
        }
    }
}

// Legacy functions để tương thích
function viewEnrollment(enrollmentId) {
    showEnrollmentDetail(enrollmentId);
}

function editEnrollment(enrollmentId) {
    showEnrollmentForm(enrollmentId);
}

function createEnrollment() {
    showEnrollmentForm();
}

function addPayment(enrollmentId) {
    showPaymentForm(enrollmentId);
}
</script>
@endpush

@endsection
