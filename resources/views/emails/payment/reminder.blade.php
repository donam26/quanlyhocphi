<x-mail::message>
# Thông báo nhắc nhở thanh toán học phí

Xin chào **{{ $payment->enrollment->student->full_name }}**,

Hệ thống ghi nhận bạn có một khoản thanh toán học phí chưa hoàn tất cho khóa học **{{ $payment->enrollment->courseClass->course->name }}**.

**Chi tiết thanh toán:**
- **Số tiền:** {{ number_format($payment->amount, 0, ',', '.') }} VND
- **Mã thanh toán:** {{ config('sepay.pattern') . $payment->id }}
- **Hạn thanh toán:** (Bạn có thể thêm thông tin hạn nếu có)

Để hoàn tất thanh toán, vui lòng nhấn vào nút bên dưới để tới trang thanh toán an toàn của chúng tôi.

<x-mail::button :url="$paymentUrl">
Thanh toán ngay
</x-mail::button>

Nếu bạn đã thanh toán, vui lòng bỏ qua email này. Nếu có bất kỳ thắc mắc nào, xin đừng ngần ngại liên hệ với chúng tôi.

Trân trọng,<br>
{{ config('app.name') }}
</x-mail::message>
