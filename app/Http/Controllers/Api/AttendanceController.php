<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\CourseItem;
use App\Models\Student;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * API: Danh sách điểm danh
     */
    public function index(Request $request)
    {
        try {
            $query = Attendance::with(['enrollment.student', 'enrollment.courseItem']);
            
            // Filters
            if ($request->has('course_item_id')) {
                $query->where('course_item_id', $request->course_item_id);
            }
            
            if ($request->has('student_id')) {
                $query->whereHas('enrollment', function ($q) use ($request) {
                    $q->where('student_id', $request->student_id);
                });
            }
            
            if ($request->has('attendance_date')) {
                $query->whereDate('attendance_date', $request->attendance_date);
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            $attendances = $query->orderBy('attendance_date', 'desc')
                ->paginate($request->input('per_page', 15));
            
            return response()->json([
                'success' => true,
                'data' => $attendances
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách điểm danh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Tạo điểm danh mới
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'enrollment_id' => 'required|exists:enrollments,id',
                'course_item_id' => 'required|exists:course_items,id',
                'attendance_date' => 'required|date',
                'status' => 'required|in:present,absent,late',
                'notes' => 'nullable|string'
            ]);
            
            $attendance = Attendance::create($validated);
            
            return response()->json([
                'success' => true,
                'data' => $attendance->load(['enrollment.student', 'enrollment.courseItem']),
                'message' => 'Điểm danh đã được tạo thành công'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo điểm danh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Chi tiết điểm danh
     */
    public function show(Attendance $attendance)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $attendance->load(['enrollment.student', 'enrollment.courseItem'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy chi tiết điểm danh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Cập nhật điểm danh
     */
    public function update(Request $request, Attendance $attendance)
    {
        try {
            $validated = $request->validate([
                'attendance_date' => 'sometimes|date',
                'status' => 'sometimes|in:present,absent,late',
                'notes' => 'nullable|string'
            ]);
            
            $attendance->update($validated);
            
            return response()->json([
                'success' => true,
                'data' => $attendance->load(['enrollment.student', 'enrollment.courseItem']),
                'message' => 'Điểm danh đã được cập nhật thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật điểm danh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Xóa điểm danh
     */
    public function destroy(Attendance $attendance)
    {
        try {
            $attendance->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Điểm danh đã được xóa thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa điểm danh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Điểm danh theo khóa học
     */
    public function byCourse(CourseItem $courseItem)
    {
        try {
            $attendances = Attendance::where('course_item_id', $courseItem->id)
                ->with(['enrollment.student'])
                ->orderBy('attendance_date', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $attendances
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy điểm danh theo khóa học: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Tạo điểm danh theo khóa học
     */
    public function storeByCourse(Request $request, CourseItem $courseItem)
    {
        try {
            $validated = $request->validate([
                'attendance_date' => 'required|date',
                'attendances' => 'required|array',
                'attendances.*.enrollment_id' => 'required|exists:enrollments,id',
                'attendances.*.status' => 'required|in:present,absent,late',
                'attendances.*.notes' => 'nullable|string'
            ]);

            // Debug: Log dữ liệu nhận được
            \Log::info('=== ATTENDANCE SAVE DEBUG ===');
            \Log::info('Course Item ID: ' . $courseItem->id);
            \Log::info('Course Item Name: ' . $courseItem->name);
            \Log::info('Has Children: ' . ($courseItem->children()->exists() ? 'Yes' : 'No'));
            \Log::info('Validated Data: ', $validated);

            $attendanceDate = Carbon::parse($validated['attendance_date']);
            $createdAttendances = [];
            
            foreach ($validated['attendances'] as $attendanceData) {
                // Lấy enrollment để biết course_item_id thực tế
                $enrollment = \App\Models\Enrollment::find($attendanceData['enrollment_id']);
                if (!$enrollment) {
                    \Log::warning('Enrollment not found: ' . $attendanceData['enrollment_id']);
                    continue; // Skip nếu không tìm thấy enrollment
                }

                \Log::info('Processing attendance for enrollment: ' . $enrollment->id .
                          ', course: ' . $enrollment->course_item_id .
                          ', status: ' . $attendanceData['status']);

                $attendance = Attendance::updateOrCreate(
                    [
                        'enrollment_id' => $attendanceData['enrollment_id'],
                        'course_item_id' => $enrollment->course_item_id, // Sử dụng course_item_id từ enrollment
                        'attendance_date' => $attendanceDate
                    ],
                    [
                        'status' => $attendanceData['status'],
                        'notes' => $attendanceData['notes'] ?? null
                    ]
                );

                \Log::info('Saved attendance: ' . $attendance->id . ', status: ' . $attendance->status);
                $createdAttendances[] = $attendance->load(['enrollment.student']);
            }
            
            return response()->json([
                'success' => true,
                'data' => $createdAttendances,
                'message' => 'Điểm danh đã được lưu thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lưu điểm danh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Điểm danh theo ngày
     */
    public function byDate(CourseItem $courseItem, $date)
    {
        try {
            $attendanceDate = Carbon::parse($date);

            // Debug: Log thông tin khóa học
            \Log::info('=== FETCH ATTENDANCE BY DATE ===');
            \Log::info('Course Item ID: ' . $courseItem->id);
            \Log::info('Course Item Name: ' . $courseItem->name);
            \Log::info('Date: ' . $attendanceDate);
            \Log::info('Has Children: ' . ($courseItem->children()->exists() ? 'Yes' : 'No'));

            // Lấy tất cả ID của khóa học này và các khóa con (đệ quy)
            $allCourseIds = $this->getAllDescendantCourseIds($courseItem);
            \Log::info('All Course IDs: ', $allCourseIds);

            // Nếu có khóa con, lấy từ tất cả khóa con
            if (count($allCourseIds) > 1) {
                // Loại bỏ khóa cha khỏi danh sách (chỉ lấy từ khóa con)
                $childCourseIds = array_filter($allCourseIds, function($id) use ($courseItem) {
                    return $id !== $courseItem->id;
                });
                \Log::info('Child Course IDs: ', $childCourseIds);
                $targetCourseIds = $childCourseIds;
            } else {
                // Nếu là khóa lá (không có con), lấy từ chính khóa đó
                \Log::info('Getting from leaf course: ' . $courseItem->id);
                $targetCourseIds = [$courseItem->id];
            }

            // 1. Lấy tất cả học viên từ các khóa đích
            $allStudents = $this->getStudentsFromCourses($targetCourseIds);
            \Log::info('Found ' . $allStudents->count() . ' students');

            // 2. Lấy attendance records có sẵn
            $attendances = Attendance::whereIn('course_item_id', $targetCourseIds)
                ->whereDate('attendance_date', $attendanceDate)
                ->with(['enrollment.student', 'enrollment.courseItem'])
                ->get();
            \Log::info('Found ' . $attendances->count() . ' attendance records');

            // 3. Tạo map attendance theo enrollment_id
            $attendanceMap = $attendances->keyBy('enrollment_id');

            // 4. Tạo response data bao gồm cả học viên và attendance
            $responseData = [
                'students' => $allStudents,
                'attendances' => $attendances,
                'date' => $attendanceDate->format('Y-m-d'),
                'course_info' => [
                    'id' => $courseItem->id,
                    'name' => $courseItem->name,
                    'has_children' => count($allCourseIds) > 1,
                    'target_course_ids' => $targetCourseIds
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy điểm danh theo ngày: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Điểm danh nhanh
     */
    public function quickAttendance(Request $request)
    {
        try {
            $validated = $request->validate([
                'course_item_id' => 'required|exists:course_items,id',
                'attendance_date' => 'required|date',
                'mark_all_as' => 'required|in:present,absent,late'
            ]);
            
            $courseItem = CourseItem::findOrFail($validated['course_item_id']);
            $enrollments = $courseItem->enrollments()->where('status', 'active')->get();
            
            $attendances = [];
            foreach ($enrollments as $enrollment) {
                $attendance = Attendance::updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'course_item_id' => $courseItem->id,
                        'attendance_date' => $validated['attendance_date']
                    ],
                    [
                        'status' => $validated['mark_all_as']
                    ]
                );
                
                $attendances[] = $attendance->load(['enrollment.student']);
            }
            
            return response()->json([
                'success' => true,
                'data' => $attendances,
                'message' => 'Điểm danh nhanh đã được thực hiện'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi thực hiện điểm danh nhanh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Báo cáo điểm danh học viên
     */
    public function studentReport(Student $student)
    {
        try {
            $attendances = Attendance::whereHas('enrollment', function ($query) use ($student) {
                $query->where('student_id', $student->id);
            })->with(['enrollment.courseItem'])->orderBy('attendance_date', 'desc')->get();

            $summary = [
                'total_sessions' => $attendances->count(),
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'attendance_rate' => $attendances->count() > 0 ?
                    round(($attendances->where('status', 'present')->count() / $attendances->count()) * 100, 2) : 0
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'attendances' => $attendances,
                    'summary' => $summary
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo điểm danh học viên: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Báo cáo điểm danh lớp học
     */
    public function classReport($class)
    {
        try {
            $courseItem = CourseItem::findOrFail($class);

            $attendances = Attendance::where('course_item_id', $courseItem->id)
                ->with(['enrollment.student'])
                ->orderBy('attendance_date', 'desc')
                ->get();

            $summary = [
                'total_sessions' => $attendances->groupBy('attendance_date')->count(),
                'total_students' => $attendances->groupBy('enrollment.student_id')->count(),
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'overall_attendance_rate' => $attendances->count() > 0 ?
                    round(($attendances->where('status', 'present')->count() / $attendances->count()) * 100, 2) : 0
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $courseItem,
                    'attendances' => $attendances,
                    'summary' => $summary
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo điểm danh lớp học: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lưu điểm danh từ tree view
     */
    public function saveFromTree(Request $request)
    {
        try {
            $validated = $request->validate([
                'course_item_id' => 'required|exists:course_items,id',
                'attendance_date' => 'required|date',
                'attendances' => 'required|array',
                'attendances.*.enrollment_id' => 'required|exists:enrollments,id',
                'attendances.*.status' => 'required|in:present,absent,late'
            ]);

            $courseItem = CourseItem::findOrFail($validated['course_item_id']);
            $attendanceDate = Carbon::parse($validated['attendance_date']);

            $savedAttendances = [];
            foreach ($validated['attendances'] as $attendanceData) {
                $attendance = Attendance::updateOrCreate(
                    [
                        'enrollment_id' => $attendanceData['enrollment_id'],
                        'course_item_id' => $courseItem->id,
                        'attendance_date' => $attendanceDate
                    ],
                    [
                        'status' => $attendanceData['status']
                    ]
                );

                $savedAttendances[] = $attendance->load(['enrollment.student']);
            }

            return response()->json([
                'success' => true,
                'data' => $savedAttendances,
                'message' => 'Điểm danh đã được lưu từ tree view'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lưu điểm danh từ tree view: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Xuất dữ liệu điểm danh
     */
    public function export(Request $request)
    {
        try {
            $request->validate([
                'course_item_id' => 'required|exists:course_items,id',
                'columns' => 'array',
                'columns.*' => 'string',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date'
            ]);

            $courseItem = \App\Models\CourseItem::findOrFail($request->course_item_id);

            $columns = $request->input('columns', [
                'student_name', 'student_phone', 'attendance_date',
                'status', 'notes'
            ]);

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Collect filters
            $filters = $request->only([
                'status', 'enrollment_status', 'payment_status'
            ]);

            $fileName = 'diem_danh_khoa_' . $courseItem->id . '_' . date('Y_m_d_H_i_s') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\AttendanceExport($courseItem, $columns, $startDate, $endDate, $filters),
                $fileName
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xuất dữ liệu điểm danh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Xuất dữ liệu điểm danh dạng ma trận
     */
    public function exportMatrix(Request $request)
    {
        try {
            $request->validate([
                'course_item_id' => 'required|exists:course_items,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date'
            ]);

            $courseItem = CourseItem::findOrFail($request->course_item_id);
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $fileName = 'diem_danh_ma_tran_khoa_' . $courseItem->id . '_' . date('Y_m_d_H_i_s') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\AttendanceMatrixExport($courseItem, $startDate, $endDate),
                $fileName
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xuất ma trận điểm danh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy cây khóa học cho điểm danh
     */
    public function getAttendanceTree()
    {
        try {
            // Get all courses with relationships
            $allCourses = CourseItem::with(['parent', 'children', 'enrollments.student'])->get();

            // Build tree structure with student counts
            $tree = $this->buildCourseTreeWithStudentCounts($allCourses);

            return response()->json([
                'success' => true,
                'data' => $tree
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy cây khóa học: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy danh sách học viên theo khóa học cho điểm danh
     */
    public function getStudentsByCourse(CourseItem $courseItem)
    {
        try {
            // Debug: Log thông tin khóa học
            \Log::info('=== GET STUDENTS BY COURSE ===');
            \Log::info('Course Item ID: ' . $courseItem->id);
            \Log::info('Course Item Name: ' . $courseItem->name);

            // Lấy tất cả ID của khóa học này và các khóa con (đệ quy)
            $allCourseIds = $this->getAllDescendantCourseIds($courseItem);
            \Log::info('All Course IDs: ', $allCourseIds);

            // Nếu có khóa con, lấy học viên từ tất cả khóa con
            if (count($allCourseIds) > 1) {
                // Loại bỏ khóa cha khỏi danh sách (chỉ lấy từ khóa con)
                $childCourseIds = array_filter($allCourseIds, function($id) use ($courseItem) {
                    return $id !== $courseItem->id;
                });
                \Log::info('Child Course IDs: ', $childCourseIds);

                if (!empty($childCourseIds)) {
                    $students = $this->getStudentsFromCourses($childCourseIds);
                    \Log::info('Getting students from child courses');
                } else {
                    // Nếu không có khóa con, lấy từ chính khóa đó
                    $students = $this->getStudentsFromCourses([$courseItem->id]);
                    \Log::info('Getting students from parent course (no children)');
                }
            } else {
                // Nếu là khóa lá (không có con), lấy học viên của chính khóa đó
                $students = $this->getStudentsFromCourses([$courseItem->id]);
                \Log::info('Getting students from leaf course');
            }

            \Log::info('Found ' . $students->count() . ' students');

            return response()->json([
                'success' => true,
                'data' => $students
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách học viên: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy tất cả ID của khóa học và các khóa con (đệ quy)
     */
    private function getAllDescendantCourseIds(CourseItem $courseItem)
    {
        $courseIds = [$courseItem->id];

        // Lấy tất cả khóa con
        $children = $courseItem->children;

        foreach ($children as $child) {
            // Đệ quy lấy ID của khóa con và các khóa con của nó
            $childIds = $this->getAllDescendantCourseIds($child);
            $courseIds = array_merge($courseIds, $childIds);
        }

        return array_unique($courseIds);
    }

    /**
     * Lấy học viên từ danh sách các khóa học
     */
    private function getStudentsFromCourses(array $courseIds)
    {
        // Lấy thông tin khóa học để hiển thị tên
        $courses = CourseItem::whereIn('id', $courseIds)
            ->orderBy('order_index')
            ->get()
            ->keyBy('id');

        // Lấy tất cả học viên từ các khóa học
        $students = Student::whereHas('enrollments', function ($query) use ($courseIds) {
            $query->whereIn('course_item_id', $courseIds)
                  ->where('status', 'active');
        })
        ->with(['enrollments' => function ($query) use ($courseIds) {
            $query->whereIn('course_item_id', $courseIds)
                  ->where('status', 'active');
        }])
        ->get()
        ->flatMap(function ($student) use ($courses) {
            // Một học viên có thể có nhiều enrollment trong các khóa khác nhau
            return $student->enrollments->map(function ($enrollment) use ($student, $courses) {
                $course = $courses->get($enrollment->course_item_id);
                return [
                    'student_id' => $student->id,
                    'student_name' => $student->full_name,
                    'student_phone' => $student->phone,
                    'student_email' => $student->email,
                    'enrollment_id' => $enrollment->id,
                    'course_name' => $course ? $course->name : 'Unknown',
                    'course_id' => $enrollment->course_item_id,
                    'course_order' => $course ? $course->order_index : 0,
                    'enrollment_status' => $enrollment->status
                ];
            });
        })
        ->sortBy(['course_order', 'student_name'])
        ->values();

        return $students;
    }

    /**
     * Build course tree structure (similar to EnrollmentController)
     */
    private function buildCourseTree($courses)
    {
        $courseMap = [];
        $rootCourses = [];

        // Create map of all courses
        foreach ($courses as $course) {
            $courseMap[$course->id] = [
                'id' => $course->id,
                'name' => $course->name,
                'parent_id' => $course->parent_id,
                'level' => $course->level,
                'is_leaf' => $course->is_leaf,
                'active' => $course->active,
                'status' => $course->status,
                'order_index' => $course->order_index,
                'fee' => $course->fee,
                'created_at' => $course->created_at,
                'updated_at' => $course->updated_at,
                'children' => []
            ];
        }

        // Build tree structure
        foreach ($courseMap as $courseId => $course) {
            if ($course['parent_id']) {
                if (isset($courseMap[$course['parent_id']])) {
                    $courseMap[$course['parent_id']]['children'][] = &$courseMap[$courseId];
                }
            } else {
                $rootCourses[] = &$courseMap[$courseId];
            }
        }

        return $rootCourses;
    }

    /**
     * Build course tree structure with student counts (including descendants)
     */
    private function buildCourseTreeWithStudentCounts($courses)
    {
        $courseMap = [];
        $rootCourses = [];

        // Tính toán student counts một lần cho tất cả courses
        $studentCounts = $this->calculateStudentCountsEfficiently($courses);

        // Create map of all courses with student counts
        foreach ($courses as $course) {
            $courseId = $course->id;
            $courseMap[$courseId] = [
                'id' => $courseId,
                'name' => $course->name,
                'parent_id' => $course->parent_id,
                'is_leaf' => $course->is_leaf,
                'level' => $course->level,
                'path' => $course->path,
                'status' => $course->status,
                'created_at' => $course->created_at,
                'updated_at' => $course->updated_at,
                'student_count' => $studentCounts[$courseId]['total'] ?? 0, // Bao gồm cả khóa con
                'direct_student_count' => $studentCounts[$courseId]['direct'] ?? 0, // Chỉ khóa này
                'total_enrollment_count' => $studentCounts[$courseId]['enrollments'] ?? 0, // Tổng enrollments
                'children' => []
            ];
        }

        // Build tree structure
        foreach ($courseMap as $courseId => $course) {
            if ($course['parent_id']) {
                if (isset($courseMap[$course['parent_id']])) {
                    $courseMap[$course['parent_id']]['children'][] = &$courseMap[$courseId];
                }
            } else {
                $rootCourses[] = &$courseMap[$courseId];
            }
        }

        return $rootCourses;
    }

    /**
     * Tính toán student counts hiệu quả cho tất cả courses
     */
    private function calculateStudentCountsEfficiently($courses)
    {
        $courseIds = $courses->pluck('id')->toArray();
        $studentCounts = [];

        // Khởi tạo counts cho tất cả courses
        foreach ($courseIds as $courseId) {
            $studentCounts[$courseId] = [
                'direct' => 0,
                'total' => 0,
                'enrollments' => 0
            ];
        }

        // Lấy direct student counts và enrollment counts trong 1 query
        $directCounts = DB::table('enrollments')
            ->select('course_item_id',
                DB::raw('COUNT(DISTINCT student_id) as student_count'),
                DB::raw('COUNT(*) as enrollment_count'))
            ->whereIn('course_item_id', $courseIds)
            ->groupBy('course_item_id')
            ->get();

        // Cập nhật direct counts
        foreach ($directCounts as $count) {
            $courseId = $count->course_item_id;
            $studentCounts[$courseId]['direct'] = $count->student_count;
            $studentCounts[$courseId]['enrollments'] = $count->enrollment_count;
        }

        // Tính total counts (bao gồm descendants) bằng cách duyệt tree
        $courseMap = $courses->keyBy('id');
        foreach ($courses as $course) {
            $studentCounts[$course->id]['total'] = $this->calculateTotalStudentCount($course, $courseMap, $studentCounts);
        }

        return $studentCounts;
    }

    /**
     * Tính tổng số học viên bao gồm descendants
     */
    private function calculateTotalStudentCount($course, $courseMap, $studentCounts)
    {
        $total = $studentCounts[$course->id]['direct'];

        // Cộng thêm từ các khóa con
        foreach ($course->children as $child) {
            if (isset($courseMap[$child->id])) {
                $total += $this->calculateTotalStudentCount($child, $courseMap, $studentCounts);
            }
        }

        return $total;
    }
}
