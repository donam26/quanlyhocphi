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
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Cấu hình Select2 cho ô tìm kiếm thanh toán với AJAX
    $('#payment_search').select2({
        theme: 'bootstrap-5',
        placeholder: 'Tìm theo tên khóa học...',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '{{ route("api.search.autocomplete") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    type: 'course' // Tìm kiếm khóa học
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
 