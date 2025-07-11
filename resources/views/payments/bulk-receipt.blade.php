<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In phiếu thu hàng loạt</title>
    <style>
        @media print {
            .no-print { display: none; }
            .page-break { page-break-after: always; }
        }
        
        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 20px;
        }
        
        .receipt {
            max-width: 21cm;
            margin: 0 auto 40px;
            padding: 20px;
            border: 1px solid #ddd;
            background: white;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            color: #666;
        }
        
        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .info-section {
            flex: 1;
        }
        
        .info-section h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .info-row {
            margin-bottom: 8px;
            display: flex;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 120px;
        }
        
        .payment-details {
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .payment-header {
            background: #f8f9fa;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        
        .payment-content {
            padding: 15px;
        }
        
        .amount {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
        }
        
        .signatures {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-section {
            text-align: center;
            flex: 1;
        }
        
        .signature-section h4 {
            margin-bottom: 60px;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 10px;
            padding-top: 5px;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> In phiếu thu
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            <i class="fas fa-times"></i> Đóng
        </button>
    </div>

    @foreach($payments as $index => $payment)
        <div class="receipt @if($index > 0) page-break @endif">
            <div class="header">
                <h1>PHIẾU THU</h1>
                <h2>Trung tâm đào tạo nghề</h2>
                <p>Địa chỉ: 123 Đường ABC, Quận XYZ, TP.HCM</p>
                <p>Điện thoại: 028.3xxx.xxxx | Email: info@center.edu.vn</p>
            </div>

            <div class="receipt-info">
                <div class="info-section">
                    <h3>THÔNG TIN HỌC VIÊN</h3>
                    <div class="info-row">
                        <span class="info-label">Họ và tên:</span>
                        <span>{{ $payment->enrollment->student->full_name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số điện thoại:</span>
                        <span>{{ $payment->enrollment->student->phone }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">CCCD/CMND:</span>
                        <span>{{ $payment->enrollment->student->citizen_id ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Địa chỉ:</span>
                        <span>{{ $payment->enrollment->student->address ?? 'N/A' }}</span>
                    </div>
                </div>
                
                <div class="info-section" style="margin-left: 20px;">
                    <h3>THÔNG TIN PHIẾU THU</h3>
                    <div class="info-row">
                        <span class="info-label">Số phiếu:</span>
                        <span>PT{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ngày thu:</span>
                        <span>{{ $payment->payment_date->format('d/m/Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Người thu:</span>
                        <span>Admin</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Trạng thái:</span>
                        <span>
                            @if($payment->status == 'confirmed')
                                <strong style="color: green;">Đã xác nhận</strong>
                            @elseif($payment->status == 'pending')
                                <strong style="color: orange;">Chờ xác nhận</strong>
                            @else
                                <strong style="color: red;">{{ ucfirst($payment->status) }}</strong>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <div class="payment-details">
                <div class="payment-header">
                    CHI TIẾT THANH TOÁN
                </div>
                <div class="payment-content">
                    <div class="info-row">
                        <span class="info-label">Khóa học:</span>
                        <span>{{ $payment->enrollment->courseClass->course->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Lớp học:</span>
                        <span>{{ $payment->enrollment->courseClass->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ngày ghi danh:</span>
                        <span>{{ $payment->enrollment->enrollment_date->format('d/m/Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tổng học phí:</span>
                        <span>{{ number_format($payment->enrollment->final_fee) }} VNĐ</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Đã thanh toán:</span>
                        <span>{{ number_format($payment->enrollment->getTotalPaidAmount()) }} VNĐ</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số tiền này:</span>
                        <span class="amount">{{ number_format($payment->amount) }} VNĐ</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Còn lại:</span>
                        <span>{{ number_format($payment->enrollment->getRemainingAmount()) }} VNĐ</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phương thức:</span>
                        <span>
                            @if($payment->payment_method == 'cash')
                                Tiền mặt
                            @elseif($payment->payment_method == 'bank_transfer')
                                Chuyển khoản
                            @elseif($payment->payment_method == 'card')
                                Thẻ
                            @else
                                Khác
                            @endif
                        </span>
                    </div>
                    @if($payment->transaction_reference)
                        <div class="info-row">
                            <span class="info-label">Mã giao dịch:</span>
                            <span>{{ $payment->transaction_reference }}</span>
                        </div>
                    @endif
                    @if($payment->notes)
                        <div class="info-row">
                            <span class="info-label">Ghi chú:</span>
                            <span>{{ $payment->notes }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="signatures">
                <div class="signature-section">
                    <h4>Người nộp tiền</h4>
                    <div class="signature-line">
                        (Ký và ghi rõ họ tên)
                    </div>
                </div>
                <div class="signature-section">
                    <h4>Người thu tiền</h4>
                    <div class="signature-line">
                        (Ký và ghi rõ họ tên)
                    </div>
                </div>
            </div>

            <div class="footer">
                <p><em>Phiếu thu được in ngày {{ now()->format('d/m/Y H:i') }}</em></p>
                <p><strong>Lưu ý:</strong> Đây là phiếu thu chính thức, vui lòng giữ lại để đối chiếu</p>
            </div>
        </div>
    @endforeach

    <script>
        // Auto print when page loads
        window.onload = function() {
            // Optional: Auto print
            // window.print();
        }
    </script>
</body>
</html> 
 