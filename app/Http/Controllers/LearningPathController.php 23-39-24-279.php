<?php

namespace App\Http\Controllers;

use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\LearningPathProgress;
use App\Services\LearningPathService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LearningPathController extends Controller
{
    protected $learningPathService;

    public function __construct(LearningPathService $learningPathService)
    {
        $this->learningPathService = $learningPathService;
    }

    /**
     * Hiển thị form để thêm lộ trình học tập cho khóa học
     */
    public function create(CourseItem $courseItem)
    {
        return view('learning-paths.create', compact('courseItem'));
    }
    
    /**
     * Lưu lộ trình học tập mới
     */
    public function store(Request $request, CourseItem $courseItem)
    {
        $validated = $request->validate([
            'paths' => 'required|array',
            'paths.*.title' => 'required|string|max:255',
            'paths.*.description' => 'nullable|string',
            'paths.*.order' => 'required|integer|min:1'
        ]);
        
        foreach ($validated['paths'] as $path) {
            $this->learningPathService->createLearningPath([
                'course_item_id' => $courseItem->id,
                'title' => $path['title'],
                'description' => $path['description'] ?? null,
                'order' => $path['order']
            ]);
        }
        
        return redirect()->route('course-items.tree')->with('success', 'Đã tạo thành công lộ trình học tập!');
    }
    
    /**
     * Hiển thị form để chỉnh sửa lộ trình học tập
     */
    public function edit(CourseItem $courseItem)
    {
        $paths = $this->learningPathService->getLearningPathsByCourse($courseItem);
        
        return view('learning-paths.edit', compact('courseItem', 'paths'));
    }
    
    /**
     * Cập nhật lộ trình học tập
     */
    public function update(Request $request, CourseItem $courseItem)
    {
        $validated = $request->validate([
            'paths' => 'required|array',
            'paths.*.id' => 'nullable|exists:learning_paths,id',
            'paths.*.title' => 'required|string|max:255',
            'paths.*.description' => 'nullable|string',
            'paths.*.order' => 'required|integer|min:1'
        ]);
        
        // Xóa các lộ trình không còn trong danh sách
        $existingPathIds = collect($validated['paths'])->pluck('id')->filter()->toArray();
        $currentPaths = $this->learningPathService->getLearningPathsByCourse($courseItem);
        
        foreach ($currentPaths as $path) {
            if (!in_array($path->id, $existingPathIds)) {
                $this->learningPathService->deleteLearningPath($path);
            }
        }
            
        // Cập nhật hoặc tạo mới lộ trình
        foreach ($validated['paths'] as $path) {
            if (!empty($path['id'])) {
                // Cập nhật lộ trình hiện có
                $learningPath = $this->learningPathService->getLearningPath($path['id']);
                $this->learningPathService->updateLearningPath($learningPath, [
                    'title' => $path['title'],
                    'description' => $path['description'] ?? null,
                    'order' => $path['order']
                ]);
            } else {
                // Tạo mới lộ trình
                $this->learningPathService->createLearningPath([
                    'course_item_id' => $courseItem->id,
                    'title' => $path['title'],
                    'description' => $path['description'] ?? null,
                    'order' => $path['order']
                ]);
            }
        }
        
        return redirect()->route('course-items.tree')->with('success', 'Đã cập nhật thành công lộ trình học tập!');
    }
    
    /**
     * Đánh dấu hoàn thành/chưa hoàn thành lộ trình
     */
    public function toggleCompletion(Request $request, Enrollment $enrollment, LearningPath $learningPath)
    {
        // Kiểm tra xem học viên có đăng ký khóa học này không
        if ($enrollment->course_item_id != $learningPath->course_item_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Học viên không đăng ký khóa học này'
            ], 403);
        }
        
        // Đảo trạng thái hoàn thành
        $progress = LearningPathProgress::firstOrCreate(
            [
                'enrollment_id' => $enrollment->id,
                'learning_path_id' => $learningPath->id
            ]
        );
        
        $newStatus = !$progress->is_completed;
        $progress = $this->learningPathService->updateProgressStatus($enrollment, $learningPath, $newStatus);
        
        return response()->json([
            'status' => 'success',
            'is_completed' => $progress->is_completed,
            'completed_at' => $progress->completed_at
        ]);
    }
    
    /**
     * Đánh dấu hoàn thành/chưa hoàn thành lộ trình từ trang khóa học (không cần enrollment)
     */
    public function toggleCompletionFromCourse(Request $request, CourseItem $courseItem, LearningPath $learningPath)
    {
        // Kiểm tra xem lộ trình có thuộc khóa học này không
        if ($learningPath->course_item_id != $courseItem->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lộ trình không thuộc về khóa học này'
            ], 403);
        }
        
        // Sử dụng session để lưu trạng thái cho người dùng hiện tại
        $sessionKey = "learning_path_{$learningPath->id}_completed";
        $isCompleted = $request->session()->get($sessionKey, false);
        
        // Đảo trạng thái hoàn thành
        $newCompletionStatus = !$isCompleted;
        $request->session()->put($sessionKey, $newCompletionStatus);
        
        // Đồng bộ với database - cập nhật cho tất cả học viên đăng ký khóa học này
        if (Auth::check() && Auth::user()->is_admin) {
            // Lấy tất cả enrollment của khóa học
            $enrollments = Enrollment::where('course_item_id', $courseItem->id)
                ->where('status', \App\Enums\EnrollmentStatus::ACTIVE->value)
                ->get();
            
            foreach ($enrollments as $enrollment) {
                $this->learningPathService->updateProgressStatus($enrollment, $learningPath, $newCompletionStatus);
            }
        }
        
        return response()->json([
            'status' => 'success',
            'is_completed' => $newCompletionStatus,
            'completed_at' => $newCompletionStatus ? now() : null
        ]);
    }
}
