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
                            <th width="20%">Khóa học</th>
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
                                    <div class="fw-medium">{{ $payment->enrollment->courseItem->name ?? 'N/A' }}</div>
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
                                @if($payment->payment_method == 'cash')
                                    <span class="badge bg-success">
                                        <i class="fas fa-money-bill me-1"></i>Tiền mặt
                                    </span>
                                @elseif($payment->payment_method == 'bank_transfer')
                                    <span class="badge bg-primary">
                                        <i class="fas fa-university me-1"></i>Chuyển khoản
                                    </span>
                                @elseif($payment->payment_method == 'card')
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
                                @if($payment->status == 'confirmed')
                                    <span class="badge bg-success">Đã xác nhận</span>
                                @elseif($payment->status == 'pending')
                                    <span class="badge bg-warning">Chờ xác nhận</span>
                                @elseif($payment->status == 'cancelled')
                                    <span class="badge bg-danger">Đã hủy</span>
                                @else
                                    <span class="badge bg-info">Đã hoàn</span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
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

@push('scripts')
<script>
$(document).ready(function() {
    // Cấu hình Select2 cho ô tìm kiếm thanh toán với AJAX
    $('#payment_search').select2({
        theme: 'bootstrap-5',
        placeholder: 'Tìm theo tên, SĐT, mã GD...',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '{{ route("api.search.autocomplete") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term
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
        var studentId = e.params.data.id;
        // Thay đổi URL để truy vấn theo ID học viên
        window.location.href = '{{ route("payments.index") }}?student_id=' + studentId;
    });

    // Xóa tìm kiếm khi clear
    $('#payment_search').on('select2:clear', function(e) {
        window.location.href = '{{ route("payments.index") }}';
    });

    // Auto-submit khi các select khác thay đổi
    $('select[name="payment_method"], select[name="status"]').change(function() {
        $(this).closest('form').submit();
    });

    // Xử lý chọn tất cả
    $('#selectAll').click(function() {
        $('.payment-checkbox').prop('checked', this.checked);
        updateBulkActions();
    });

    // Xử lý chọn từng khoản thanh toán
    $('.payment-checkbox').click(function() {
        updateBulkActions();
        
        // Nếu bỏ chọn một item, bỏ chọn cả "Chọn tất cả"
        if (!this.checked) {
            $('#selectAll').prop('checked', false);
        }
        
        // Nếu đã chọn tất cả item, chọn cả "Chọn tất cả"
        else if ($('.payment-checkbox:checked').length == $('.payment-checkbox').length) {
            $('#selectAll').prop('checked', true);
        }
    });
    
    // Cập nhật nút thao tác hàng loạt
    function updateBulkActions() {
        var count = $('.payment-checkbox:checked').length;
        if (count > 0) {
            $('#bulk-action-btn').text('Thao tác (' + count + ')').removeAttr('disabled');
        } else {
            $('#bulk-action-btn').text('Thao tác').attr('disabled', 'disabled');
        }
    }
});

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.payment-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    updateBulkActions();
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

// Show/hide bulk action fields
$('select[name="bulk_action"]').change(function() {
    const action = $(this).val();
    $('#methodSelect').hide();
    
    if (action === 'change_method') {
        $('#methodSelect').show();
    }
});

function updateBulkActions() {
    var count = $('.payment-checkbox:checked').length;
    if (count > 0) {
        $('#bulk-action-btn').text('Thao tác (' + count + ')').removeAttr('disabled');
    } else {
        $('#bulk-action-btn').text('Thao tác').attr('disabled', 'disabled');
    }
}
</script>
@endpush 
 