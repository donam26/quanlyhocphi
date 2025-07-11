@extends('layouts.app')

@section('page-title', 'Quản lý thanh toán')

@section('breadcrumb')
<li class="breadcrumb-item active">Thanh toán</li>
@endsection

@section('content')
<!-- Filter & Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('payments.index') }}">
            <div class="row">
                <div class="col-md-3">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Tìm theo tên, SĐT, mã GD..." 
                               value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="method" class="form-select">
                        <option value="">Tất cả phương thức</option>
                        <option value="cash" {{ request('method') == 'cash' ? 'selected' : '' }}>Tiền mặt</option>
                        <option value="bank_transfer" {{ request('method') == 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                        <option value="card" {{ request('method') == 'card' ? 'selected' : '' }}>Thẻ</option>
                        <option value="other" {{ request('method') == 'other' ? 'selected' : '' }}>Khác</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xác nhận</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Thất bại</option>
                        <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Đã hoàn</option>
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
                        <a href="{{ route('payments.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Ghi nhận
                        </a>
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
            <i class="fas fa-credit-card me-2"></i>
            Lịch sử thanh toán
            <span class="badge bg-primary ms-2">{{ $payments->total() }} giao dịch</span>
        </h5>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline-success" onclick="exportPayments()">
                <i class="fas fa-file-excel me-1"></i>Xuất Excel
            </button>
            <button class="btn btn-sm btn-outline-info" onclick="printReceipts()">
                <i class="fas fa-print me-1"></i>In phiếu thu
            </button>
            <button class="btn btn-sm btn-outline-warning" onclick="bulkActions()">
                <i class="fas fa-tasks me-1"></i>Hàng loạt
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        @if($payments->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="3%">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th width="15%">Mã GD</th>
                            <th width="25%">Học viên</th>
                            <th width="20%">Lớp học</th>
                            <th width="12%">Số tiền</th>
                            <th width="10%">Phương thức</th>
                            <th width="10%">Trạng thái</th>
                            <th width="5%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                        <tr class="{{ $payment->status == 'pending' ? 'table-warning' : '' }}">
                            <td>
                                <input type="checkbox" name="selected_payments[]" 
                                       value="{{ $payment->id }}" class="payment-checkbox">
                            </td>
                            <td>
                                <div class="fw-medium">{{ $payment->transaction_id ?? 'PT' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</div>
                                <small class="text-muted">{{ $payment->payment_date->format('d/m/Y H:i') }}</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                        {{ strtoupper(substr($payment->enrollment->student->full_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-medium">{{ $payment->enrollment->student->full_name }}</div>
                                        <small class="text-muted">{{ $payment->enrollment->student->phone }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div class="fw-medium">{{ $payment->enrollment->courseClass->name }}</div>
                                    <small class="text-muted">{{ $payment->enrollment->courseClass->course->name }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-success">{{ number_format($payment->amount) }} VNĐ</div>
                                @if($payment->notes)
                                    <small class="text-muted" title="{{ $payment->notes }}">
                                        <i class="fas fa-sticky-note"></i>
                                    </small>
                                @endif
                            </td>
                            <td>
                                @if($payment->method == 'cash')
                                    <span class="badge bg-success">
                                        <i class="fas fa-money-bill me-1"></i>Tiền mặt
                                    </span>
                                @elseif($payment->method == 'bank_transfer')
                                    <span class="badge bg-primary">
                                        <i class="fas fa-university me-1"></i>Chuyển khoản
                                    </span>
                                @elseif($payment->method == 'card')
                                    <span class="badge bg-info">
                                        <i class="fas fa-credit-card me-1"></i>Thẻ
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-question me-1"></i>Khác
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($payment->status == 'completed')
                                    <span class="badge bg-success">Hoàn thành</span>
                                @elseif($payment->status == 'pending')
                                    <span class="badge bg-warning">Chờ xác nhận</span>
                                @elseif($payment->status == 'failed')
                                    <span class="badge bg-danger">Thất bại</span>
                                @else
                                    <span class="badge bg-info">Đã hoàn</span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('payments.show', $payment) }}">
                                                <i class="fas fa-eye me-2"></i>Chi tiết
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('payments.edit', $payment) }}">
                                                <i class="fas fa-edit me-2"></i>Chỉnh sửa
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('payments.receipt', $payment) }}" target="_blank">
                                                <i class="fas fa-print me-2"></i>In phiếu thu
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        @if($payment->status == 'pending')
                                        <li>
                                            <button class="dropdown-item" onclick="confirmPayment({{ $payment->id }})">
                                                <i class="fas fa-check me-2"></i>Xác nhận
                                            </button>
                                        </li>
                                        @endif
                                        @if($payment->status == 'completed')
                                        <li>
                                            <button class="dropdown-item text-warning" onclick="refundPayment({{ $payment->id }})">
                                                <i class="fas fa-undo me-2"></i>Hoàn tiền
                                            </button>
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

            <!-- Pagination -->
            <div class="card-footer">
                {{ $payments->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Chưa có giao dịch nào</h5>
                <p class="text-muted">Hãy ghi nhận giao dịch đầu tiên</p>
                <a href="{{ route('payments.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Ghi nhận thanh toán
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Revenue Chart -->
@if($payments->count() > 0)
<div class="card mt-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="fas fa-chart-line me-2"></i>Biểu đồ doanh thu 7 ngày gần đây
        </h6>
    </div>
    <div class="card-body">
        <canvas id="revenueChart" height="100"></canvas>
    </div>
</div>
@endif

<!-- Bulk Actions Modal -->
<div class="modal fade" id="bulkActionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thao tác hàng loạt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bulkActionForm">
                    <div class="mb-3">
                        <label class="form-label">Chọn thao tác:</label>
                        <select name="bulk_action" class="form-select" required>
                            <option value="">Chọn thao tác</option>
                            <option value="confirm">Xác nhận thanh toán</option>
                            <option value="export">Xuất danh sách</option>
                            <option value="print_receipts">In phiếu thu</option>
                            <option value="change_method">Đổi phương thức</option>
                        </select>
                    </div>
                    
                    <div id="methodSelect" class="mb-3" style="display: none;">
                        <label class="form-label">Phương thức mới:</label>
                        <select name="new_method" class="form-select">
                            <option value="cash">Tiền mặt</option>
                            <option value="bank_transfer">Chuyển khoản</option>
                            <option value="card">Thẻ</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="executeBulkAction()">Thực hiện</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart.js for revenue chart
@if($payments->count() > 0 && isset($chartData))
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: {!! json_encode($chartData['labels']) !!},
        datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: {!! json_encode($chartData['data']) !!},
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('vi-VN').format(value) + ' VNĐ';
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' VNĐ';
                    }
                }
            }
        }
    }
});
@endif

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.payment-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function bulkActions() {
    const selected = document.querySelectorAll('.payment-checkbox:checked');
    if (selected.length === 0) {
        alert('Vui lòng chọn ít nhất một giao dịch!');
        return;
    }
    
    $('#bulkActionsModal').modal('show');
}

