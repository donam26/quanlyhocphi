<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassesController extends Controller
{
    /**
     * Hiển thị danh sách lớp học
     */
    public function index()
    {
        $classes = Classes::with('courseItem')
                    ->orderBy('status')
                    ->orderBy('start_date', 'desc')
                    ->paginate(15);
        
        return view('classes.index', compact('classes'));
    }

    /**
     * Hiển thị form tạo lớp học mới
     */
    public function create()
    {
        // Lấy danh sách khóa học là nút lá để chọn
        $courseItems = CourseItem::where('is_leaf', true)
                        ->where('active', true)
                        ->orderBy('level')
                        ->orderBy('name')
                        ->get();
        
        return view('classes.create', compact('courseItems'));
    }

    /**
     * Lưu lớp học mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'course_item_id' => 'required|exists:course_items,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:online,offline,hybrid',
            'batch_number' => 'nullable|integer',
            'max_students' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'registration_deadline' => 'nullable|date',
            'status' => 'nullable|in:planned,open,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
        ]);
        
        // Kiểm tra xem course_item có phải là nút lá không
        $courseItem = CourseItem::findOrFail($request->course_item_id);
        if (!$courseItem->is_leaf) {
            return back()->withErrors(['course_item_id' => 'Chỉ có thể tạo lớp học cho nút lá']);
        }
        
        $class = Classes::create($request->all());
        
        return redirect()->route('classes.show', $class)
                ->with('success', 'Đã tạo lớp học mới thành công!');
    }

    /**
     * Hiển thị chi tiết lớp học
     */
    public function show(Classes $class)
    {
        $class->load(['courseItem', 'enrollments.student', 'enrollments.payments']);
        
        // Thống kê
        $stats = [
            'total_students' => $class->enrollments->count(),
            'total_revenue' => $class->enrollments->sum(function($enrollment) {
                return $enrollment->payments->where('status', 'confirmed')->sum('amount');
            }),
            'unpaid_count' => $class->enrollments->filter(function($enrollment) {
                return $enrollment->payments->where('status', 'pending')->count() > 0;
            })->count(),
        ];
        
        return view('classes.show', compact('class', 'stats'));
    }

    /**
     * Hiển thị form chỉnh sửa lớp học
     */
    public function edit(Classes $class)
    {
        // Lấy danh sách khóa học là nút lá để chọn
        $courseItems = CourseItem::where('is_leaf', true)
                        ->where('active', true)
                        ->orderBy('level')
                        ->orderBy('name')
                        ->get();
        
        return view('classes.edit', compact('class', 'courseItems'));
    }

    /**
     * Cập nhật lớp học
     */
    public function update(Request $request, Classes $class)
    {
        $request->validate([
            'course_item_id' => 'required|exists:course_items,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:online,offline,hybrid',
            'batch_number' => 'nullable|integer',
            'max_students' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'registration_deadline' => 'nullable|date',
            'status' => 'nullable|in:planned,open,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
        ]);
        
        // Nếu thay đổi course_item_id, kiểm tra xem course_item mới có phải là nút lá không
        if ($request->course_item_id != $class->course_item_id) {
            $courseItem = CourseItem::findOrFail($request->course_item_id);
            if (!$courseItem->is_leaf) {
                return back()->withErrors(['course_item_id' => 'Chỉ có thể gán lớp học cho nút lá']);
            }
        }
        
        $class->update($request->all());
        
        return redirect()->route('classes.show', $class)
                ->with('success', 'Đã cập nhật lớp học thành công!');
    }

    /**
     * Xóa lớp học
     */
    public function destroy(Classes $class)
    {
        // Lấy thông tin course_item_id để redirect sau khi xóa
        $courseItemId = $class->course_item_id;
        
        // Xóa tất cả các ghi danh và thanh toán liên quan
        foreach ($class->enrollments as $enrollment) {
            // Xóa các thanh toán liên quan
            $enrollment->payments()->delete();
            
            // Xóa các điểm danh liên quan
            $enrollment->attendances()->delete();
            
            // Xóa ghi danh
            $enrollment->delete();
        }
        
        // Xóa lớp học
        $class->delete();
        
        return redirect()->route('course-items.show', $courseItemId)
                ->with('success', 'Đã xóa lớp học thành công!');
    }
    
    /**
     * Hiển thị danh sách học viên của lớp
     */
    public function students(Classes $class)
    {
        $class->load(['enrollments.student', 'enrollments.payments']);
        
        return view('classes.students', compact('class'));
    }
    
    /**
     * Hiển thị báo cáo tài chính của lớp
     */
    public function financialReport(Classes $class)
    {
        $class->load(['enrollments.student', 'enrollments.payments']);
        
        // Thống kê
        $stats = [
            'total_students' => $class->enrollments->count(),
            'total_revenue' => $class->enrollments->sum(function($enrollment) {
                return $enrollment->payments->where('status', 'confirmed')->sum('amount');
            }),
            'unpaid_amount' => $class->enrollments->sum(function($enrollment) {
                return max(0, $enrollment->final_fee - $enrollment->payments->where('status', 'confirmed')->sum('amount'));
            }),
        ];
        
        return view('classes.financial-report', compact('class', 'stats'));
    }
    
    /**
     * Đánh dấu lớp học đã đầy
     */
    public function markFull(Classes $class)
    {
        $class->update([
            'status' => 'in_progress',
            'max_students' => $class->enrollments()->count(),
        ]);
        
        return back()->with('success', 'Đã đánh dấu lớp học đã đầy!');
    }
    
    /**
     * Thay đổi trạng thái lớp học
     */
    public function changeStatus(Request $request, Classes $class)
    {
        $request->validate([
            'status' => 'required|in:planned,open,in_progress,completed,cancelled',
        ]);
        
        $class->update(['status' => $request->status]);
        
        return back()->with('success', 'Đã thay đổi trạng thái lớp học!');
    }
    
    /**
     * Nhân bản lớp học
     */
    public function duplicate(Classes $class)
    {
        $newClass = $class->replicate();
        $newClass->name = $class->name . ' (Bản sao)';
        $newClass->batch_number = $class->batch_number + 1;
        $newClass->status = 'planned';
        $newClass->save();
        
        return redirect()->route('classes.edit', $newClass)
                ->with('success', 'Đã nhân bản lớp học thành công!');
    }
    
    /**
     * Hiển thị tổng quan các lớp học
     */
    public function overview()
    {
        $stats = [
            'total_classes' => Classes::count(),
            'active_classes' => Classes::whereIn('status', ['open', 'in_progress'])->count(),
            'upcoming_classes' => Classes::where('status', 'planned')->count(),
            'completed_classes' => Classes::where('status', 'completed')->count(),
        ];
        
        $classesByType = Classes::select('type', DB::raw('count(*) as total'))
                        ->groupBy('type')
                        ->get();
        
        $classesByStatus = Classes::select('status', DB::raw('count(*) as total'))
                        ->groupBy('status')
                        ->get();
        
        return view('classes.overview', compact('stats', 'classesByType', 'classesByStatus'));
    }
} 