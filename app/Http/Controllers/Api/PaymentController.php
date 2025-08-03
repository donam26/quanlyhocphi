<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;
    
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    
    /**
     * Lấy thông tin thanh toán
     */
    public function getInfo($id)
    {
        try {
            $payment = $this->paymentService->getPayment($id);
            
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thanh toán'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            Log::error('Payment API error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Tạo thanh toán mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'amount' => 'required|numeric|min:1000',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,card,qr_code,sepay,other',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,confirmed'
        ]);
        
        try {
            $payment = $this->paymentService->createPayment($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Thanh toán đã được tạo thành công!',
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            Log::error('Payment API creation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Cập nhật thông tin thanh toán
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,card,qr_code,sepay,other',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,confirmed,cancelled,refunded'
        ]);
        
        try {
            $payment = Payment::findOrFail($id);
            $payment = $this->paymentService->updatePayment($payment, $validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Thanh toán đã được cập nhật thành công!',
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            Log::error('Payment API update error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Xác nhận thanh toán
     */
    public function confirm($id)
    {
        try {
            $payment = Payment::findOrFail($id);
            $payment = $this->paymentService->updatePayment($payment, ['status' => 'confirmed']);
            
            return response()->json([
                'success' => true,
                'message' => 'Thanh toán đã được xác nhận thành công!',
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            Log::error('Payment API confirmation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Hủy thanh toán
     */
    public function cancel($id)
    {
        try {
            $payment = Payment::findOrFail($id);
            $payment = $this->paymentService->updatePayment($payment, ['status' => 'cancelled']);
            
            return response()->json([
                'success' => true,
                'message' => 'Thanh toán đã được hủy thành công!',
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            Log::error('Payment API cancellation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
} 