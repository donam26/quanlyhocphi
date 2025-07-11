<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biên lai thanh toán #{{ $payment->id }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .receipt {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #ddd;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .receipt-title {
            font-size: 24px;
            color: #2c3e50;
            margin: 10px 0;
        }
        .receipt-subtitle {
            color: #7f8c8d;
            font-size: 16px;
            margin-top: 0;
        }
        .receipt-id {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .receipt-date {
            color: #7f8c8d;
            margin-bottom: 20px;
        }
        .receipt-body {
            margin-bottom: 30px;
        }
        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .receipt-section {
            width: 48%;
        }
        .receipt-section h3 {
            color: #3498db;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .receipt-detail {
            margin: 10px 0;
        }
        .receipt-detail strong {
            display: inline-block;
            width: 120px;
        }
        .receipt-amount {
            font-size: 22px;
            color: #16a085;
            text-align: right;
            margin: 20px 0;
            border-top: 1px dashed #ddd;
            padding-top: 10px;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            color: #7f8c8d;
            font-size: 14px;
        }
        .receipt-note {
            font-style: italic;
            margin: 20px 0;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 3px solid #ddd;
        }
        .receipt-signature {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        .signature-section {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
        .print-button {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        .print-button:hover {
            background-color: #2980b9;
        }
        @media print {
            .print-button {
                display: none;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .receipt {
                box-shadow: none;
                border: none;
            }
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-button">In biên lai</button>
    
    <div class="receipt">
        <div class="receipt-header">
            <h1 class="receipt-title">BIÊN LAI THANH TOÁN</h1>
            <p class="receipt-subtitle">Trung tâm đào tạo kế toán</p>
            <p class="receipt-id">Mã biên lai: #{{ $payment->id }}</p>
            <p class="receipt-date">Ngày: {{ $payment->payment_date->format('d/m/Y') }}</p>
        </div>
        
        <div class="receipt-body">
            <div class="receipt-info">
                <div class="receipt-section">
                    <h3>Thông tin học viên</h3>
                    <p class="receipt-detail"><strong>Họ tên:</strong> {{ $payment->enrollment->student->full_name }}</p>
                    <p class="receipt-detail"><strong>Số điện thoại:</strong> {{ $payment->enrollment->student->phone }}</p>
                    <p class="receipt-detail"><strong>Email:</strong> {{ $payment->enrollment->student->email }}</p>
                    <p class="receipt-detail"><strong>CMND/CCCD:</strong> {{ $payment->enrollment->student->citizen_id }}</p>
                </div>
                
                <div class="receipt-section">
                    <h3>Thông tin khoá học</h3>
                    <p class="receipt-detail"><strong>Tên khoá học:</strong> {{ $payment->enrollment->courseClass->course->name }}</p>
                    <p class="receipt-detail"><strong>Tên lớp:</strong> {{ $payment->enrollment->courseClass->name }}</p>
                    <p class="receipt-detail"><strong>Học phí:</strong> {{ number_format($payment->enrollment->final_fee, 0, ',', '.') }} VNĐ</p>
                    <p class="receipt-detail"><strong>Đã thanh toán:</strong> {{ number_format($payment->enrollment->getTotalPaidAmount(), 0, ',', '.') }} VNĐ</p>
                </div>
            </div>
            
            <div class="receipt-details">
                <h3>Chi tiết thanh toán</h3>
                <p class="receipt-detail"><strong>Số tiền:</strong> {{ number_format($payment->amount, 0, ',', '.') }} VNĐ</p>
                <p class="receipt-detail"><strong>Hình thức:</strong> 
                    @switch($payment->payment_method)
                        @case('cash')
                            Tiền mặt
                            @break
                        @case('bank_transfer')
                            Chuyển khoản ngân hàng
                            @break
                        @case('card')
                            Thẻ tín dụng/ghi nợ
                            @break
                        @case('qr_code')
                            Quét mã QR
                            @break
                        @default
                            {{ $payment->payment_method }}
                    @endswitch
                </p>
                @if($payment->transaction_reference)
                <p class="receipt-detail"><strong>Mã giao dịch:</strong> {{ $payment->transaction_reference }}</p>
                @endif
                <p class="receipt-detail"><strong>Trạng thái:</strong> 
                    @switch($payment->status)
                        @case('confirmed')
                            <span style="color: green;">Đã xác nhận</span>
                            @break
                        @case('pending')
                            <span style="color: orange;">Chờ xác nhận</span>
                            @break
                        @case('cancelled')
                            <span style="color: red;">Đã huỷ</span>
                            @break
                        @default
                            {{ $payment->status }}
                    @endswitch
                </p>
            </div>
            
            <div class="receipt-amount">
                <p>Tổng thanh toán: <strong>{{ number_format($payment->amount, 0, ',', '.') }}</strong> VNĐ</p>
                <p style="font-size: 14px;">Bằng chữ: {{ $payment->amountInWords() ?? 'Không đồng' }}</p>
            </div>
            
            @if($payment->notes)
            <div class="receipt-note">
                <p><strong>Ghi chú:</strong> {{ $payment->notes }}</p>
            </div>
            @endif
        </div>
        
        <div class="receipt-signature">
            <div class="signature-section">
                <p>Người nộp tiền</p>
                <div class="signature-line"></div>
                <p>{{ $payment->enrollment->student->full_name }}</p>
            </div>
            
            <div class="signature-section">
                <p>Người thu tiền</p>
                <div class="signature-line"></div>
                <p>{{ auth()->user()->name ?? 'Nhân viên thu ngân' }}</p>
            </div>
        </div>
        
        <div class="receipt-footer">
            <p>Cảm ơn bạn đã lựa chọn học tập tại Trung tâm đào tạo kế toán!</p>
            <p>Biên lai này được tạo tự động bởi hệ thống quản lý học phí.</p>
        </div>
    </div>
</body>
</html> 