@extends('layouts.app')

@section('page-title', 'Thanh toán - ' . $courseItem->name)

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('course-items.tree') }}">Khóa học</a></li>
<li class="breadcrumb-item active">Thanh toán</li>
@endsection

@section('page-actions')
<a href="{{ route('course-items.tree') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Quay lại
</a>
@endsection

@section('content')
<!-- Nội dung hiện tại -->

<!-- Thêm các modal -->
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
<div class="container-fluid">
    <!-- Thống kê thanh toán -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Tổng học viên</h5>
                    <h2>{{ $enrollments->count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Đã thanh toán</h5>
                    <h2>{{ number_format($totalPaid) }} đ</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Tổng học phí</h5>
                    <h2>{{ number_format($totalFees) }} đ</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Còn lại</h5>
                    <h2>{{ number_format($remainingAmount) }} đ</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4" id="paymentTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="students-tab" data-bs-toggle="tab" data-bs-target="#students-tab-pane" type="button" role="tab" aria-controls="students-tab-pane" aria-selected="true">
                <i class="fas fa-users me-2"></i>Học viên
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments-tab-pane" type="button" role="tab" aria-controls="payments-tab-pane" aria-selected="false">
                <i class="fas fa-money-bill me-2"></i>Lịch sử thanh toán
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="paymentTabContent">
        <!-- Học viên Tab -->
        <div class="tab-pane fade show active" id="students-tab-pane" role="tabpanel" aria-labelledby="students-tab" tabindex="0">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Danh sách học viên</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-success" onclick="exportPayments({{ $courseItem->id }})">
                            <i class="fas fa-file-excel me-1"></i>Xuất Excel
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>STT</th>
                                    <th>Học viên</th>
                                    <th>Học phí gốc</th>
                                    <th>Chiết khấu</th>
                                    <th>Học phí cuối</th>
                                    <th>Đã đóng</th>
                                    <th>Còn lại</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($enrollments as $index => $enrollment)
                                    @php
                                        $paidAmount = $payments->where('enrollment_id', $enrollment->id)->where('status', 'confirmed')->sum('amount');
                                        $remainingAmount = $enrollment->final_fee - $paidAmount;
                                        $isFullyPaid = $remainingAmount <= 0;
                                    @endphp
                                    <tr class="{{ $isFullyPaid ? 'table-success' : '' }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    {{ substr($enrollment->student->full_name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $enrollment->student->full_name }}</div>
                                                    <small class="text-muted">{{ $enrollment->student->phone }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ number_format($courseItem->fee) }} đ</td>
                                        <td>
                                            @if($enrollment->discount_percentage > 0)
                                                {{ $enrollment->discount_percentage }}%
                                            @elseif($enrollment->discount_amount > 0)
                                                {{ number_format($enrollment->discount_amount) }} đ
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ number_format($enrollment->final_fee) }} đ</span>
                                            <button class="btn btn-sm btn-link p-0 ms-1" type="button" onclick="editEnrollmentFee({{ $enrollment->id }}, {{ $enrollment->final_fee }})">
                                                <i class="fas fa-edit text-primary"></i>
                                            </button>
                                        </td>
                                        <td class="text-success fw-bold">{{ number_format($paidAmount) }} đ</td>
                                        <td class="{{ $isFullyPaid ? 'text-success' : 'text-danger' }} fw-bold">
                                            @if($isFullyPaid)
                                                Đã thanh toán đủ
                                            @else
                                                {{ number_format($remainingAmount) }} đ
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-primary btn-create-payment" data-enrollment-id="{{ $enrollment->id }}">
                                                    <i class="fas fa-plus me-1"></i>Thanh toán
                                                </button>
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="viewPaymentHistory({{ $enrollment->id }})">
                                                    <i class="fas fa-history me-1"></i>Lịch sử
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lịch sử thanh toán Tab -->
        <div class="tab-pane fade" id="payments-tab-pane" role="tabpanel" aria-labelledby="payments-tab" tabindex="0">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lịch sử thanh toán</h5>
                </div>
                <div class="card-body p-0">
                    @if($payments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã giao dịch</th>
                                        <th>Học viên</th>
                                        <th>Số tiền</th>
                                        <th>Ngày thanh toán</th>
                                        <th>Phương thức</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $payment)
                                        <tr class="{{ $payment->status == 'pending' ? 'table-warning' : '' }}">
                                            <td>
                                                <div class="fw-medium">{{ $payment->transaction_reference ?? 'PT' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</div>
                                            </td>
                                            <td>
                                                {{ $payment->enrollment->student->full_name }}
                                                <br>
                                                <small class="text-muted">{{ $payment->enrollment->student->phone }}</small>
                                            </td>
                                            <td class="fw-bold text-success">{{ number_format($payment->amount) }} đ</td>
                                            <td>{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                                            <td>
                                                @if($payment->payment_method == 'cash')
                                                    <span class="badge bg-success">Tiền mặt</span>
                                                @elseif($payment->payment_method == 'bank_transfer')
                                                    <span class="badge bg-primary">Chuyển khoản</span>
                                                @elseif($payment->payment_method == 'card')
                                                    <span class="badge bg-info">Thẻ</span>
                                                @else
                                                    <span class="badge bg-secondary">Khác</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($payment->status == 'confirmed')
                                                    <span class="badge bg-success">Đã xác nhận</span>
                                                @elseif($payment->status == 'pending')
                                                    <span class="badge bg-warning">Chờ xác nhận</span>
                                                @elseif($payment->status == 'cancelled')
                                                    <span class="badge bg-danger">Đã hủy</span>
                                                @elseif($payment->status == 'refunded')
                                                    <span class="badge bg-info">Đã hoàn tiền</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a href="#" class="dropdown-item" onclick="showPaymentDetails({{ $payment->id }}); return false;">
                                                                <i class="fas fa-eye me-2"></i>Chi tiết
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#" class="dropdown-item" onclick="editPayment({{ $payment->id }}); return false;">
                                                                <i class="fas fa-edit me-2"></i>Chỉnh sửa
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#" class="dropdown-item" onclick="printPayment({{ $payment->id }}); return false;">
                                                                <i class="fas fa-print me-2"></i>In phiếu thu
                                                            </a>
                                                        </li>
                                                        @if($payment->status == 'pending')
                                                        <li>
                                                            <a href="#" class="dropdown-item" onclick="confirmPayment({{ $payment->id }}); return false;">
                                                                <i class="fas fa-check-circle me-2"></i>Xác nhận
                                                            </a>
                                                        </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-money-bill fa-3x text-muted mb-3"></i>
                            <p class="mb-0">Chưa có giao dịch thanh toán nào.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal lịch sử thanh toán -->
<div class="modal fade" id="paymentHistoryModal" tabindex="-1" aria-labelledby="paymentHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentHistoryModalLabel">
                    <i class="fas fa-history me-2"></i>Lịch sử thanh toán
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="paymentHistoryContent">
                <!-- Nội dung sẽ được thêm bằng JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal chỉnh sửa học phí -->
<div class="modal fade" id="editFeeModal" tabindex="-1" aria-labelledby="editFeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editFeeModalLabel">Chỉnh sửa học phí</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editFeeForm" action="{{ route('enrollments.update-fee') }}" method="POST">
                    @csrf
                    <input type="hidden" name="enrollment_id" id="enrollment_id">
                    
                    <div class="mb-3">
                        <label for="discount_type" class="form-label">Loại chiết khấu</label>
                        <select class="form-select" id="discount_type" name="discount_type">
                            <option value="percentage">Phần trăm (%)</option>
                            <option value="fixed">Số tiền cố định</option>
                            <option value="custom" selected>Tùy chỉnh học phí</option>
                        </select>
                    </div>
                    
                    <div class="mb-3 d-none" id="discount_percentage_group">
                        <label for="discount_percentage" class="form-label">Phần trăm chiết khấu (%)</label>
                        <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" min="0" max="100" step="0.1">
                    </div>
                    
                    <div class="mb-3 d-none" id="discount_amount_group">
                        <label for="discount_amount" class="form-label">Số tiền chiết khấu</label>
                        <input type="number" class="form-control" id="discount_amount" name="discount_amount" min="0">
                    </div>
                    
                    <div class="mb-3" id="final_fee_group">
                        <label for="final_fee" class="form-label">Học phí cuối cùng</label>
                        <input type="number" class="form-control" id="final_fee" name="final_fee" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fee_notes" class="form-label">Ghi chú</label>
                        <textarea class="form-control" id="fee_notes" name="fee_notes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('editFeeForm').submit()">Lưu thay đổi</button>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-sm {
        width: 32px;
        height: 32px;
        font-size: 14px;
    }
</style>

@push('scripts')
<script src="{{ asset('js/payment.js') }}"></script>
<script>
$(document).ready(function() {
    // Thay đổi nút tạo thanh toán để sử dụng modal
    $('.btn-create-payment').on('click', function(e) {
        e.preventDefault();
        const enrollmentId = $(this).data('enrollment-id');
        createPayment(enrollmentId);
    });
});
</script>
@endpush
@endsection 