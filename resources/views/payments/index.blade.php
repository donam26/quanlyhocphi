@extends('layouts.app')

@section('page-title', 'Quản lý thanh toán')

@section('breadcrumb')
<li class="breadcrumb-item active">Thanh toán</li>
@endsection

@section('content')
<!-- Custom CSS for dropdown menu fix -->
<style>
    .dropdown-menu {
        z-index: 1050;
        position: absolute !important;
    }
    
    .table .dropdown {
        position: relative;
    }
    
    /* Đảm bảo dropdown menu hiển thị bên phải khi gần biên phải */
    .table tr:last-child .dropdown-menu,
    .table tr:nth-last-child(2) .dropdown-menu {
        right: 0;
        left: auto !important;
    }
</style>

<!-- Filter & Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('payments.index') }}" id="paymentSearchForm">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <select id="payment_search" name="search" class="form-control select2-ajax" style="width: 100%;">
                            @if(request('search'))
                                <option value="{{ request('search') }}" selected>{{ request('search') }}</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="payment_method" class="form-select" onchange="this.form.submit()">
                        <option value="">Tất cả phương thức</option>
                        <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Tiền mặt</option>
                        <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                        <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>Thẻ</option>
                        <option value="other" {{ request('payment_method') == 'other' ? 'selected' : '' }}>Khác</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xác nhận</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" 
                           value="{{ request('date_from') }}" placeholder="Từ ngày">
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <input type="date" name="date_to" class="form-control" 
                               value="{{ request('date_to') }}" placeholder="Đến ngày">
                        <button type="button" class="btn btn-primary create-payment-btn">
                            <i class="fas fa-plus me-1"></i>Ghi nhận
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $stats['total_payments'] }}</p>
                    <p class="stats-label">Tổng giao dịch</p>
                </div>
                <i class="fas fa-receipt fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ number_format($stats['total_amount']) }}</p>
                    <p class="stats-label">Tổng thu (VNĐ)</p>
                </div>
                <i class="fas fa-money-bill-wave fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ number_format($stats['today_amount']) }}</p>
                    <p class="stats-label">Thu hôm nay (VNĐ)</p>
                </div>
                <i class="fas fa-calendar-day fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card danger">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $stats['pending_count'] }}</p>
                    <p class="stats-label">Chờ xác nhận</p>
                </div>
                <i class="fas fa-clock fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
</div>

