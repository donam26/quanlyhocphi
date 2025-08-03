<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Mail\PaymentReminderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\CourseItem; 
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $paymentService;
    
    /**
     * Khởi tạo controller
     */
    public function __construct(PaymentService $paymentService)
    {
        // Trang thanh toán trực tiếp không yêu cầu đăng nhập
        $this->middleware('auth')->except(['showDirectPaymentGateway']);
        $this->paymentService = $paymentService;
    }

    /**
     * Hiển thị danh sách thanh toán
     */
    public function index(Request $request)
    {
        // Lấy tất cả các khóa học (CourseItems) có học viên đăng ký
        $courseItems = CourseItem::whereHas('enrollments')->get();
        
        // Tạo một mảng để lưu trữ thông tin thống kê cho mỗi khóa học
        $courseStats = [];
        
        foreach ($courseItems as $courseItem) {
            // Lấy danh sách ghi danh của khóa học này
            $enrollments = Enrollment::where('course_item_id', $courseItem->id)->get();
            $enrollmentIds = $enrollments->pluck('id')->toArray();
            
            // Lấy tất cả thanh toán liên quan đến các ghi danh này
            $payments = Payment::whereIn('enrollment_id', $enrollmentIds)
                               ->where('status', 'confirmed')
                               ->get();
            
            // Tính toán thống kê
            $courseStats[$courseItem->id] = [
                'total_enrollments' => $enrollments->count(),
                'total_paid' => $payments->sum('amount'),
                'total_fee' => $enrollments->sum('final_fee'),
                'remaining' => $enrollments->sum('final_fee') - $payments->sum('amount'),
            ];
        }
        
        // Thống kê tổng quan
        $stats = [
            'total_payments' => Payment::count(),
            'total_amount' => Payment::where('status', 'confirmed')->sum('amount'),
            'today_amount' => Payment::whereDate('payment_date', today())
                                    ->where('status', 'confirmed')
                                    ->sum('amount'),
            'pending_count' => Payment::where('status', 'pending')->count()
        ];
        
        return view('payments.index', compact('courseItems', 'courseStats', 'stats'));
    }

    /**
     * Lưu thanh toán mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'amount' => 'required|numeric|min:1000',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,card,qr_code,sepay,other',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,confirmed',
            'transaction_id' => 'nullable|string'
        ]);
        
        try {
            $payment = $this->paymentService->createPayment($validated);
            
            return redirect()->route('payments.index')
                ->with('success', 'Thanh toán đã được tạo thành công!');
        } catch (\Exception $e) {
            Log::error('Payment creation error: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Cập nhật thông tin thanh toán
     */
    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,card,qr_code,sepay,other',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,confirmed,cancelled,refunded',
            'transaction_id' => 'nullable|string'
        ]);

        try {
            $payment = $this->paymentService->updatePayment($payment, $validated);
            
            return redirect()->route('payments.index')
                ->with('success', 'Thanh toán đã được cập nhật thành công!');
        } catch (\Exception $e) {
            Log::error('Payment update error: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Xóa thanh toán
     */
    public function destroy(Payment $payment)
    {
        try {
            $this->paymentService->deletePayment($payment);
            
            return redirect()->route('payments.index')
                ->with('success', 'Thanh toán đã được xóa thành công!');
        } catch (\Exception $e) {
            Log::error('Payment deletion error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Xác nhận thanh toán
     */
    public function confirm(Payment $payment)
    {
        try {
            $payment = $this->paymentService->updatePayment($payment, ['status' => 'confirmed']);
            
            return redirect()->back()
                ->with('success', 'Thanh toán đã được xác nhận thành công!');
        } catch (\Exception $e) {
            Log::error('Payment confirmation error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Xử lý tạo thanh toán nhanh
     */
    public function quickPayment(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'amount' => 'required|numeric|min:1000',
            'payment_method' => 'required|in:cash,bank_transfer,card,qr_code,sepay,other',
            'notes' => 'nullable|string'
        ]);
        
        // Thêm các giá trị mặc định
        $validated['payment_date'] = now();
        $validated['status'] = 'confirmed';
        
        try {
            $payment = $this->paymentService->createPayment($validated);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thanh toán đã được tạo thành công!',
                    'payment' => $payment
                ]);
            }
            
            return redirect()->back()
                ->with('success', 'Thanh toán đã được tạo thành công!');
        } catch (\Exception $e) {
            Log::error('Quick payment error: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Gửi nhắc nhở thanh toán
     */
    public function sendReminder(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
        ]);
        
        try {
            $enrollment = Enrollment::findOrFail($validated['enrollment_id']);
            $sent = $this->paymentService->sendPaymentReminder($enrollment);
            
            if ($sent) {
                return redirect()->back()
                    ->with('success', 'Email nhắc thanh toán đã được gửi thành công!');
            } else {
                return redirect()->back()
                    ->with('warning', 'Không thể gửi email vì học viên không có địa chỉ email.');
            }
        } catch (\Exception $e) {
            Log::error('Payment reminder error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Tạo biên lai thanh toán
     */
    public function generateReceipt(Payment $payment)
    {
        $receipt = $this->paymentService->generateReceipt($payment);
        
        return view('payments.receipt', compact('payment', 'receipt'));
    }

    /**
     * Tạo biên lai thanh toán hàng loạt
     */
    public function bulkReceipt(Request $request)
    {
        $validated = $request->validate([
            'payment_ids' => 'required|array',
            'payment_ids.*' => 'exists:payments,id',
        ]);
        
        $receipt = $this->paymentService->generateBulkReceipt($validated['payment_ids']);
        
        if (!$receipt) {
            return redirect()->back()->with('error', 'Không tìm thấy thanh toán');
        }
        
        return view('payments.bulk-receipt', compact('receipt'));
    }

    /**
     * Lấy danh sách thanh toán của một khóa học
     */
    public function coursePayments(CourseItem $courseItem)
    {
        // Lấy tất cả ghi danh của khóa học
        $enrollments = Enrollment::where('course_item_id', $courseItem->id)
                               ->with('student')
                               ->get();
                               
        $totalFees = $enrollments->sum('final_fee');
        $enrollmentIds = $enrollments->pluck('id');
        
        // Lấy tất cả thanh toán liên quan đến các ghi danh
        $payments = Payment::whereIn('enrollment_id', $enrollmentIds)
                         ->orderBy('payment_date', 'desc')
                         ->with('enrollment')
                         ->get();
                         
        $totalPaid = $payments->where('status', 'confirmed')->sum('amount');
        $remainingAmount = $totalFees - $totalPaid;
        
        return view('payments.course', compact('courseItem', 'enrollments', 'payments', 'totalFees', 'totalPaid', 'remainingAmount'));
    }

    /**
     * Lấy danh sách thanh toán của một ghi danh
     */
    public function getEnrollmentPayments(Enrollment $enrollment)
    {
        $payments = $this->paymentService->getPaymentsByEnrollment($enrollment);
        $totalPaid = $this->paymentService->getTotalPaidForEnrollment($enrollment);
        $remainingAmount = max(0, $enrollment->final_fee - $totalPaid);
        
        return view('payments.partials.enrollment-payments', compact('enrollment', 'payments', 'totalPaid', 'remainingAmount'));
    }

    /**
     * Xử lý hoàn tiền
     */
    public function refundPayment(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);
        
        try {
            $this->paymentService->updatePayment($payment, [
                'status' => 'refunded',
                'notes' => ($payment->notes ? $payment->notes . "\n" : '') . 'Lý do hoàn tiền: ' . $validated['reason']
            ]);
            
            return redirect()->back()
                ->with('success', 'Thanh toán đã được đánh dấu hoàn tiền thành công!');
        } catch (\Exception $e) {
            Log::error('Payment refund error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }
}
