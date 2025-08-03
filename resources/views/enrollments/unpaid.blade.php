@extends('layouts.app')

@section('page-title', 'Học viên chưa đóng đủ học phí')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Thanh toán</a></li>
    <li class="breadcrumb-item active">Chưa đóng đủ học phí</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
         <h5 class="card-title mb-0">Danh sách học viên chưa đóng đủ học phí</h5>
    </div>
    <div class="card-body">
         <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <form action="{{ route('payments.send-reminder') }}" method="POST" id="bulk-reminder-form" class="d-inline">
                     @csrf
                     <input type="hidden" name="payment_ids[]" class="bulk-payment-ids">
                     <button type="submit" class="btn btn-info btn-sm" id="bulk-remind-btn" disabled>
                        <i class="fas fa-envelope me-1"></i> Gửi nhắc nhở đã chọn
                    </button>
                </form>
                {{-- Thêm các nút hàng loạt khác ở đây --}}
            </div>
            <div>
                {{-- Nút tìm kiếm --}}
            </div>
        </div>
        {{-- Bỏ form này vì không cần thiết và đang gây lỗi--}}
        <div>
             @csrf
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="check-all"></th>
                            <th>Học viên</th>
                            <th>Khóa học</th>
                            <th class="text-end">Học phí</th>
                            <th class="text-end">Đã đóng</th>
                            <th class="text-end">Còn lại</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($studentEnrollments as $studentId => $studentData)
                            <tr class="table-secondary">
                                <td colspan="7">
                                    <strong class="fs-5">{{ $studentData['student']->full_name }}</strong>
                                    <span class="badge bg-primary">{{ $studentData['student']->phone }}</span>
                                    <span class="badge bg-warning">Tổng nợ: {{ number_format($studentData['total_remaining']) }}đ</span>
                                </td>
                            </tr>
                            @foreach($studentData['enrollments'] as $index => $enrollmentData)
                                @php
                                    $enrollment = $enrollmentData['enrollment'];
                                    
                                    // Sử dụng thanh toán hiện có nếu có, nếu không thì chỉ để trống
                                    $existingPayment = $enrollment->payments->first();
                                    $remainingAmount = $enrollmentData['remaining'];
                                @endphp
                                <tr>
                                    <td>
                                        @if($existingPayment)
                                            <input type="checkbox" class="check-item" value="{{ $existingPayment->id }}" data-type="payment">
                                        @else
                                            <input type="checkbox" class="check-item" value="{{ $enrollment->id }}" data-type="enrollment">
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $studentData['student']->email }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $enrollment->courseItem->name }}</strong>
                                    </td>
                                    <td class="text-end">{{ number_format($enrollmentData['fee']) }}đ</td>
                                    <td class="text-end">{{ number_format($enrollmentData['paid']) }}đ</td>
                                    <td class="text-end text-danger fw-bold">{{ number_format($remainingAmount) }}đ</td>
                                    <td>
                                        {{-- Thêm nút xem chi tiết --}}
                                        <button type="button" class="btn btn-info btn-sm" onclick="showEnrollmentDetails({{ $enrollment->id }})" title="Xem chi tiết">
                                            <i class="fas fa-eye me-1"></i> Chi tiết
                                        </button>
                                        
                                        {{-- Link trang thanh toán QR - luôn hiển thị, không phụ thuộc vào existingPayment --}}
                                        <a href="{{ $existingPayment ? route('payment.gateway.show', $existingPayment) : route('payment.gateway.direct', $enrollment) }}" class="btn btn-success btn-sm" title="Trang thanh toán">
                                            <i class="fas fa-qrcode me-1"></i> Trang thanh toán
                                        </a>
                                        
                                        {{-- Nút gửi nhắc nhở - luôn hiển thị, không phụ thuộc vào existingPayment --}}
                                        @if($existingPayment)
                                            <form action="{{ route('payments.send-reminder') }}" method="POST" class="d-inline" onsubmit="return confirm('Gửi email nhắc nhở cho học viên này?')">
                                                @csrf
                                                <input type="hidden" name="payment_ids[]" value="{{ $existingPayment->id }}">
                                                <button type="submit" class="btn btn-warning btn-sm" title="Gửi nhắc nhở">
                                                    <i class="fas fa-bell me-1"></i> Gửi nhắc nhở
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('payments.send-direct-reminder') }}" method="POST" class="d-inline" onsubmit="return confirm('Gửi email nhắc nhở cho học viên này?')">
                                                @csrf
                                                <input type="hidden" name="enrollment_ids[]" value="{{ $enrollment->id }}">
                                                <button type="submit" class="btn btn-warning btn-sm" title="Gửi nhắc nhở">
                                                    <i class="fas fa-bell me-1"></i> Gửi nhắc nhở
                                                </button>
                                            </form>
                                        @endif
                                        
                                     
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Không có công nợ nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Xử lý checkbox chọn tất cả
        document.getElementById('check-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.check-item');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkButtons();
        });
        
        // Xử lý khi checkbox đơn lẻ thay đổi
        document.querySelectorAll('.check-item').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateBulkButtons();
            });
        });
        
        // Cập nhật trạng thái các nút hành động hàng loạt
        function updateBulkButtons() {
            const checkedCount = document.querySelectorAll('.check-item:checked').length;
            const bulkRemindBtn = document.getElementById('bulk-remind-btn');
            
            if (checkedCount > 0) {
                bulkRemindBtn.disabled = false;
                
                // Cập nhật danh sách ID cho form gửi nhắc nhở hàng loạt
                const paymentIds = [];
                const enrollmentIds = [];
                
                document.querySelectorAll('.check-item:checked').forEach(checkbox => {
                    if (checkbox.dataset.type === 'payment') {
                        paymentIds.push(checkbox.value);
                    } else if (checkbox.dataset.type === 'enrollment') {
                        enrollmentIds.push(checkbox.value);
                    }
                });
                
                document.querySelector('.bulk-payment-ids').value = paymentIds.join(',');
            } else {
                bulkRemindBtn.disabled = true;
            }
        }
    });
    
    // Hiển thị chi tiết ghi danh trong modal
    function showEnrollmentDetails(enrollmentId) {
        // Hiển thị loading
        document.getElementById('enrollmentDetailsContent').innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Đang tải thông tin...</p>
            </div>
        `;
        
        // Hiển thị modal
        const modal = new bootstrap.Modal(document.getElementById('enrollmentDetailsModal'));
        modal.show();
        
        // Tải thông tin ghi danh
        fetch(`/api/enrollments/${enrollmentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const enrollment = data.data;
                    const student = enrollment.student;
                    const courseItem = enrollment.course_item;
                    const payments = enrollment.payments || [];
                    
                    // Tính toán số tiền đã đóng và còn lại
                    let totalPaid = 0;
                    payments.forEach(payment => {
                        if (payment.status === 'confirmed') {
                            totalPaid += parseFloat(payment.amount);
                        }
                    });
                    
                    const remainingAmount = parseFloat(enrollment.final_fee) - totalPaid;
                    
                    // Tạo HTML hiển thị chi tiết
                    let html = `
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">Thông tin học viên</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">Họ tên</th>
                                        <td>${student.full_name}</td>
                                    </tr>
                                    <tr>
                                        <th>Số điện thoại</th>
                                        <td>${student.phone}</td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td>${student.email || 'Không có'}</td>
                                    </tr>
                                    <tr>
                                        <th>Địa chỉ</th>
                                        <td>${student.address || 'Không có'}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-3">Thông tin khóa học</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">Khóa học</th>
                                        <td>${courseItem.name}</td>
                                    </tr>
                                    <tr>
                                        <th>Ngày ghi danh</th>
                                        <td>${formatDate(enrollment.enrollment_date)}</td>
                                    </tr>
                                    <tr>
                                        <th>Học phí</th>
                                        <td>${formatCurrency(enrollment.final_fee)} đ</td>
                                    </tr>
                                    <tr>
                                        <th>Đã đóng</th>
                                        <td class="text-success">${formatCurrency(totalPaid)} đ</td>
                                    </tr>
                                    <tr>
                                        <th>Còn lại</th>
                                        <td class="text-danger fw-bold">${formatCurrency(remainingAmount)} đ</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <h5 class="mt-4 mb-3">Lịch sử thanh toán</h5>
                    `;
                    
                    if (payments.length > 0) {
                        html += `
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Mã GD</th>
                                            <th>Ngày thanh toán</th>
                                            <th>Số tiền</th>
                                            <th>Phương thức</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        payments.forEach(payment => {
                            html += `
                                <tr>
                                    <td>${payment.transaction_reference || 'PT' + String(payment.id).padStart(6, '0')}</td>
                                    <td>${formatDate(payment.payment_date)}</td>
                                    <td class="text-end">${formatCurrency(payment.amount)} đ</td>
                                    <td>${getPaymentMethodText(payment.payment_method)}</td>
                                    <td>${getPaymentStatusBadge(payment.status)}</td>
                                </tr>
                            `;
                        });
                        
                        html += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        html += `<div class="alert alert-info">Chưa có lịch sử thanh toán nào.</div>`;
                    }
                    
                    // Hiển thị nút tạo thanh toán mới
                    html += `
                        <div class="mt-4 text-center">
                            <button type="button" class="btn btn-primary" onclick="createPayment(${enrollmentId})">
                                <i class="fas fa-plus-circle me-2"></i>Tạo thanh toán mới
                            </button>
                        </div>
                    `;
                    
                    document.getElementById('enrollmentDetailsContent').innerHTML = html;
                } else {
                    document.getElementById('enrollmentDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            Không thể tải thông tin ghi danh. Vui lòng thử lại sau.
                        </div>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('enrollmentDetailsContent').innerHTML = `
                    <div class="alert alert-danger">
                        Đã xảy ra lỗi: ${error.message}
                    </div>
                `;
            });
    }
    
    // Hàm tạo thanh toán mới
    function createPayment(enrollmentId) {
        // Đóng modal chi tiết
        bootstrap.Modal.getInstance(document.getElementById('enrollmentDetailsModal')).hide();
        
        // Chuyển đến trang tạo thanh toán với enrollment_id
        window.location.href = `/payments/create?enrollment_id=${enrollmentId}`;
    }
    
    // Hàm định dạng tiền tệ
    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount);
    }
    
    // Hàm định dạng ngày tháng
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN');
    }
    
    // Hàm lấy text phương thức thanh toán
    function getPaymentMethodText(method) {
        const methodMap = {
            'cash': 'Tiền mặt',
            'bank_transfer': 'Chuyển khoản',
            'card': 'Thẻ',
            'qr_code': 'Mã QR',
            'sepay': 'SePay',
            'other': 'Khác'
        };
        return methodMap[method] || method;
    }
    
    // Hàm lấy badge trạng thái thanh toán
    function getPaymentStatusBadge(status) {
        const statusMap = {
            'pending': '<span class="badge bg-warning">Chờ xác nhận</span>',
            'confirmed': '<span class="badge bg-success">Đã xác nhận</span>',
            'cancelled': '<span class="badge bg-danger">Đã hủy</span>',
            'refunded': '<span class="badge bg-info">Đã hoàn tiền</span>'
        };
        return statusMap[status] || status;
    }
</script>
@endpush 

<!-- Modal chi tiết ghi danh -->
<div class="modal fade" id="enrollmentDetailsModal" tabindex="-1" aria-labelledby="enrollmentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="enrollmentDetailsModalLabel">
                    <i class="fas fa-info-circle me-2"></i>Chi tiết ghi danh
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="enrollmentDetailsContent">
                <!-- Nội dung sẽ được thêm bằng JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div> 