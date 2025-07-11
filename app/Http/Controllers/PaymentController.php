<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Mail\PaymentReminderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Hiển thị danh sách thanh toán
     */
    public function index(Request $request)
    {
        $query = Payment::with(['enrollment.student', 'enrollment.courseClass.course']);

        // Lọc theo học viên
        if ($request->filled('student_id')) {
            $query->whereHas('enrollment', function($q) use ($request) {
                $q->where('student_id', $request->student_id);
            });
        }

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Lọc theo phương thức thanh toán
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Lọc theo khoảng thời gian
        if ($request->filled('date_from')) {
            $query->where('payment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('payment_date', '<=', $request->date_to);
        }

        $payments = $query->orderBy('payment_date', 'desc')->paginate(20);

        // Thống kê nhanh
        $stats = [
            'total_payments' => Payment::count(),
            'total_amount' => Payment::where('status', 'confirmed')->sum('amount'),
            'today_amount' => Payment::whereDate('payment_date', today())
                                    ->where('status', 'confirmed')
                                    ->sum('amount'),
            'pending_count' => Payment::where('status', 'pending')->count()
        ];

        return view('payments.index', compact('payments', 'stats'));
    }

    /**
     * Hiển thị form thanh toán mới
     */
    public function create(Request $request)
    {
        $enrollment = null;
        if ($request->filled('enrollment_id')) {
            $enrollment = Enrollment::with(['student', 'courseClass.course'])
                                   ->findOrFail($request->enrollment_id);
        }

        return view('payments.create', compact('enrollment'));
    }

    /**
     * Lưu thanh toán mới
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'amount' => 'required|numeric|min:1000',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,other',
            'notes' => 'nullable|string',
        ]);

        $enrollment = Enrollment::findOrFail($validatedData['enrollment_id']);
        
        // Kiểm tra số tiền thanh toán không vượt quá số tiền còn thiếu
        $remainingAmount = $enrollment->getRemainingAmount();
        if ($validatedData['amount'] > $remainingAmount) {
            return back()->withInput()->withErrors(['amount' => 'Số tiền thanh toán không được vượt quá số tiền còn thiếu: ' . number_format($remainingAmount) . ' VNĐ']);
        }

        $payment = Payment::create([
            'enrollment_id' => $validatedData['enrollment_id'],
            'amount' => $validatedData['amount'],
            'payment_date' => $validatedData['payment_date'],
            'payment_method' => $validatedData['payment_method'],
            'notes' => $validatedData['notes'],
            'status' => 'confirmed', // Tự động xác nhận thanh toán mới
        ]);

        return redirect()->route('payments.show', $payment)
                        ->with('success', 'Ghi nhận thanh toán thành công!');
    }

    /**
     * Hiển thị chi tiết thanh toán
     */
    public function show(Payment $payment)
    {
        $payment->load(['enrollment.student', 'enrollment.courseClass.course']);

        return view('payments.show', compact('payment'));
    }

    /**
     * Hiển thị form chỉnh sửa thanh toán
     */
    public function edit(Payment $payment)
    {
        $payment->load(['enrollment.student', 'enrollment.courseClass.course']);

        return view('payments.edit', compact('payment'));
    }

    /**
     * Cập nhật thanh toán
     */
    public function update(Request $request, Payment $payment)
    {
        $validatedData = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,card,qr_code',
            'transaction_reference' => 'nullable|string|max:255',
            'status' => 'required|in:pending,confirmed,cancelled',
            'notes' => 'nullable|string',
        ]);

        // Kiểm tra số tiền thanh toán nếu thay đổi
        if ($validatedData['amount'] != $payment->amount) {
            $enrollment = $payment->enrollment;
            $currentPaidAmount = $enrollment->getTotalPaidAmount() - $payment->amount; // Trừ đi thanh toán hiện tại
            $remainingAmount = $enrollment->final_fee - $currentPaidAmount;
            
            if ($validatedData['amount'] > $remainingAmount) {
                return back()->withErrors(['amount' => 'Số tiền thanh toán không được vượt quá số tiền còn thiếu: ' . number_format($remainingAmount) . ' VND']);
            }
        }

        $payment->update($validatedData);

        return redirect()->route('payments.show', $payment)
                        ->with('success', 'Cập nhật thanh toán thành công!');
    }

    /**
     * Xóa thanh toán
     */
    public function destroy(Payment $payment)
    {
        try {
            $payment->delete();
            return redirect()->route('payments.index')
                            ->with('success', 'Xóa thanh toán thành công!');
        } catch (\Exception $e) {
            return redirect()->route('payments.index')
                            ->with('error', 'Không thể xóa thanh toán!');
        }
    }

    /**
     * Xác nhận thanh toán
     */
    public function confirm(Payment $payment)
    {
        $payment->update(['status' => 'confirmed']);

        return redirect()->back()
                        ->with('success', 'Xác nhận thanh toán thành công!');
    }

    /**
     * Hủy thanh toán
     */
    public function cancel(Payment $payment)
    {
        $payment->update(['status' => 'cancelled']);

        return redirect()->back()
                        ->with('success', 'Hủy thanh toán thành công!');
    }

    /**
     * Tạo thanh toán nhanh cho một ghi danh
     */
    public function quickPayment(Request $request)
    {
        $validatedData = $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,bank_transfer,card,qr_code',
            'transaction_reference' => 'nullable|string|max:255',
        ]);

        $enrollment = Enrollment::findOrFail($validatedData['enrollment_id']);
        
        // Kiểm tra số tiền
        $remainingAmount = $enrollment->getRemainingAmount();
        if ($validatedData['amount'] > $remainingAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Số tiền thanh toán không được vượt quá số tiền còn thiếu: ' . number_format($remainingAmount) . ' VND'
            ]);
        }

        $payment = Payment::create([
            'enrollment_id' => $validatedData['enrollment_id'],
            'amount' => $validatedData['amount'],
            'payment_date' => now()->toDateString(),
            'payment_method' => $validatedData['payment_method'],
            'transaction_reference' => $validatedData['transaction_reference'],
            'status' => 'confirmed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ghi nhận thanh toán thành công!',
            'payment_id' => $payment->id
        ]);
    }

    /**
     * Báo cáo thu chi theo tháng
     */
    public function monthlyReport(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        
        $payments = Payment::with(['enrollment.student', 'enrollment.courseClass.course'])
                          ->whereYear('payment_date', substr($month, 0, 4))
                          ->whereMonth('payment_date', substr($month, 5, 2))
                          ->where('status', 'confirmed')
                          ->get();

        $totalRevenue = $payments->sum('amount');
        $revenueByMethod = $payments->groupBy('payment_method')
                                  ->map(function($group) {
                                      return $group->sum('amount');
                                  });

        return view('payments.monthly-report', compact('payments', 'totalRevenue', 'revenueByMethod', 'month'));
    }

    /**
     * Gửi email nhắc nhở thanh toán
     */
    public function sendReminder(Request $request)
    {
        $request->validate([
            'payment_ids' => 'required|array|min:1',
            'payment_ids.*' => 'exists:payments,id'
        ]);

        $paymentIds = $request->payment_ids;
        $sentCount = 0;
        $errors = [];

        foreach($paymentIds as $paymentId) {
            try {
                $payment = Payment::with('enrollment.student')->find($paymentId);

                // Chỉ gửi nếu có email và khoản thanh toán chưa được xác nhận
                if ($payment && $payment->enrollment->student->email && $payment->status !== 'confirmed') {
                    Mail::to($payment->enrollment->student->email)->send(new PaymentReminderMail($payment));
                    $sentCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "Lỗi khi gửi email cho thanh toán ID: {$paymentId}. " . $e->getMessage();
                Log::error("Send Reminder Error for Payment ID {$paymentId}: " . $e->getMessage());
            }
        }

        $message = "Đã gửi thành công {$sentCount} email nhắc nhở.";
        if (!empty($errors)) {
            return redirect()->back()->with('success', $message)->withErrors($errors);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Lấy thông tin thanh toán của một ghi danh (API)
     */
    public function getEnrollmentPayments(Enrollment $enrollment)
    {
        $payments = $enrollment->payments()->orderBy('payment_date', 'desc')->get();
        $totalPaid = $enrollment->getTotalPaidAmount();
        $remaining = $enrollment->getRemainingAmount();

        return response()->json([
            'payments' => $payments,
            'total_paid' => $totalPaid,
            'remaining' => $remaining,
            'fully_paid' => $enrollment->hasFullyPaid()
        ]);
    }

    /**
     * In phiếu thu hàng loạt
     */
    public function bulkReceipt(Request $request)
    {
        $paymentIds = explode(',', $request->get('ids', ''));
        $payments = Payment::with(['enrollment.student', 'enrollment.courseClass.course'])
                          ->whereIn('id', $paymentIds)
                          ->get();

        return view('payments.bulk-receipt', compact('payments'));
    }

    /**
     * API lấy thông tin một thanh toán cụ thể
     */
    public function getPaymentInfo(Payment $payment)
    {
        return response()->json([
            'id' => $payment->id,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'payment_date' => $payment->payment_date ? $payment->payment_date->format('d/m/Y H:i') : null,
        ]);
    }

    /**
     * API thao tác hàng loạt
     */
    public function bulkAction(Request $request)
    {
        $paymentIds = $request->get('payment_ids', []);
        $action = $request->get('action');

        if (empty($paymentIds) || empty($action)) {
            return response()->json(['success' => false, 'message' => 'Dữ liệu không hợp lệ'], 400);
        }

        $payments = Payment::whereIn('id', $paymentIds)->get();

        try {
            switch ($action) {
                case 'confirm':
                    $payments->each(function($payment) {
                        $payment->update(['status' => 'confirmed']);
                    });
                    break;
                    
                case 'change_method':
                    $newMethod = $request->get('new_method');
                    if ($newMethod) {
                        $payments->each(function($payment) use ($newMethod) {
                            $payment->update(['payment_method' => $newMethod]);
                        });
                    }
                    break;
                    
                case 'export':
                    // Logic xuất Excel sẽ được implement sau
                    break;
            }

            return response()->json(['success' => true, 'message' => 'Thao tác thành công']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API xác nhận thanh toán
     */
    public function confirmPayment(Payment $payment)
    {
        try {
            $payment->update(['status' => 'confirmed']);
            return response()->json(['success' => true, 'message' => 'Xác nhận thành công']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
        }
    }

    /**
     * API hoàn tiền
     */
    public function refundPayment(Request $request, Payment $payment)
    {
        try {
            $payment->update([
                'status' => 'refunded',
                'notes' => $payment->notes . "\n[Hoàn tiền] " . $request->get('reason', '')
            ]);
            return response()->json(['success' => true, 'message' => 'Hoàn tiền thành công']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
        }
    }
    
    /**
     * Tạo biên lai thanh toán
     */
    public function generateReceipt(Payment $payment)
    {
        $payment->load(['enrollment.student', 'enrollment.courseClass.course']);
        
        return view('payments.receipt', compact('payment'));
    }
}
