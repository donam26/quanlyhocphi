@component('mail::message')
# Nhắc nhở thanh toán học phí

**Kính gửi: {{ $student->full_name }}**

Chúng tôi gửi email này để nhắc nhở bạn về khoản học phí còn thiếu cho khóa học **{{ $courseItem->name }}**.

Chi tiết:
- Học viên: {{ $student->full_name }}
- Khóa học: {{ $courseItem->name }}
- Số tiền còn phải thanh toán: {{ number_format($amount) }} VNĐ

Bạn có thể thanh toán bằng cách quét mã QR hoặc chuyển khoản theo thông tin bên dưới.

@component('mail::button', ['url' => $payment_url])
Quét mã QR để thanh toán ngay
@endcomponent

Nếu bạn đã thanh toán, vui lòng bỏ qua email này.

Cảm ơn bạn!

Trân trọng,<br>
{{ config('app.name') }}
@endcomponent
