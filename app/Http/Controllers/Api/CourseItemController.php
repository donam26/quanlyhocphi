<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Services\CourseItemService;
use Illuminate\Support\Facades\Validator;

class CourseItemController extends Controller
{
    protected $courseItemService;

    public function __construct(CourseItemService $courseItemService)
    {
        $this->courseItemService = $courseItemService;
    }

    /**
     * Display a listing of course items
     */
    public function index(Request $request)
    {
        $query = CourseItem::query();

        // Search
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by learning method
        if ($request->has('learning_method') && $request->learning_method) {
            $query->where('learning_method', $request->learning_method);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'order_index');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $courses = $query->with(['parent', 'children'])->get();

        return response()->json([
            'data' => $courses
        ]);
    }

    /**
     * Check if course is special or has special parent
     */
    private function isSpecialCourseOrHasSpecialParent(CourseItem $courseItem)
    {
        // Kiểm tra chính khóa học này
        if ($courseItem->is_special) {
            return true;
        }

        // Kiểm tra các khóa cha
        $current = $courseItem;
        while ($current->parent_id) {
            $current = CourseItem::find($current->parent_id);
            if ($current && $current->is_special) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get course tree structure
     */
    public function tree(Request $request)
    {
        $courses = CourseItem::with(['children' => function ($query) {
            $query->orderBy('order_index');
        }])
        ->whereNull('parent_id')
        ->orderBy('order_index')
        ->get();

        // Build tree recursively
        $tree = $this->buildTree($courses);

        return response()->json([
            'data' => $tree
        ]);
    }

    /**
     * Store a newly created course item
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:course_items,id',
            'fee' => 'nullable|numeric|min:0',
            'learning_method' => 'nullable|in:online,offline',
            'status' => 'in:' . implode(',', array_column(CourseStatus::cases(), 'value')),
            'is_special' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate special course requirements
        if ($request->boolean('is_special')) {
            // Khóa học đặc biệt phải có học phí
            if (!$request->fee || $request->fee <= 0) {
                return response()->json([
                    'message' => 'Khóa học đặc biệt phải có học phí',
                    'errors' => ['fee' => ['Khóa học đặc biệt phải có học phí']]
                ], 422);
            }
        }

        // Validate parent course: Khóa đích không thể có con
        if ($request->parent_id) {
            $parent = CourseItem::find($request->parent_id);
            if ($parent->is_leaf) {
                return response()->json([
                    'message' => 'Không thể tạo khóa học con cho khóa đích',
                    'errors' => ['parent_id' => ['Khóa đích không thể có khóa học con']]
                ], 422);
            }
        }

        // Calculate level and is_leaf
        $level = 1;
        $isLeaf = $request->boolean('is_leaf', false); // Sử dụng giá trị từ request, mặc định false

        if ($request->parent_id) {
            $parent = CourseItem::find($request->parent_id);
            $level = $parent->level + 1;

            // Update parent to not be a leaf (already validated above)
            $parent->update(['is_leaf' => false]);
        }

        // Get next order index
        $orderIndex = CourseItem::where('parent_id', $request->parent_id)->max('order_index') + 1;

        $courseItem = CourseItem::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'level' => $level,
            'is_leaf' => $isLeaf,
            'fee' => $request->fee ?? 0,
            'order_index' => $orderIndex,
            'status' => $request->status ?? CourseStatus::ACTIVE,
            'is_special' => $request->boolean('is_special'),
            'learning_method' => $request->learning_method,
            'custom_fields' => $request->custom_fields ?? [],
        ]);

        $courseItem->load(['parent', 'children']);

        return response()->json([
            'message' => $courseItem->is_special ?
                'Khóa học đặc biệt đã được tạo thành công. Khi thêm học viên, hệ thống sẽ yêu cầu thông tin bổ sung cho lớp Kế toán trưởng.' :
                'Khóa học đã được tạo thành công',
            'data' => $courseItem
        ], 201);
    }

    /**
     * Display the specified course item
     */
    public function show(CourseItem $courseItem)
    {
        $courseItem->load(['parent', 'children', 'enrollments.student']);

        // Add student count (bao gồm cả khóa con)
        $courseItem->student_count = $courseItem->getTotalStudentsCount();
        $courseItem->active_student_count = $courseItem->getAllEnrollments()->where('status', 'active')->distinct('student_id')->count('student_id');

        // Add direct enrollment count (chỉ khóa này)
        $courseItem->direct_enrollment_count = $courseItem->enrollments()->count();
        $courseItem->direct_active_count = $courseItem->enrollments()->where('status', 'active')->count();

        // Add total enrollment count (bao gồm cả khóa con)
        $courseItem->total_enrollment_count = $courseItem->getTotalEnrollmentsCount();

        return response()->json($courseItem);
    }

    /**
     * Get student statistics recursively (online/offline counts)
     */
    public function getStudentStats(CourseItem $courseItem)
    {
        try {
            $stats = $this->calculateStudentStatsRecursive($courseItem);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê học viên: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate student statistics recursively
     */
    private function calculateStudentStatsRecursive(CourseItem $courseItem)
    {
        $stats = [
            'total_students' => 0,
            'online_students' => 0,
            'offline_students' => 0,
            'active_enrollments' => 0,
            'completed_enrollments' => 0,
            'waiting_enrollments' => 0,
            'cancelled_enrollments' => 0,
            'breakdown_by_course' => []
        ];

        // Lấy tất cả khóa con đệ quy
        $allCourseIds = [$courseItem->id];
        $this->collectAllChildrenIds($courseItem, $allCourseIds);

        // Lấy tất cả enrollments từ các khóa này
        $enrollments = \App\Models\Enrollment::whereIn('course_item_id', $allCourseIds)
            ->with(['student', 'courseItem'])
            ->get();

        // Thống kê theo trạng thái enrollment
        $stats['active_enrollments'] = $enrollments->where('status', 'active')->count();
        $stats['completed_enrollments'] = $enrollments->where('status', 'completed')->count();
        $stats['waiting_enrollments'] = $enrollments->where('status', 'waiting')->count();
        $stats['cancelled_enrollments'] = $enrollments->where('status', 'cancelled')->count();

        // Thống kê theo phương thức học (online/offline) - tính theo enrollments
        // Lấy tất cả active enrollments
        $activeEnrollments = $enrollments->where('status', \App\Enums\EnrollmentStatus::ACTIVE);

        // Đếm enrollments theo phương thức học
        $onlineEnrollments = $activeEnrollments->filter(function ($enrollment) {
            return $enrollment->courseItem &&
                   $enrollment->courseItem->learning_method === \App\Enums\LearningMethod::ONLINE;
        });

        $offlineEnrollments = $activeEnrollments->filter(function ($enrollment) {
            return $enrollment->courseItem &&
                   $enrollment->courseItem->learning_method === \App\Enums\LearningMethod::OFFLINE;
        });

        $stats['online_students'] = $onlineEnrollments->count();
        $stats['offline_students'] = $offlineEnrollments->count();

        // Tính tổng students theo enrollments (không unique)
        // Nếu 1 học viên tham gia 2 khóa con thì tính là 2
        $stats['total_students'] = $activeEnrollments->count();

        // Thống kê theo trạng thái enrollment - sử dụng enum constants
        $stats['active_enrollments'] = $enrollments->where('status', \App\Enums\EnrollmentStatus::ACTIVE)->count();
        $stats['completed_enrollments'] = $enrollments->where('status', \App\Enums\EnrollmentStatus::COMPLETED)->count();
        $stats['waiting_enrollments'] = $enrollments->where('status', \App\Enums\EnrollmentStatus::WAITING)->count();
        $stats['cancelled_enrollments'] = $enrollments->where('status', \App\Enums\EnrollmentStatus::CANCELLED)->count();

        // Debug log
        \Log::info('Course Stats Debug', [
            'course_id' => $courseItem->id,
            'course_name' => $courseItem->name,
            'is_leaf' => $courseItem->is_leaf,
            'all_course_ids' => $allCourseIds,
            'total_enrollments' => $enrollments->count(),
            'active_enrollments' => $activeEnrollments->count(),
            'total_students' => $stats['total_students'],
            'online_students' => $stats['online_students'],
            'offline_students' => $stats['offline_students'],
            'breakdown_count' => count($courseStats ?? []),
            'course_methods' => $activeEnrollments->map(function($e) {
                return [
                    'course_id' => $e->course_item_id,
                    'course_name' => $e->courseItem->name ?? 'N/A',
                    'learning_method' => $e->courseItem->learning_method ?? 'N/A',
                    'student_id' => $e->student_id
                ];
            })->toArray()
        ]);

        // Breakdown theo từng khóa học
        $courseStats = [];
        foreach ($allCourseIds as $courseId) {
            $course = \App\Models\CourseItem::find($courseId);
            if ($course && $course->is_leaf) {
                $courseEnrollments = $enrollments->where('course_item_id', $courseId);
                $activeCourseEnrollments = $courseEnrollments->where('status', 'active');

                $courseStats[] = [
                    'course_id' => $courseId,
                    'course_name' => $course->name,
                    'learning_method' => $course->learning_method,
                    'total_enrollments' => $courseEnrollments->count(),
                    'active_enrollments' => $activeCourseEnrollments->count(),
                    'unique_students' => $activeCourseEnrollments->pluck('student_id')->unique()->count()
                ];
            }
        }

        $stats['breakdown_by_course'] = $courseStats;

        return $stats;
    }

    /**
     * Collect all children IDs recursively
     */
    private function collectAllChildrenIds(CourseItem $courseItem, &$courseIds)
    {
        $children = $courseItem->children()->get();
        foreach ($children as $child) {
            $courseIds[] = $child->id;
            $this->collectAllChildrenIds($child, $courseIds);
        }
    }

    /**
     * Update the specified course item
     */
    public function update(Request $request, CourseItem $courseItem)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:course_items,id',
            'fee' => 'nullable|numeric|min:0',
            'learning_method' => 'nullable|in:online,offline',
            'status' => 'in:' . implode(',', array_column(CourseStatus::cases(), 'value')),
            'is_special' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate is_leaf: Khóa học có con không thể là khóa đích
        if ($request->has('is_leaf') && $request->boolean('is_leaf')) {
            // Kiểm tra xem có khóa học con nào không
            $hasChildren = $courseItem->children()->exists();
            if ($hasChildren) {
                return response()->json([
                    'message' => 'Không thể đánh dấu khóa học có con là khóa đích',
                    'errors' => ['is_leaf' => ['Khóa học có con không thể là khóa đích']]
                ], 422);
            }
        }

        // Validate special course requirements
        if ($request->boolean('is_special')) {
            // Khóa học đặc biệt phải có học phí
            if (!$request->fee || $request->fee <= 0) {
                return response()->json([
                    'message' => 'Khóa học đặc biệt phải có học phí',
                    'errors' => ['fee' => ['Khóa học đặc biệt phải có học phí']]
                ], 422);
            }
        }

        // Kiểm tra nếu đang chuyển từ khóa học thường sang đặc biệt
        $wasSpecial = $courseItem->is_special;
        $willBeSpecial = $request->boolean('is_special');

        if (!$wasSpecial && $willBeSpecial) {
            // Kiểm tra xem có học viên nào đã ghi danh chưa
            $enrollmentCount = $courseItem->enrollments()->count();
            if ($enrollmentCount > 0) {
                return response()->json([
                    'message' => 'Không thể chuyển khóa học đã có học viên thành khóa học đặc biệt. Vui lòng tạo khóa học đặc biệt mới.',
                    'errors' => ['is_special' => ['Không thể chuyển khóa học đã có học viên thành khóa học đặc biệt']]
                ], 422);
            }
        }

        // Handle parent change
        if ($request->parent_id != $courseItem->parent_id) {
            // Validate new parent: Khóa đích không thể có con
            if ($request->parent_id) {
                $newParent = CourseItem::find($request->parent_id);
                if ($newParent->is_leaf) {
                    return response()->json([
                        'message' => 'Không thể chuyển khóa học vào khóa đích',
                        'errors' => ['parent_id' => ['Khóa đích không thể có khóa học con']]
                    ], 422);
                }
            }

            // Update old parent
            if ($courseItem->parent_id) {
                $oldParent = CourseItem::find($courseItem->parent_id);
                if ($oldParent && $oldParent->children()->count() == 1) {
                    $oldParent->update(['is_leaf' => true]);
                }
            }

            // Update new parent
            if ($request->parent_id) {
                $newParent = CourseItem::find($request->parent_id);
                $newParent->update(['is_leaf' => false]);
                $level = $newParent->level + 1;
            } else {
                $level = 1;
            }

            $courseItem->level = $level;
        }

        $courseItem->update($request->only([
            'name', 'parent_id', 'fee', 'learning_method', 'status', 'is_special', 'is_leaf', 'custom_fields'
        ]));

        $courseItem->load(['parent', 'children']);

        return response()->json([
            'message' => 'Course updated successfully',
            'data' => $courseItem
        ]);
    }

    /**
     * Remove the specified course item
     */
    public function destroy(CourseItem $courseItem)
    {
        try {
            // Luôn luôn xóa đệ quy tất cả khóa học con và dữ liệu liên quan
            $this->courseItemService->deleteCourseItem($courseItem);

            return response()->json([
                'message' => 'Khóa học đã được xóa thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi xóa khóa học: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Toggle course status and all child courses
     */
    public function toggleStatus(CourseItem $courseItem)
    {
        try {
            if ($courseItem->status === CourseStatus::ACTIVE->value) {
                // Kết thúc khóa học và tất cả khóa học con
                $success = $courseItem->completeCourse();
                $message = $success ? 'Khóa học và tất cả khóa học con đã được kết thúc' : 'Có lỗi xảy ra';
            } else {
                // Mở lại khóa học và tất cả khóa học con
                $success = $courseItem->reopenCourse();
                $message = $success ? 'Khóa học và tất cả khóa học con đã được mở lại' : 'Có lỗi xảy ra';
            }

            if ($success) {
                return response()->json([
                    'message' => $message,
                    'data' => $courseItem->fresh(['children'])
                ]);
            } else {
                return response()->json([
                    'message' => 'Có lỗi xảy ra khi thay đổi trạng thái khóa học'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi thay đổi trạng thái khóa học',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete course and all child courses
     */
    public function complete(CourseItem $courseItem)
    {
        try {
            $success = $courseItem->completeCourse();

            if ($success) {
                return response()->json([
                    'message' => 'Khóa học và tất cả khóa học con đã được kết thúc',
                    'data' => $courseItem->fresh(['children'])
                ]);
            } else {
                return response()->json([
                    'message' => 'Có lỗi xảy ra khi kết thúc khóa học'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi kết thúc khóa học',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reopen course and all child courses
     */
    public function reopen(CourseItem $courseItem)
    {
        try {
            $success = $courseItem->reopenCourse();

            if ($success) {
                return response()->json([
                    'message' => 'Khóa học và tất cả khóa học con đã được mở lại',
                    'data' => $courseItem->fresh(['children'])
                ]);
            } else {
                return response()->json([
                    'message' => 'Có lỗi xảy ra khi mở lại khóa học'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi mở lại khóa học',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get course students
     */
    public function students(CourseItem $courseItem)
    {
        // Nếu là khóa cha (có khóa con), lấy tất cả học viên từ các khóa con
        if ($courseItem->children()->exists()) {
            $allEnrollments = collect();

            // Lấy tất cả khóa con theo thứ tự order_index
            $childCourses = $courseItem->children()->orderBy('order_index')->get();

            foreach ($childCourses as $childCourse) {
                $childEnrollments = $childCourse->enrollments()
                    ->with(['student.province', 'payments', 'courseItem'])
                    ->whereNotIn('status', [EnrollmentStatus::WAITING, EnrollmentStatus::CANCELLED])
                    ->orderBy('enrollment_date', 'desc')
                    ->get();

                // Thêm thông tin khóa con vào mỗi enrollment
                $childEnrollments->each(function ($enrollment) use ($childCourse) {
                    $enrollment->child_course_name = $childCourse->name;
                    $enrollment->child_course_id = $childCourse->id;
                    $enrollment->child_course_order = $childCourse->order_index;
                });

                $allEnrollments = $allEnrollments->concat($childEnrollments);
            }

            $enrollments = $allEnrollments;
        } else {
            // Nếu là khóa con hoặc khóa độc lập, lấy học viên của chính khóa đó
            $enrollments = $courseItem->enrollments()
                ->with(['student.province', 'payments'])
                ->whereNotIn('status', [EnrollmentStatus::WAITING, EnrollmentStatus::CANCELLED])
                ->orderBy('enrollment_date', 'desc')
                ->get();
        }

        // Thêm thông tin tính toán cho mỗi enrollment
        $enrollments->each(function ($enrollment) use ($courseItem) {
            if ($enrollment->student) {
                // Tính toán thông tin thanh toán
                $totalPaid = $enrollment->payments()->where('status', 'confirmed')->sum('amount');
                $enrollment->total_paid = $totalPaid;
                $enrollment->remaining_amount = max(0, $enrollment->final_fee - $totalPaid);
                $enrollment->payment_status = $enrollment->remaining_amount <= 0 ? 'paid' : 'unpaid';

                // Đảm bảo có đầy đủ thông tin cho khóa học đặc biệt
                if ($courseItem->is_special) {
                    $student = $enrollment->student;
                    $enrollment->student->makeVisible([
                        'current_workplace',
                        'accounting_experience_years',
                        'training_specialization',
                        'education_level',
                        'hard_copy_documents'
                    ]);
                }
            }
        });

        return response()->json($enrollments);
    }

    /**
     * Get course students recursively (for folder courses)
     * Returns same format as students() method for consistency
     */
    public function studentsRecursive(CourseItem $courseItem)
    {
        $allEnrollments = collect();

        // Lấy học viên từ khóa hiện tại (nếu là khóa lá)
        if ($courseItem->is_leaf) {
            $enrollments = $courseItem->enrollments()
                ->with(['student.province', 'student.ethnicity', 'courseItem', 'payments'])
                ->whereNotIn('status', [EnrollmentStatus::WAITING, EnrollmentStatus::CANCELLED])
                ->orderBy('enrollment_date', 'desc')
                ->get();

            $allEnrollments = $allEnrollments->concat($enrollments);
        }

        // Lấy học viên từ tất cả khóa con
        $this->collectEnrollmentsRecursive($courseItem, $allEnrollments);

        // Thêm thông tin tính toán cho mỗi enrollment
        $allEnrollments->each(function ($enrollment) use ($courseItem) {
            if ($enrollment->student) {
                // Tính toán thông tin thanh toán
                $totalPaid = $enrollment->payments()->where('status', 'confirmed')->sum('amount');
                $enrollment->total_paid = $totalPaid;
                $enrollment->remaining_amount = max(0, $enrollment->final_fee - $totalPaid);
                $enrollment->payment_status = $enrollment->remaining_amount <= 0 ? 'paid' : 'unpaid';

                // Đảm bảo có đầy đủ thông tin cho khóa học đặc biệt
                if ($courseItem->is_special) {
                    $enrollment->student->makeVisible([
                        'current_workplace',
                        'accounting_experience_years',
                        'training_specialization',
                        'education_level',
                        'hard_copy_documents'
                    ]);
                }
            }
        });

        return response()->json($allEnrollments);
    }

    /**
     * Collect enrollments recursively from all children
     */
    private function collectEnrollmentsRecursive(CourseItem $courseItem, &$allEnrollments)
    {
        $childCourses = $courseItem->children()->orderBy('order_index')->get();

        foreach ($childCourses as $childCourse) {
            if ($childCourse->is_leaf) {
                // Nếu là khóa lá, lấy học viên
                $childEnrollments = $childCourse->enrollments()
                    ->with(['student.province', 'student.ethnicity', 'courseItem', 'payments'])
                    ->whereNotIn('status', [EnrollmentStatus::WAITING, EnrollmentStatus::CANCELLED])
                    ->orderBy('enrollment_date', 'desc')
                    ->get();

                // Thêm thông tin khóa con vào mỗi enrollment
                $childEnrollments->each(function ($enrollment) use ($childCourse) {
                    $enrollment->child_course_name = $childCourse->name;
                    $enrollment->child_course_id = $childCourse->id;
                    $enrollment->child_course_order = $childCourse->order_index;
                });

                $allEnrollments = $allEnrollments->merge($childEnrollments);
            } else {
                // Nếu là thư mục, đệ quy tiếp
                $this->collectEnrollmentsRecursive($childCourse, $allEnrollments);
            }
        }
    }

    /**
     * Collect enrollments grouped by course from all children
     */
    private function collectEnrollmentsGrouped(CourseItem $courseItem, &$groupedEnrollments)
    {
        $childCourses = $courseItem->children()->orderBy('order_index')->get();

        foreach ($childCourses as $childCourse) {
            if ($childCourse->is_leaf) {
                // Nếu là khóa lá, lấy học viên và nhóm theo course_id
                $childEnrollments = $childCourse->enrollments()
                    ->with(['student.province', 'student.ethnicity', 'courseItem'])
                    ->whereNotIn('status', [EnrollmentStatus::WAITING, EnrollmentStatus::CANCELLED])
                    ->get();

                if ($childEnrollments->count() > 0) {
                    $groupedEnrollments[$childCourse->id] = $childEnrollments;
                }
            } else {
                // Nếu là thư mục, đệ quy tiếp
                $this->collectEnrollmentsGrouped($childCourse, $groupedEnrollments);
            }
        }
    }

    /**
     * Get waiting list for course
     */
    public function waitingList(CourseItem $courseItem)
    {
        $waitingList = $courseItem->enrollments()
            ->where('status', 'waiting')
            ->with(['student'])
            ->orderBy('enrollment_date', 'asc')
            ->get();

        return response()->json($waitingList);
    }

    /**
     * Get waiting count for course
     */
    public function waitingCount(CourseItem $courseItem)
    {
        $count = $courseItem->enrollments()->where('status', 'waiting')->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Search courses
     */
    public function search(Request $request)
    {
        $term = $request->get('term', '');

        $courses = CourseItem::where('name', 'like', "%{$term}%")
            ->with(['parent'])
            ->orderBy('name')
            ->limit(20)
            ->get();

        // Add path information to each course
        $courses->each(function ($course) {
            $course->path = $course->getPathAttribute();
        });

        return response()->json([
            'success' => true,
            'data' => $courses,
            'message' => 'Tìm kiếm thành công'
        ]);
    }

    /**
     * Search active courses
     */
    public function searchActive(Request $request)
    {
        $term = $request->get('term', '');

        $courses = CourseItem::where('name', 'like', "%{$term}%")
            ->where('status', 'active')
            ->with(['parent'])
            ->orderBy('name')
            ->limit(20)
            ->get();

        // Add path information to each course
        $courses->each(function ($course) {
            $course->path = $course->getPathAttribute();
        });

        return response()->json([
            'success' => true,
            'data' => $courses,
            'message' => 'Tìm kiếm thành công'
        ]);
    }

    /**
     * Get available courses
     */
    public function available()
    {
        $courses = CourseItem::where('status', 'active')
            ->orderBy('order_index')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $courses,
            'message' => 'Khóa học khả dụng được tải thành công'
        ]);
    }

    /**
     * Get active leaf courses
     */
    public function activeLeafCourses()
    {
        try {
            $courses = CourseItem::where('status', 'active')
                ->where('is_leaf', true)
                ->where('fee', '>', 0) // Only courses with fee > 0 can be enrolled
                ->orderBy('order_index')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $courses,
                'message' => 'Active leaf courses loaded successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading active leaf courses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to load courses'
            ], 500);
        }
    }

    /**
     * Get courses available for enrollment for a specific student
     */
    public function availableForEnrollment(Request $request)
    {
        try {
            $studentId = $request->query('student_id');

            if (!$studentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student ID is required'
                ], 400);
            }

            // Verify student exists
            $student = \App\Models\Student::find($studentId);
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found'
                ], 404);
            }

            // Get active leaf courses with fee > 0
            $query = CourseItem::where('status', 'active')
                ->where('is_leaf', true)
                ->where('fee', '>', 0);

            // Exclude courses the student is already enrolled in
            $query->whereNotExists(function ($subQuery) use ($studentId) {
                $subQuery->select(\DB::raw(1))
                    ->from('enrollments')
                    ->whereColumn('enrollments.course_item_id', 'course_items.id')
                    ->where('enrollments.student_id', $studentId)
                    ->whereIn('enrollments.status', ['waiting', 'active', 'completed']);
            });

            $courses = $query->orderBy('order_index')->get();

            return response()->json([
                'success' => true,
                'data' => $courses,
                'message' => 'Courses loaded successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading available courses for enrollment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to load available courses'
            ], 500);
        }
    }

    /**
     * Get leaf courses
     */
    public function leafCourses()
    {
        $courses = CourseItem::where('is_leaf', true)
            ->orderBy('order_index')
            ->get();

        return response()->json($courses);
    }

    /**
     * Update course order
     */
    public function updateOrder(Request $request)
    {
        $orderData = $request->get('order', []);

        foreach ($orderData as $item) {
            CourseItem::where('id', $item['id'])
                ->update(['order_index' => $item['order']]);
        }

        return response()->json([
            'message' => 'Order updated successfully'
        ]);
    }

    /**
     * Reorder root courses (tabs) via drag and drop
     */
    public function reorderRootCourses(Request $request)
    {
        $request->validate([
            'courseId' => 'required|integer|exists:course_items,id',
            'sourceIndex' => 'required|integer|min:0',
            'destIndex' => 'required|integer|min:0',
            'courses' => 'required|array',
            'courses.*.id' => 'required|integer|exists:course_items,id'
        ]);

        try {
            \DB::beginTransaction();

            $courseId = $request->courseId;
            $sourceIndex = $request->sourceIndex;
            $destIndex = $request->destIndex;
            $courses = $request->courses;

            // Verify all courses are root courses (parent_id is null)
            $rootCourseIds = collect($courses)->pluck('id');
            $invalidCourses = CourseItem::whereIn('id', $rootCourseIds)
                ->whereNotNull('parent_id')
                ->count();

            if ($invalidCourses > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể sắp xếp các khóa học gốc'
                ], 400);
            }

            // Get the course being moved
            $movingCourse = CourseItem::find($courseId);
            if (!$movingCourse || $movingCourse->parent_id !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Khóa học không hợp lệ'
                ], 400);
            }

            // Reorder the courses array
            $reorderedCourses = collect($courses);
            $movingItem = $reorderedCourses->splice($sourceIndex, 1)->first();
            $reorderedCourses->splice($destIndex, 0, [$movingItem]);

            // Update order_index for all root courses
            foreach ($reorderedCourses as $index => $courseData) {
                CourseItem::where('id', $courseData['id'])
                    ->whereNull('parent_id')
                    ->update(['order_index' => $index]);
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật thứ tự tab thành công'
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error reordering root courses', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi sắp xếp thứ tự: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder courses via drag and drop
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'courseId' => 'required|integer|exists:course_items,id',
            'sourceParentId' => 'nullable|integer|exists:course_items,id',
            'destParentId' => 'nullable|integer|exists:course_items,id',
            'sourceIndex' => 'required|integer|min:0',
            'destIndex' => 'required|integer|min:0'
        ]);

        try {
            $courseId = $request->input('courseId');
            $sourceParentId = $request->input('sourceParentId');
            $destParentId = $request->input('destParentId');
            $sourceIndex = $request->input('sourceIndex');
            $destIndex = $request->input('destIndex');

            // Get the course being moved
            $course = CourseItem::findOrFail($courseId);

            // If moving to a different parent
            if ($sourceParentId !== $destParentId) {
                $course->parent_id = $destParentId;
                $course->save();
            }

            // Get all siblings in the destination parent
            $siblings = CourseItem::where('parent_id', $destParentId)
                ->orderBy('order_index')
                ->get();

            // Remove the moved course from the list temporarily
            $siblings = $siblings->reject(function ($item) use ($courseId) {
                return $item->id === $courseId;
            })->values();

            // Insert the moved course at the new position
            $siblings->splice($destIndex, 0, [$course]);

            // Update order_index for all siblings
            foreach ($siblings as $index => $sibling) {
                $sibling->order_index = $index;
                $sibling->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật thứ tự khóa học thành công'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error reordering courses', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật thứ tự: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export students of a course to Excel
     */
    public function exportStudents(Request $request, CourseItem $courseItem)
    {
        try {
            $request->validate([
                'columns' => 'array',
                'columns.*' => 'string',
                'status' => 'nullable|string',
                'payment_status' => 'nullable|string|in:paid,partial,unpaid',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'include_summary' => 'boolean'
            ]);

            $columns = $request->input('columns', [
                'student_last_name', 'student_first_name', 'student_phone', 'student_email',
                'student_date_of_birth', 'student_gender', 'student_province', 'ethnicity',
                'enrollment_date', 'enrollment_status', 'final_fee', 'total_paid', 'remaining_amount', 'payment_status'
            ]);

            $filters = [
                'status' => $request->input('status'),
                'payment_status' => $request->input('payment_status'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'include_summary' => $request->boolean('include_summary', false)
            ];

            $fileName = 'hoc_vien_khoa_' . $courseItem->id . '_' . date('Y_m_d_H_i_s') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\CourseStudentsExport($courseItem, $columns, $filters),
                $fileName
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xuất file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import students to course
     */
    public function importStudents(Request $request, CourseItem $courseItem)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'enrollment_status' => 'in:active,waiting',
            'discount_percentage' => 'numeric|min:0|max:100'
        ]);

        try {
            $enrollmentStatus = $request->input('enrollment_status', 'active');
            $discountPercentage = $request->input('discount_percentage', 0);

            $import = new \App\Imports\UnifiedStudentImport('create_and_update', $courseItem, $enrollmentStatus, $discountPercentage);
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Import thành công!',
                'data' => [
                    'imported_count' => $import->getImportedCount(),
                    'enrolled_count' => $import->getEnrolledCount(),
                    'created_count' => $import->getCreatedCount(),
                    'updated_count' => $import->getUpdatedCount(),
                    'skipped_count' => $import->getSkippedCount(),
                    'errors' => $import->getErrors()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi import file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import students to waiting list
     */
    public function importStudentsToWaiting(Request $request, CourseItem $courseItem)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'notes' => 'nullable|string'
        ]);

        // Kiểm tra xem khóa học có thu phí không
        if (!$courseItem->fee || $courseItem->fee <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể import học viên vào khóa học miễn phí.'
            ], 422);
        }

        try {


            $import = new \App\Imports\UnifiedStudentImport('create_and_update', $courseItem, 'waiting', 0);
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Import vào danh sách chờ thành công!',
                'data' => [
                    'imported_count' => $import->getImportedCount(),
                    'enrolled_count' => $import->getEnrolledCount(),
                    'created_count' => $import->getCreatedCount(),
                    'updated_count' => $import->getUpdatedCount(),
                    'skipped_count' => $import->getSkippedCount(),
                    'errors' => $import->getErrors()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi import file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add student to course
     */
    public function addStudent(Request $request, CourseItem $courseItem)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'enrollment_status' => 'in:active,waiting',
            'discount_percentage' => 'numeric|min:0|max:100',
            'notes' => 'nullable|string'
        ]);

        try {
            // Check if student already enrolled
            $existingEnrollment = \App\Models\Enrollment::where('student_id', $request->student_id)
                ->where('course_item_id', $courseItem->id)
                ->first();

            if ($existingEnrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Học viên đã được ghi danh vào khóa học này'
                ], 422);
            }

            // Kiểm tra thông tin bổ sung cho khóa học đặc biệt (bao gồm cả khóa cha)
            $isSpecialCourse = $this->isSpecialCourseOrHasSpecialParent($courseItem);
            if ($isSpecialCourse) {
                $student = \App\Models\Student::find($request->student_id);
                $missingFields = [];

                if (empty($student->current_workplace)) {
                    $missingFields[] = 'Nơi công tác hiện tại';
                }
                if (is_null($student->accounting_experience_years)) {
                    $missingFields[] = 'Số năm kinh nghiệm kế toán';
                }
                if (empty($student->training_specialization)) {
                    $missingFields[] = 'Chuyên môn đào tạo';
                }
                if (empty($student->education_level)) {
                    $missingFields[] = 'Trình độ học vấn';
                }
                if (empty($student->hard_copy_documents)) {
                    $missingFields[] = 'Tình trạng hồ sơ bản cứng';
                }

                if (!empty($missingFields)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Học viên chưa đủ thông tin bổ sung cho khóa học đặc biệt',
                        'missing_fields' => $missingFields,
                        'errors' => [
                            'student_info' => 'Vui lòng cập nhật đầy đủ thông tin: ' . implode(', ', $missingFields)
                        ]
                    ], 422);
                }
            }

            // Calculate fees
            $originalFee = $courseItem->fee;
            $discountPercentage = $request->input('discount_percentage', 0);
            $discountAmount = ($originalFee * $discountPercentage) / 100;
            $finalFee = $originalFee - $discountAmount;

            $enrollment = \App\Models\Enrollment::create([
                'student_id' => $request->student_id,
                'course_item_id' => $courseItem->id,
                'enrollment_date' => now(),
                'status' => $request->input('enrollment_status', 'active'),
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
                'final_fee' => $finalFee,
                'notes' => $request->input('notes', '')
            ]);

            $enrollment->load(['student', 'courseItem']);

            return response()->json([
                'success' => true,
                'message' => $courseItem->is_special ?
                    'Đã thêm học viên vào khóa học đặc biệt' :
                    'Đã thêm học viên vào khóa học',
                'data' => $enrollment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi thêm học viên: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build tree structure recursively
     */
    private function buildTree($courses)
    {
        return $courses->map(function ($course) {
            if ($course->children->count() > 0) {
                $course->children = $this->buildTree($course->children);
            }
            return $course;
        });
    }

    /**
     * Get learning paths for a course
     */
    public function getLearningPaths($id)
    {
        try {
            $course = CourseItem::findOrFail($id);

            // Cho phép thiết lập lộ trình cho mọi khóa học
            // Không cần kiểm tra is_leaf nữa

            $learningPaths = LearningPath::where('course_item_id', $id)
                ->orderBy('order')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $learningPaths
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải lộ trình học tập'
            ], 500);
        }
    }

    /**
     * Save learning paths for a course
     */
    public function saveLearningPaths(Request $request, $id)
    {
        try {
            $course = CourseItem::findOrFail($id);

            // Cho phép thiết lập lộ trình cho mọi khóa học
            // Không cần kiểm tra is_leaf nữa

            // Debug: Log request data
            \Log::info('Learning paths save request:', [
                'course_id' => $course->id,
                'request_all' => $request->all(),
                'has_paths' => $request->has('paths'),
                'paths_value' => $request->input('paths')
            ]);

            $validator = Validator::make($request->all(), [
                'paths' => 'present|array', // present cho phép mảng rỗng
                'paths.*.title' => 'required_with:paths.*|string|max:255',
                'paths.*.description' => 'nullable|string',
                'paths.*.order' => 'nullable|integer',
                'paths.*.is_completed' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                \Log::error('Learning paths validation failed:', [
                    'errors' => $validator->errors(),
                    'request_data' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Xóa tất cả learning paths cũ
            LearningPath::where('course_item_id', $id)->delete();

            // Tạo learning paths mới
            $paths = $request->input('paths');
            \Log::info('Saving learning paths', [
                'course_id' => $id,
                'paths_count' => count($paths),
                'paths_data' => $paths
            ]);

            foreach ($paths as $index => $pathData) {
                LearningPath::create([
                    'course_item_id' => $id,
                    'title' => $pathData['title'],
                    'description' => $pathData['description'] ?? '',
                    'order' => $pathData['order'] ?? ($index + 1),
                    'is_completed' => $pathData['is_completed'] ?? false
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã lưu lộ trình học tập thành công'
            ]);
        } catch (\Exception $e) {
            \Log::error('Learning paths save error:', [
                'course_id' => $courseItem->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lưu lộ trình học tập: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete course and all active enrollments
     * Also complete all child courses
     */
    public function completeCourse(CourseItem $courseItem)
    {
        try {
            $success = $courseItem->completeCourse();

            if ($success) {
                return response()->json([
                    'message' => 'Khóa học và tất cả khóa học con đã được kết thúc',
                    'data' => $courseItem->fresh(['enrollments', 'children'])
                ]);
            } else {
                return response()->json([
                    'message' => 'Có lỗi xảy ra khi kết thúc khóa học'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi kết thúc khóa học',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reopen course with student status options
     */
    public function reopenCourse(CourseItem $courseItem, Request $request)
    {
        $request->validate([
            'student_action' => 'required|in:keep_completed,set_active,set_waiting',
            'selected_students' => 'array', // Optional: specific students to update
            'selected_students.*' => 'exists:enrollments,id'
        ]);

        \DB::beginTransaction();

        try {
            // Sử dụng method từ model để mở lại khóa học và tất cả khóa học con
            $success = $courseItem->reopenCourse();

            if (!$success) {
                return response()->json([
                    'message' => 'Có lỗi xảy ra khi mở lại khóa học'
                ], 500);
            }

            // Xử lý trạng thái học viên nếu có yêu cầu cụ thể
            $studentAction = $request->input('student_action');
            $selectedStudents = $request->input('selected_students', []);

            if ($studentAction !== 'keep_completed') {
                $newStatus = $studentAction === 'set_active' ? 'active' : 'waiting';

                // Cập nhật cho khóa học chính
                $this->updateStudentStatusForCourse($courseItem, $newStatus, $selectedStudents);

                // Cập nhật cho tất cả khóa học con
                $this->updateStudentStatusForAllChildren($courseItem, $newStatus, $selectedStudents);
            }

            \DB::commit();

            $message = $this->getSuccessMessage($studentAction, count($selectedStudents));

            return response()->json([
                'message' => 'Khóa học và tất cả khóa học con đã được mở lại. ' . $message,
                'data' => $courseItem->fresh(['enrollments', 'children'])
            ]);
        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => 'Có lỗi xảy ra khi mở lại khóa học',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật trạng thái học viên cho một khóa học
     */
    private function updateStudentStatusForCourse(CourseItem $courseItem, string $newStatus, array $selectedStudents = [])
    {
        $query = $courseItem->enrollments()->where('status', 'completed');

        // If specific students selected, only update those
        if (!empty($selectedStudents)) {
            $query->whereIn('id', $selectedStudents);
        }

        $query->update([
            'status' => $newStatus,
            'completion_date' => null
        ]);
    }

    /**
     * Đệ quy cập nhật trạng thái học viên cho tất cả khóa học con
     */
    private function updateStudentStatusForAllChildren(CourseItem $courseItem, string $newStatus, array $selectedStudents = [])
    {
        foreach ($courseItem->children as $child) {
            $this->updateStudentStatusForCourse($child, $newStatus, $selectedStudents);

            // Đệ quy cho các khóa học con của khóa học con
            $this->updateStudentStatusForAllChildren($child, $newStatus, $selectedStudents);
        }
    }

    /**
     * Get success message based on student action
     */
    private function getSuccessMessage($studentAction, $selectedCount)
    {
        switch ($studentAction) {
            case 'keep_completed':
                return 'Khóa học đã được mở lại. Trạng thái học viên được giữ nguyên.';
            case 'set_active':
                $studentText = $selectedCount > 0 ? "{$selectedCount} học viên được chọn" : "Tất cả học viên";
                return "Khóa học đã được mở lại. {$studentText} đã chuyển về trạng thái đang học.";
            case 'set_waiting':
                $studentText = $selectedCount > 0 ? "{$selectedCount} học viên được chọn" : "Tất cả học viên";
                return "Khóa học đã được mở lại. {$studentText} đã chuyển về trạng thái chờ xác nhận.";
            default:
                return 'Khóa học đã được mở lại thành công.';
        }
    }
}
