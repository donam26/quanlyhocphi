<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán thành công</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .success-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
            max-width: 500px;
        }
    </style>
</head>
<body>
    <div class="success-card">
        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
        <h3 class="mt-3 mb-3 text-success">{{ $message }}</h3>
        
        @if(isset($enrollment))
        <div class="mt-4 p-3 bg-light rounded text-start">
            <h6><i class="fas fa-info-circle text-primary me-2"></i>Thông tin khóa học</h6>
            <p class="mb-1"><strong>Khóa học:</strong> {{ $enrollment->courseItem->name }}</p>
            <p class="mb-1"><strong>Học viên:</strong> {{ $enrollment->student->full_name }}</p>
            <p class="mb-0"><strong>Trạng thái:</strong> <span class="text-success">Đã thanh toán đủ</span></p>
        </div>
        @endif
    </div>
</body>
</html>
