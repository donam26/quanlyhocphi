@component('mail::message')
# Xác nhận thanh toán học phí

**Kính gửi: {{ $student->full_name }}**

Chúng tôi xin thông báo rằng khoản thanh toán của bạn đã được xác nhận.

Chi tiết thanh toán:
- Học viên: {{ $student->full_name }}
- Khóa học: {{ $courseItem->name }}
- Số tiền đã thanh toán: {{ number_format($amount) }} VNĐ
- Ngày thanh toán: {{ $payment->payment_date->format('d/m/Y') }}
- Phương thức: {{ $payment->payment_method == 'cash' ? 'Tiền mặt' : ($payment->payment_method == 'bank_transfer' ? 'Chuyển khoản' : 'Khác') }}

@if($payment->transaction_reference)
- Mã giao dịch: {{ $payment->transaction_reference }}
@endif

Cảm ơn bạn đã thanh toán!

@component('mail::button', ['url' => route('enrollments.show', $payment->enrollment_id)])
Xem chi tiết ghi danh
@endcomponent

Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi.

Trân trọng,<br>
{{ config('app.name') }}
@endcomponent 