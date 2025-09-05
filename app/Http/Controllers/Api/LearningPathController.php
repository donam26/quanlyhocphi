<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseItem;
use App\Models\LearningPath;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LearningPathController extends Controller
{
    /**
     * Lấy danh sách khóa học có lộ trình chưa hoàn thành
     */
    public function getCoursesWithIncompletePaths()
    {
        try {
            $courses = CourseItem::with(['learningPaths' => function($query) {
                $query->orderBy('order');
            }])
            // Loại bỏ điều kiện is_leaf để hiển thị cả khóa học cha và con
            ->where('status', 'active')
            ->whereHas('learningPaths')
            ->get()
            ->map(function($course) {
                $totalSteps = $course->learningPaths->count();
                $completedSteps = $course->learningPaths->where('is_completed', true)->count();
                $progressPercentage = $totalSteps > 0 ? ($completedSteps / $totalSteps) * 100 : 0;

                // Xác định ngành học dựa trên tên khóa học hoặc parent
                $major = $this->determineMajor($course);

                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'major' => $major,
                    'learning_method' => $course->learning_method ?: 'online',
                    'total_steps' => $totalSteps,
                    'completed_steps' => $completedSteps,
                    'progress_percentage' => round($progressPercentage, 1),
                    'learning_paths' => $course->learningPaths->map(function($path) {
                        return [
                            'id' => $path->id,
                            'title' => $path->title,
                            'description' => $path->description,
                            'order' => $path->order,
                            'is_completed' => $path->is_completed
                        ];
                    })
                ];
            })
            // Chỉ hiển thị các khóa học chưa hoàn thành 100%
            ->filter(function($course) {
                return $course['progress_percentage'] < 100;
            })
            ->values();

            return response()->json($courses);
        } catch (\Exception $e) {
            Log::error('Error fetching courses with incomplete paths: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra khi tải dữ liệu'], 500);
        }
    }

    /**
     * Lấy lộ trình học tập của một khóa học cụ thể
     */
    public function getCoursePathProgress($courseId)
    {
        try {
            $course = CourseItem::with(['learningPaths' => function($query) {
                $query->orderBy('order');
            }])->findOrFail($courseId);

            $totalSteps = $course->learningPaths->count();
            $completedSteps = $course->learningPaths->where('is_completed', true)->count();
            $progressPercentage = $totalSteps > 0 ? ($completedSteps / $totalSteps) * 100 : 0;

            return response()->json([
                'course_id' => $course->id,
                'course_name' => $course->name,
                'total_steps' => $totalSteps,
                'completed_steps' => $completedSteps,
                'progress_percentage' => round($progressPercentage, 1),
                'learning_paths' => $course->learningPaths
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching course path progress: ' . $e->getMessage());
            return response()->json(['error' => 'Không tìm thấy khóa học'], 404);
        }
    }

    /**
     * Cập nhật trạng thái hoàn thành của một bước trong lộ trình
     */
    public function updatePathStepCompletion($courseId, $stepId, Request $request)
    {
        try {
            $request->validate([
                'is_completed' => 'required|boolean'
            ]);

            $learningPath = LearningPath::where('course_item_id', $courseId)
                ->where('id', $stepId)
                ->firstOrFail();

            $learningPath->is_completed = $request->is_completed;
            $learningPath->save();

            return response()->json([
                'message' => 'Cập nhật trạng thái thành công',
                'learning_path' => $learningPath
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating path step completion: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra khi cập nhật'], 500);
        }
    }

    /**
     * Lấy thống kê tổng quan về lộ trình học tập
     */
    public function getPathStatistics()
    {
        try {
            $totalCourses = CourseItem::where('is_leaf', true)
                ->where('active', true)
                ->whereHas('learningPaths')
                ->count();

            $coursesWithProgress = CourseItem::with(['learningPaths'])
                ->where('is_leaf', true)
                ->where('active', true)
                ->whereHas('learningPaths')
                ->get()
                ->map(function($course) {
                    $totalSteps = $course->learningPaths->count();
                    $completedSteps = $course->learningPaths->where('is_completed', true)->count();
                    $progressPercentage = $totalSteps > 0 ? ($completedSteps / $totalSteps) * 100 : 0;

                    return [
                        'course' => $course,
                        'progress_percentage' => $progressPercentage,
                        'major' => $this->determineMajor($course)
                    ];
                });

            $completedCourses = $coursesWithProgress->where('progress_percentage', 100)->count();
            $inProgressCourses = $coursesWithProgress->where('progress_percentage', '>', 0)
                ->where('progress_percentage', '<', 100)->count();
            $notStartedCourses = $coursesWithProgress->where('progress_percentage', 0)->count();

            $overallProgress = $totalCourses > 0 ? ($completedCourses / $totalCourses) * 100 : 0;

            // Thống kê theo ngành
            $majorBreakdown = $coursesWithProgress->groupBy('major')->map(function($courses, $major) {
                $total = $courses->count();
                $completed = $courses->where('progress_percentage', 100)->count();

                return [
                    'total' => $total,
                    'completed' => $completed,
                    'percentage' => $total > 0 ? ($completed / $total) * 100 : 0
                ];
            });

            return response()->json([
                'total_courses' => $totalCourses,
                'completed_courses' => $completedCourses,
                'in_progress_courses' => $inProgressCourses,
                'not_started_courses' => $notStartedCourses,
                'overall_progress' => round($overallProgress, 1),
                'major_breakdown' => $majorBreakdown
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching path statistics: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra khi tải thống kê'], 500);
        }
    }

    /**
     * Đánh dấu hoàn thành toàn bộ lộ trình của một khóa học
     */
    public function completeCoursePathProgress($courseId)
    {
        try {
            DB::beginTransaction();

            $course = CourseItem::findOrFail($courseId);

            LearningPath::where('course_item_id', $courseId)
                ->update(['is_completed' => true]);

            DB::commit();

            return response()->json([
                'message' => 'Đã hoàn thành toàn bộ lộ trình khóa học',
                'course_id' => $courseId
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error completing course path: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra khi hoàn thành lộ trình'], 500);
        }
    }

    /**
     * Reset lộ trình của một khóa học
     */
    public function resetCoursePathProgress($courseId)
    {
        try {
            DB::beginTransaction();

            $course = CourseItem::findOrFail($courseId);

            LearningPath::where('course_item_id', $courseId)
                ->update(['is_completed' => false]);

            DB::commit();

            return response()->json([
                'message' => 'Đã reset lộ trình khóa học',
                'course_id' => $courseId
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error resetting course path: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra khi reset lộ trình'], 500);
        }
    }

    public function getEnrollmentsGroupedByParent(Request $request)
    {
        $user = $request->user();
        $studentId = null;

        if ($user->isAdmin() && $request->has('student_id')) {
            $studentId = $request->input('student_id');
        } elseif ($user->student) {
            $studentId = $user->student->id;
        } else if ($user->isAdmin()) {
            // Fallback for admin to see some data for testing
            $firstStudent = \App\Models\Student::first();
            if ($firstStudent) {
                $studentId = $firstStudent->id;
            }
        }

        if (!$studentId) {
            return response()->json([]);
        }

        // Lấy tất cả các khóa học con (is_leaf = 1) có lộ trình học tập
        $courses = CourseItem::where('is_leaf', 1)
            ->whereHas('learningPaths')
            ->with(['parent.parent', 'learningPaths'])
            ->get();

        // Lấy danh sách ghi danh của học viên để tra cứu
        $studentEnrollments = Enrollment::where('student_id', $studentId)
            ->pluck('course_item_id')
            ->flip(); // flip để tra cứu O(1)

        $grouped = [];

        foreach ($courses as $course) {
            $parent = $course->getRootParent();

            if (!isset($grouped[$parent->id])) {
                $grouped[$parent->id] = [
                    'parent_id' => $parent->id,
                    'parent_name' => $parent->name,
                    'courses' => [],
                ];
            }

            $totalSteps = $course->learningPaths->count();
            $completedSteps = 0;
            $progress = 0;
            $isEnrolled = isset($studentEnrollments[$course->id]);

            // Chỉ tính tiến độ nếu học viên đã ghi danh
            if ($isEnrolled) {
                // Cần lấy đúng tiến độ của student, logic này cần được điều chỉnh
                // Tạm thời tính toán dựa trên learning paths chung
                $completedSteps = $course->learningPaths->where('is_completed', true)->count();
                if ($totalSteps > 0) {
                    $progress = ($completedSteps / $totalSteps) * 100;
                }
            }

            // Thêm khóa học vào danh sách nếu nó có lộ trình
            if ($totalSteps > 0) {
                $grouped[$parent->id]['courses'][] = [
                    'id' => $course->id,
                    'name' => $course->name,
                    'progress' => $progress,
                    'total_steps' => $totalSteps,
                    'completed_steps' => $completedSteps,
                    'is_enrolled' => $isEnrolled,
                    // 'enrollment_id' => $isEnrolled ? $studentEnrollments[$course->id] : null, // Cần lấy enrollment id đúng
                ];
            }
        }

        // Lọc ra các group không có course nào
        $filteredGroups = array_filter($grouped, function ($group) {
            return !empty($group['courses']);
        });

        return response()->json(array_values($filteredGroups));
    }

    /**
     * Xác định ngành học dựa trên tên khóa học hoặc parent
     */
    public function determineMajor($course)
    {
        $name = strtolower($course->name);
        $path = strtolower($course->path ?? '');

        if (str_contains($name, 'kế toán') || str_contains($path, 'kế toán')) {
            return 'Kế toán';
        } elseif (str_contains($name, 'marketing') || str_contains($path, 'marketing')) {
      return 'Marketing';
        } elseif (str_contains($name, 'quản trị') || str_contains($path, 'quản trị')) {
            return 'Quản trị';
        } elseif (str_contains($name, 'kinh tế') || str_contains($path, 'kinh tế')) {
            return 'Kinh tế';
        }

        return 'Khác';
    }
    public function getCoursePathDetails(Request $request, $courseId)
    {
        $course = CourseItem::with(['learningPaths' => function ($query) {
            $query->orderBy('order', 'asc');
        }])->find($courseId);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        $totalSteps = $course->learningPaths->count();
        $completedSteps = $course->learningPaths->where('is_completed', true)->count();
        $progress = $totalSteps > 0 ? ($completedSteps / $totalSteps) * 100 : 0;

        return response()->json([
            'id' => $course->id,
            'name' => $course->name,
            'total_steps' => $totalSteps,
            'completed_steps' => $completedSteps,
            'progress' => round($progress, 1),
            'paths' => $course->learningPaths,
        ]);
    }
      
}
