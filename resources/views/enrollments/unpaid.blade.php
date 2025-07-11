@extends('layouts.app')

@section('page-title', 'Học viên chưa đóng đủ học phí')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Thanh toán</a></li>
    <li class="breadcrumb-item active">Chưa đóng đủ học phí</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Thống kê công nợ</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Tổng học viên nợ học phí</h6>
                                <h2 class="mt-2 mb-0">{{ number_format($stats['total_unpaid_count']) }}</h2>
                            </div>
                            <i class="fas fa-users fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-danger text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Tổng số tiền chưa thu</h6>
                                <h2 class="mt-2 mb-0">{{ number_format($stats['total_unpaid_amount']) }}đ</h2>
                            </div>
                            <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Đã thu được</h6>
                                <h2 class="mt-2 mb-0">{{ number_format($stats['total_paid_amount']) }}đ</h2>
                            </div>
                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Công nợ trung bình</h6>
                                <h2 class="mt-2 mb-0">{{ number_format($stats['average_remaining']) }}đ</h2>
                            </div>
                            <i class="fas fa-calculator fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
        <form id="main-form" action="" method="POST">
             @csrf
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="check-all"></th>
                            <th>Học viên</th>
                            <th>Khóa học / Lớp</th>
                            <th class="text-end">Học phí</th>
                            <th class="text-end">Đã đóng</th>
                            <th class="text-end">Còn lại</th>
                            <th>Ngày ĐK</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($unpaidEnrollments as $enrollment)
                            @php
                                $payment = $enrollment->payments->first() ?? new \App\Models\Payment([
                                    'amount' => 0, 
                                    'enrollment_id' => $enrollment->id,
                                    'payment_date' => now() // Thêm trường payment_date bắt buộc
                                ]);
                                if(!$payment->id) { // Tạo một payment ảo nếu chưa có để lấy link
                                    $payment->amount = $enrollment->getRemainingAmount();
                                    $payment->status = 'pending';
                                    $payment->payment_method = 'bank_transfer'; // Thêm payment_method mặc định
                                    $payment->save();
                                }
                            @endphp
                            <tr>
                                <td><input type="checkbox" class="check-item" value="{{ $payment->id }}"></td>
                                <td>
                                    <strong>{{ $enrollment->student->full_name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $enrollment->student->phone }}</small>
                                </td>
                                <td>
                                    {{ $enrollment->courseClass->course->name }}
                                    <br>
                                    <small class="text-muted">{{ $enrollment->courseClass->name }}</small>
                                </td>
                                <td class="text-end">{{ number_format($enrollment->final_fee) }}đ</td>
                                <td class="text-end">{{ number_format($enrollment->getTotalPaidAmount()) }}đ</td>
                                <td class="text-end text-danger fw-bold">{{ number_format($enrollment->getRemainingAmount()) }}đ</td>
                                <td>{{ $enrollment->enrollment_date->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('payment.gateway.show', $payment) }}" class="btn btn-success btn-sm" title="Trang thanh toán">
                                        <i class="fas fa-dollar-sign"></i>
                                    </a>
                                    <a href="{{ route('enrollments.show', $enrollment) }}" class="btn btn-primary btn-sm" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form action="{{ route('payments.send-reminder') }}" method="POST" class="d-inline" onsubmit="return confirm('Gửi email nhắc nhở cho học viên này?')">
                                        @csrf
                                        <input type="hidden" name="payment_ids[]" value="{{ $payment->id }}">
                                        <button type="submit" class="btn btn-warning btn-sm" title="Gửi nhắc nhở">
                                            <i class="fas fa-bell"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Không có công nợ nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#check-all').on('click', function() {
        $('.check-item').prop('checked', $(this).prop('checked'));
        toggleBulkActions();
    });

    $('.check-item').on('click', function() {
        if ($('.check-item:checked').length === $('.check-item').length) {
            $('#check-all').prop('checked', true);
        } else {
            $('#check-all').prop('checked', false);
        }
        toggleBulkActions();
    });

    function toggleBulkActions() {
        if ($('.check-item:checked').length > 0) {
            $('#bulk-remind-btn').prop('disabled', false);
        } else {
            $('#bulk-remind-btn').prop('disabled', true);
        }
    }

    $('#bulk-reminder-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var paymentIds = [];
        $('.check-item:checked').each(function() {
            paymentIds.push($(this).val());
        });

        // Xóa các input cũ và thêm input mới
        form.find('input[name="payment_ids[]"]').remove();
        paymentIds.forEach(function(id) {
            form.append('<input type="hidden" name="payment_ids[]" value="' + id + '">');
        });
        
        if(confirm('Bạn có chắc muốn gửi nhắc nhở cho ' + paymentIds.length + ' mục đã chọn?')) {
            form.get(0).submit();
        }
    });
});
</script>
@endpush 