@extends('layouts.app')

@section('page-title', 'Thanh toán - ' . $courseItem->name)

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2>Thanh toán: {{ $courseItem->name }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('course-items.show', $courseItem->id) }}">Khóa học</a></li>
                    <li class="breadcrumb-item active">Thanh toán</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('course-items.show', $courseItem->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <!-- Thống kê thanh toán -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Tổng học viên</h5>
                    <h2>{{ $stats['total_enrollments'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Đã thanh toán</h5>
                    <h2>{{ number_format($stats['total_paid']) }} đ</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Tổng học phí</h5>
                    <h2>{{ number_format($stats['total_fee']) }} đ</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Còn lại</h5>
                    <h2>{{ number_format($stats['remaining']) }} đ</h2>
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
                        <button class="btn btn-sm btn-success" onclick="exportToExcel()">
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
                                            <button class="btn btn-sm btn-link p-0 ms-1" onclick="editEnrollmentFee({{ $enrollment->id }}, {{ $enrollment->final_fee }})">
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
                                            <div class="btn-group">
                                                @if(!$isFullyPaid)
                                                    <a href="{{ route('payments.create', ['enrollment_id' => $enrollment->id]) }}" class="btn btn-sm btn-success" title="Thêm thanh toán">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route('students.show', $enrollment->student_id) }}" class="btn btn-sm btn-info" title="Chi tiết học viên">
                                                    <i class="fas fa-user"></i>
                                                </a>
                                                <button class="btn btn-sm btn-primary" onclick="viewPaymentHistory({{ $enrollment->id }})" title="Lịch sử thanh toán">
                                                    <i class="fas fa-history"></i>
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
                                                <div class="btn-group">
                                                    <a href="{{ route('payments.show', $payment->id) }}" class="btn btn-sm btn-info" title="Chi tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('payments.receipt', $payment->id) }}" target="_blank" class="btn btn-sm btn-success" title="In biên lai">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                    @if($payment->status == 'pending')
                                                        <form action="{{ route('payments.confirm', $payment->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success" title="Xác nhận">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    @endif
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

<!-- Modal chỉnh sửa học phí -->
<div class="modal fade" id="editFeeModal" tabindex="-1" aria-labelledby="editFeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editFeeModalLabel">Chỉnh sửa học phí</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editFeeForm" action="{{ route('enrollments.update-fee') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="enrollment_id" name="enrollment_id">
                    
                    <div class="mb-3">
                        <label for="original_fee" class="form-label">Học phí gốc</label>
                        <input type="text" class="form-control" value="{{ number_format($courseItem->fee) }} đ" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="discount_type" class="form-label">Loại chiết khấu</label>
                        <select class="form-select" id="discount_type" name="discount_type">
                            <option value="none">Không chiết khấu</option>
                            <option value="percentage">Phần trăm (%)</option>
                            <option value="fixed">Số tiền cụ thể (đ)</option>
                            <option value="custom">Tùy chỉnh học phí cuối</option>
                        </select>
                    </div>
                    
                    <div id="discount_percentage_group" class="mb-3 d-none">
                        <label for="discount_percentage" class="form-label">Phần trăm chiết khấu (%)</label>
                        <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" min="0" max="100" step="0.1">
                    </div>
                    
                    <div id="discount_amount_group" class="mb-3 d-none">
                        <label for="discount_amount" class="form-label">Số tiền chiết khấu (đ)</label>
                        <input type="number" class="form-control" id="discount_amount" name="discount_amount" min="0">
                    </div>
                    
                    <div id="final_fee_group" class="mb-3 d-none">
                        <label for="final_fee" class="form-label">Học phí cuối cùng (đ)</label>
                        <input type="number" class="form-control" id="final_fee" name="final_fee" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Lý do điều chỉnh</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal lịch sử thanh toán -->
<div class="modal fade" id="paymentHistoryModal" tabindex="-1" aria-labelledby="paymentHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentHistoryModalLabel">Lịch sử thanh toán</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="paymentHistoryContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
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
<script>
    function editEnrollmentFee(enrollmentId, currentFee) {
        document.getElementById('enrollment_id').value = enrollmentId;
        document.getElementById('final_fee').value = currentFee;
        
        // Reset form
        document.getElementById('discount_type').value = 'custom';
        document.getElementById('discount_percentage_group').classList.add('d-none');
        document.getElementById('discount_amount_group').classList.add('d-none');
        document.getElementById('final_fee_group').classList.remove('d-none');
        
        // Show modal
        new bootstrap.Modal(document.getElementById('editFeeModal')).show();
    }
    
    document.getElementById('discount_type').addEventListener('change', function() {
        const discountType = this.value;
        
        // Hide all input groups first
        document.getElementById('discount_percentage_group').classList.add('d-none');
        document.getElementById('discount_amount_group').classList.add('d-none');
        document.getElementById('final_fee_group').classList.add('d-none');
        
        // Show the appropriate input based on selection
        if (discountType === 'percentage') {
            document.getElementById('discount_percentage_group').classList.remove('d-none');
        } else if (discountType === 'fixed') {
            document.getElementById('discount_amount_group').classList.remove('d-none');
        } else if (discountType === 'custom') {
            document.getElementById('final_fee_group').classList.remove('d-none');
        }
    });
    
    function viewPaymentHistory(enrollmentId) {
        const modal = new bootstrap.Modal(document.getElementById('paymentHistoryModal'));
        modal.show();
        
        // Load payment history
        fetch(`/enrollments/${enrollmentId}/payments`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('paymentHistoryContent').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('paymentHistoryContent').innerHTML = 
                    `<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu: ${error.message}</div>`;
            });
    }
    
    function exportToExcel() {
        // Implement export functionality
        alert('Chức năng xuất Excel đang được phát triển!');
    }
</script>
@endpush
@endsection 