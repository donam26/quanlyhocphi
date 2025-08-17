<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả thanh toán</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .result-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .result-header {
            padding: 30px;
            text-align: center;
        }
        .result-header.success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .result-header.error {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        .result-body {
            padding: 30px;
        }
        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="result-container">
        @if(isset($payment) && $payment->status === 'confirmed')
            <div class="result-header success">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>Thanh toán thành công!</h1>
                <p class="mb-0">Cảm ơn bạn đã thanh toán học phí</p>
            </div>

            <div class="result-body">
                <div class="info-card">
                    <h5><i class="fas fa-receipt"></i> Thông tin thanh toán</h5>
                    <p><strong>Mã thanh toán:</strong> {{ $payment->receipt_number }}</p>
                    <p><strong>Số tiền:</strong> {{ number_format($payment->amount, 0, ',', '.') }} VND</p>
                    <p><strong>Ngày thanh toán:</strong> {{ $payment->payment_date->format('d/m/Y H:i:s') }}</p>
                    <p class="mb-0"><strong>Phương thức:</strong> Chuyển khoản ngân hàng</p>
                </div>

                <div class="info-card">
                    <h5><i class="fas fa-user"></i> Thông tin học viên</h5>
                    <p><strong>Họ tên:</strong> {{ $payment->enrollment->student->full_name }}</p>
                    <p class="mb-0"><strong>Khóa học:</strong> {{ $payment->enrollment->courseItem->name }}</p>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Thông báo:</strong> Thanh toán của bạn đã được xác nhận. Bạn sẽ nhận được email xác nhận trong vài phút tới.
                </div>

                <div class="text-center">
                    <a href="mailto:support@example.com" class="btn btn-outline-primary">
                        <i class="fas fa-envelope"></i> Liên hệ hỗ trợ
                    </a>
                </div>
            </div>
        @else
            <div class="result-header error">
                <div class="error-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h1>Thanh toán chưa thành công</h1>
                <p class="mb-0">Vui lòng thử lại hoặc liên hệ hỗ trợ</p>
            </div>

            <div class="result-body">
                @if(isset($payment))
                    <div class="info-card">
                        <h5><i class="fas fa-clock"></i> Trạng thái thanh toán</h5>
                        <p><strong>Mã thanh toán:</strong> {{ $payment->receipt_number }}</p>
                        <p><strong>Trạng thái:</strong> 
                            @if($payment->status === 'pending')
                                <span class="badge bg-warning">Đang chờ xác nhận</span>
                            @elseif($payment->status === 'cancelled')
                                <span class="badge bg-danger">Đã hủy</span>
                            @else
                                <span class="badge bg-secondary">{{ $payment->status }}</span>
                            @endif
                        </p>
                        <p class="mb-0"><strong>Số tiền:</strong> {{ number_format($payment->amount, 0, ',', '.') }} VND</p>
                    </div>

                    @if($payment->status === 'pending')
                        <div class="alert alert-warning">
                            <i class="fas fa-hourglass-half"></i>
                            <strong>Đang xử lý:</strong> Thanh toán của bạn đang được xử lý. Vui lòng chờ trong vài phút.
                        </div>
                    @endif
                @endif

                <div class="text-center">
                    <a href="/payment/{{ $payment->enrollment_id ?? '' }}" class="btn btn-primary">
                        <i class="fas fa-redo"></i> Thử lại
                    </a>
                    <a href="mailto:support@example.com" class="btn btn-outline-secondary">
                        <i class="fas fa-envelope"></i> Liên hệ hỗ trợ
                    </a>
                </div>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
