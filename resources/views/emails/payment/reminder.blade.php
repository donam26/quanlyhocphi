<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nhắc nhở thanh toán học phí</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
        }
        h1 {
            color: #3490dc;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .content {
            padding: 15px 0;
        }
        .button {
            display: inline-block;
            background-color: #3490dc;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #777;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Nhắc nhở thanh toán học phí</h1>
        </div>
        
        <div class="content">
            <p><strong>Kính gửi: {{ $student->full_name }}</strong></p>
            
            <p>Chúng tôi gửi email này để nhắc nhở bạn về khoản học phí còn thiếu cho khóa học <strong>{{ $course->name }}</strong>.</p>
            
            <p>Chi tiết:</p>
            <ul>
                <li>Học viên: {{ $student->full_name }}</li>
                <li>Khóa học: {{ $course->name }}</li>
                <li>Số tiền còn phải thanh toán: {{ number_format($remaining) }} VNĐ</li>
            </ul>
            
            <p>Bạn có thể thanh toán bằng cách quét mã QR hoặc chuyển khoản theo thông tin bên dưới.</p>
            
            <div style="text-align: center;">
                <a href="{{ $paymentUrl }}" class="button">Quét mã QR để thanh toán ngay</a>
            </div>
            
            <p>Nếu bạn đã thanh toán, vui lòng bỏ qua email này.</p>
            
            <p>Cảm ơn bạn!</p>
            
            <p>Trân trọng,<br>
            {{ config('app.name') }}</p>
        </div>
        
        <div class="footer">
            <p>Email này được gửi tự động. Vui lòng không phản hồi lại email này.</p>
        </div>
    </div>
</body>
</html>
