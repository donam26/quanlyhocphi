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
                                <h2 class="mt-2 mb-0">{{ count($studentEnrollments) }}</h2>
                            </div>
                            <i class="fas fa-users fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            @php
                $totalRemaining = 0;
                $totalFee = 0;
                $totalPaid = 0;
                
                foreach ($studentEnrollments as $student) {
                    $totalRemaining += $student['total_remaining'];
                    $totalFee += $student['total_fee'];
                    $totalPaid += $student['total_paid'];
                }
                
                $averageRemaining = count($studentEnrollments) > 0 ? $totalRemaining / count($studentEnrollments) : 0;
            @endphp
            
            <div class="col-md-3 mb-3">
                <div class="card bg-danger text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Tổng số tiền chưa thu</h6>
                                <h2 class="mt-2 mb-0">{{ number_format($totalRemaining) }}đ</h2>
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
                                <h2 class="mt-2 mb-0">{{ number_format($totalPaid) }}đ</h2>
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
                                <h2 class="mt-2 mb-0">{{ number_format($averageRemaining) }}đ</h2>
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
                                            <input type="checkbox" class="check-item" value="{{ $existingPayment->id }}">
                                        @else
                                            <i class="fas fa-exclamation-circle text-warning" title="Chưa có thanh toán"></i>
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
                                        @if($existingPayment)
                                            <a href="{{ route('payment.gateway.show', $existingPayment) }}" class="btn btn-success btn-sm" title="Trang thanh toán">
                                            <i class="fas fa-dollar-sign"></i>
                                        </a>
                                        <form action="{{ route('payments.send-reminder') }}" method="POST" class="d-inline" onsubmit="return confirm('Gửi email nhắc nhở cho học viên này?')">
                                            @csrf
                                                <input type="hidden" name="payment_ids[]" value="{{ $existingPayment->id }}">
                                            <button type="submit" class="btn btn-warning btn-sm" title="Gửi nhắc nhở">
                                                <i class="fas fa-bell"></i>
                                            </button>
                                        </form>
                                        @else
                                            <a href="{{ route('payments.create', ['enrollment_id' => $enrollment->id]) }}" class="btn btn-primary btn-sm" title="Tạo thanh toán mới">
                                                <i class="fas fa-plus"></i> Tạo thanh toán
                                            </a>
                                        @endif
                                        <a href="{{ route('enrollments.show', $enrollment) }}" class="btn btn-info btn-sm" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
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

        if (paymentIds.length === 0) {
            alert('Vui lòng chọn ít nhất một thanh toán để gửi nhắc nhở');
            return;
        }

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