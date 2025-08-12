@extends('layouts.app')

@section('title', 'Quản lý thanh toán')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-money-bill-wave me-2"></i>Quản lý thanh toán
                    </h5>
                    <button type="button" class="btn btn-primary" onclick="showPaymentForm()">
                        <i class="fas fa-plus me-1"></i>Tạo phiếu thu
                    </button>
                </div>

                <!-- Search and Filter Form -->
                <div class="card-body border-bottom">
                    <form method="GET" action="{{ route('payments.index') }}" id="paymentSearchForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Tìm kiếm học viên</label>
                                <select name="student_search" id="student_search" class="form-select student-search">
                                    <option value="">Tìm theo tên, SĐT...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Phương thức thanh toán</label>
                                <select name="payment_method" class="form-select">
                                    <option value="">Tất cả phương thức</option>
                                    <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Tiền mặt</option>
                                    <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                                    <option value="credit_card" {{ request('payment_method') == 'credit_card' ? 'selected' : '' }}>Thẻ tín dụng</option>
                                    <option value="e_wallet" {{ request('payment_method') == 'e_wallet' ? 'selected' : '' }}>Ví điện tử</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Trạng thái</label>
                                <select name="status" class="form-select">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xác nhận</option>
                                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
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

                <!-- Payments Table -->
                @if(isset($payments) && $payments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Học viên</th>
                                    <th width="15%">Khóa học</th>
                                    <th width="10%">Số tiền</th>
                                    <th width="10%">Phương thức</th>
                                    <th width="10%">Ngày thanh toán</th>
                                    <th width="10%">Trạng thái</th>
                                    <th width="10%">Người thu</th>
                                    <th width="15%">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payments as $index => $payment)
                                    <tr>
                                        <td>{{ $payments->firstItem() + $index }}</td>
                                        <td>
                                            <strong>{{ $payment->enrollment->student->full_name }}</strong>
                                            <br><small class="text-muted">{{ $payment->enrollment->student->phone }}</small>
                                        </td>
                                        <td>
                                            <strong>{{ $payment->enrollment->courseItem->name }}</strong>
                                            @if($payment->enrollment->courseItem->parent)
                                                <br><small class="text-muted">{{ $payment->enrollment->courseItem->parent->name }}</small>
                                            @endif
                                        </td>
                                        <td class="fw-bold text-success">{{ number_format($payment->amount) }} VNĐ</td>
                                        <td>
                                            @if($payment->payment_method === 'cash')
                                                <span class="badge bg-success">Tiền mặt</span>
                                            @elseif($payment->payment_method === 'bank_transfer')
                                                <span class="badge bg-primary">Chuyển khoản</span>
                                            @elseif($payment->payment_method === 'credit_card')
                                                <span class="badge bg-warning">Thẻ tín dụng</span>
                                            @elseif($payment->payment_method === 'e_wallet')
                                                <span class="badge bg-info">Ví điện tử</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $payment->payment_method }}</span>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y H:i') }}</td>
                                        <td>
                                            @if($payment->status === 'confirmed')
                                                <span class="badge bg-success">Đã xác nhận</span>
                                            @elseif($payment->status === 'pending')
                                                <span class="badge bg-warning">Chờ xác nhận</span>
                                            @elseif($payment->status === 'cancelled')
                                                <span class="badge bg-danger">Đã hủy</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $payment->status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($payment->collected_by)
                                                {{ $payment->collectedBy->name }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="showPaymentDetail({{ $payment->id }})"
                                                        title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" 
                                                        onclick="printReceipt({{ $payment->id }})"
                                                        title="In biên lai">
                                                    <i class="fas fa-print"></i>
                                                </button>
                                                @if($payment->status === 'pending')
                                                    <button class="btn btn-sm btn-outline-warning" 
                                                            onclick="showPaymentForm({{ $payment->id }})"
                                                            title="Chỉnh sửa">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                @endif
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDeletePayment({{ $payment->id }}, '{{ number_format($payment->amount) }} VNĐ')"
                                                        title="Xóa thanh toán">
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
                        {{ $payments->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Chưa có thanh toán nào</h5>
                        <p class="text-muted">Hãy tạo phiếu thu đầu tiên để bắt đầu quản lý.</p>
                        <button type="button" class="btn btn-primary" onclick="showPaymentForm()">
                            <i class="fas fa-plus me-1"></i>Tạo phiếu thu đầu tiên
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
// Payments Page với Unified Modal System
document.addEventListener('app:ready', function() {
    console.log('Payments page ready with Unified Modal System');
});

// Functions để sử dụng Unified Modal System
function showPaymentDetail(paymentId) {
    if (window.unifiedModals) {
        window.unifiedModals.showPaymentDetail(paymentId);
    } else {
        alert('Chức năng xem chi tiết thanh toán đang được phát triển');
    }
}

function showPaymentForm(paymentId = null) {
    if (window.unifiedModals) {
        window.unifiedModals.showPaymentForm(paymentId);
    } else {
        alert('Chức năng form thanh toán đang được phát triển');
    }
}

function printReceipt(paymentId) {
    window.open(`/payments/${paymentId}/receipt`, '_blank');
}

function confirmDeletePayment(paymentId, amount) {
    if (window.unifiedModals) {
        window.unifiedModals.confirmDelete(paymentId, `Thanh toán ${amount}`, 'payment');
    } else {
        if (confirm(`Bạn có chắc chắn muốn xóa thanh toán "${amount}"?`)) {
            alert('Chức năng xóa thanh toán đang được phát triển');
        }
    }
}

// Legacy functions để tương thích
function viewPayment(paymentId) {
    showPaymentDetail(paymentId);
}

function editPayment(paymentId) {
    showPaymentForm(paymentId);
}

function createPayment() {
    showPaymentForm();
}
</script>
@endpush

@endsection
