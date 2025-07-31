<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\CourseItem;
use App\Models\LearningPathProgress;

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
            
            // Nếu là status waiting và cần lọc theo cần liên hệ
            if ($request->status === Enrollment::STATUS_WAITING && $request->filled('needs_contact')) {
                $query->whereNull('notes');
            }
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

        // Lọc theo ID học viên
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
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

        // Lọc theo trạng thái thanh toán
        if ($request->filled('payment_status')) {
            $status = $request->payment_status;
            if ($status === 'paid') {
                $query->whereHas('payments', function ($q) {
                    $q->groupBy('enrollment_id')
                      ->havingRaw('SUM(CASE WHEN status = "confirmed" THEN amount ELSE 0 END) >= enrollments.final_fee');
                });
            } elseif ($status === 'partial') {
                $query->whereHas('payments', function ($q) {
                    $q->where('status', 'confirmed');
                })->whereRaw('(SELECT SUM(amount) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") < enrollments.final_fee');
            } elseif ($status === 'pending') {
                $query->whereDoesntHave('payments', function ($q) {
                    $q->where('status', 'confirmed');
                });
            }
        }

        // Sắp xếp kết quả
        if ($request->status === Enrollment::STATUS_WAITING) {
            $enrollments = $query->latest('request_date')->paginate(15);
        } else {
        $enrollments = $query->latest()->paginate(15);
        }

        // Tính toán thống kê
        $totalPaid = Enrollment::join('payments', 'enrollments.id', '=', 'payments.enrollment_id')
                              ->where('payments.status', 'confirmed')
                              ->sum('payments.amount');
        
        $totalFees = Enrollment::whereIn('status', [Enrollment::STATUS_ACTIVE, Enrollment::STATUS_CONFIRMED])->sum('final_fee');
        $totalUnpaid = max(0, $totalFees - $totalPaid);
        
        $pendingCount = Enrollment::whereIn('status', [Enrollment::STATUS_ACTIVE, Enrollment::STATUS_CONFIRMED])
                                ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") < enrollments.final_fee')
                                ->count();

        return view('enrollments.index', compact('enrollments', 'totalPaid', 'totalUnpaid', 'pendingCount'));
    }

    /**
     * Hiển thị danh sách học viên chưa thanh toán đủ
     */
    public function unpaidList()
    {
        // Lấy danh sách ghi danh có trạng thái "active" hoặc "confirmed"
        $enrollments = Enrollment::whereIn('status', [Enrollment::STATUS_ACTIVE, Enrollment::STATUS_CONFIRMED])
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
     * Hiển thị danh sách chờ
     */
    public function waitingList(Request $request)
    {
        // Chuyển hướng đến trang ghi danh với filter trạng thái là waiting
        return redirect()->route('enrollments.index', ['status' => 'waiting']);
    }

    /**
     * Hiển thị danh sách chờ cần liên hệ
     */
    public function needsContact()
    {
        // Chuyển hướng đến trang ghi danh với filter trạng thái là waiting
        return redirect()->route('enrollments.index', ['status' => 'waiting', 'needs_contact' => 1]);
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
            'status' => 'required|in:pending,waiting,confirmed,active,completed,cancelled',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'final_fee' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        // Kiểm tra xem học viên đã ghi danh vào khóa học này chưa
        $existingEnrollment = Enrollment::where('student_id', $request->student_id)
                                    ->where('course_item_id', $request->course_item_id)
                                    ->first();
        
        if ($existingEnrollment) {
            return back()->withErrors(['error' => 'Học viên này đã được ghi danh vào khóa học này.'])
                        ->withInput();
        }
        
        // Lấy thông tin khóa học
        $courseItem = CourseItem::findOrFail($request->course_item_id);
        
        // Xử lý custom_fields nếu khóa học là đặc biệt
        $customFields = null;
        if ($courseItem->is_special && $courseItem->custom_fields) {
            $customFields = [];
            foreach ($courseItem->custom_fields as $key => $value) {
                $customFields[$key] = ""; // Giá trị ban đầu trống
            }
        }
        
        // Tạo ghi danh mới
        $enrollment = Enrollment::create([
            'student_id' => $request->student_id,
            'course_item_id' => $request->course_item_id,
            'enrollment_date' => $request->enrollment_date,
            'status' => $request->status,
            'request_date' => $request->status === Enrollment::STATUS_WAITING ? now() : null,
            'discount_percentage' => $request->discount_percentage,
            'discount_amount' => $request->discount_amount,
            'final_fee' => $request->final_fee,
            'notes' => $request->notes,
            'custom_fields' => $customFields,
            'last_status_change' => now()
        ]);
        
        // Tạo các bản ghi tiến độ học tập cho ghi danh mới nếu trạng thái là active
        if ($request->status === Enrollment::STATUS_ACTIVE) {
        $this->createLearningPathProgress($enrollment);
        }
        
        return redirect()->route('enrollments.show', $enrollment->id)
                        ->with('success', 'Đã ghi danh học viên thành công.');
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
            'status' => 'required|in:pending,waiting,confirmed,active,completed,cancelled',
            'notes' => 'nullable|string',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'custom_field_keys.*' => 'nullable|string',
            'custom_field_values.*' => 'nullable|string'
        ]);
        
        // Chuyển đổi sang số nguyên để so sánh chính xác
        $newStudentId = (int)$request->student_id;
        $newCourseId = (int)$request->course_item_id;
        $currentStudentId = (int)$enrollment->student_id;
        $currentCourseId = (int)$enrollment->course_item_id;
        
        // Tạo mảng dữ liệu cần cập nhật
        $dataToUpdate = [
            'enrollment_date' => $request->enrollment_date,
            'discount_percentage' => $request->discount_percentage ?? 0,
            'discount_amount' => $request->discount_amount ?? 0,
            'final_fee' => $request->final_fee,
            'notes' => $request->notes,
        ];
        
        // Cập nhật trạng thái nếu thay đổi
        if ($request->status !== $enrollment->status) {
            $enrollment->updateStatus($request->status);
        } else {
            // Nếu không thay đổi trạng thái, cập nhật các trường khác
            $dataToUpdate['status'] = $request->status;
        }
        
        // Chỉ cập nhật student_id và course_item_id khi thực sự thay đổi
        $studentOrCourseChanged = false;
        
        if ($newStudentId !== $currentStudentId) {
            $dataToUpdate['student_id'] = $newStudentId;
            $studentOrCourseChanged = true;
        }
        
        if ($newCourseId !== $currentCourseId) {
            $dataToUpdate['course_item_id'] = $newCourseId;
            $studentOrCourseChanged = true;
        }
       
        
        // Xử lý thông tin tùy chỉnh
        $customFields = $enrollment->custom_fields;
        
        // Nếu thay đổi khóa học và khóa học mới là đặc biệt
        if ($newCourseId !== $currentCourseId) {
            $courseItem = CourseItem::findOrFail($newCourseId);
            if ($courseItem->is_special && !empty($courseItem->custom_fields)) {
                $customFields = [];
                foreach ($courseItem->custom_fields as $key => $value) {
                    $customFields[$key] = "";
                }
            }
        }
        
        // Xử lý các trường tùy chỉnh gửi từ form
        if ($request->has('custom_field_keys')) {
            $keys = $request->input('custom_field_keys', []);
            $values = $request->input('custom_field_values', []);
            
            if (!empty($keys)) {
                $customFields = [];
                foreach ($keys as $index => $key) {
                    if (!empty($key) && isset($values[$index])) {
                        $customFields[$key] = $values[$index];
                    }
                }
            }
        }
        
        // Thêm trường custom_fields vào dữ liệu cập nhật
        $dataToUpdate['custom_fields'] = !empty($customFields) ? $customFields : null;
        
        try {
            // Cập nhật đăng ký
            $enrollment->update($dataToUpdate);
            
            return redirect()->route('enrollments.show', $enrollment)
                ->with('success', 'Thông tin ghi danh đã được cập nhật.');
                
        } catch (\Illuminate\Database\QueryException $e) {
            // Xử lý lỗi integrity constraint violation
            if ($e->errorInfo[1] == 1062) { // Mã lỗi duplicate entry
                return back()->withErrors([
                    'error' => 'Không thể cập nhật vì học viên đã đăng ký khóa học này.'
                ])->withInput();
            }
            
            // Nếu là lỗi khác, ném ra để xử lý ở handler
            throw $e;
        }
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
        
        Log::info('Update Fee Request', $request->all()); // Log request data
        
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
        
        // Trả về JSON nếu là AJAX request, chuyển hướng nếu là request thông thường
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật học phí thành công!',
                'enrollment' => $enrollment
            ]);
        }
        
        return redirect()->back()->with('success', 'Đã cập nhật học phí thành công!');
    }

    /**
     * Chuyển đổi trạng thái của danh sách chờ thành ghi danh xác nhận
     */
    public function confirmFromWaiting(Enrollment $enrollment)
    {
        if (!$enrollment->isWaiting()) {
            return back()->with('error', 'Ghi danh này không phải trong danh sách chờ.');
        }
        
        $enrollment->updateStatus(Enrollment::STATUS_CONFIRMED);
        $enrollment->confirmation_date = now();
        $enrollment->save();
        
        // Tạo các bản ghi tiến độ học tập
        $this->createLearningPathProgress($enrollment);
        
        return redirect()->route('enrollments.show', $enrollment)
            ->with('success', 'Học viên đã được xác nhận ghi danh từ danh sách chờ.');
    }
    
    /**
     * Thêm ghi chú cho danh sách chờ
     */
    public function addWaitingNote(Request $request, Enrollment $enrollment)
    {
        $request->validate([
            'note' => 'required|string'
        ]);
        
        if (!$enrollment->isWaiting()) {
            return back()->with('error', 'Ghi danh này không phải trong danh sách chờ.');
        }
        
        $currentNotes = $enrollment->notes ?? '';
        $newNote = "[" . now()->format('d/m/Y H:i') . "] " . $request->note;
        
        if (!empty($currentNotes)) {
            $enrollment->notes = $currentNotes . "\n" . $newNote;
        } else {
            $enrollment->notes = $newNote;
        }
        
        $enrollment->save();
            
        return back()->with('success', 'Đã thêm ghi chú thành công.');
    }

    /**
     * Chuyển học viên từ trạng thái ghi danh sang danh sách chờ
     */
    public function moveToWaiting(Request $request)
    {
        $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'reason' => 'required|string'
        ]);
        
        $enrollment = Enrollment::findOrFail($request->enrollment_id);
        
        // Lưu trạng thái cũ
        $previousStatus = $enrollment->status;
        
        // Thêm ghi chú về lý do chuyển sang danh sách chờ
        $currentNotes = $enrollment->notes ?? '';
        $newNote = "[" . now()->format('d/m/Y H:i') . "] Chuyển sang danh sách chờ. Lý do: " . $request->reason;
        
        if (!empty($currentNotes)) {
            $enrollment->notes = $currentNotes . "\n" . $newNote;
        } else {
            $enrollment->notes = $newNote;
        }
        
        // Cập nhật trạng thái
        $enrollment->updateStatus(Enrollment::STATUS_WAITING);
        $enrollment->previous_status = $previousStatus;
        $enrollment->request_date = now();
        $enrollment->save();
        
        return redirect()->route('enrollments.show', $enrollment->id)
            ->with('success', 'Học viên đã được chuyển sang danh sách chờ thành công.');
    }

    /**
     * Tạo các bản ghi tiến độ học tập cho ghi danh mới
     */
    private function createLearningPathProgress($enrollment)
    {
        // Lấy danh sách các lộ trình học tập của khóa học
        $learningPaths = $enrollment->courseItem->learningPaths;
        
        // Tạo bản ghi tiến độ cho mỗi lộ trình
        foreach ($learningPaths as $path) {
            LearningPathProgress::create([
                'learning_path_id' => $path->id,
                'enrollment_id' => $enrollment->id,
                'is_completed' => false,
                'completed_at' => null
            ]);
        }
    }
}
