<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PaymentGatewayController extends Controller
{
    /**
     * Hiển thị trang thanh toán
     */
    public function show(Payment $payment)
    {
        $payment->load('enrollment.student', 'enrollment.courseItem');

        // Đảm bảo thanh toán đang ở trạng thái pending
        if ($payment->status !== 'pending') {
            abort(403, 'Giao dịch này không ở trạng thái chờ thanh toán.');
        }

        return view('payments.gateway.show', compact('payment'));
    }

    /**
     * Tạo mã QR để thanh toán
     */
    public function generateQrCode(Request $request, Payment $payment)
    {
        $payment->load('enrollment.student', 'enrollment.courseItem');
        
        $courseItem = $payment->enrollment->courseItem;
        
        // Tạo nội dung chuyển khoản
        $transferInfo = [
            'amount' => $payment->amount,
            'content' => 'HP.' . $payment->id . '.' . substr($payment->enrollment->student->full_name, 0, 20),
            'account_name' => config('payment.bank.account_name'),
            'account_number' => config('payment.bank.account_number'),
            'bank_name' => config('payment.bank.name')
        ];

        // Tạo mã giao dịch và nội dung chuyển khoản
        $transactionId = 'HP' . $payment->id . time();
        $content = config('sepay.pattern') . $payment->enrollment->student->id . $payment->id;
        
        // Cập nhật mã giao dịch cho khoản thanh toán
        $payment->transaction_reference = $transactionId;
        $payment->save();

        // Thông tin tài khoản ngân hàng
        $bankCode = config('sepay.bank_code'); // Mã ngân hàng (VCB, ICB, ...)
        $bankNumber = config('sepay.bank_number'); // Số tài khoản
        $accountName = config('sepay.account_owner'); // Tên chủ tài khoản
        $bankName = config('sepay.bank_name'); // Tên ngân hàng
        $amount = (int) $payment->amount;

        // Tạo mã QR sử dụng VietQR
        try {
            // Tạo URL VietQR - sử dụng định dạng compact để có logo ngân hàng
            $vietQrUrl = "https://img.vietqr.io/image/{$bankCode}-{$bankNumber}-compact.png?amount={$amount}&addInfo={$content}&accountName=" . urlencode($accountName);
            
            // Trả về thông tin QR
            return response()->json([
                'success' => true,
                'data' => [
                    'qr_image' => $vietQrUrl,
                    'bank_name' => $bankName,
                    'bank_account' => $bankNumber,
                    'account_owner' => $accountName,
                    'content' => $content,
                    'amount' => $amount,
                    'transaction_id' => $transactionId,
                    'student_name' => $payment->enrollment->student->full_name,
                    'course_name' => $courseItem->name,
                    'vietqr_url' => $vietQrUrl,
                    'auto_generated' => true
                ]
            ]);
        } 
        // Nếu có lỗi, sử dụng phương thức tạo QR dự phòng
        catch (\Exception $e) {
            Log::error('Lỗi tạo QR VietQR: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            
            // Tạo QR code dự phòng
            $qrData = "Ngân hàng: {$bankName}\nSố tài khoản: {$bankNumber}\nChủ tài khoản: {$accountName}\nSố tiền: {$amount}\nNội dung: {$content}";
            $qrImage = base64_encode(QrCode::format('png')->size(300)->generate($qrData));
            
            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => $qrData,
                    'qr_image' => "data:image/png;base64," . $qrImage,
                    'bank_name' => $bankName,
                    'bank_account' => $bankNumber,
                    'account_owner' => $accountName,
                    'content' => $content,
                    'amount' => $amount,
                    'transaction_id' => $transactionId,
                    'student_name' => $payment->enrollment->student->full_name,
                    'course_name' => $courseItem->name,
                    'fallback' => true
                ]
            ]);
        }
    }
    
    /**
     * Kiểm tra trạng thái thanh toán
     */
    public function checkPaymentStatus(Request $request, Payment $payment)
    {
        $payment->refresh(); // Cập nhật thông tin mới nhất từ cơ sở dữ liệu
        
        return response()->json([
            'success' => true,
            'status' => $payment->status,
            'confirmed' => $payment->status === 'confirmed',
            'transaction_reference' => $payment->transaction_reference,
            'payment_date' => $payment->payment_date ? $payment->payment_date->format('d/m/Y H:i:s') : null
        ]);
    }
}
