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
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .payment-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .payment-header {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .qr-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin: 1rem 0;
        }
        .qr-code {
            max-width: 300px;
            width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .bank-info {
            background: #e3f2fd;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        .amount-input {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 1rem;
            font-size: 1.2rem;
            text-align: center;
        }
        .btn-payment {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            border: none;
            border-radius: 10px;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-payment:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        .loading {
            display: none;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <!-- Header -->
            <div class="payment-header">
                <h2><i class="fas fa-graduation-cap me-2"></i>Thanh2 toán học phí</h2>
                <p class="mb-0">{{ $enrollment->courseItem->name }}</p>
            </div>

            <div class="p-4">
                <!-- Thông tin học viên -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5><i class="fas fa-user text-primary me-2"></i>Thông tin học viên</h5>
                        <p class="mb-1"><strong>Họ tên:</strong> {{ $enrollment->student->full_name }}</p>
                        <p class="mb-1"><strong>Điện thoại:</strong> {{ $enrollment->student->phone }}</p>
                        @if($enrollment->student->email)
                        <p class="mb-1"><strong>Email:</strong> {{ $enrollment->student->email }}</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-money-bill-wave text-success me-2"></i>Thông tin học phí</h5>
                        <p class="mb-1"><strong>Tổng học phí:</strong> {{ number_format($enrollment->final_fee) }} VND</p>
                        <p class="mb-1"><strong>Đã thanh toán:</strong> {{ number_format($totalPaid) }} VND</p>
                        <p class="mb-1"><strong>Còn thiếu:</strong> <span class="text-danger fw-bold">{{ number_format($remaining) }} VND</span></p>
                    </div>
                </div>

                @if($qrData)
                <!-- QR Code hiện tại -->
                <div class="qr-section">
                    <h5><i class="fas fa-qrcode text-primary me-2"></i>Quét mã để thanh toán</h5>
                    <img src="{{ $qrData['qr_image'] }}" alt="QR Code" class="qr-code mb-3">
                    
                    <div class="bank-info">
                        <div class="row text-start">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Ngân hàng:</strong> {{ $qrData['bank_name'] }}</p>
                                <p class="mb-1"><strong>Số tài khoản:</strong> {{ $qrData['bank_account'] }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Chủ tài khoản:</strong> {{ $qrData['account_owner'] }}</p>
                                <p class="mb-1"><strong>Nội dung:</strong> {{ $qrData['content'] }}</p>
                            </div>
                        </div>
                        <div class="text-center mt-2">
                            <p class="mb-0"><strong>Số tiền:</strong> <span class="text-success fs-4">{{ number_format($qrData['amount']) }} VND</span></p>
                        </div>
                    </div>

                    @if($pendingPayment)
                    <div class="mt-3">
                        <span class="status-badge status-pending">
                            <i class="fas fa-clock me-1"></i>Đang chờ thanh toán
                        </span>
                        <p class="mt-2 text-muted">Vui lòng quét mã QR bằng app ngân hàng để thanh toán</p>
                    </div>
                    @endif
                </div>
                @endif

                <!-- Form tạo thanh toán mới -->
                <div class="mt-4">
                    <h5><i class="fas fa-plus-circle text-primary me-2"></i>Tạo thanh toán mới</h5>
                    <form id="paymentForm">
                        <div class="row">
                            <div class="col-md-8">
                                <label class="form-label">Số tiền thanh toán (VND)</label>
                                <input type="number" 
                                       class="form-control amount-input" 
                                       id="amount" 
                                       name="amount" 
                                       min="1000" 
                                       max="{{ $remaining }}" 
                                       value="{{ $remaining }}"
                                       placeholder="Nhập số tiền">
                                <small class="text-muted">Tối thiểu 1,000 VND - Tối đa {{ number_format($remaining) }} VND</small>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-payment w-100">
                                    <span class="btn-text">
                                        <i class="fas fa-qrcode me-2"></i>Tạo mã QR
                                    </span>
                                    <span class="loading">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Đang tạo...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Hướng dẫn -->
                <div class="mt-4 p-3 bg-light rounded">
                    <h6><i class="fas fa-info-circle text-info me-2"></i>Hướng dẫn thanh toán</h6>
                    <ol class="mb-0">
                        <li>Nhập số tiền cần thanh toán và nhấn "Tạo mã QR"</li>
                        <li>Mở app ngân hàng trên điện thoại</li>
                        <li>Chọn chức năng "Quét QR" hoặc "Chuyển khoản QR"</li>
                        <li>Quét mã QR hiển thị trên màn hình</li>
                        <li>Kiểm tra thông tin và xác nhận thanh toán</li>
                        <li>Trang web sẽ tự động cập nhật khi thanh toán thành công</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentPaymentId = {{ $pendingPayment ? $pendingPayment->id : 'null' }};
        let checkInterval;

        // Form submit
        document.getElementById('paymentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const amount = document.getElementById('amount').value;
            const btn = this.querySelector('button[type="submit"]');
            const btnText = btn.querySelector('.btn-text');
            const loading = btn.querySelector('.loading');
            
            if (!amount || amount < 1000) {
                alert('Vui lòng nhập số tiền hợp lệ (tối thiểu 1,000 VND)');
                return;
            }

            // Show loading
            btnText.style.display = 'none';
            loading.style.display = 'inline';
            btn.disabled = true;

            try {
                const response = await fetch(`/payment/{{ $token }}/create`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ amount: parseInt(amount) })
                });

                const result = await response.json();

                if (result.success) {
                    currentPaymentId = result.data.payment_id;
                    updateQRCode(result.data.qr_data);
                    startPaymentCheck();
                } else {
                    alert(result.message || 'Có lỗi xảy ra');
                }
            } catch (error) {
                alert('Có lỗi xảy ra khi tạo thanh toán');
                console.error(error);
            } finally {
                // Hide loading
                btnText.style.display = 'inline';
                loading.style.display = 'none';
                btn.disabled = false;
            }
        });

        // Update QR code
        function updateQRCode(qrData) {
            location.reload(); // Reload để hiển thị QR mới
        }

        // Check payment status
        function startPaymentCheck() {
            if (!currentPaymentId) return;

            checkInterval = setInterval(async () => {
                try {
                    const response = await fetch(`/payment/{{ $token }}/status/${currentPaymentId}`);
                    const result = await response.json();

                    if (result.success && result.data.is_confirmed) {
                        clearInterval(checkInterval);
                        showSuccessMessage();
                    }
                } catch (error) {
                    console.error('Error checking payment status:', error);
                }
            }, 5000); // Check every 5 seconds
        }

        // Show success message
        function showSuccessMessage() {
            const qrSection = document.querySelector('.qr-section');
            if (qrSection) {
                qrSection.innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        <h4 class="text-success mt-3">Thanh toán thành công!</h4>
                        <p>Cảm ơn bạn đã thanh toán học phí.</p>
                        <button class="btn btn-primary" onclick="location.reload()">
                            <i class="fas fa-refresh me-2"></i>Tải lại trang
                        </button>
                    </div>
                `;
            }
        }

        // Start checking if there's a pending payment
        if (currentPaymentId) {
            startPaymentCheck();
        }

        // Cleanup interval when page unloads
        window.addEventListener('beforeunload', () => {
            if (checkInterval) {
                clearInterval(checkInterval);
            }
        });
    </script>
</body>
</html>
