<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán học phí - {{ $enrollment->courseItem->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .payment-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .payment-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .payment-body {
            padding: 30px;
        }
        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        .amount-display {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
        .amount-display .amount {
            font-size: 2rem;
            font-weight: bold;
        }
        .qr-section {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }
        .qr-section img {
            max-width: 250px;
            border: 3px solid #007bff;
            border-radius: 10px;
        }
        .bank-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .btn-payment {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            color: white;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn-payment:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        .status-checking {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .spinner-border {
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h1><i class="fas fa-graduation-cap"></i> Thanh toán học phí</h1>
            <p class="mb-0">Trung tâm Đào tạo</p>
        </div>

        <div class="payment-body">
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
                <p class="mb-0"><strong>Tổng học phí:</strong> {{ number_format($enrollment->final_fee, 0, ',', '.') }} VND</p>
            </div>

            <div class="amount-display">
                <div>Số tiền cần thanh toán</div>
                <div class="amount">{{ number_format($remaining, 0, ',', '.') }} VND</div>
            </div>

            <div class="text-center">
                <button class="btn btn-payment btn-lg" onclick="initiatePayment()">
                    <i class="fas fa-qrcode"></i> Tạo mã QR thanh toán
                </button>
            </div>

            <div id="qr-section" class="qr-section" style="display: none;">
                <h5><i class="fas fa-mobile-alt"></i> Quét mã QR để thanh toán</h5>
                <div id="qr-code"></div>
                <div id="bank-info" class="bank-info"></div>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Lưu ý:</strong> Vui lòng chuyển khoản đúng số tiền và ghi đúng nội dung để hệ thống tự động xác nhận thanh toán.
                </div>
            </div>

            <div id="status-checking" class="status-checking">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Đang kiểm tra trạng thái thanh toán...</p>
                <p class="text-muted">Vui lòng không đóng trang này</p>
            </div>

            <div id="payment-success" class="alert alert-success" style="display: none;">
                <i class="fas fa-check-circle"></i>
                <strong>Thanh toán thành công!</strong> Cảm ơn bạn đã thanh toán học phí.
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let paymentId = null;
        let statusCheckInterval = null;

        function initiatePayment() {
            const amount = {{ $remaining }};
            const enrollmentId = {{ $enrollment->id }};

            fetch('/payment/initiate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    enrollment_id: enrollmentId,
                    amount: amount
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    paymentId = data.data.payment_id;
                    displayQRCode(data.data.qr_data);
                    startStatusChecking();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi tạo mã QR');
            });
        }

        function displayQRCode(qrData) {
            document.getElementById('qr-section').style.display = 'block';
            
            document.getElementById('qr-code').innerHTML = `
                <img src="${qrData.qr_image}" alt="QR Code thanh toán" class="img-fluid">
            `;

            document.getElementById('bank-info').innerHTML = `
                <h6><i class="fas fa-university"></i> Thông tin chuyển khoản</h6>
                <p><strong>Ngân hàng:</strong> ${qrData.bank_name}</p>
                <p><strong>Số tài khoản:</strong> ${qrData.bank_account}</p>
                <p><strong>Chủ tài khoản:</strong> ${qrData.account_owner}</p>
                <p><strong>Số tiền:</strong> ${new Intl.NumberFormat('vi-VN').format(qrData.amount)} VND</p>
                <p><strong>Nội dung:</strong> <code>${qrData.content}</code></p>
            `;
        }

        function startStatusChecking() {
            document.getElementById('status-checking').style.display = 'block';
            
            statusCheckInterval = setInterval(() => {
                checkPaymentStatus();
            }, 5000); // Kiểm tra mỗi 5 giây
        }

        function checkPaymentStatus() {
            if (!paymentId) return;

            fetch(`/payment/status/${paymentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.status === 'confirmed') {
                    clearInterval(statusCheckInterval);
                    document.getElementById('status-checking').style.display = 'none';
                    document.getElementById('payment-success').style.display = 'block';
                    
                    // Redirect sau 3 giây
                    setTimeout(() => {
                        window.location.href = '/payment/result?payment_id=' + paymentId + '&status=success';
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error checking status:', error);
            });
        }

        // Cleanup khi rời khỏi trang
        window.addEventListener('beforeunload', function() {
            if (statusCheckInterval) {
                clearInterval(statusCheckInterval);
            }
        });
    </script>
</body>
</html>
