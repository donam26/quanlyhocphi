<?php

namespace App\Http\Controllers;

use App\Models\CourseClass;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\SubCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CourseClassController extends Controller
{
    /**
     * Hiển thị danh sách lớp học
     */
    public function index(Request $request)
    {
        $query = CourseClass::with(['course.major', 'subCourse', 'enrollments', 'parentClass', 'childClasses']);

        // Lọc theo khóa học
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        // Lọc theo loại lớp
        if ($request->filled('class_type')) {
            $query->where('type', $request->class_type);
        }

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Lọc theo ngày bắt đầu
        if ($request->filled('start_date_from')) {
            $query->where('start_date', '>=', $request->start_date_from);
        }
        if ($request->filled('start_date_to')) {
            $query->where('start_date', '<=', $request->start_date_to);
        }

        // Lọc theo is_package
        if ($request->filled('is_package')) {
            $query->where('is_package', $request->boolean('is_package'));
        }

        // Lọc theo parent_class_id
        if ($request->filled('parent_class_id')) {
            $query->where('parent_class_id', $request->parent_class_id);
        } elseif ($request->filled('standalone')) {
            // Lấy các lớp độc lập (không thuộc gói)
            $query->whereNull('parent_class_id');
        }

        $courseClasses = $query->orderBy('start_date', 'desc')->paginate(20);
        $courses = Course::where('active', true)->get();
        $packages = CoursePackage::all();
        
        // Thêm biến $classes để tương thích với view cũ
        $classes = $courseClasses;

        return view('course-classes.index', compact('courseClasses', 'courses', 'packages', 'classes'));
    }

    /**
     * Hiển thị form tạo lớp học mới
     */
    public function create(Request $request)
    {
        $course = null;
        $subCourse = null;
        $parentClass = null;
        
        if ($request->filled('course_id')) {
            $course = Course::findOrFail($request->course_id);
        }
        
        if ($request->filled('sub_course_id')) {
            $subCourse = SubCourse::findOrFail($request->sub_course_id);
        }
        
        if ($request->filled('parent_class_id')) {
            $parentClass = CourseClass::findOrFail($request->parent_class_id);
        }

        $courses = Course::where('active', true)->with('subCourses')->get();
        $parentClasses = CourseClass::where('is_package', true)->get();

        return view('course-classes.create', compact('course', 'subCourse', 'parentClass', 'courses', 'parentClasses'));
    }

    /**
     * Lưu lớp học mới
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'sub_course_id' => 'nullable|exists:sub_courses,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:online,offline',
            'batch_number' => 'required|integer|min:1',
            'max_students' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'registration_deadline' => 'nullable|date|before_or_equal:start_date',
            'status' => 'required|in:planned,open,in_progress,completed,cancelled',
            'is_package' => 'boolean',
            'parent_class_id' => 'nullable|exists:course_classes,id',
            'notes' => 'nullable|string',
        ]);

        // Kiểm tra nếu là gói, không được có parent_class_id
        if ($request->boolean('is_package') && !empty($validatedData['parent_class_id'])) {
            return back()->withInput()->withErrors(['parent_class_id' => 'Một gói khóa học không thể là lớp con của một gói khác.']);
        }

        // Kiểm tra nếu có parent_class_id, không được là gói
        if (!empty($validatedData['parent_class_id']) && $request->boolean('is_package')) {
            return back()->withInput()->withErrors(['is_package' => 'Một lớp con không thể đồng thời là gói khóa học.']);
        }

        // Tạo lớp học
        $courseClass = CourseClass::create($validatedData);

        return redirect()->route('course-classes.show', $courseClass)
                        ->with('success', 'Tạo lớp học thành công!');
    }

    /**
     * Hiển thị chi tiết lớp học
     */
    public function show(CourseClass $courseClass)
    {
        $courseClass->load([
            'course', 
            'subCourse', 
            'enrollments.student', 
            'enrollments.payments',
            'parentClass',
            'childClasses',
            'packages'
        ]);

        return view('course-classes.show', compact('courseClass'));
    }

    /**
     * Hiển thị form chỉnh sửa lớp học
     */
    public function edit(CourseClass $courseClass)
    {
        $courseClass->load(['course', 'subCourse', 'parentClass', 'childClasses', 'packages']);
        
        $courses = Course::where('active', true)->get();
        $subCourses = $courseClass->course ? $courseClass->course->subCourses : collect();
        $parentClasses = CourseClass::where('is_package', true)
            ->where('id', '!=', $courseClass->id)
            ->get();

        return view('course-classes.edit', compact('courseClass', 'courses', 'subCourses', 'parentClasses'));
    }

    /**
     * Cập nhật lớp học
     */
    public function update(Request $request, CourseClass $courseClass)
    {
        $validatedData = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'sub_course_id' => 'nullable|exists:sub_courses,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:online,offline',
            'batch_number' => 'required|integer|min:1',
            'max_students' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'registration_deadline' => 'nullable|date|before_or_equal:start_date',
            'status' => 'required|in:planned,open,in_progress,completed,cancelled',
            'is_package' => 'boolean',
            'parent_class_id' => 'nullable|exists:course_classes,id',
            'notes' => 'nullable|string',
        ]);

        // Kiểm tra parent_class_id không trỏ đến chính nó
        if (!empty($validatedData['parent_class_id']) && $validatedData['parent_class_id'] == $courseClass->id) {
            return back()->withInput()->withErrors(['parent_class_id' => 'Một lớp không thể là lớp con của chính nó.']);
        }

        // Kiểm tra nếu là gói, không được có parent_class_id
        if ($request->boolean('is_package') && !empty($validatedData['parent_class_id'])) {
            return back()->withInput()->withErrors(['parent_class_id' => 'Một gói khóa học không thể là lớp con của một gói khác.']);
        }

        // Kiểm tra nếu có parent_class_id, không được là gói
        if (!empty($validatedData['parent_class_id']) && $request->boolean('is_package')) {
            return back()->withInput()->withErrors(['is_package' => 'Một lớp con không thể đồng thời là gói khóa học.']);
        }

        // Cập nhật lớp học
        $courseClass->update($validatedData);

        return redirect()->route('course-classes.show', $courseClass)
                        ->with('success', 'Cập nhật lớp học thành công!');
    }

    /**
     * Xóa lớp học
     */
    public function destroy(CourseClass $courseClass)
    {
        try {
            // Kiểm tra xem lớp có học viên ghi danh không
            if ($courseClass->enrollments()->exists()) {
                return back()->with('error', 'Không thể xóa lớp học đã có học viên ghi danh!');
            }

            $courseClass->delete();
            return redirect()->route('course-classes.index')
                            ->with('success', 'Xóa lớp học thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể xóa lớp học!');
        }
    }

    /**
     * Thêm lớp học vào gói
     */
    public function addToPackage(Request $request, CourseClass $courseClass)
    {
        $request->validate([
            'package_id' => 'required|exists:course_packages,id',
            'order' => 'nullable|integer|min:1'
        ]);

        $package = CoursePackage::findOrFail($request->package_id);
        
        // Kiểm tra xem lớp có phù hợp với gói không (online/offline)
        if ($courseClass->type !== $package->type) {
            return back()->with('error', 'Không thể thêm lớp ' . ucfirst($courseClass->type) . ' vào gói ' . ucfirst($package->type));
        }

        $order = $request->order ?? ($package->classes()->count() + 1);
        $package->addClass($courseClass, $order);

        return back()->with('success', 'Đã thêm lớp vào gói khóa học!');
    }

    /**
     * Xóa lớp học khỏi gói
     */
    public function removeFromPackage(Request $request, CourseClass $courseClass)
    {
        $request->validate([
            'package_id' => 'required|exists:course_packages,id',
        ]);

        $package = CoursePackage::findOrFail($request->package_id);
        $package->removeClass($courseClass);

        return back()->with('success', 'Đã xóa lớp khỏi gói khóa học!');
    }

    /**
     * Thêm lớp con vào lớp gói (parent class)
     */
    public function addChildClass(Request $request, CourseClass $parentClass)
    {
        if (!$parentClass->is_package) {
            return back()->with('error', 'Chỉ gói khóa học mới có thể chứa các lớp con!');
        }

        $request->validate([
            'child_class_id' => 'required|exists:course_classes,id',
        ]);

        $childClass = CourseClass::findOrFail($request->child_class_id);
        
        // Kiểm tra xem lớp con đã thuộc gói khác chưa
        if ($childClass->parent_class_id && $childClass->parent_class_id != $parentClass->id) {
            return back()->with('error', 'Lớp học này đã thuộc về một gói khác!');
        }

        $childClass->update(['parent_class_id' => $parentClass->id]);

        return back()->with('success', 'Đã thêm lớp con vào gói!');
    }

    /**
     * Xóa lớp con khỏi lớp gói (parent class)
     */
    public function removeChildClass(CourseClass $parentClass, CourseClass $childClass)
    {
        if ($childClass->parent_class_id != $parentClass->id) {
            return back()->with('error', 'Lớp học này không thuộc gói!');
        }

        $childClass->update(['parent_class_id' => null]);

        return back()->with('success', 'Đã xóa lớp con khỏi gói!');
    }
}
