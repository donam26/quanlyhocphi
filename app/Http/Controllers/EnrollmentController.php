<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    /**
     * Hiển thị danh sách ghi danh
     */
    public function index(Request $request)
    {
        $query = Enrollment::with(['student', 'courseClass.course', 'payments']);
        
        // Áp dụng các bộ lọc
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('full_name', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%");
            });
        }
        
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('payment_status') && $request->payment_status) {
            if ($request->payment_status === 'paid') {
                $query->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") >= enrollments.final_fee');
            } elseif ($request->payment_status === 'partial') {
                $query->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") > 0')
                      ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") < enrollments.final_fee');
            } elseif ($request->payment_status === 'pending') {
                $query->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") = 0');
            }
        }
        
        $enrollments = $query->latest()->paginate(15);
        
        // Thống kê
        $pendingCount = Enrollment::whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") = 0')->count();
        
        $totalPaid = Payment::where('status', 'confirmed')->sum('amount');
        
        // Tính tổng số tiền còn nợ
        $totalFees = Enrollment::sum('final_fee');
        $totalUnpaid = $totalFees - $totalPaid;
        
        return view('enrollments.index', compact('enrollments', 'pendingCount', 'totalPaid', 'totalUnpaid'));
    }

    /**
     * Hiển thị danh sách ghi danh chưa thanh toán
     */
    public function unpaidList()
    {
        $unpaidEnrollments = Enrollment::with(['student', 'courseClass.course', 'payments'])
            ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") < enrollments.final_fee')
            ->latest()
            ->paginate(15);
            
        // Tính tổng số tiền còn nợ và đã đóng
        $totalFees = $unpaidEnrollments->sum('final_fee');
        $totalPaid = 0;
        $totalUnpaid = 0;
        
        foreach ($unpaidEnrollments as $enrollment) {
            $paid = $enrollment->getTotalPaidAmount();
            $totalPaid += $paid;
            $totalUnpaid += $enrollment->getRemainingAmount();
        }
        
        // Tạo mảng thống kê
        $stats = [
            'total_unpaid_count' => $unpaidEnrollments->total(),
            'total_unpaid_amount' => $totalUnpaid,
            'total_paid_amount' => $totalPaid,
            'average_remaining' => $unpaidEnrollments->count() > 0 ? $totalUnpaid / $unpaidEnrollments->count() : 0
        ];
        
        return view('enrollments.unpaid', compact('unpaidEnrollments', 'stats'));
    }

    /**
     * Hiển thị form tạo ghi danh mới
     */
    public function create()
    {
        $students = Student::all();
        $classes = CourseClass::where('status', 'open')->get();
        
        return view('enrollments.create', compact('students', 'classes'));
    }

    /**
     * Lưu ghi danh mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'course_class_id' => 'required|exists:course_classes,id',
            'enrollment_date' => 'required|date',
            'final_fee' => 'required|numeric|min:0',
            'status' => 'required|in:enrolled,cancelled',
            'notes' => 'nullable|string',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0'
        ]);
        
        $enrollment = Enrollment::create([
            'student_id' => $request->student_id,
            'course_class_id' => $request->course_class_id,
            'enrollment_date' => $request->enrollment_date,
            'discount_percentage' => $request->discount_percentage ?? 0,
            'discount_amount' => $request->discount_amount ?? 0,
            'final_fee' => $request->final_fee,
            'status' => $request->status,
            'notes' => $request->notes
        ]);
        
        // Nếu có thanh toán ban đầu
        if ($request->has('initial_payment') && $request->initial_payment > 0) {
            Payment::create([
                'enrollment_id' => $enrollment->id,
                'amount' => $request->initial_payment,
                'payment_date' => now(),
                'payment_method' => $request->payment_method ?? 'cash',
                'status' => 'confirmed',
                'notes' => 'Thanh toán ban đầu'
            ]);
        }
        
        return redirect()->route('enrollments.show', $enrollment)
            ->with('success', 'Ghi danh mới đã được tạo thành công.');
    }

    /**
     * Hiển thị chi tiết ghi danh
     */
    public function show(Enrollment $enrollment)
    {
        $enrollment->load(['student', 'courseClass.course', 'payments']);
        
        return view('enrollments.show', compact('enrollment'));
    }

    /**
     * Hiển thị form chỉnh sửa ghi danh
     */
    public function edit(Enrollment $enrollment)
    {
        $students = Student::all();
        $classes = CourseClass::all();
        
        return view('enrollments.edit', compact('enrollment', 'students', 'classes'));
    }

    /**
     * Cập nhật ghi danh
     */
    public function update(Request $request, Enrollment $enrollment)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'course_class_id' => 'required|exists:course_classes,id',
            'enrollment_date' => 'required|date',
            'final_fee' => 'required|numeric|min:0',
            'status' => 'required|in:enrolled,cancelled',
            'notes' => 'nullable|string',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0'
        ]);
        
        $enrollment->update([
            'student_id' => $request->student_id,
            'course_class_id' => $request->course_class_id,
            'enrollment_date' => $request->enrollment_date,
            'discount_percentage' => $request->discount_percentage ?? 0,
            'discount_amount' => $request->discount_amount ?? 0,
            'final_fee' => $request->final_fee,
            'status' => $request->status,
            'notes' => $request->notes
        ]);
        
        return redirect()->route('enrollments.show', $enrollment)
            ->with('success', 'Thông tin ghi danh đã được cập nhật.');
    }

    /**
     * Xóa ghi danh
     */
    public function destroy(Enrollment $enrollment)
    {
        // Kiểm tra nếu có thanh toán liên quan
        if ($enrollment->payments->count() > 0) {
            return back()->with('error', 'Không thể xóa ghi danh đã có thanh toán.');
        }
        
        $enrollment->delete();
        
        return redirect()->route('enrollments.index')
            ->with('success', 'Ghi danh đã được xóa.');
    }
}
