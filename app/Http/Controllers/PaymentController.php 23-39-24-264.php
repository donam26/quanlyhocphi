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
use App\Rules\DateDDMMYYYY; 
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
        
        // Filter theo enrollment_id (thêm mới)
        if ($request->filled('enrollment_id')) {
            $query->where('enrollment_id', $request->enrollment_id);
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
                      $sq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(IFNULL(first_name, ''), ' ', IFNULL(last_name, '')) LIKE ?", ["%{$search}%"])
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
     * Lưu thông tin thanh toán mới.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'enrollment_id' => 'required|exists:enrollments,id',
                'amount' => 'required|numeric|min:1000',
                'payment_date' => ['required', new DateDDMMYYYY],
                'payment_method' => 'required|in:cash,bank_transfer,card,qr_code,sepay,other',
                'note' => 'nullable|string',
                'notes' => 'nullable|string', // Support cả 2 field name
                'status' => 'nullable|in:pending,confirmed,cancelled,refunded'
            ]);
            
            $enrollment = Enrollment::findOrFail($validated['enrollment_id']);
            
            // Tạo thanh toán mới
            $payment = $this->paymentService->createPayment([
                'enrollment_id' => $validated['enrollment_id'],
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? $validated['note'] ?? '', // Support cả 2 field name
                'status' => $validated['status'] ?? 'pending',
                'created_by' => auth()->id(),
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đã tạo thanh toán thành công.',
                    'data' => $payment
                ]);
            }
            
            return redirect()->route('payments.index')->with('success', 'Đã tạo thanh toán thành công.');
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo thanh toán: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->withInput()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Cập nhật thông tin thanh toán
     */
    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'payment_date' => ['required', new DateDDMMYYYY],
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
        $query = Payment::with(['enrollment.student.province', 'enrollment.courseItem']);
        
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
                      $sq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(IFNULL(first_name, ''), ' ', IFNULL(last_name, '')) LIKE ?", ["%{$search}%"])
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter theo tỉnh/thành phố
        if ($request->filled('province_id')) {
            $query->whereHas('enrollment.student', function($q) use ($request) {
                $q->where('province_id', $request->province_id);
            });
        }

        // Filter theo giới tính
        if ($request->filled('gender')) {
            $query->whereHas('enrollment.student', function($q) use ($request) {
                $q->where('gender', $request->gender);
            });
        }

        // Filter theo ngày sinh từ
        if ($request->filled('birth_date_from')) {
            $query->whereHas('enrollment.student', function($q) use ($request) {
                $q->whereDate('date_of_birth', '>=', $request->birth_date_from);
            });
        }

        // Filter theo ngày sinh đến
        if ($request->filled('birth_date_to')) {
            $query->whereHas('enrollment.student', function($q) use ($request) {
                $q->whereDate('date_of_birth', '<=', $request->birth_date_to);
            });
        }
        
        $payments = $query->orderBy('payment_date', 'desc')->get();

        // Lấy danh sách cột cần xuất
        $columns = $request->get('columns', ['full_name', 'phone', 'email', 'date_of_birth', 'course_registered']);

        return Excel::download(new PaymentHistoryExport($payments, $columns), 'lich-su-thanh-toan-' . date('Y-m-d') . '.xlsx');
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
        try {
            // Xử lý course_ids (gửi nhắc nhở cho nhiều lớp học)
            if ($request->has('course_ids')) {
                $validated = $request->validate([
                    'course_ids' => 'required|array',
                    'course_ids.*' => 'exists:course_items,id',
                ]);
                
                // Kiểm tra có khóa học nào không
                if (empty($validated['course_ids'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không có khóa học nào được chọn.'
                    ]);
                }
                
                // Xác nhận khóa học tồn tại
                $courseCount = CourseItem::whereIn('id', $validated['course_ids'])->count();
                if ($courseCount != count($validated['course_ids'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Một số khóa học không tồn tại.'
                    ]);
                }
                
                $result = $this->reminderService->sendBulkCourseReminders($validated['course_ids']);
                
                return response()->json($result);
            }
            
            // Xử lý enrollment_id
            if ($request->has('enrollment_id')) {
                $validated = $request->validate([
                    'enrollment_id' => 'required|exists:enrollments,id',
                ]);
                
                // Kiểm tra enrollment có student hợp lệ
                $enrollment = Enrollment::with('student')->find($validated['enrollment_id']);
                if (!$enrollment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin ghi danh.'
                    ]);
                }
                
                if (!$enrollment->student) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin học viên.'
                    ]);
                }
                
                if (!$enrollment->student->email) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Học viên không có địa chỉ email.'
                    ]);
                }
                
                $result = $this->reminderService->sendIndividualReminder($validated['enrollment_id']);
                
                return response()->json($result);
            }
            
            // Xử lý enrollment_ids
            if ($request->has('enrollment_ids')) {
                $validated = $request->validate([
                    'enrollment_ids' => 'required|array',
                    'enrollment_ids.*' => 'exists:enrollments,id',
                ]);
                
                if (empty($validated['enrollment_ids'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không có học viên nào để gửi nhắc nhở.'
                    ]);
                }
                
                // Kiểm tra tất cả enrollment tồn tại và có student
                $validEnrollments = Enrollment::with('student')
                    ->whereIn('id', $validated['enrollment_ids'])
                    ->whereHas('student', function($query) {
                        $query->whereNotNull('email');
                    })
                    ->get();
                
                if ($validEnrollments->count() === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không tìm thấy học viên hợp lệ để gửi nhắc nhở.'
                    ]);
                }
                
                $validEnrollmentIds = $validEnrollments->pluck('id')->toArray();
                $results = [];
                $successCount = 0;
                
                // Gửi nhắc nhở từng enrollment một
                foreach ($validEnrollmentIds as $enrollmentId) {
                    $result = $this->reminderService->sendIndividualReminder($enrollmentId);
                    $results[] = $result;
                    
                    if ($result['success']) {
                        $successCount++;
                    }
                }
                
                return response()->json([
                    'success' => $successCount > 0,
                    'message' => "Đã gửi nhắc nhở cho {$successCount}/{$validEnrollments->count()} học viên.",
                    'details' => $results
                ]);
            }
            
            // Thiếu thông tin cần thiết
            return response()->json([
                'success' => false,
                'message' => 'Thiếu thông tin cần thiết để gửi nhắc nhở.'
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error sending reminder: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
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

    /**
     * Lấy lịch sử thanh toán cho một ghi danh cụ thể.
     *
     * @param  int  $enrollmentId
     * @return \Illuminate\Http\Response
     */
    public function getPaymentHistory($enrollmentId)
    {
        try {
            $enrollment = Enrollment::with(['student', 'courseItem', 'payments'])
                ->findOrFail($enrollmentId);
            
            $payments = $enrollment->payments()->orderBy('payment_date', 'desc')->get();
            $totalPaid = $payments->where('status', 'confirmed')->sum('amount');
            $remaining = $enrollment->final_fee - $totalPaid;
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'enrollment' => $enrollment,
                        'payments' => $payments,
                        'total_paid' => $totalPaid,
                        'remaining' => $remaining
                    ]
                ]);
            }
            
            return view('payments.history', [
                'enrollment' => $enrollment,
                'payments' => $payments,
                'totalPaid' => $totalPaid,
                'remaining' => $remaining
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi tải lịch sử thanh toán: ' . $e->getMessage(), [
                'enrollment_id' => $enrollmentId,
                'trace' => $e->getTraceAsString()
            ]);
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }
}
