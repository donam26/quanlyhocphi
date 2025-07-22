<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CourseItem; // Added this import

class EnrollmentController extends Controller
{
    /**
     * Hiển thị danh sách ghi danh
     */
    public function index(Request $request)
    {
        $query = Enrollment::with(['student', 'courseItem', 'payments']);

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Lọc theo khóa học
        if ($request->filled('course_item_id')) {
            $query->where('course_item_id', $request->course_item_id);
        }

        // Lọc theo ngày ghi danh
        if ($request->filled('date_from')) {
            $query->where('enrollment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('enrollment_date', '<=', $request->date_to);
        }

        // Lọc theo tìm kiếm
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $enrollments = $query->latest()->paginate(15);

        return view('enrollments.index', compact('enrollments'));
    }

    /**
     * Hiển thị danh sách học viên chưa thanh toán đủ
     */
    public function unpaidList()
    {
        // Lấy danh sách ghi danh có trạng thái "enrolled" (đang học)
        $enrollments = Enrollment::where('status', 'enrolled')
                                ->with(['student', 'courseItem', 'payments' => function ($query) {
                                    $query->where('status', 'confirmed'); // Chỉ lấy các thanh toán đã xác nhận
                                }])
                                ->get();
        
        // Lọc các ghi danh chưa thanh toán đủ
        $unpaidEnrollments = $enrollments->filter(function ($enrollment) {
            $paidAmount = $enrollment->payments->sum('amount');
            return $paidAmount < $enrollment->final_fee; // Sử dụng final_fee thay vì fee cố định
        });
        
        // Nhóm theo student_id để tạo danh sách học viên
        $studentEnrollments = [];
        foreach ($unpaidEnrollments as $enrollment) {
            $studentId = $enrollment->student_id;
            if (!isset($studentEnrollments[$studentId])) {
                $studentEnrollments[$studentId] = [
                    'student' => $enrollment->student,
                    'enrollments' => [],
                    'total_fee' => 0,
                    'total_paid' => 0,
                    'total_remaining' => 0
                ];
            }
            
            $paidAmount = $enrollment->payments->sum('amount');
            $remainingAmount = $enrollment->final_fee - $paidAmount;
            
            $studentEnrollments[$studentId]['enrollments'][] = [
                'enrollment' => $enrollment,
                'course_name' => $enrollment->courseItem->name,
                'fee' => $enrollment->final_fee, // Học phí cá nhân hóa
                'paid' => $paidAmount,
                'remaining' => $remainingAmount
            ];
            
            $studentEnrollments[$studentId]['total_fee'] += $enrollment->final_fee;
            $studentEnrollments[$studentId]['total_paid'] += $paidAmount;
            $studentEnrollments[$studentId]['total_remaining'] += $remainingAmount;
        }
        
        // Sắp xếp theo tổng số tiền còn lại giảm dần
        uasort($studentEnrollments, function ($a, $b) {
            return $b['total_remaining'] <=> $a['total_remaining'];
        });
        
        return view('enrollments.unpaid', compact('studentEnrollments'));
    }

    /**
     * Hiển thị form tạo ghi danh mới
     */
    public function create()
    {
        $students = Student::all();
        $courseItems = CourseItem::where('is_leaf', true)->where('active', true)->get();
        
        return view('enrollments.create', compact('students', 'courseItems'));
    }

    /**
     * Lưu ghi danh mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'course_item_id' => 'required|exists:course_items,id',
            'enrollment_date' => 'required|date',
            'final_fee' => 'required|numeric|min:0',
            'status' => 'required|in:enrolled,cancelled',
            'notes' => 'nullable|string',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0'
        ]);
        
        $enrollment = Enrollment::create([
            'student_id' => $request->student_id,
            'course_item_id' => $request->course_item_id,
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
        $enrollment->load(['student', 'courseItem', 'payments']);
        return view('enrollments.show', compact('enrollment'));
    }

    /**
     * Hiển thị form chỉnh sửa ghi danh
     */
    public function edit(Enrollment $enrollment)
    {
        $students = Student::all();
        $courseItems = CourseItem::where('is_leaf', true)->where('active', true)->get();
        
        return view('enrollments.edit', compact('enrollment', 'students', 'courseItems'));
    }

    /**
     * Cập nhật ghi danh
     */
    public function update(Request $request, Enrollment $enrollment)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'course_item_id' => 'required|exists:course_items,id',
            'enrollment_date' => 'required|date',
            'final_fee' => 'required|numeric|min:0',
            'status' => 'required|in:enrolled,cancelled',
            'notes' => 'nullable|string',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0'
        ]);
        
        $enrollment->update([
            'student_id' => $request->student_id,
            'course_item_id' => $request->course_item_id,
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

    /**
     * Cập nhật học phí cho ghi danh
     */
    public function updateFee(Request $request)
    {
        $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'discount_type' => 'required|in:none,percentage,fixed,custom',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'final_fee' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string'
        ]);
        
        $enrollment = Enrollment::with('courseItem')->findOrFail($request->enrollment_id);
        $originalFee = $enrollment->courseItem->fee;
        
        // Tính học phí cuối cùng dựa trên loại chiết khấu
        switch ($request->discount_type) {
            case 'none':
                $finalFee = $originalFee;
                $discountPercentage = 0;
                $discountAmount = 0;
                break;
                
            case 'percentage':
                $discountPercentage = $request->discount_percentage;
                $discountAmount = ($originalFee * $discountPercentage) / 100;
                $finalFee = $originalFee - $discountAmount;
                break;
                
            case 'fixed':
                $discountAmount = $request->discount_amount;
                $discountPercentage = $originalFee > 0 ? ($discountAmount / $originalFee) * 100 : 0;
                $finalFee = $originalFee - $discountAmount;
                break;
                
            case 'custom':
                $finalFee = $request->final_fee;
                $discountAmount = $originalFee - $finalFee;
                $discountPercentage = $originalFee > 0 ? ($discountAmount / $originalFee) * 100 : 0;
                break;
        }
        
        // Cập nhật ghi danh
        $enrollment->update([
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'final_fee' => $finalFee,
            'notes' => $enrollment->notes . "\n[" . now()->format('d/m/Y H:i') . "] Điều chỉnh học phí: " . $request->reason
        ]);
        
        return redirect()->back()->with('success', 'Đã cập nhật học phí thành công!');
    }
}
