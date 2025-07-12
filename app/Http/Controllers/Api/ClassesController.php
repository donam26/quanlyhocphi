<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\CourseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClassesController extends Controller
{
    /**
     * Lấy danh sách các lớp học
     */
    public function index(Request $request)
    {
        $query = Classes::query();
        
        // Lọc theo course_item_id nếu có
        if ($request->has('course_item_id')) {
            $query->where('course_item_id', $request->course_item_id);
        }
        
        // Lọc theo trạng thái
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Lọc theo loại lớp
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        $classes = $query->with('courseItem')->orderBy('batch_number')->get();
        
        return response()->json($classes);
    }

    /**
     * Lưu lớp học mới
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_item_id' => 'required|exists:course_items,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:online,offline,hybrid',
            'batch_number' => 'nullable|integer',
            'max_students' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'registration_deadline' => 'nullable|date',
            'status' => 'nullable|in:upcoming,ongoing,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Kiểm tra xem course_item có phải là nút lá không
        $courseItem = CourseItem::findOrFail($request->course_item_id);
        if (!$courseItem->is_leaf) {
            return response()->json(['error' => 'Chỉ có thể tạo lớp học cho nút lá'], 422);
        }
        
        $class = Classes::create([
            'course_item_id' => $request->course_item_id,
            'name' => $request->name,
            'type' => $request->type,
            'batch_number' => $request->batch_number,
            'max_students' => $request->max_students,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'registration_deadline' => $request->registration_deadline,
            'status' => $request->status ?? 'upcoming',
            'notes' => $request->notes,
        ]);
        
        return response()->json($class, 201);
    }

    /**
     * Hiển thị chi tiết lớp học
     */
    public function show($id)
    {
        $class = Classes::with(['courseItem', 'enrollments.student'])->findOrFail($id);
        return response()->json($class);
    }

    /**
     * Cập nhật lớp học
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'course_item_id' => 'exists:course_items,id',
            'name' => 'string|max:255',
            'type' => 'in:online,offline,hybrid',
            'batch_number' => 'nullable|integer',
            'max_students' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'registration_deadline' => 'nullable|date',
            'status' => 'nullable|in:upcoming,ongoing,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $class = Classes::findOrFail($id);
        
        // Nếu thay đổi course_item_id, kiểm tra xem course_item mới có phải là nút lá không
        if ($request->has('course_item_id') && $request->course_item_id != $class->course_item_id) {
            $courseItem = CourseItem::findOrFail($request->course_item_id);
            if (!$courseItem->is_leaf) {
                return response()->json(['error' => 'Chỉ có thể gán lớp học cho nút lá'], 422);
            }
        }
        
        $class->update($request->all());
        
        return response()->json($class);
    }

    /**
     * Xóa lớp học
     */
    public function destroy($id)
    {
        $class = Classes::findOrFail($id);
        
        // Kiểm tra xem có học viên đã ghi danh không
        if ($class->enrollments()->count() > 0) {
            return response()->json(['error' => 'Không thể xóa lớp học đã có học viên ghi danh'], 422);
        }
        
        $class->delete();
        
        return response()->json(['message' => 'Đã xóa thành công']);
    }
} 