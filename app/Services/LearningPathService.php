<?php

namespace App\Services;

use App\Models\CourseItem;
use App\Models\LearningPath;
use App\Models\LearningPathProgress;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class LearningPathService
{
    public function getLearningPaths($filters = [])
    {
        $query = LearningPath::query();
        
        if (isset($filters['course_item_id'])) {
            $query->where('course_item_id', $filters['course_item_id']);
        }
        
        return $query->orderBy('order')
            ->paginate(isset($filters['per_page']) ? $filters['per_page'] : 15);
    }

    public function getLearningPath($id)
    {
        return LearningPath::findOrFail($id);
    }

    public function createLearningPath(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Lấy max order hiện có
            $maxOrder = LearningPath::where('course_item_id', $data['course_item_id'])->max('order') ?? 0;
            
            $learningPath = LearningPath::create([
                'course_item_id' => $data['course_item_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'content' => $data['content'] ?? null,
                'order' => $maxOrder + 1,
                'is_required' => $data['is_required'] ?? true
            ]);
            
            DB::commit();
            return $learningPath;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateLearningPath(LearningPath $learningPath, array $data)
    {
        $learningPath->update($data);
        return $learningPath;
    }

    public function deleteLearningPath(LearningPath $learningPath)
    {
        DB::beginTransaction();
        
        try {
            // Xóa tiến độ liên quan
            LearningPathProgress::where('learning_path_id', $learningPath->id)->delete();
            
            // Xóa lộ trình
            $learningPath->delete();
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateLearningPathOrder(array $orders)
    {
        DB::beginTransaction();
        
        try {
            foreach ($orders as $id => $order) {
                LearningPath::where('id', $id)->update(['order' => $order]);
            }
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getLearningPathsByCourse(CourseItem $courseItem)
    {
        return LearningPath::where('course_item_id', $courseItem->id)
            ->orderBy('order')
            ->get();
    }

    public function getLearningPathProgress(LearningPath $learningPath, Student $student = null)
    {
        $query = LearningPathProgress::where('learning_path_id', $learningPath->id);
        
        if ($student) {
            $query->whereHas('enrollment', function($q) use ($student) {
                $q->where('student_id', $student->id);
            });
        }
        
        return $query->with('enrollment.student')->get();
    }

    public function updateProgressStatus(Enrollment $enrollment, LearningPath $learningPath, $isCompleted = true)
    {
        $progress = LearningPathProgress::firstOrNew([
            'enrollment_id' => $enrollment->id,
            'learning_path_id' => $learningPath->id
        ]);
        
        $progress->is_completed = $isCompleted;
        $progress->completion_date = $isCompleted ? now() : null;
        $progress->save();
        
        return $progress;
    }

    public function getStudentProgress(Student $student, CourseItem $courseItem = null)
    {
        $query = LearningPathProgress::whereHas('enrollment', function($q) use ($student) {
            $q->where('student_id', $student->id);
        });
        
        if ($courseItem) {
            $query->whereHas('learningPath', function($q) use ($courseItem) {
                $q->where('course_item_id', $courseItem->id);
            });
        }
        
        return $query->with(['learningPath', 'enrollment.courseItem'])->get();
    }

    public function getCourseCompletionStatus(CourseItem $courseItem, Student $student)
    {
        // Lấy tất cả lộ trình của khóa học
        $learningPaths = $this->getLearningPathsByCourse($courseItem);
        
        if ($learningPaths->isEmpty()) {
            return [
                'total' => 0,
                'completed' => 0,
                'percentage' => 0
            ];
        }
        
        // Lấy enrollment của học viên trong khóa học
        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_item_id', $courseItem->id)
            ->first();
        
        if (!$enrollment) {
            return [
                'total' => $learningPaths->count(),
                'completed' => 0,
                'percentage' => 0
            ];
        }
        
        // Lấy tiến độ của học viên trong khóa học
        $completedPaths = LearningPathProgress::where('enrollment_id', $enrollment->id)
            ->where('is_completed', true)
            ->count();
        
        $percentage = $learningPaths->count() > 0 
            ? round(($completedPaths / $learningPaths->count()) * 100) 
            : 0;
        
        return [
            'total' => $learningPaths->count(),
            'completed' => $completedPaths,
            'percentage' => $percentage
        ];
    }
} 