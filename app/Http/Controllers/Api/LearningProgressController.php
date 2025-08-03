<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
     * Lấy thông tin tiến độ học tập của khóa học
     */
    public function getCourseProgress($courseId)
    {
        try {
            $courseItem = CourseItem::findOrFail($courseId);
            $progressData = $this->learningProgressService->getCourseProgressData($courseItem);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'courseItem' => $progressData['courseItem'],
                    'learningPaths' => $progressData['learningPaths'],
                    'pathCompletionStats' => $progressData['pathCompletionStats']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tải thông tin tiến độ học tập: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thông tin tiến độ học tập của học viên
     */
    public function getStudentProgress($studentId, Request $request)
    {
        try {
            $student = Student::findOrFail($studentId);
            $enrollmentId = $request->input('enrollment_id');
            $progressData = $this->learningProgressService->getStudentProgressData($student, $enrollmentId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'student' => $student,
                    'enrollments' => $progressData['enrollments'],
                    'selectedEnrollment' => $progressData['selectedEnrollment'],
                    'learningPaths' => $progressData['learningPaths'],
                    'progress' => $progressData['progress']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tải thông tin tiến độ học tập: ' . $e->getMessage()
            ], 500);
        }
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
        
        try {
            $progress = $this->learningProgressService->updateProgress($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật tiến độ học tập thành công',
                'data' => [
                    'is_completed' => $progress->is_completed,
                    'completed_at' => $progress->completed_at
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
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
                'success' => true,
                'message' => 'Đã cập nhật tiến độ học tập thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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