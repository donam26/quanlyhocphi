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
            
            $attendanceDate = Carbon::parse($validated['attendance_date']);
            $createdAttendances = [];
            
            foreach ($validated['attendances'] as $attendanceData) {
                // Lấy enrollment để biết course_item_id thực tế
                $enrollment = \App\Models\Enrollment::find($attendanceData['enrollment_id']);
                if (!$enrollment) {
                    continue; // Skip nếu không tìm thấy enrollment
                }

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

            // Nếu là khóa cha, lấy điểm danh từ tất cả khóa con
            if ($courseItem->children()->exists()) {
                $childCourseIds = $courseItem->children()->pluck('id');
                $attendances = Attendance::whereIn('course_item_id', $childCourseIds)
                    ->whereDate('attendance_date', $attendanceDate)
                    ->with(['enrollment.student', 'enrollment.courseItem'])
                    ->get();
            } else {
                // Nếu là khóa con hoặc khóa độc lập
                $attendances = Attendance::where('course_item_id', $courseItem->id)
                    ->whereDate('attendance_date', $attendanceDate)
                    ->with(['enrollment.student'])
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $attendances
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
                'attendance_status', 'notes'
            ]);

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $fileName = 'diem_danh_khoa_' . $courseItem->id . '_' . date('Y_m_d_H_i_s') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\AttendanceExport($courseItem, $columns, $startDate, $endDate),
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
     * API: Lấy cây khóa học cho điểm danh
     */
    public function getAttendanceTree()
    {
        try {
            // Get all courses similar to EnrollmentController
            $allCourses = CourseItem::with(['parent', 'children'])->get();

            // Build tree structure using the same method as other controllers
            $tree = $this->buildCourseTree($allCourses);

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
            // Nếu là khóa cha (có khóa con), lấy tất cả học viên từ các khóa con
            if ($courseItem->children()->exists()) {
                $allStudents = collect();

                // Lấy tất cả khóa con theo thứ tự order_index
                $childCourses = $courseItem->children()->orderBy('order_index')->get();

                foreach ($childCourses as $childCourse) {
                    $childStudents = Student::whereHas('enrollments', function ($query) use ($childCourse) {
                        $query->where('course_item_id', $childCourse->id)
                              ->where('status', 'active');
                    })
                    ->with(['enrollments' => function ($query) use ($childCourse) {
                        $query->where('course_item_id', $childCourse->id);
                    }])
                    ->get()
                    ->map(function ($student) use ($childCourse) {
                        $enrollment = $student->enrollments->first();
                        return [
                            'student_id' => $student->id,   
                            'student_name' => $student->full_name,   
                            'student_phone' => $student->phone,
                            'student_email' => $student->email,
                            'enrollment_id' => $enrollment ? $enrollment->id : null,
                            'course_name' => $childCourse->name,
                            'child_course_name' => $childCourse->name,
                            'child_course_id' => $childCourse->id,
                            'child_course_order' => $childCourse->order_index,
                            'enrollment_status' => $enrollment ? $enrollment->status : null
                        ];
                    });

                    $allStudents = $allStudents->concat($childStudents);
                }

                $students = $allStudents;
            } else {
                // Nếu là khóa con hoặc khóa độc lập, lấy học viên của chính khóa đó
                $students = Student::whereHas('enrollments', function ($query) use ($courseItem) {
                    $query->where('course_item_id', $courseItem->id)
                          ->where('status', 'active');
                })
                ->with(['enrollments' => function ($query) use ($courseItem) {
                    $query->where('course_item_id', $courseItem->id);
                }])
                ->get()
                ->map(function ($student) use ($courseItem) {
                    $enrollment = $student->enrollments->first();
                    return [
                        'student_id' => $student->id,
                        'student_name' => $student->name,
                        'student_phone' => $student->phone,
                        'student_email' => $student->email,
                        'enrollment_id' => $enrollment ? $enrollment->id : null,
                        'course_name' => $courseItem->name,
                        'enrollment_status' => $enrollment ? $enrollment->status : null
                    ];
                });
            }

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
}
