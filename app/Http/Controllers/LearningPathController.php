<?php

namespace App\Http\Controllers;

use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\LearningPathProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LearningPathController extends Controller
{
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
            LearningPath::create([
                'course_item_id' => $courseItem->id,
                'title' => $path['title'],
                'description' => $path['description'] ?? null,
                'order' => $path['order']
            ]);
        }
        
        return redirect()->route('course-items.show', $courseItem)
            ->with('success', 'Lộ trình học tập đã được tạo thành công!');
    }
    
    /**
     * Hiển thị form để chỉnh sửa lộ trình học tập
     */
    public function edit(CourseItem $courseItem)
    {
        $paths = $courseItem->learningPaths;
        
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
        LearningPath::where('course_item_id', $courseItem->id)
            ->whereNotIn('id', $existingPathIds)
            ->delete();
            
        // Cập nhật hoặc tạo mới lộ trình
        foreach ($validated['paths'] as $path) {
            if (!empty($path['id'])) {
                // Cập nhật lộ trình hiện có
                LearningPath::find($path['id'])->update([
                    'title' => $path['title'],
                    'description' => $path['description'] ?? null,
                    'order' => $path['order']
                ]);
            } else {
                // Tạo mới lộ trình
                LearningPath::create([
                    'course_item_id' => $courseItem->id,
                    'title' => $path['title'],
                    'description' => $path['description'] ?? null,
                    'order' => $path['order']
                ]);
            }
        }
        
        return redirect()->route('course-items.show', $courseItem)
            ->with('success', 'Lộ trình học tập đã được cập nhật thành công!');
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
        
        // Tìm hoặc tạo bản ghi tiến độ
        $progress = LearningPathProgress::firstOrCreate(
            [
                'enrollment_id' => $enrollment->id,
                'learning_path_id' => $learningPath->id
            ]
        );
        
        // Đảo trạng thái hoàn thành
        $progress->is_completed = !$progress->is_completed;
        $progress->completed_at = $progress->is_completed ? now() : null;
        $progress->save();
        
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
                ->where('status', 'enrolled')
                ->get();
            
            foreach ($enrollments as $enrollment) {
                // Cập nhật hoặc tạo bản ghi tiến độ
                LearningPathProgress::updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'learning_path_id' => $learningPath->id
                    ],
                    [
                        'is_completed' => $newCompletionStatus,
                        'completed_at' => $newCompletionStatus ? now() : null
                    ]
                );
            }
        }
        
        return response()->json([
            'status' => 'success',
            'is_completed' => $newCompletionStatus,
            'completed_at' => $newCompletionStatus ? now() : null
        ]);
    }
}
