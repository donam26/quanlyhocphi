<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EnrollmentController extends Controller
{
    protected $enrollmentService;

    public function __construct(EnrollmentService $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }

    /**
     * Tạo ghi danh mới qua API
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'course_item_id' => 'required|exists:course_items,id',
            'enrollment_date' => 'required|date',
            'status' => 'required|in:active,waiting,completed,cancelled',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->first()
            ], 422);
        }

        // Kiểm tra xem học viên đã đăng ký khóa học này chưa
        $existingEnrollment = Enrollment::where('student_id', $request->student_id)
            ->where('course_item_id', $request->course_item_id)
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Học viên này đã đăng ký khóa học này rồi'
            ], 200);
        }

        try {
            DB::beginTransaction();

            // Tạo dữ liệu ghi danh mới
            $enrollmentData = $request->only([
                'student_id', 'course_item_id', 'enrollment_date', 'status',
                'discount_percentage', 'discount_amount', 'notes'
            ]);

            $enrollment = $this->enrollmentService->createEnrollment($enrollmentData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký khóa học thành công',
                'data' => $enrollment
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('API Enrollment creation error: ' . $e->getMessage());
            logger('123');
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách ghi danh của học viên
     */
    public function getStudentEnrollments($studentId)
    {
        try {
            $student = Student::findOrFail($studentId);
            $enrollments = $this->enrollmentService->getStudentEnrollments($student);

            return response()->json([
                'success' => true,
                'data' => $enrollments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy học viên hoặc có lỗi xảy ra'
            ], 404);
        }
    }

    /**
     * Lấy thông tin chi tiết ghi danh
     */
    public function getInfo($id)
    {
        try {
            $enrollment = Enrollment::with(['student', 'courseItem', 'payments' => function($query) {
                $query->orderBy('payment_date', 'desc');
            }])->findOrFail($id);

            // Thêm thông tin bổ sung
            $enrollment->formatted_enrollment_date = $enrollment->enrollment_date ? $enrollment->enrollment_date->format('d/m/Y') : null;
            $enrollment->total_paid = $enrollment->getTotalPaidAmount();
            $enrollment->remaining_amount = $enrollment->getRemainingAmount();
            $enrollment->is_fully_paid = $enrollment->isFullyPaid();

            return response()->json([
                'success' => true,
                'data' => $enrollment
            ]);
        } catch (\Exception $e) {
            Log::error('Enrollment API error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin ghi danh
     */
    public function update(Request $request, $id)
    {
        try {
            $enrollment = Enrollment::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'enrollment_date' => 'required|date',
                'status' => 'required|in:active,waiting,completed,cancelled',
                'discount_percentage' => 'nullable|numeric|min:0|max:100',
                'discount_amount' => 'nullable|numeric|min:0',
                'final_fee' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Cập nhật thông tin
            $enrollment->update($request->only([
                'enrollment_date', 'status', 'discount_percentage',
                'discount_amount', 'final_fee', 'notes'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật ghi danh thành công',
                'data' => $enrollment->fresh(['student', 'courseItem', 'payments'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy tất cả thanh toán của một ghi danh
     */
    public function getPayments($id)
    {
        try {
            $enrollment = Enrollment::with(['payments.enrollment', 'student', 'courseItem'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'enrollment' => $enrollment,
                    'payments' => $enrollment->payments
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Enrollment payments API error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hủy đăng ký (xóa khỏi danh sách chờ)
     */
    public function cancelEnrollment(Request $request, $id)
    {
        try {
            $enrollment = Enrollment::findOrFail($id);

            // Chỉ cho phép hủy nếu đang ở trạng thái chờ
            if ($enrollment->status !== 'waiting') {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể hủy đăng ký khi đang ở trạng thái chờ!'
                ], 422);
            }

            DB::beginTransaction();

            // Cập nhật trạng thái thành cancelled
            $enrollment->status = 'cancelled';
            $enrollment->cancelled_at = now();
            
            // Thêm lý do hủy vào ghi chú
            if ($request->filled('reason')) {
                $currentNotes = $enrollment->notes ? $enrollment->notes . "\n" : '';
                $enrollment->notes = $currentNotes . '[' . now()->format('d/m/Y H:i') . '] Hủy: ' . $request->reason;
            }

            $enrollment->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã hủy đăng ký thành công!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cancel enrollment error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