function executeBulkAction() {
    const selected = Array.from(document.querySelectorAll('.payment-checkbox:checked')).map(cb => cb.value);
    const action = $('select[name="bulk_action"]').val();
    
    if (!action) {
        alert('Vui lòng chọn thao tác!');
        return;
    }
    
    const data = {
        _token: $('meta[name="csrf-token"]').attr('content'),
        payment_ids: selected,
        action: action
    };
    
    if (action === 'change_method') {
        data.new_method = $('select[name="new_method"]').val();
    }
    
    $.post('{{ route("api.payments.bulk-action") }}', data)
        .done(function(response) {
            alert('Thao tác thành công!');
            location.reload();
        })
        .fail(function() {
            alert('Có lỗi xảy ra!');
        });
    
    $('#bulkActionsModal').modal('hide');
}

function confirmPayment(paymentId) {
    if (confirm('Xác nhận giao dịch này đã hoàn thành?')) {
        const apiUrl = '/api/payments/' + paymentId + '/confirm';
        $.post(apiUrl, {
            _token: $('meta[name="csrf-token"]').attr('content')
        }).done(function() {
            alert('Xác nhận thành công!');
            location.reload();
        }).fail(function() {
            alert('Có lỗi xảy ra!');
        });
    }
}

function refundPayment(paymentId) {
    const reason = prompt('Lý do hoàn tiền:');
    if (reason) {
        const apiUrl = '/api/payments/' + paymentId + '/refund';
        $.post(apiUrl, {
            _token: $('meta[name="csrf-token"]').attr('content'),
            reason: reason
        }).done(function() {
            alert('Hoàn tiền thành công!');
            location.reload();
        }).fail(function() {
            alert('Có lỗi xảy ra!');
        });
    }
}

function exportPayments() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '{{ route("payments.index") }}?' + params.toString();
}

function printReceipts() {
    const selected = Array.from(document.querySelectorAll('.payment-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        alert('Vui lòng chọn giao dịch để in!');
        return;
    }
    
    const url = '{{ route("payments.bulk-receipt") }}?ids=' + selected.join(',');
    window.open(url, '_blank');
}

// Auto-submit form when filters change
$('select[name="method"], select[name="status"], input[name="date_from"], input[name="date_to"]').change(function() {
    $(this).closest('form').submit();
});

// Show/hide bulk action fields
$('select[name="bulk_action"]').change(function() {
    const action = $(this).val();
    $('#methodSelect').hide();
    
    if (action === 'change_method') {
        $('#methodSelect').show();
    }
});
</script>
@endsection 
 