<!-- Payments List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-graduation-cap me-2"></i>
            Danh sách khóa học
            <span class="badge bg-primary ms-2">{{ count($courseItems) }} khóa học</span>
        </h5>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline-success" onclick="exportPayments()">
                <i class="fas fa-file-excel me-1"></i>Xuất Excel
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        @if($courseItems->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="30%">Tên khóa học</th>
                            <th width="15%">Tổng học viên</th>
                            <th width="15%">Tổng học phí</th>
                            <th width="15%">Đã thu</th>
                            <th width="15%">Còn lại</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($courseItems as $courseItem)
                        @php
                            $stats = $courseStats[$courseItem->id] ?? [
                                'total_enrollments' => 0,
                                'total_fee' => 0,
                                'total_paid' => 0,
                                'remaining' => 0
                            ];
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $courseItem->name }}</div>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $stats['total_enrollments'] }} học viên</div>
                            </td>
                            <td>
                                <div class="fw-bold">{{ number_format($stats['total_fee']) }} VNĐ</div>
                            </td>
                            <td>
                                <div class="fw-bold text-success">{{ number_format($stats['total_paid']) }} VNĐ</div>
                            </td>
                            <td>
                                <div class="fw-bold {{ $stats['remaining'] > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($stats['remaining']) }} VNĐ
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('payments.course', $courseItem->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye me-1"></i>Chi tiết
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Chưa có khóa học nào có học viên đăng ký</h5>
                <a href="{{ route('course-items.index') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Quản lý khóa học
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Modal tạo thanh toán mới -->
<div class="modal fade" id="createPaymentModal" tabindex="-1" aria-labelledby="createPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createPaymentModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Tạo phiếu thu
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="student_search">Tìm học viên</label>
                            <select class="form-control select2" id="student_search"></select>
                            <div class="form-text text-muted">Nhập tên hoặc số điện thoại để tìm học viên</div>
                        </div>
                    </div>
                </div>

                <!-- Hiển thị thông tin ghi danh -->
                <div id="enrollment_info" style="display: none;">
                    <h5 class="border-bottom pb-2 mb-3">Thông tin ghi danh</h5>
                    <div id="enrollment_details"></div>
                </div>
                    
                <!-- Form thanh toán -->
                <div id="payment_form" style="display: none;" class="mt-4">
                    <h5 class="border-bottom pb-2 mb-3">Thông tin thanh toán</h5>
                    <form id="createPaymentForm">
                        @csrf
                        <input type="hidden" name="enrollment_id" id="enrollment_id">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="amount">Số tiền thanh toán <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" 
                                            id="amount" name="amount" min="1000" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">VNĐ</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="payment_date">Ngày thanh toán <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" 
                                        id="payment_date" name="payment_date" required 
                                        value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="payment_method">Hình thức thanh toán <span class="text-danger">*</span></label>
                                    <select class="form-control" 
                                        id="payment_method" name="payment_method" required>
                                        <option value="cash">Tiền mặt</option>
                                        <option value="bank_transfer">Chuyển khoản</option>
                                        <option value="card">Thẻ</option>
                                        <option value="other">Khác</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="status">Trạng thái <span class="text-danger">*</span></label>
                                    <select class="form-control" 
                                        id="status" name="status" required>
                                        <option value="pending">Chờ xác nhận</option>
                                        <option value="confirmed" selected>Đã xác nhận</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Chọn "Chờ xác nhận" để học viên thanh toán qua mã QR, hoặc "Đã xác nhận" nếu bạn đã nhận được thanh toán
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="notes">Ghi chú</label>
                                <input type="text" class="form-control" 
                                    id="notes" name="notes">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Hủy
                </button>
                <button type="button" class="btn btn-primary" id="saveNewPaymentBtn" onclick="saveNewPayment()">
                    <i class="fas fa-save me-2"></i>Lưu thanh toán
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal chi tiết thanh toán -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentDetailsModalLabel">
                    <i class="fas fa-receipt me-2"></i>Chi tiết phiếu thu
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <!-- Nội dung sẽ được thêm bằng JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="confirmPaymentBtn" style="display: none;">
                    <i class="fas fa-check-circle me-2"></i>Xác nhận thanh toán
                </button>
                <button type="button" class="btn btn-warning" id="editPaymentBtn">
                    <i class="fas fa-edit me-2"></i>Chỉnh sửa
                </button>
                <button type="button" class="btn btn-primary" id="printPaymentBtn">
                    <i class="fas fa-print me-2"></i>In phiếu
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal chỉnh sửa thanh toán -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPaymentModalLabel">
                    <i class="fas fa-edit me-2"></i>Chỉnh sửa phiếu thu
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editPaymentContent">
                <!-- Nội dung sẽ được thêm bằng JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Hủy
                </button>
                <button type="button" class="btn btn-primary" id="savePaymentBtn">
                    <i class="fas fa-save me-2"></i>Lưu thay đổi
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/payment.js') }}"></script>
<script>
$(document).ready(function() {
    // Cấu hình Select2 cho ô tìm kiếm thanh toán với AJAX
    $('#payment_search').select2({
        theme: 'bootstrap-5',
        placeholder: 'Tìm theo tên khóa học...',
        allowClear: true,
        minimumInputLength: 0, // Cho phép hiển thị data ngay khi mở dropdown
        ajax: {
            url: '{{ route("api.search.autocomplete") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term || '',
                    type: 'course', // Tìm kiếm khóa học
                    preload: params.term ? 'false' : 'true'
                };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(item) {
                        return {
                            id: item.id,
                            text: item.text
                        };
                    })
                };
            },
            cache: true
        }
    });

    // Auto-submit khi search select2 thay đổi
    $('#payment_search').on('select2:select', function(e) {
        var courseId = e.params.data.id;
        // Điều hướng đến trang chi tiết khóa học
        window.location.href = '{{ route("payments.index") }}?course_id=' + courseId;
    });

    // Xóa tìm kiếm khi clear
    $('#payment_search').on('select2:clear', function(e) {
        window.location.href = '{{ route("payments.index") }}';
    });
});

function exportPayments() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '{{ route("payments.index") }}?' + params.toString();
}
</script>
@endpush 
 