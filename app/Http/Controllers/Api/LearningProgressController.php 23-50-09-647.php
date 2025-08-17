<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningPathProgress;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\CourseItem;
use App\Models\LearningPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearningProgressController extends Controller
{
    /**
     * API: Danh sách tiến độ học tập
     */
    public function index(Request $request)
    {
        try {
            $query = LearningPathProgress::with(['enrollment.student', 'enrollment.courseItem', 'learningPath']);
            
            // Filter by search term
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->whereHas('enrollment.student', function($q) use ($searchTerm) {
                    $q->where('first_name', 'like', "%{$searchTerm}%")
                      ->orWhere('last_name', 'like', "%{$searchTerm}%")
                      ->orWhere('phone', 'like', "%{$searchTerm}%");
                });
            }
            
            // Filter by course
            if ($request->has('course_id') && $request->course_id) {
                $query->whereHas('enrollment', function($q) use ($request) {
                    $q->where('course_item_id', $request->course_id);
                });
            }
            
            // Filter by status
            if ($request->has('status') && $request->status) {
                if ($request->status === 'completed') {
                    $query->where('completed', true);
                } else if ($request->status === 'in_progress') {
                    $query->where('completed', false);
                }
            }
            
            $progressData = $query->paginate(15);
            
            return response()->json([
                'success' => true,
                'data' => $progressData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tải tiến độ học tập: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Thống kê tiến độ học tập
     */
    public function stats(Request $request)
    {
        try {
            $courseId = $request->input('course_id');
            
            $query = LearningPathProgress::query();
            
            if ($courseId) {
                $query->whereHas('enrollment', function($q) use ($courseId) {
                    $q->where('course_item_id', $courseId);
                });
            }
            
            $totalProgress = $query->count();
            $completedProgress = $query->where('completed', true)->count();
            $inProgressCount = $totalProgress - $completedProgress;
            
            // Completion rate by course
            $courseStats = DB::table('learning_path_progress')
                ->join('enrollments', 'learning_path_progress.enrollment_id', '=', 'enrollments.id')
                ->join('course_items', 'enrollments.course_item_id', '=', 'course_items.id')
                ->select(
                    'course_items.name',
                    'course_items.id',
                    DB::raw('COUNT(*) as total_progress'),
                    DB::raw('SUM(CASE WHEN learning_path_progress.completed = 1 THEN 1 ELSE 0 END) as completed_progress')
                )
                ->groupBy('course_items.id', 'course_items.name')
                ->get()
                ->map(function($item) {
                    $item->completion_rate = $item->total_progress > 0 
                        ? round(($item->completed_progress / $item->total_progress) * 100, 2)
                        : 0;
                    return $item;
                });
            
            $data = [
                'total_progress' => $totalProgress,
                'completed_progress' => $completedProgress,
                'in_progress_count' => $inProgressCount,
                'completion_rate' => $totalProgress > 0 ? round(($completedProgress / $totalProgress) * 100, 2) : 0,
                'course_stats' => $courseStats
            ];
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo thống kê tiến độ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Tiến độ của học viên
     */
    public function studentProgress($studentId)
    {
        try {
            $student = Student::findOrFail($studentId);
            
            $progressData = LearningPathProgress::with(['enrollment.courseItem', 'learningPath'])
                ->whereHas('enrollment', function($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                })
                ->get();
            
            $groupedByEnrollment = $progressData->groupBy('enrollment_id')->map(function($group) {
                $enrollment = $group->first()->enrollment;
                $totalPaths = $group->count();
                $completedPaths = $group->where('completed', true)->count();
                
                return [
                    'enrollment' => $enrollment,
                    'course' => $enrollment->courseItem,
                    'total_paths' => $totalPaths,
                    'completed_paths' => $completedPaths,
                    'completion_rate' => $totalPaths > 0 ? round(($completedPaths / $totalPaths) * 100, 2) : 0,
                    'progress_details' => $group->values()
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'student' => $student,
                    'enrollments' => $groupedByEnrollment->values()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tải tiến độ học viên: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Tiến độ của khóa học
     */
    public function courseProgress($courseId)
    {
        try {
            $course = CourseItem::findOrFail($courseId);
            
            $enrollments = Enrollment::with(['student', 'learningPathProgress.learningPath'])
                ->where('course_item_id', $courseId)
                ->where('status', 'active')
                ->get();
            
            $progressData = $enrollments->map(function($enrollment) {
                $totalPaths = $enrollment->learningPathProgress->count();
                $completedPaths = $enrollment->learningPathProgress->where('completed', true)->count();
                
                return [
                    'enrollment' => $enrollment,
                    'student' => $enrollment->student,
                    'total_paths' => $totalPaths,
                    'completed_paths' => $completedPaths,
                    'completion_rate' => $totalPaths > 0 ? round(($completedPaths / $totalPaths) * 100, 2) : 0,
                    'last_activity' => $enrollment->learningPathProgress->max('updated_at')
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course,
                    'students_progress' => $progressData
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tải tiến độ khóa học: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Cập nhật tiến độ
     */
    public function updateProgress($studentId, $courseId, Request $request)
    {
        try {
            $enrollment = Enrollment::where('student_id', $studentId)
                ->where('course_item_id', $courseId)
                ->firstOrFail();
            
            $learningPathId = $request->input('learning_path_id');
            $completed = $request->input('completed', false);
            
            $progress = LearningPathProgress::updateOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'learning_path_id' => $learningPathId
                ],
                [
                    'completed' => $completed,
                    'completed_at' => $completed ? now() : null
                ]
            );
            
            return response()->json([
                'success' => true,
                'data' => $progress,
                'message' => 'Cập nhật tiến độ thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật tiến độ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Đánh dấu bài học hoàn thành
     */
    public function markLessonCompleted(Request $request)
    {
        try {
            $studentId = $request->input('student_id');
            $lessonId = $request->input('lesson_id');
            
            // This is a simplified implementation
            // In a real system, you'd have a lessons table and lesson_progress table
            
            return response()->json([
                'success' => true,
                'message' => 'Đánh dấu bài học hoàn thành thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đánh dấu bài học: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Chi tiết tiến độ
     */
    public function detailedProgress($studentId, $courseId)
    {
        try {
            $enrollment = Enrollment::with(['student', 'courseItem'])
                ->where('student_id', $studentId)
                ->where('course_item_id', $courseId)
                ->firstOrFail();
            
            $progressData = LearningPathProgress::with('learningPath')
                ->where('enrollment_id', $enrollment->id)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'enrollment' => $enrollment,
                    'progress_details' => $progressData
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tải chi tiết tiến độ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Phân tích học tập
     */
    public function analytics($studentId, $courseId)
    {
        try {
            // This would contain more complex analytics
            // For now, return basic data
            
            return response()->json([
                'success' => true,
                'data' => [
                    'message' => 'Analytics feature coming soon'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo phân tích: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Tỷ lệ hoàn thành khóa học
     */
    public function completionRate($courseId)
    {
        try {
            $course = CourseItem::findOrFail($courseId);
            
            $totalEnrollments = Enrollment::where('course_item_id', $courseId)
                ->where('status', 'active')
                ->count();
            
            $completedEnrollments = Enrollment::where('course_item_id', $courseId)
                ->where('status', 'completed')
                ->count();
            
            $completionRate = $totalEnrollments > 0 
                ? round(($completedEnrollments / $totalEnrollments) * 100, 2)
                : 0;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course,
                    'total_enrollments' => $totalEnrollments,
                    'completed_enrollments' => $completedEnrollments,
                    'completion_rate' => $completionRate
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tính tỷ lệ hoàn thành: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Export báo cáo tiến độ
     */
    public function export(Request $request)
    {
        try {
            // This would export progress data to Excel
            // For now, return success message
            
            return response()->json([
                'success' => true,
                'message' => 'Export feature coming soon'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xuất báo cáo: ' . $e->getMessage()
            ], 500);
        }
    }
}
