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
            
        $students = Student::orderBy('first_name')->orderBy('last_name')->get();

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
     * Hiển thị danh sách lớp học chưa thanh toán đủ học phí
     */
    public function unpaidList()
    {
        $enrollments = $this->enrollmentService->getUnpaidEnrollments();
        
        // Nhóm các ghi danh theo khóa học (lớp)
        $courseEnrollments = [];
        
        foreach ($enrollments as $enrollment) {
            $courseItemId = $enrollment->course_item_id;
            $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
            $remaining = $enrollment->final_fee - $totalPaid;
            
            if (!isset($courseEnrollments[$courseItemId])) {
                $courseEnrollments[$courseItemId] = [
                    'course_item' => $enrollment->courseItem,
                    'enrollments' => [],
                    'total_students' => 0,
                    'total_fee' => 0,
                    'total_paid' => 0,
                    'total_remaining' => 0,
                    'unpaid_students' => 0
                ];
            }
            
            $courseEnrollments[$courseItemId]['enrollments'][] = [
                'enrollment' => $enrollment,
                'student' => $enrollment->student,
                'fee' => $enrollment->final_fee,
                'paid' => $totalPaid,
                'remaining' => $remaining
            ];
            
            $courseEnrollments[$courseItemId]['total_students']++;
            $courseEnrollments[$courseItemId]['total_fee'] += $enrollment->final_fee;
            $courseEnrollments[$courseItemId]['total_paid'] += $totalPaid;
            $courseEnrollments[$courseItemId]['total_remaining'] += $remaining;
            
            if ($remaining > 0) {
                $courseEnrollments[$courseItemId]['unpaid_students']++;
            }
        }
        
        // Sắp xếp theo số tiền còn thiếu giảm dần
        uasort($courseEnrollments, function($a, $b) {
            return $b['total_remaining'] <=> $a['total_remaining'];
        });
        
        return view('enrollments.unpaid', compact('courseEnrollments'));
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
    public function confirmFromWaiting(Request $request, Enrollment $enrollment)
    {
        try {
            if ($enrollment->status !== 'waiting') {
                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Chỉ có thể xác nhận từ trạng thái chờ!'
                    ], 422);
                }
                return redirect()->back()
                    ->withErrors(['error' => 'Chỉ có thể xác nhận từ trạng thái chờ!']);
            }
            
            DB::beginTransaction();
            
            // Cập nhật trạng thái
            $enrollment->status = 'enrolled';
            $enrollment->confirmation_date = now();
            
            // Xử lý chiết khấu
            if ($request->filled('discount_percentage')) {
                $discountPercentage = min(100, max(0, $request->discount_percentage));
                $enrollment->discount_percentage = $discountPercentage;
                $enrollment->discount_amount = ($enrollment->final_fee * $discountPercentage) / 100;
                $enrollment->final_fee = $enrollment->final_fee - $enrollment->discount_amount;
            }
            
            // Xử lý chuyển khóa học
            if ($request->filled('new_course_id')) {
                $newCourseId = $request->new_course_id;
                $newCourse = \App\Models\CourseItem::find($newCourseId);
                
                if ($newCourse && $newCourse->is_leaf && $newCourse->active) {
                    $enrollment->course_item_id = $newCourseId;
                    // Cập nhật học phí theo khóa học mới nếu chưa có chiết khấu
                    if (!$request->filled('discount_percentage') && $newCourse->fee) {
                        $enrollment->final_fee = $newCourse->fee;
                    }
                }
            }
            
            // Thêm ghi chú nếu có
            if ($request->filled('notes')) {
                $currentNotes = $enrollment->notes ? $enrollment->notes . "\n" : '';
                $enrollment->notes = $currentNotes . '[' . now()->format('d/m/Y H:i') . '] ' . $request->notes;
            }
            
            $enrollment->save();
            
            DB::commit();
            
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đã xác nhận học viên thành công!'
                ]);
            }
            
            return redirect()->route('enrollments.show', $enrollment->id)
                ->with('success', 'Đã chuyển trạng thái sang Đang học thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Enrollment confirmation error: ' . $e->getMessage());
            
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
     * Xác nhận hàng loạt học viên từ danh sách chờ
     */
    public function bulkConfirmWaiting(Request $request)
    {
        $request->validate([
            'enrollment_ids' => 'required|array',
            'enrollment_ids.*' => 'exists:enrollments,id'
        ]);

        try {
            DB::beginTransaction();
            
            $confirmedCount = 0;
            $errors = [];
            
            foreach ($request->enrollment_ids as $enrollmentId) {
                $enrollment = Enrollment::find($enrollmentId);
                
                if ($enrollment && $enrollment->status === 'waiting') {
                    $enrollment->status = 'enrolled';
                    $enrollment->confirmation_date = now();
                    $enrollment->save();
                    $confirmedCount++;
                } else {
                    $errors[] = "Enrollment ID {$enrollmentId} không thể xác nhận";
                }
            }
            
            DB::commit();
            
            $message = "Đã xác nhận {$confirmedCount} học viên thành công!";
            if (!empty($errors)) {
                $message .= " Có " . count($errors) . " lỗi.";
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'confirmed_count' => $confirmedCount,
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk confirmation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
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
            if ($enrollment->status !== 'waiting') {
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
