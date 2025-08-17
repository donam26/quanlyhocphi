<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\CourseItem;
use App\Services\EnrollmentService;
use App\Enums\EnrollmentStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EnrollmentController extends Controller
{
    protected $enrollmentService;

    public function __construct(EnrollmentService $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }

    /**
     * Lấy danh sách ghi danh với filter
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'status', 'course_item_id', 'student_id', 'search',
                'per_page', 'needs_contact', 'date_from', 'date_to'
            ]);

            $enrollments = $this->enrollmentService->getEnrollments($filters);

            return response()->json([
                'success' => true,
                'data' => $enrollments,
                'message' => 'Lấy danh sách ghi danh thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách ghi danh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy chi tiết ghi danh
     */
    public function show($id)
    {
        try {
            $enrollment = $this->enrollmentService->getEnrollment($id);

            return response()->json([
                'success' => true,
                'data' => $enrollment,
                'message' => 'Lấy chi tiết ghi danh thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy chi tiết ghi danh: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Tạo ghi danh mới
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'student_id' => 'required|exists:students,id',
                'course_item_id' => 'required|exists:course_items,id',
                'enrollment_date' => 'required|date_format:d/m/Y',
                'status' => ['required', Rule::in(['waiting', 'active', 'completed', 'cancelled'])],
                'discount_percentage' => 'nullable|numeric|min:0|max:100',
                'discount_amount' => 'nullable|numeric|min:0',
                'final_fee' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:1000'
            ]);

            // Convert dd/mm/yyyy to Y-m-d format for database
            if (isset($validated['enrollment_date'])) {
                $validated['enrollment_date'] = Carbon::createFromFormat('d/m/Y', $validated['enrollment_date'])->format('Y-m-d');
            }

            $enrollment = $this->enrollmentService->createEnrollment($validated);

            return response()->json([
                'success' => true,
                'data' => $enrollment,
                'message' => 'Tạo ghi danh thành công'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo ghi danh: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Cập nhật ghi danh
     */
    public function update(Request $request, $id)
    {
        try {
            Log::info('Update enrollment request data:', $request->all());

            $validated = $request->validate([
                'enrollment_date' => 'sometimes|date_format:d/m/Y',
                'status' => ['sometimes', Rule::in(['waiting', 'active', 'completed', 'cancelled'])],
                'discount_percentage' => 'nullable|numeric|min:0|max:100',
                'discount_amount' => 'nullable|numeric|min:0',
                'final_fee' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:1000'
            ]);

            // Convert dd/mm/yyyy to Y-m-d format for database
            if (isset($validated['enrollment_date'])) {
                $validated['enrollment_date'] = Carbon::createFromFormat('d/m/Y', $validated['enrollment_date'])->format('Y-m-d');
            }

            Log::info('Validated data:', $validated);

            $enrollment = Enrollment::findOrFail($id);
            $enrollment = $this->enrollmentService->updateEnrollment($enrollment, $validated);

            return response()->json([
                'success' => true,
                'data' => $enrollment,
                'message' => 'Cập nhật ghi danh thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật ghi danh: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Xóa ghi danh
     */
    public function destroy($id)
    {
        try {
            $this->enrollmentService->deleteEnrollment($id);

            return response()->json([
                'success' => true,
                'message' => 'Xóa ghi danh thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa ghi danh: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Chuyển từ danh sách chờ sang ghi danh chính thức
     */
    public function confirmFromWaiting($id)
    {
        try {
            $enrollment = Enrollment::findOrFail($id);


            $confirmedEnrollment = $this->enrollmentService->moveFromWaitingToEnrolled($enrollment);

            return response()->json([
                'success' => true,
                'data' => $confirmedEnrollment,
                'message' => 'Xác nhận ghi danh thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xác nhận ghi danh: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Hủy ghi danh
     */
    public function cancel($id)
    {
        try {
            $enrollment = Enrollment::findOrFail($id);

            $enrollment->updateStatus(EnrollmentStatus::CANCELLED->value);
            $enrollment->cancelled_at = now();
            $enrollment->save();

            return response()->json([
                'success' => true,
                'data' => $enrollment->load(['student', 'courseItem']),
                'message' => 'Hủy ghi danh thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi hủy ghi danh: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Chuyển ghi danh về danh sách chờ
     */
    public function moveToWaiting(Request $request, $id)
    {
        try {
            $enrollment = Enrollment::findOrFail($id);
            $reason = $request->input('reason', 'Chuyển về danh sách chờ');

            $enrollment->updateStatus(EnrollmentStatus::WAITING->value);
            $enrollment->notes = $enrollment->notes ? $enrollment->notes . "\n" . $reason : $reason;
            $enrollment->save();

            return response()->json([
                'success' => true,
                'data' => $enrollment->load(['student', 'courseItem']),
                'message' => 'Chuyển về danh sách chờ thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi chuyển về danh sách chờ: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Lấy danh sách chờ theo khóa học
     */
    public function getWaitingListByCourse($courseId)
    {
        try {
            $enrollments = Enrollment::where('course_item_id', $courseId)
                ->where('status', EnrollmentStatus::WAITING->value)
                ->with(['student', 'courseItem'])
                ->orderBy('request_date', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $enrollments,
                'message' => 'Lấy danh sách chờ thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách chờ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy cây khóa học với số lượng chờ
     */
    public function getWaitingListTree()
    {
        try {
            $tree = $this->enrollmentService->getWaitingListTree();

            return response()->json([
                'success' => true,
                'data' => $tree,
                'message' => 'Lấy cây danh sách chờ thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy cây danh sách chờ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy học viên đang chờ theo khóa học
     */
    public function getWaitingStudentsByCourse($courseId, Request $request)
    {
        try {
            $courseItem = CourseItem::findOrFail($courseId);
            $filters = $request->only(['search', 'per_page']);
            $filters['status'] = EnrollmentStatus::WAITING->value;

            // Nếu là khóa cha (có khóa con), lấy tất cả học viên từ các khóa con
            if ($courseItem->children()->exists()) {
                $childCourseIds = $courseItem->children()->pluck('id')->toArray();
                $filters['course_item_ids'] = $childCourseIds; // Sử dụng array để filter nhiều khóa
            } else {
                // Nếu là khóa con hoặc khóa độc lập
                $filters['course_item_id'] = $courseId;
            }

            $enrollments = $this->enrollmentService->getEnrollments($filters);

            // Transform data để frontend dễ sử dụng
            $transformedData = collect($enrollments->items())->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'student_id' => $enrollment->student_id,
                    'course_item_id' => $enrollment->course_item_id,
                    'student_name' => $enrollment->student ?
                        trim($enrollment->student->first_name . ' ' . $enrollment->student->last_name) : 'N/A',
                    'student_phone' => $enrollment->student->phone ?? null,
                    'student_email' => $enrollment->student->email ?? null,
                    'course_name' => $enrollment->courseItem->name ?? 'N/A',
                    'final_fee' => $enrollment->final_fee,
                    'status' => $enrollment->status,
                    'enrollment_date' => $enrollment->enrollment_date,
                    'notes' => $enrollment->notes,
                    'student' => $enrollment->student,
                    'courseItem' => $enrollment->courseItem
                ];
            });

            // Tạo response với pagination info
            $response = [
                'data' => $transformedData,
                'current_page' => $enrollments->currentPage(),
                'last_page' => $enrollments->lastPage(),
                'per_page' => $enrollments->perPage(),
                'total' => $enrollments->total(),
                'from' => $enrollments->firstItem(),
                'to' => $enrollments->lastItem(),
            ];

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Lấy danh sách học viên chờ thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách học viên chờ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Chuyển học viên sang khóa học khác
     */
    public function transferStudent(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'target_course_id' => 'required|exists:course_items,id',
                'notes' => 'nullable|string|max:1000'
            ]);

            $enrollment = $this->enrollmentService->transferStudent($id, $validated);

            return response()->json([
                'success' => true,
                'data' => $enrollment,
                'message' => 'Chuyển học viên thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi chuyển học viên: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Thống kê ghi danh
     */
    public function getStats(Request $request)
    {
        try {
            $stats = $this->enrollmentService->getEnrollmentStats($request->all());

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Lấy thống kê ghi danh thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xuất danh sách ghi danh
     */
    public function export(Request $request)
    {
        try {
            $request->validate([
                'columns' => 'array',
                'columns.*' => 'string',
                'search' => 'nullable|string',
                'status' => 'nullable|in:waiting,active,completed,cancelled',
                'payment_status' => 'nullable|in:unpaid,partial,paid,no_fee',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'course_item_id' => 'nullable|integer'
            ]);

            $columns = $request->input('columns', [
                'student_name', 'student_phone', 'course_name',
                'enrollment_date', 'status', 'final_fee', 'payment_status'
            ]);

            $filters = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'payment_status' => $request->input('payment_status'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'course_item_id' => $request->input('course_item_id')
            ];

            $fileName = 'danh_sach_ghi_danh_' . date('Y_m_d_H_i_s') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\EnrollmentExport($columns, $filters),
                $fileName
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xuất dữ liệu ghi danh: ' . $e->getMessage()
            ], 500);
        }
    }
}