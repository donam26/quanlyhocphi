<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đã thanh toán đủ học phí</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .completed-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .completed-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .completed-body {
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
    </style>
</head>
<body>
    <div class="completed-container">
        <div class="completed-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Đã thanh toán đủ học phí!</h1>
            <p class="mb-0">Học viên đã hoàn thành thanh toán</p>
        </div>

        <div class="completed-body">
            <div class="info-card">
                <h5><i class="fas fa-user"></i> Thông tin học viên</h5>
                <p><strong>Họ tên:</strong> {{ $enrollment->student->full_name }}</p>
                <p><strong>Số điện thoại:</strong> {{ $enrollment->student->phone }}</p>
                <p class="mb-0"><strong>Email:</strong> {{ $enrollment->student->email }}</p>
            </div>

            <div class="info-card">
                <h5><i class="fas fa-book"></i> Thông tin khóa học</h5>
                <p><strong>Tên khóa học:</strong> {{ $enrollment->courseItem->name }}</p>
                <p><strong>Ngày ghi danh:</strong> {{ $enrollment->enrollment_date->format('d/m/Y') }}</p>
                <p><strong>Tổng học phí:</strong> {{ number_format($enrollment->final_fee, 0, ',', '.') }} VND</p>
                <p class="mb-0"><strong>Đã thanh toán:</strong> {{ number_format($enrollment->getTotalPaidAmount(), 0, ',', '.') }} VND</p>
            </div>

            <div class="alert alert-success">
                <i class="fas fa-graduation-cap"></i>
                <strong>Chúc mừng!</strong> Bạn đã hoàn thành thanh toán học phí. Chúc bạn học tập tốt!
            </div>

            <div class="text-center">
                <a href="mailto:support@example.com" class="btn btn-outline-primary">
                    <i class="fas fa-envelope"></i> Liên hệ hỗ trợ
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
