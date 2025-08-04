<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\PaymentService;
use App\Services\ReminderService;
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
use App\Exports\PaymentHistoryExport;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    protected $paymentService;
    protected $reminderService;
    
    /**
     * Khởi tạo controller
     */
    public function __construct(PaymentService $paymentService, ReminderService $reminderService)
    {
        // Trang thanh toán trực tiếp không yêu cầu đăng nhập
        $this->middleware('auth')->except(['showDirectPaymentGateway']);
        $this->paymentService = $paymentService;
        $this->reminderService = $reminderService;
    }

    /**
     * Hiển thị lịch sử thanh toán của tất cả học viên
     */
    public function index(Request $request)
    {
        $query = Payment::with(['enrollment.student', 'enrollment.courseItem']);
        
        // Filter theo khóa học
        if ($request->filled('course_item_id')) {
            $query->whereHas('enrollment', function($q) use ($request) {
                $q->where('course_item_id', $request->course_item_id);
            });
        }
        
        // Filter theo phương thức thanh toán
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        
        // Filter theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter theo ngày
        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }
        
        // Search theo tên học viên hoặc mã giao dịch
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('transaction_reference', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('enrollment.student', function($sq) use ($search) {
                      $sq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
        
        // Sắp xếp theo ngày thanh toán mới nhất
        $query->orderBy('payment_date', 'desc')->orderBy('created_at', 'desc');
        
        // Phân trang
        $payments = $query->paginate(20)->appends($request->query());
        
        // Thống kê tổng quan
        $stats = [
            'total_payments' => Payment::count(),
            'total_amount' => Payment::where('status', 'confirmed')->sum('amount'),
            'today_amount' => Payment::whereDate('payment_date', today())
                                    ->where('status', 'confirmed')
                                    ->sum('amount'),
            'pending_count' => Payment::where('status', 'pending')->count(),
            'pending_amount' => Payment::where('status', 'pending')->sum('amount'),
        ];
        
        // Lấy danh sách khóa học để filter
        $courseItems = CourseItem::where('is_leaf', true)
            ->where('active', true)
            ->orderBy('name')
            ->get();
        
        // Lưu filters để hiển thị lại trong view
        $filters = $request->only(['course_item_id', 'payment_method', 'status', 'date_from', 'date_to', 'search']);
        
        return view('payments.history', compact('payments', 'stats', 'courseItems', 'filters'));
    }

    /**
     * Hiển thị chi tiết thanh toán (API)
     */
    public function show(Payment $payment)
    {
        try {
            $payment->load(['enrollment.student', 'enrollment.courseItem']);
            
            return response()->json([
                'success' => true,
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            Log::error('Payment show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
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
            $payment->update(['status' => 'confirmed']);
            
            return response()->json([
                'success' => true,
                'message' => 'Đã xác nhận thanh toán thành công!'
            ]);
        } catch (\Exception $e) {
            Log::error('Payment confirmation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hủy thanh toán
     */
    public function cancel(Payment $payment)
    {
        try {
            $payment->update(['status' => 'cancelled']);
            
            return response()->json([
                'success' => true,
                'message' => 'Đã hủy thanh toán thành công!'
            ]);
        } catch (\Exception $e) {
            Log::error('Payment cancellation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hoàn tiền
     */
    public function refund(Payment $payment)
    {
        try {
            $payment->update(['status' => 'refunded']);
            
            return response()->json([
                'success' => true,
                'message' => 'Đã hoàn tiền thành công!'
            ]);
        } catch (\Exception $e) {
            Log::error('Payment refund error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xuất Excel lịch sử thanh toán
     */
    public function export(Request $request)
    {
        $query = Payment::with(['enrollment.student', 'enrollment.courseItem']);
        
        // Áp dụng các filter giống như trong index
        if ($request->filled('course_item_id')) {
            $query->whereHas('enrollment', function($q) use ($request) {
                $q->where('course_item_id', $request->course_item_id);
            });
        }
        
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('transaction_reference', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('enrollment.student', function($sq) use ($search) {
                      $sq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
        
        $payments = $query->orderBy('payment_date', 'desc')->get();
        
        return Excel::download(new PaymentHistoryExport($payments), 'lich-su-thanh-toan-' . date('Y-m-d') . '.xlsx');
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
        // Xử lý bulk reminders (nhiều khóa học)
        if ($request->has('course_ids')) {
            $validated = $request->validate([
                'course_ids' => 'required|array',
                'course_ids.*' => 'exists:course_items,id',
            ]);
            
            $result = $this->reminderService->sendBulkCourseReminders($validated['course_ids']);
            
            return response()->json($result);
        }
        
        // Xử lý course reminders (một khóa học với nhiều học viên)
        if ($request->has('course_item_id')) {
            $validated = $request->validate([
                'course_item_id' => 'required|exists:course_items,id',
                'enrollment_ids' => 'nullable|array',
                'enrollment_ids.*' => 'exists:enrollments,id',
            ]);
            
            $result = $this->reminderService->sendCourseReminders(
                $validated['course_item_id'],
                $validated['enrollment_ids'] ?? null
            );
            
            return response()->json($result);
        }
        
        // Xử lý individual reminder (một học viên)
        if ($request->has('enrollment_id')) {
            $validated = $request->validate([
                'enrollment_id' => 'required|exists:enrollments,id',
            ]);
            
            $result = $this->reminderService->sendIndividualReminder($validated['enrollment_id']);
            
            if ($request->expectsJson()) {
                return response()->json($result);
            }
            
            if ($result['success']) {
                return redirect()->back()->with('success', $result['message']);
            } else {
                return redirect()->back()->withErrors(['error' => $result['message']]);
            }
        }
        
        // Xử lý legacy format (payment_ids hoặc enrollment_ids)
        if ($request->has('payment_ids') || $request->has('enrollment_ids')) {
            $enrollmentIds = [];
            
            if ($request->has('payment_ids')) {
                $validated = $request->validate([
                    'payment_ids' => 'required|array',
                    'payment_ids.*' => 'exists:payments,id',
                ]);
                
                // Lấy enrollment_ids từ payment_ids
                $enrollmentIds = Payment::whereIn('id', $validated['payment_ids'])
                    ->pluck('enrollment_id')
                    ->unique()
                    ->toArray();
            } else {
                $validated = $request->validate([
                    'enrollment_ids' => 'required|array',
                    'enrollment_ids.*' => 'exists:enrollments,id',
                ]);
                
                $enrollmentIds = $validated['enrollment_ids'];
            }
            
            if (empty($enrollmentIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có học viên nào để gửi nhắc nhở.'
                ]);
            }
            
            // Nếu chỉ có 1 enrollment, gửi individual
            if (count($enrollmentIds) === 1) {
                $result = $this->reminderService->sendIndividualReminder($enrollmentIds[0]);
            } else {
                // Nhóm theo khóa học và gửi batch
                $enrollmentsByCourse = Enrollment::whereIn('id', $enrollmentIds)
                    ->get()
                    ->groupBy('course_item_id');
                
                $results = [];
                foreach ($enrollmentsByCourse as $courseItemId => $enrollments) {
                    $result = $this->reminderService->sendCourseReminders(
                        $courseItemId,
                        $enrollments->pluck('id')->toArray()
                    );
                    $results[] = $result;
                }
                
                $totalSuccess = collect($results)->where('success', true)->count();
                $totalEmails = collect($results)->sum('total_emails');
                
                $result = [
                    'success' => $totalSuccess > 0,
                    'message' => "Đã bắt đầu gửi nhắc nhở cho {$totalEmails} học viên trong {$totalSuccess} khóa học.",
                    'details' => $results
                ];
            }
            
            return response()->json($result);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Thiếu thông tin cần thiết để gửi nhắc nhở.'
        ], 400);
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

    /**
     * Lấy thống kê batch reminder
     */
    public function getBatchStats($batchId)
    {
        $stats = $this->reminderService->getBatchStats($batchId);
        
        if (!$stats) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy batch.'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Lấy danh sách batch của user hiện tại
     */
    public function getUserBatches()
    {
        $batches = $this->reminderService->getUserBatches(Auth::id());
        
        return response()->json([
            'success' => true,
            'data' => $batches
        ]);
    }
}
