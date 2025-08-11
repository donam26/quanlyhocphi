<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\LearningPathProgress;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class LearningProgressService
{
    protected $learningPathService;

    public function __construct(LearningPathService $learningPathService)
    {
        $this->learningPathService = $learningPathService;
    }

    /**
     * Lấy danh sách các khóa học chưa hoàn thành
     */
    public function getIncompleteCoursesData()
    {
        // Lấy các khóa học đang hoạt động
        $courseItems = CourseItem::where('is_leaf', true)
                            ->where('active', true)
                            ->orderBy('name')
                            ->get();
        
        $incompleteCoursesData = [];
        
        foreach ($courseItems as $courseItem) {
            // Lấy danh sách lộ trình học tập của khóa học
            $learningPaths = $courseItem->learningPaths()->orderBy('order')->get();
            
            if ($learningPaths->isEmpty()) {
                // Bỏ qua khóa học không có lộ trình
                continue;
            }
            
            // Đếm tổng số học viên đã đăng ký khóa học (cho hiển thị)
            $totalStudents = Enrollment::where('course_item_id', $courseItem->id)
                ->where('status', EnrollmentStatus::ACTIVE->value)
                ->count();
                
            // Tính toán đơn giản dựa trên field is_completed
            $totalPathways = $learningPaths->count();
            $completedCount = $learningPaths->where('is_completed', true)->count();
            
            // Tính phần trăm hoàn thành
            $progressPercentage = $totalPathways > 0 ? round(($completedCount / $totalPathways) * 100) : 0;
            
            // Thêm tất cả khóa học có lộ trình và chưa hoàn thành 100%
            if ($progressPercentage < 100) {
                $incompleteCoursesData[] = [
                    'course' => $courseItem,
                    'total_pathways' => $totalPathways,
                    'completed_pathways' => $completedCount,
                    'progress_percentage' => $progressPercentage,
                    'total_students' => $totalStudents
                ];
            }
        }
        
        return $incompleteCoursesData;
    }

    /**
     * Lấy tiến độ học tập của học viên
     */
    public function getStudentProgressData(Student $student, $selectedEnrollmentId = null)
    {
        $enrollments = Enrollment::where('student_id', $student->id)
                                ->where('status', EnrollmentStatus::ACTIVE->value)
                                ->with('courseItem')
                                ->get();
        
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
        
        return [
            'enrollments' => $enrollments,
            'selectedEnrollment' => $selectedEnrollment,
            'learningPaths' => $learningPaths,
            'progress' => $progress
        ];
    }

    /**
     * Lấy tiến độ học tập của một khóa học
     */
    public function getCourseProgressData(CourseItem $courseItem)
    {
        // Lấy danh sách lộ trình học tập của khóa học với trạng thái completion
        $learningPaths = $courseItem->learningPaths()->orderBy('order')->get();
        
        // Tạo stats đơn giản dựa trên field is_completed
        $pathCompletionStats = [];
        
        foreach ($learningPaths as $path) {
            $pathCompletionStats[$path->id] = [
                'path' => $path,
                'completed_count' => $path->is_completed ? 1 : 0 // Đơn giản: 1 nếu completed, 0 nếu chưa
            ];
        }
        
        return [
            'courseItem' => $courseItem,
            'learningPaths' => $learningPaths,
            'pathCompletionStats' => $pathCompletionStats,
            'totalStudents' => 0 // Không cần đếm students nữa
        ];
    }

    /**
     * Cập nhật tiến độ học tập
     */
    public function updateProgress(array $data)
    {
        $progress = LearningPathProgress::updateOrCreate(
            [
                'enrollment_id' => $data['enrollment_id'],
                'learning_path_id' => $data['learning_path_id']
            ],
            [
                'is_completed' => $data['is_completed'],
                'completed_at' => $data['is_completed'] ? now() : null
            ]
        );
        
        return $progress;
    }

    /**
     * Cập nhật nhiều tiến độ học tập cùng lúc
     */
    public function updateBulkProgress(array $progressData)
    {
        DB::beginTransaction();
        
        try {
            foreach ($progressData as $item) {
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
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cập nhật trạng thái hoàn thành của một lộ trình học tập cho khóa học
     */
    public function updatePathStatus($pathId, $courseId, $isCompleted)
    {
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
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
} 