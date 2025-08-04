@extends('layouts.app')

@section('page-title', 'Lịch sử thanh toán')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Lịch sử thanh toán</li>
@endsection

@section('content')
<div class="row mb-4">
    <!-- Thống kê tổng quan -->
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">{{ $stats['total_payments'] }}</h4>
                        <p class="mb-0">Tổng giao dịch</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-receipt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">{{ number_format($stats['total_amount']) }}</h4>
                        <p class="mb-0">Tổng thu (VNĐ)</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-money-bill fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">{{ number_format($stats['today_amount']) }}</h4>
                        <p class="mb-0">Thu hôm nay (VNĐ)</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-calendar-day fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">{{ $stats['pending_count'] }}</h4>
                        <p class="mb-0">Chờ xác nhận</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-history me-2"></i>
                Lịch sử thanh toán
            </h5>
            <div>
                <button type="button" class="btn btn-success btn-sm" onclick="exportExcel()">
                    <i class="fas fa-file-excel me-1"></i>
                    Xuất Excel
                </button>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                    <i class="fas fa-plus me-1"></i>
                    Ghi nhận thanh toán
                </button>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Form filter -->
        <form method="GET" action="{{ route('payments.index') }}" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Tên học viên, SĐT, mã GD..." 
                           value="{{ $filters['search'] ?? '' }}">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Khóa học</label>
                    <select name="course_item_id" class="form-select">
                        <option value="">Tất cả khóa học</option>
                        @foreach($courseItems as $courseItem)
                            <option value="{{ $courseItem->id }}" 
                                    {{ ($filters['course_item_id'] ?? '') == $courseItem->id ? 'selected' : '' }}>
                                {{ $courseItem->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Phương thức</label>
                    <select name="payment_method" class="form-select">
                        <option value="">Tất cả phương thức</option>
                        <option value="cash" {{ ($filters['payment_method'] ?? '') == 'cash' ? 'selected' : '' }}>Tiền mặt</option>
                        <option value="bank_transfer" {{ ($filters['payment_method'] ?? '') == 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                        <option value="card" {{ ($filters['payment_method'] ?? '') == 'card' ? 'selected' : '' }}>Thẻ</option>
                        <option value="qr_code" {{ ($filters['payment_method'] ?? '') == 'qr_code' ? 'selected' : '' }}>Mã QR</option>
                        <option value="sepay" {{ ($filters['payment_method'] ?? '') == 'sepay' ? 'selected' : '' }}>SePay</option>
                        <option value="other" {{ ($filters['payment_method'] ?? '') == 'other' ? 'selected' : '' }}>Khác</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" {{ ($filters['status'] ?? '') == 'pending' ? 'selected' : '' }}>Chờ xác nhận</option>
                        <option value="confirmed" {{ ($filters['status'] ?? '') == 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                        <option value="cancelled" {{ ($filters['status'] ?? '') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                        <option value="refunded" {{ ($filters['status'] ?? '') == 'refunded' ? 'selected' : '' }}>Đã hoàn tiền</option>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" name="date_from" class="form-control" 
                           value="{{ $filters['date_from'] ?? '' }}">
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" name="date_to" class="form-control" 
                           value="{{ $filters['date_to'] ?? '' }}">
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="{{ route('payments.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Bảng lịch sử thanh toán -->
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>Mã GD</th>
                        <th>Ngày thanh toán</th>
                        <th>Học viên</th>
                        <th>Khóa học</th>
                        <th class="text-end">Số tiền</th>
                        <th>Phương thức</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ $payment->transaction_reference ?: 'PT' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}
                                </span>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $payment->payment_date->format('d/m/Y') }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $payment->payment_date->format('H:i') }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                        {{ strtoupper(substr($payment->enrollment->student->full_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <strong>{{ $payment->enrollment->student->full_name }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-phone me-1"></i>
                                            {{ $payment->enrollment->student->phone }}
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $payment->enrollment->courseItem->name }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-layer-group me-1"></i>
                                        Level {{ $payment->enrollment->courseItem->level }}
                                    </small>
                                </div>
                            </td>
                            <td class="text-end">
                                <strong class="text-success">{{ number_format($payment->amount) }} đ</strong>
                            </td>
                            <td>
                                @switch($payment->payment_method)
                                    @case('cash')
                                        <span class="badge bg-success">
                                            <i class="fas fa-money-bill me-1"></i>Tiền mặt
                                        </span>
                                        @break
                                    @case('bank_transfer')
                                        <span class="badge bg-info">
                                            <i class="fas fa-university me-1"></i>Chuyển khoản
                                        </span>
                                        @break
                                    @case('card')
                                        <span class="badge bg-primary">
                                            <i class="fas fa-credit-card me-1"></i>Thẻ
                                        </span>
                                        @break
                                    @case('qr_code')
                                        <span class="badge bg-warning">
                                            <i class="fas fa-qrcode me-1"></i>Mã QR
                                        </span>
                                        @break
                                    @case('sepay')
                                        <span class="badge bg-dark">
                                            <i class="fas fa-mobile-alt me-1"></i>SePay
                                        </span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-question me-1"></i>Khác
                                        </span>
                                @endswitch
                            </td>
                            <td>
                                @switch($payment->status)
                                    @case('confirmed')
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Đã xác nhận
                                        </span>
                                        @break
                                    @case('pending')
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock me-1"></i>Chờ xác nhận
                                        </span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times me-1"></i>Đã hủy
                                        </span>
                                        @break
                                    @case('refunded')
                                        <span class="badge bg-info">
                                            <i class="fas fa-undo me-1"></i>Đã hoàn tiền
                                        </span>
                                        @break
                                @endswitch
                            </td>
                            <td>
                                <span class="text-muted">{{ $payment->notes ?: '-' }}</span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-info" 
                                            onclick="viewPaymentDetail({{ $payment->id }})"
                                            title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if($payment->status === 'pending')
                                        <button type="button" class="btn btn-success" 
                                                onclick="confirmPayment({{ $payment->id }})"
                                                title="Xác nhận">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger" 
                                                onclick="cancelPayment({{ $payment->id }})"
                                                title="Hủy">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                    @if($payment->status === 'confirmed')
                                        <button type="button" class="btn btn-warning" 
                                                onclick="refundPayment({{ $payment->id }})"
                                                title="Hoàn tiền">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-search fa-3x mb-3"></i>
                                    <h5>Không tìm thấy giao dịch nào</h5>
                                    <p>Thử thay đổi bộ lọc hoặc tìm kiếm với từ khóa khác.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Phân trang -->
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted">
                Hiển thị {{ $payments->firstItem() ?? 0 }} - {{ $payments->lastItem() ?? 0 }} 
                trong tổng số {{ $payments->total() }} giao dịch
            </div>
            {{ $payments->links() }}
        </div>
    </div>
</div>

<!-- Modal thêm thanh toán -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPaymentModalLabel">
                    <i class="fas fa-plus me-2"></i>
                    Ghi nhận thanh toán mới
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('payments.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Học viên <span class="text-danger">*</span></label>
                                <select name="enrollment_id" class="form-select" required>
                                    <option value="">Chọn học viên...</option>
                                    @foreach(App\Models\Enrollment::with(['student', 'courseItem'])->get() as $enrollment)
                                        <option value="{{ $enrollment->id }}">
                                            {{ $enrollment->student->full_name }} - {{ $enrollment->courseItem->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Số tiền <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control" min="1000" step="1000" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ngày thanh toán <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phương thức <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="">Chọn phương thức...</option>
                                    <option value="cash">Tiền mặt</option>
                                    <option value="bank_transfer">Chuyển khoản</option>
                                    <option value="card">Thẻ</option>
                                    <option value="qr_code">Mã QR</option>
                                    <option value="sepay">SePay</option>
                                    <option value="other">Khác</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="confirmed">Đã xác nhận</option>
                                    <option value="pending">Chờ xác nhận</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Mã giao dịch</label>
                                <input type="text" name="transaction_id" class="form-control" placeholder="Mã giao dịch (nếu có)">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Ghi chú</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Ghi chú thêm..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Lưu thanh toán
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal chi tiết thanh toán -->
<div class="modal fade" id="paymentDetailModal" tabindex="-1" aria-labelledby="paymentDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentDetailModalLabel">
                    <i class="fas fa-receipt me-2"></i>
                    Chi tiết thanh toán
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="paymentDetailContent">
                    <!-- Nội dung sẽ được load bằng JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Xem chi tiết thanh toán
    function viewPaymentDetail(paymentId) {
        const modal = new bootstrap.Modal(document.getElementById('paymentDetailModal'));
        document.getElementById('paymentDetailContent').innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Đang tải thông tin...</p>
            </div>
        `;
        modal.show();
        
        fetch(`/api/payments/${paymentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const payment = data.data;
                    const enrollment = payment.enrollment;
                    const student = enrollment.student;
                    const courseItem = enrollment.course_item;
                    
                    let html = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3">Thông tin thanh toán</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">Mã giao dịch</th>
                                        <td>${payment.transaction_reference || 'PT' + String(payment.id).padStart(6, '0')}</td>
                                    </tr>
                                    <tr>
                                        <th>Ngày thanh toán</th>
                                        <td>${formatDate(payment.payment_date)}</td>
                                    </tr>
                                    <tr>
                                        <th>Số tiền</th>
                                        <td class="fw-bold text-success">${formatCurrency(payment.amount)} đ</td>
                                    </tr>
                                    <tr>
                                        <th>Phương thức</th>
                                        <td>${getPaymentMethodText(payment.payment_method)}</td>
                                    </tr>
                                    <tr>
                                        <th>Trạng thái</th>
                                        <td>${getPaymentStatusBadge(payment.status)}</td>
                                    </tr>
                                    <tr>
                                        <th>Ghi chú</th>
                                        <td>${payment.notes || '-'}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3">Thông tin học viên</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">Họ tên</th>
                                        <td>${student.full_name}</td>
                                    </tr>
                                    <tr>
                                        <th>Số điện thoại</th>
                                        <td>${student.phone}</td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td>${student.email || '-'}</td>
                                    </tr>
                                    <tr>
                                        <th>Khóa học</th>
                                        <td>${courseItem.name}</td>
                                    </tr>
                                    <tr>
                                        <th>Học phí</th>
                                        <td>${formatCurrency(enrollment.final_fee)} đ</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('paymentDetailContent').innerHTML = html;
                } else {
                    document.getElementById('paymentDetailContent').innerHTML = 
                        `<div class="alert alert-danger">Có lỗi xảy ra: ${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('paymentDetailContent').innerHTML = 
                    `<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu: ${error.message}</div>`;
            });
    }
    
    // Xác nhận thanh toán
    function confirmPayment(paymentId) {
        if (confirm('Bạn có chắc chắn muốn xác nhận thanh toán này?')) {
            fetch(`/payments/${paymentId}/confirm`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Đã xác nhận thanh toán thành công!');
                    window.location.reload();
                } else {
                    alert('Có lỗi xảy ra: ' + data.message);
                }
            })
            .catch(error => {
                alert('Có lỗi xảy ra: ' + error.message);
            });
        }
    }
    
    // Hủy thanh toán
    function cancelPayment(paymentId) {
        if (confirm('Bạn có chắc chắn muốn hủy thanh toán này?')) {
            fetch(`/payments/${paymentId}/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Đã hủy thanh toán thành công!');
                    window.location.reload();
                } else {
                    alert('Có lỗi xảy ra: ' + data.message);
                }
            })
            .catch(error => {
                alert('Có lỗi xảy ra: ' + error.message);
            });
        }
    }
    
    // Hoàn tiền
    function refundPayment(paymentId) {
        if (confirm('Bạn có chắc chắn muốn hoàn tiền cho thanh toán này?')) {
            fetch(`/payments/${paymentId}/refund`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Đã hoàn tiền thành công!');
                    window.location.reload();
                } else {
                    alert('Có lỗi xảy ra: ' + data.message);
                }
            })
            .catch(error => {
                alert('Có lỗi xảy ra: ' + error.message);
            });
        }
    }
    
    // Xuất Excel
    function exportExcel() {
        const params = new URLSearchParams(window.location.search);
        window.open(`{{ route('payments.export') }}?${params.toString()}`, '_blank');
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