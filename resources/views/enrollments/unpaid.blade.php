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
                                        
                                        {{-- Xem chi tiết --}}
                                        <a href="{{ route('enrollments.show', $enrollment) }}" class="btn btn-info btn-sm mt-1" title="Xem chi tiết">
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
        var enrollmentIds = [];
        
        // Phân loại các checkbox theo data-type
        $('.check-item:checked').each(function() {
            var id = $(this).val();
            var type = $(this).data('type');
            
            if (type === 'payment') {
                paymentIds.push(id);
            } else if (type === 'enrollment') {
                enrollmentIds.push(id);
            }
        });

        if (paymentIds.length === 0 && enrollmentIds.length === 0) {
            alert('Vui lòng chọn ít nhất một học viên để gửi nhắc nhở');
            return;
        }
        
        // Tạo form động để gửi
        var dynamicForm = $('<form>', {
            'method': 'post',
            'action': paymentIds.length > 0 && enrollmentIds.length === 0 
                ? '{{ route("payments.send-reminder") }}'
                : enrollmentIds.length > 0 && paymentIds.length === 0
                ? '{{ route("payments.send-direct-reminder") }}'
                : '{{ route("payments.send-combined-reminder") }}'
        });
        
        // Thêm CSRF token
        dynamicForm.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
        }));
        
        // Thêm các payment_ids nếu có
        if (paymentIds.length > 0) {
            paymentIds.forEach(function(id) {
                dynamicForm.append($('<input>', {
                    'type': 'hidden',
                    'name': 'payment_ids[]',
                    'value': id
                }));
            });
        }
        
        // Thêm các enrollment_ids nếu có
        if (enrollmentIds.length > 0) {
            enrollmentIds.forEach(function(id) {
                dynamicForm.append($('<input>', {
                    'type': 'hidden',
                    'name': 'enrollment_ids[]',
                    'value': id
                }));
            });
        }
        
        // Cần xử lý case hỗn hợp (có cả payment và enrollment)
        var totalCount = paymentIds.length + enrollmentIds.length;
        
        if(confirm('Bạn có chắc muốn gửi nhắc nhở cho ' + totalCount + ' học viên đã chọn?')) {
            // Thêm form vào body và submit
            $('body').append(dynamicForm);
            dynamicForm.submit();
        }
    });
});
</script>
@endpush 