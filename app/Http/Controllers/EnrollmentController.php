<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Student;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\CourseItem;
use App\Models\LearningPathProgress;
use App\Enums\EnrollmentStatus;

class EnrollmentController extends Controller
{
    protected $enrollmentService;

    public function __construct(EnrollmentService $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }

    /**
     * Hiển thị danh sách ghi danh
     */
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->status,
            'course_item_id' => $request->course_item_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'student_id' => $request->student_id,
            'search' => $request->search,
            'payment_status' => $request->payment_status,
            'needs_contact' => $request->filled('needs_contact') ? true : false
        ];

        $enrollments = $this->enrollmentService->getEnrollments($filters);
        
        // Tính toán thống kê
        $stats = $this->enrollmentService->getEnrollmentsPaymentStats();
        
        $courseItems = CourseItem::where('is_leaf', true)
            ->where('active', true)
            ->orderBy('name')
            ->get();
            
        $students = Student::orderBy('full_name')->get();

        return view('enrollments.index', [
            'enrollments' => $enrollments,
            'courseItems' => $courseItems,
            'students' => $students,
            'totalPaid' => $stats['totalPaid'],
            'totalFees' => $stats['totalFees'],
            'totalUnpaid' => $stats['totalUnpaid'],
            'pendingCount' => $stats['pendingCount']
        ]);
    }

    /**
     * Hiển thị danh sách học viên chưa thanh toán đủ học phí
     */
    public function unpaidList()
    {
        $enrollments = $this->enrollmentService->getUnpaidEnrollments();
        
        // Nhóm các ghi danh theo học viên
        $studentEnrollments = [];
        
        foreach ($enrollments as $enrollment) {
            $studentId = $enrollment->student_id;
            $totalPaid = $enrollment->payments->sum('amount');
            $remaining = $enrollment->final_fee - $totalPaid;
            
            if (!isset($studentEnrollments[$studentId])) {
                $studentEnrollments[$studentId] = [
                    'student' => $enrollment->student,
                    'enrollments' => [],
                    'total_remaining' => 0
                ];
            }
            
            $studentEnrollments[$studentId]['enrollments'][] = [
                'enrollment' => $enrollment,
                'fee' => $enrollment->final_fee,
                'paid' => $totalPaid,
                'remaining' => $remaining
            ];
            
            $studentEnrollments[$studentId]['total_remaining'] += $remaining;
        }
        
        return view('enrollments.unpaid', compact('studentEnrollments'));
    }

    /**
     * Hiển thị danh sách học viên đang trong trạng thái chờ
     */
    public function waitingList(Request $request)
    {
        $filters = [
            'course_item_id' => $request->course_item_id,
            'search' => $request->search
        ];
        
        $waitingEnrollments = $this->enrollmentService->getWaitingList($filters);
        $courseItems = CourseItem::where('is_leaf', true)->where('active', true)->orderBy('name')->get();
        
        return view('enrollments.waiting', compact('waitingEnrollments', 'courseItems'));
    }

    /**
     * Hiển thị danh sách chờ liên hệ
     */
    public function needsContact()
    {
        $waitingEnrollments = $this->enrollmentService->getNeedsContactEnrollments();
        
        return view('enrollments.needs-contact', compact('waitingEnrollments'));
    }

    /**
     * Hiển thị form ghi danh mới
     * 
     * @deprecated Sử dụng modal popup thay vì redirect
     */
    public function create()
    {
        // Phương thức này không còn được sử dụng, chuyển sang modal popup
        return redirect()->route('enrollments.index');
    }

    /**
     * Lưu ghi danh mới
     * 
     * @deprecated Sử dụng API endpoint thay vì form submit
     */
    public function store(Request $request)
    {
        // Phương thức này không còn được sử dụng, chuyển sang API endpoint
        return redirect()->route('enrollments.index');
    }

    /**
     * Hiển thị chi tiết ghi danh
     * 
     * @deprecated Sử dụng modal popup thay vì redirect
     */
    public function show(Enrollment $enrollment)
    {
        // Phương thức này không còn được sử dụng, chuyển sang modal popup
        return redirect()->route('enrollments.index');
    }

    /**
     * Hiển thị form chỉnh sửa ghi danh
     * 
     * @deprecated Sử dụng modal popup thay vì redirect
     */
    public function edit(Enrollment $enrollment)
    {
        // Phương thức này không còn được sử dụng, chuyển sang modal popup
        return redirect()->route('enrollments.index');
    }

    /**
     * Cập nhật ghi danh
     * 
     * @deprecated Sử dụng API endpoint thay vì form submit
     */
    public function update(Request $request, Enrollment $enrollment)
    {
        // Phương thức này không còn được sử dụng, chuyển sang API endpoint
        return redirect()->route('enrollments.index');
    }

    /**
     * Xóa ghi danh
     */
    public function destroy(Enrollment $enrollment)
    {
        try {
            $this->enrollmentService->deleteEnrollment($enrollment);
            
            return redirect()->route('enrollments.index')
                           ->with('success', 'Ghi danh đã được xóa thành công!');
        } catch (\Exception $e) {
            Log::error('Enrollment deletion error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Cập nhật học phí
     */
    public function updateFee(Request $request)
    {
        $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'new_fee' => 'required|numeric|min:0',
        ]);
        
        try {
            $enrollment = $this->enrollmentService->updateFee(
                $request->enrollment_id,
                $request->new_fee
            );
            
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Học phí đã được cập nhật thành công!',
                    'new_fee' => number_format($enrollment->final_fee),
                    'enrollment' => $enrollment
                ]);
            }
            
            return redirect()->back()->with('success', 'Học phí đã được cập nhật thành công!');
        } catch (\Exception $e) {
            Log::error('Fee update error: ' . $e->getMessage());
            
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Xác nhận ghi danh từ trạng thái chờ
     */
    public function confirmFromWaiting(Enrollment $enrollment)
    {
        try {
            if ($enrollment->status !== EnrollmentStatus::WAITING->value) {
                return redirect()->back()
                    ->withErrors(['error' => 'Chỉ có thể xác nhận từ trạng thái chờ!']);
            }
            
            $enrollment = $this->enrollmentService->moveFromWaitingToEnrolled($enrollment);
            
            return redirect()->route('enrollments.show', $enrollment->id)
                ->with('success', 'Đã chuyển trạng thái sang Đang học thành công!');
        } catch (\Exception $e) {
            Log::error('Enrollment confirmation error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Thêm ghi chú cho ghi danh đang chờ
     */
    public function addWaitingNote(Request $request, Enrollment $enrollment)
    {
        $request->validate([
            'notes' => 'required|string'
        ]);
        
        try {
            if ($enrollment->status !== EnrollmentStatus::WAITING->value) {
                return redirect()->back()
                    ->withErrors(['error' => 'Chỉ có thể thêm ghi chú cho trạng thái chờ!']);
            }
            
            $this->enrollmentService->addWaitingNote($enrollment, $request->notes);
            
            return redirect()->back()
                ->with('success', 'Đã thêm ghi chú thành công!');
        } catch (\Exception $e) {
            Log::error('Add waiting note error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Thêm yêu cầu ghi danh vào danh sách chờ
     */
    public function moveToWaiting(Request $request)
    {
        $validatedData = $request->validate([
            'student_id' => 'required|exists:students,id',
            'course_item_id' => 'required|exists:course_items,id',
            'notes' => 'nullable|string',
        ]);
        
        try {
            $enrollment = $this->enrollmentService->moveToWaiting($validatedData);
            
            return redirect()->route('enrollments.waiting')
                ->with('success', 'Đã thêm học viên vào danh sách chờ thành công!');
        } catch (\Exception $e) {
            Log::error('Move to waiting error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }
}
