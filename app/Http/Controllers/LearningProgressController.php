<?php

namespace App\Http\Controllers;

use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\LearningPathProgress;
use App\Models\Student;
use App\Services\LearningProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearningProgressController extends Controller
{
    protected $learningProgressService;

    public function __construct(LearningProgressService $learningProgressService)
    {
        $this->learningProgressService = $learningProgressService;
    }

    /**
     * Hiển thị trang quản lý tiến độ học tập
     */
    public function index(Request $request)
    {
        $incompleteCoursesData = $this->learningProgressService->getIncompleteCoursesData();
        
        return view('learning-progress.index', compact('incompleteCoursesData'));
    }

    /**
     * Hiển thị tiến độ học tập của một học viên cụ thể
     */
    public function showStudentProgress(Request $request, Student $student)
    {
        $selectedEnrollmentId = $request->input('enrollment_id');
        $progressData = $this->learningProgressService->getStudentProgressData($student, $selectedEnrollmentId);
        
        return view('learning-progress.student', [
            'student' => $student,
            'enrollments' => $progressData['enrollments'],
            'selectedEnrollment' => $progressData['selectedEnrollment'],
            'learningPaths' => $progressData['learningPaths'],
            'progress' => $progressData['progress']
        ]);
    }

    /**
     * Hiển thị tiến độ học tập của một khóa học cụ thể
     */
    public function showCourseProgress(CourseItem $courseItem)
    {
        $progressData = $this->learningProgressService->getCourseProgressData($courseItem);
        
        return view('learning-progress.course', [
            'courseItem' => $progressData['courseItem'],
            'learningPaths' => $progressData['learningPaths'],
            'pathCompletionStats' => $progressData['pathCompletionStats']
        ]);
    }

    /**
     * Cập nhật tiến độ học tập
     */
    public function updateProgress(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'learning_path_id' => 'required|exists:learning_paths,id',
            'is_completed' => 'required|boolean'
        ]);
        
        $progress = $this->learningProgressService->updateProgress($validated);
        
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
        $validated = $request->validate([
            'progress_data' => 'required|array',
            'progress_data.*.enrollment_id' => 'required|exists:enrollments,id',
            'progress_data.*.learning_path_id' => 'required|exists:learning_paths,id',
            'progress_data.*.is_completed' => 'required|boolean'
        ]);
        
        try {
            $this->learningProgressService->updateBulkProgress($validated['progress_data']);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Đã cập nhật tiến độ học tập thành công'
            ]);
        } catch (\Exception $e) {
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
        $validated = $request->validate([
            'path_id' => 'required|exists:learning_paths,id',
            'course_id' => 'required|exists:course_items,id',
            'is_completed' => 'required|boolean'
        ]);
        
        try {
            $this->learningProgressService->updatePathStatus(
                $validated['path_id'], 
                $validated['course_id'], 
                $validated['is_completed']
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái lộ trình học tập thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
