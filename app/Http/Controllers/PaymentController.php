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
use App\Models\CourseItem; // Added this import
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Khởi tạo controller
     */
    public function __construct()
    {
        // Trang thanh toán trực tiếp không yêu cầu đăng nhập
        $this->middleware('auth')->except(['showDirectPaymentGateway']);
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
     * Hiển thị form thanh toán mới
     */
    public function create(Request $request)
    {
        $enrollment = null;
        if ($request->filled('enrollment_id')) {
            $enrollment = Enrollment::with(['student', 'courseItem'])
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
            'status' => 'required|in:pending,confirmed',
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
            'status' => $validatedData['status'] ?? 'pending', // Sử dụng trạng thái từ form hoặc mặc định 'pending'
            'notes' => $validatedData['notes']
        ]);

        $successMessage = 'Đã tạo thanh toán thành công!';
        $infoMessage = null;

        // Thêm thông báo hướng dẫn theo trạng thái
        if ($payment->status === 'pending') {
            $infoMessage = 'Thanh toán đang ở trạng thái chờ xác nhận. Học viên cần thanh toán qua mã QR hoặc bạn cần xác nhận khi nhận được tiền.';
            return redirect()->route('payment.gateway.show', $payment)
                ->with('success', $successMessage)
                ->with('info', $infoMessage);
        }

        return redirect()->route('payments.show', $payment)
            ->with('success', $successMessage);
    }

    /**
     * Hiển thị chi tiết thanh toán
     */
    public function show(Payment $payment)
    {
        $payment->load(['enrollment.student', 'enrollment.courseItem']);

        return view('payments.show', compact('payment'));
    }

    /**
     * Hiển thị form chỉnh sửa thanh toán
     */
    public function edit(Payment $payment)
    {
        $payment->load(['enrollment.student', 'enrollment.courseItem']);

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
        $payments = Payment::with(['enrollment.student', 'enrollment.courseItem'])
                          ->where('status', 'confirmed')
                          ->whereYear('payment_date', $request->year ?? date('Y'))
                          ->whereMonth('payment_date', $request->month ?? date('m'))
                          ->get();
        
        $total = $payments->sum('amount');
        $count = $payments->count();
        
        return view('payments.monthly-report', compact('payments', 'total', 'count'));
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
     * Gửi email nhắc nhở trực tiếp từ enrollment (không cần payment_id)
     */
    public function sendDirectReminder(Request $request)
    {
        $request->validate([
            'enrollment_ids' => 'required|array|min:1',
            'enrollment_ids.*' => 'exists:enrollments,id'
        ]);
        
        $enrollmentIds = $request->enrollment_ids;
        $sentCount = 0;
        $errors = [];
        
        foreach($enrollmentIds as $enrollmentId) {
            try {
                $enrollment = Enrollment::with('student', 'courseItem')->find($enrollmentId);
                
                // Chỉ gửi nếu có email và học phí còn thiếu
                if ($enrollment && $enrollment->student->email && $enrollment->getRemainingAmount() > 0) {
                    // Tạo dữ liệu tạm thời cho email (không lưu vào DB)
                    $tempPaymentData = [
                        'enrollment' => $enrollment,
                        'amount' => $enrollment->getRemainingAmount(),
                        'id' => 'temp_' . $enrollmentId . '_' . time(),
                    ];
                    
                    Mail::to($enrollment->student->email)->send(new PaymentReminderMail((object)$tempPaymentData));
                    $sentCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "Lỗi khi gửi email cho ghi danh ID: {$enrollmentId}. " . $e->getMessage();
                Log::error("Send Direct Reminder Error for Enrollment ID {$enrollmentId}: " . $e->getMessage());
            }
        }
        
        $message = "Đã gửi thành công {$sentCount} email nhắc nhở.";
        if (!empty($errors)) {
            return redirect()->back()->with('success', $message)->withErrors($errors);
        }
        
        return redirect()->back()->with('success', $message);
    }

    /**
     * Gửi email nhắc nhở cho cả payment_ids và enrollment_ids
     */
    public function sendCombinedReminder(Request $request)
    {
        $validated = $request->validate([
            'payment_ids' => 'nullable|array',
            'payment_ids.*' => 'exists:payments,id',
            'enrollment_ids' => 'nullable|array',
            'enrollment_ids.*' => 'exists:enrollments,id',
        ]);
        
        $paymentIds = $request->payment_ids ?? [];
        $enrollmentIds = $request->enrollment_ids ?? [];
        
        $sentCount = 0;
        $errors = [];
        
        // Xử lý payment_ids
        if (!empty($paymentIds)) {
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
                    Log::error("Send Combined Reminder Error for Payment ID {$paymentId}: " . $e->getMessage());
                }
            }
        }
        
        // Xử lý enrollment_ids
        if (!empty($enrollmentIds)) {
            foreach($enrollmentIds as $enrollmentId) {
                try {
                    $enrollment = Enrollment::with('student', 'courseItem')->find($enrollmentId);
                    
                    // Chỉ gửi nếu có email và học phí còn thiếu
                    if ($enrollment && $enrollment->student->email && $enrollment->getRemainingAmount() > 0) {
                        // Tạo dữ liệu tạm thời cho email (không lưu vào DB)
                        $tempPaymentData = [
                            'enrollment' => $enrollment,
                            'amount' => $enrollment->getRemainingAmount(),
                            'id' => 'temp_' . $enrollmentId . '_' . time(),
                        ];
                        
                        Mail::to($enrollment->student->email)->send(new PaymentReminderMail((object)$tempPaymentData));
                        $sentCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Lỗi khi gửi email cho ghi danh ID: {$enrollmentId}. " . $e->getMessage();
                    Log::error("Send Combined Reminder Error for Enrollment ID {$enrollmentId}: " . $e->getMessage());
                }
            }
        }
        
        $message = "Đã gửi thành công {$sentCount} email nhắc nhở.";
        if (!empty($errors)) {
            return redirect()->back()->with('success', $message)->withErrors($errors);
        }
        
        return redirect()->back()->with('success', $message);
    }

    /**
     * Hiển thị trang thanh toán QR mà không cần payment_id
     */
    public function showDirectPaymentGateway(Request $request, Enrollment $enrollment)
    {
        // Kiểm tra xem học viên có còn nợ học phí không
        $remainingAmount = $enrollment->getRemainingAmount();
        if ($remainingAmount <= 0) {
            return redirect()->route('enrollments.show', $enrollment)
                ->with('info', 'Học viên đã thanh toán đủ học phí.');
        }
        
        // Lấy thông tin học viên và khóa học
        $enrollment->load('student', 'courseItem');
        
        // Tạo dữ liệu tạm thời cho trang thanh toán (không lưu vào DB)
        $tempPayment = [
            'id' => 'direct_' . $enrollment->id . '_' . time(),
            'enrollment' => $enrollment,
            'amount' => $remainingAmount,
            'status' => 'pending',
            'created_at' => now(),
        ];
        
        return view('payments.gateway.direct', [
            'payment' => (object)$tempPayment,
            'enrollment' => $enrollment
        ]);
    }

    /**
     * Lấy lịch sử thanh toán của một ghi danh
     */
    public function getEnrollmentPayments(Enrollment $enrollment)
    {
        $payments = $enrollment->payments()->orderBy('payment_date', 'desc')->get();
        
        // Tính toán số tiền đã đóng và còn lại
        $paidAmount = $payments->where('status', 'confirmed')->sum('amount');
        $remainingAmount = $enrollment->final_fee - $paidAmount;
        
        // Nếu request là AJAX, trả về HTML để hiển thị trong modal
        if (request()->ajax()) {
            return view('payments.partials.enrollment-payments', compact('enrollment', 'payments', 'paidAmount', 'remainingAmount'))->render();
        }
        
        // Nếu không phải AJAX, hiển thị trang đầy đủ
        return view('payments.enrollment', compact('enrollment', 'payments', 'paidAmount', 'remainingAmount'));
    }

    /**
     * In phiếu thu hàng loạt
     */
    public function bulkReceipt(Request $request)
    {
        $ids = explode(',', $request->ids);
        $payments = Payment::whereIn('id', $ids)
                          ->with(['enrollment.student', 'enrollment.courseItem'])
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
        $payment->load(['enrollment.student', 'enrollment.courseItem']);
        
        return view('payments.receipt', compact('payment'));
    }

    /**
     * Hiển thị thanh toán theo khóa học
     */
    public function coursePayments(CourseItem $courseItem)
    {
        // Lấy danh sách ghi danh của khóa học này
        $enrollments = Enrollment::where('course_item_id', $courseItem->id)
                                ->with('student')
                                ->get();
        
        // Lấy tất cả thanh toán liên quan đến các ghi danh này
        $enrollmentIds = $enrollments->pluck('id')->toArray();
        $payments = Payment::whereIn('enrollment_id', $enrollmentIds)
                        ->with('enrollment.student')
                        ->orderBy('payment_date', 'desc')
                        ->get();
        
        // Thống kê thanh toán
        $stats = [
            'total_enrollments' => $enrollments->count(),
            'total_paid' => $payments->where('status', 'confirmed')->sum('amount'),
            'total_fee' => $enrollments->sum('final_fee'),
            'remaining' => $enrollments->sum('final_fee') - $payments->where('status', 'confirmed')->sum('amount'),
        ];
        
        return view('payments.course', compact('courseItem', 'enrollments', 'payments', 'stats'));
    }

    /**
     * Xuất Excel danh sách thanh toán theo khóa học
     */
    public function exportCoursePayments(CourseItem $courseItem)
    {
        // Lấy danh sách ghi danh của khóa học này
        $enrollments = Enrollment::where('course_item_id', $courseItem->id)
                                ->with('student')
                                ->get();
        
        // Tạo dữ liệu cho file Excel
        $fileName = 'thanh-toan-' . Str::slug($courseItem->name) . '-' . date('d-m-Y') . '.xlsx';
        
        return Excel::download(new class($courseItem, $enrollments) implements FromCollection, WithHeadings, ShouldAutoSize {
            protected $courseItem;
            protected $enrollments;
            
            public function __construct($courseItem, $enrollments) {
                $this->courseItem = $courseItem;
                $this->enrollments = $enrollments;
            }
            
            public function collection()
            {
                $data = [];
                $index = 1;
                
                foreach ($this->enrollments as $enrollment) {
                    $paidAmount = $enrollment->getTotalPaidAmount();
                    $remainingAmount = $enrollment->getRemainingAmount();
                    
                    // Chiết khấu
                    $discount = '';
                    if ($enrollment->discount_percentage > 0) {
                        $discount = $enrollment->discount_percentage . '%';
                    } elseif ($enrollment->discount_amount > 0) {
                        $discount = number_format($enrollment->discount_amount) . ' đ';
                    }
                    
                    $data[] = [
                        'STT' => $index++,
                        'Họ và tên' => $enrollment->student->full_name,
                        'SĐT' => $enrollment->student->phone,
                        'Học phí gốc' => number_format($this->courseItem->fee) . ' đ',
                        'Chiết khấu' => $discount,
                        'Học phí cuối' => number_format($enrollment->final_fee) . ' đ',
                        'Đã đóng' => number_format($paidAmount) . ' đ',
                        'Còn lại' => $remainingAmount > 0 ? number_format($remainingAmount) . ' đ' : 'Đã thanh toán đủ'
                    ];
                }
                
                return collect($data);
            }
            
            public function headings(): array
            {
                return [
                    'STT',
                    'Họ và tên',
                    'SĐT',
                    'Học phí gốc',
                    'Chiết khấu',
                    'Học phí cuối',
                    'Đã đóng',
                    'Còn lại'
                ];
            }
        }, 'xlsx');
    }
}
