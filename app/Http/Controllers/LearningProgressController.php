<?php

namespace App\Http\Controllers;

use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\LearningPathProgress;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearningProgressController extends Controller
{
    /**
     * Hiển thị trang quản lý tiến độ học tập
     */
    public function index(Request $request)
    {
        $courseItems = CourseItem::where('is_leaf', true)
                                ->where('active', true)
                                ->orderBy('name')
                                ->get();

        $selectedCourseId = $request->input('course_id');
        $selectedCourse = null;
        $learningPaths = collect([]);
        $pathCompletionStats = [];
        $totalStudents = 0;
        $avgProgress = 0;
        
        if ($selectedCourseId) {
            $selectedCourse = CourseItem::findOrFail($selectedCourseId);
            $learningPaths = $selectedCourse->learningPaths()->orderBy('order')->get();
            
            // Đếm tổng số học viên đã đăng ký khóa học
            $totalStudents = Enrollment::where('course_item_id', $selectedCourseId)
                ->where('status', 'enrolled')
                ->count();

            // Tính toán tiến độ chung của khóa học
            foreach ($learningPaths as $path) {
                // Đếm số học viên đã hoàn thành path này
                $completedCount = LearningPathProgress::whereHas('enrollment', function($query) use ($selectedCourseId) {
                    $query->where('course_item_id', $selectedCourseId)
                        ->where('status', 'enrolled');
                })
                ->where('learning_path_id', $path->id)
                ->where('is_completed', true)
                ->count();
                
                // Tính tỷ lệ hoàn thành cho path này
                $completionRate = $totalStudents > 0 ? round(($completedCount / $totalStudents) * 100) : 0;
                
                $pathCompletionStats[$path->id] = [
                    'path' => $path,
                    'total_students' => $totalStudents,
                    'completed_count' => $completedCount,
                    'completion_rate' => $completionRate
                ];
            }
            
            // Tính tiến độ trung bình của toàn khóa học
            if ($learningPaths->count() > 0) {
                $totalCompletionRate = 0;
                foreach ($pathCompletionStats as $stat) {
                    $totalCompletionRate += $stat['completion_rate'];
                }
                $avgProgress = round($totalCompletionRate / $learningPaths->count());
            }
        }
        
        return view('learning-progress.index', compact('courseItems', 'selectedCourse', 'learningPaths', 'pathCompletionStats', 'totalStudents', 'avgProgress'));
    }

    /**
     * Hiển thị tiến độ học tập của một học viên cụ thể
     */
    public function showStudentProgress(Request $request, Student $student)
    {
        $enrollments = Enrollment::where('student_id', $student->id)
                                ->where('status', 'enrolled')
                                ->with('courseItem')
                                ->get();
        
        $selectedEnrollmentId = $request->input('enrollment_id');
        $selectedEnrollment = null;
        $learningPaths = collect([]);
        $progress = collect([]);
        
        if ($selectedEnrollmentId) {
            $selectedEnrollment = Enrollment::findOrFail($selectedEnrollmentId);
            $learningPaths = $selectedEnrollment->courseItem->learningPaths()->orderBy('order')->get();
            
            // Lấy tiến độ học tập của học viên
            $progress = LearningPathProgress::where('enrollment_id', $selectedEnrollmentId)
                                            ->get()
                                            ->keyBy('learning_path_id');
        }
        
        return view('learning-progress.student', compact('student', 'enrollments', 'selectedEnrollment', 'learningPaths', 'progress'));
    }

    /**
     * Hiển thị tiến độ học tập của một khóa học cụ thể
     */
    public function showCourseProgress(CourseItem $courseItem)
    {
        // Lấy danh sách lộ trình học tập của khóa học
        $learningPaths = $courseItem->learningPaths()->orderBy('order')->get();
        
        // Đếm tổng số học viên đã đăng ký khóa học
        $totalStudents = Enrollment::where('course_item_id', $courseItem->id)
            ->where('status', 'enrolled')
            ->count();

        // Tính toán tiến độ đơn giản - chỉ xác định lộ trình nào đã hoàn thành
        $pathCompletionStats = [];
        
        foreach ($learningPaths as $path) {
            // Đếm số học viên đã hoàn thành path này
            $completedCount = LearningPathProgress::whereHas('enrollment', function($query) use ($courseItem) {
                $query->where('course_item_id', $courseItem->id)
                    ->where('status', 'enrolled');
            })
            ->where('learning_path_id', $path->id)
            ->where('is_completed', true)
            ->count();
            
            $pathCompletionStats[$path->id] = [
                'path' => $path,
                'completed_count' => $completedCount
            ];
        }
        
        return view('learning-progress.course', compact('courseItem', 'learningPaths', 'pathCompletionStats'));
    }

    /**
     * Cập nhật tiến độ học tập
     */
    public function updateProgress(Request $request)
    {
        $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'learning_path_id' => 'required|exists:learning_paths,id',
            'is_completed' => 'required|boolean'
        ]);
        
        $progress = LearningPathProgress::updateOrCreate(
            [
                'enrollment_id' => $request->enrollment_id,
                'learning_path_id' => $request->learning_path_id
            ],
            [
                'is_completed' => $request->is_completed,
                'completed_at' => $request->is_completed ? now() : null
            ]
        );
        
        return response()->json([
            'status' => 'success',
            'is_completed' => $progress->is_completed,
            'completed_at' => $progress->completed_at
        ]);
    }

    /**
     * Cập nhật nhiều tiến độ học tập cùng lúc
     */
    public function updateBulkProgress(Request $request)
    {
        $request->validate([
            'progress_data' => 'required|array',
            'progress_data.*.enrollment_id' => 'required|exists:enrollments,id',
            'progress_data.*.learning_path_id' => 'required|exists:learning_paths,id',
            'progress_data.*.is_completed' => 'required|boolean'
        ]);
        
        DB::beginTransaction();
        
        try {
            foreach ($request->progress_data as $item) {
                LearningPathProgress::updateOrCreate(
                    [
                        'enrollment_id' => $item['enrollment_id'],
                        'learning_path_id' => $item['learning_path_id']
                    ],
                    [
                        'is_completed' => $item['is_completed'],
                        'completed_at' => $item['is_completed'] ? now() : null
                    ]
                );
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Đã cập nhật tiến độ học tập thành công'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật trạng thái hoàn thành của một lộ trình học tập cho khóa học
     */
    public function updatePathStatus(Request $request)
    {
        $request->validate([
            'path_id' => 'required|exists:learning_paths,id',
            'course_id' => 'required|exists:course_items,id',
            'is_completed' => 'required|boolean'
        ]);
        
        $pathId = $request->path_id;
        $courseId = $request->course_id;
        $isCompleted = $request->is_completed;
        
        try {
            DB::beginTransaction();
            
            // Lấy tất cả học viên đăng ký khóa học này
            $enrollments = Enrollment::where('course_item_id', $courseId)
                ->where('status', 'enrolled')
                ->get();
             
            
            // Cập nhật trạng thái hoàn thành cho tất cả học viên
            foreach ($enrollments as $enrollment) {
                LearningPathProgress::updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'learning_path_id' => $pathId
                    ],
                    [
                        'is_completed' => $isCompleted,
                        'completed_at' => $isCompleted ? now() : null
                    ]
                );
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái lộ trình học tập thành công!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
