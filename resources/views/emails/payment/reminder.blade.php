<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhắc nhở thanh toán học phí</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .amount-highlight {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
        }
        .amount-highlight .amount {
            font-size: 24px;
            font-weight: bold;
            color: #e17055;
        }
        .qr-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        .qr-section img {
            max-width: 200px;
            height: auto;
            border: 2px solid #ddd;
            border-radius: 10px;
        }
        .bank-info {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .bank-info h4 {
            margin: 0 0 10px 0;
            color: #1976d2;
        }
        .bank-info p {
            margin: 5px 0;
            font-family: monospace;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 10px 5px;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .footer {
            border-top: 1px solid #ddd;
            padding-top: 20px;
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 NHẮC NHỞ THANH TOÁN HỌC PHÍ</h1>
            <p>Trung tâm Đào tạo</p>
        </div>

        <div class="content">
            <p>Xin chào <strong>{{ $student->full_name }}</strong>,</p>
            
            <p>Chúng tôi xin nhắc nhở bạn về việc thanh toán học phí cho khóa học:</p>
            
            <div class="info-box">
                <h3>📚 Thông tin khóa học</h3>
                <p><strong>Tên khóa học:</strong> {{ $course->name }}</p>
                <p><strong>Ngày ghi danh:</strong> {{ $enrollment->enrollment_date->format('d/m/Y') }}</p>
                <p><strong>Tổng học phí:</strong> {{ number_format($enrollment->final_fee, 0, ',', '.') }} VND</p>
            </div>

            <div class="amount-highlight">
                <p><strong>💰 Số tiền cần thanh toán:</strong></p>
                <div class="amount">{{ $formattedAmount }}</div>
                <p><small>Hạn thanh toán: {{ $dueDate }}</small></p>
            </div>

            @if($qrData)
            <div class="qr-section">
                <h3>📱 Thanh toán nhanh bằng QR Code</h3>
                <p>Quét mã QR bên dưới để thanh toán ngay:</p>
                <img src="{{ $qrData['qr_image'] }}" alt="QR Code thanh toán">
                
                <div class="bank-info">
                    <h4>🏦 Thông tin chuyển khoản</h4>
                    <p><strong>Ngân hàng:</strong> {{ $qrData['bank_name'] }}</p>
                    <p><strong>Số tài khoản:</strong> {{ $qrData['bank_account'] }}</p>
                    <p><strong>Chủ tài khoản:</strong> {{ $qrData['account_owner'] }}</p>
                    <p><strong>Số tiền:</strong> {{ number_format($qrData['amount'], 0, ',', '.') }} VND</p>
                    <p><strong>Nội dung:</strong> {{ $qrData['content'] }}</p>
                </div>
                
                <div class="warning">
                    ⚠️ <strong>Lưu ý:</strong> Vui lòng chuyển khoản đúng số tiền và ghi đúng nội dung để hệ thống tự động xác nhận thanh toán.
                </div>
            </div>
            @endif

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $paymentLink }}" class="button">💳 Thanh toán trực tuyến</a>
                <a href="tel:0123456789" class="button" style="background-color: #28a745;">📞 Liên hệ hỗ trợ</a>
            </div>

            <div class="info-box">
                <h4>📋 Hướng dẫn thanh toán</h4>
                <ol>
                    <li>Quét mã QR bằng ứng dụng ngân hàng của bạn</li>
                    <li>Kiểm tra thông tin chuyển khoản</li>
                    <li>Xác nhận thanh toán</li>
                    <li>Hệ thống sẽ tự động cập nhật sau khi nhận được tiền</li>
                </ol>
            </div>

            <p>Nếu bạn đã thanh toán, vui lòng bỏ qua email này. Nếu có bất kỳ thắc mắc nào, xin vui lòng liên hệ với chúng tôi.</p>
        </div>

        <div class="footer">
            <p><strong>Trung tâm Đào tạo</strong></p>
            <p>📧 Email: support@example.com | 📞 Hotline: 0123 456 789</p>
            <p>🌐 Website: {{ config('app.url') }}</p>
            <hr style="margin: 15px 0;">
            <p><small>Email này được gửi tự động. Vui lòng không trả lời email này.</small></p>
        </div>
    </div>
</body>
</html>
