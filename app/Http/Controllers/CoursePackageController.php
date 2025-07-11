<?php

namespace App\Http\Controllers;

use App\Models\CoursePackage;
use App\Models\Course;
use App\Models\CourseClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoursePackageController extends Controller
{
    /**
     * Hiển thị danh sách gói khóa học
     */
    public function index(Request $request)
    {
        $query = CoursePackage::with(['course', 'classes']);

        // Lọc theo khóa học
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        // Lọc theo loại gói
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Lọc theo trạng thái
        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        // Lọc theo batch number
        if ($request->filled('batch_number')) {
            $query->where('batch_number', $request->batch_number);
        }

        $packages = $query->orderBy('batch_number', 'desc')->paginate(20);
        $courses = Course::where('active', true)->get();

        return view('course-packages.index', compact('packages', 'courses'));
    }

    /**
     * Hiển thị form tạo gói khóa học mới
     */
    public function create(Request $request)
    {
        $course = null;
        if ($request->filled('course_id')) {
            $course = Course::findOrFail($request->course_id);
        }

        $courses = Course::where('active', true)->with('subCourses')->get();
        $type = $request->type ?? 'online';

        return view('course-packages.create', compact('course', 'courses', 'type'));
    }

    /**
     * Lưu gói khóa học mới
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:online,offline',
            'batch_number' => 'required|integer|min:1',
            'package_fee' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'active' => 'boolean',
        ]);

        $package = CoursePackage::create($validatedData);

        return redirect()->route('course-packages.show', $package)
            ->with('success', 'Tạo gói khóa học thành công!');
    }

    /**
     * Hiển thị chi tiết gói khóa học
     */
    public function show(CoursePackage $package)
    {
        $package->load([
            'course',
            'classes' => function ($query) {
                $query->orderBy('course_package_classes.order');
            },
        ]);

        $availableClasses = CourseClass::where('type', $package->type)
            ->where('course_id', $package->course_id)
            ->whereDoesntHave('packages', function ($query) use ($package) {
                $query->where('course_packages.id', $package->id);
            })
            ->get();

        return view('course-packages.show', compact('package', 'availableClasses'));
    }

    /**
     * Hiển thị form chỉnh sửa gói khóa học
     */
    public function edit(CoursePackage $package)
    {
        $package->load(['course', 'classes']);
        $courses = Course::where('active', true)->get();

        return view('course-packages.edit', compact('package', 'courses'));
    }

    /**
     * Cập nhật gói khóa học
     */
    public function update(Request $request, CoursePackage $package)
    {
        $validatedData = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:online,offline',
            'batch_number' => 'required|integer|min:1',
            'package_fee' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'active' => 'boolean',
        ]);

        $package->update($validatedData);

        return redirect()->route('course-packages.show', $package)
            ->with('success', 'Cập nhật gói khóa học thành công!');
    }

    /**
     * Xóa gói khóa học
     */
    public function destroy(CoursePackage $package)
    {
        try {
            $package->delete();
            return redirect()->route('course-packages.index')
                ->with('success', 'Xóa gói khóa học thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể xóa gói khóa học này!');
        }
    }

    /**
     * Thêm lớp học vào gói
     */
    public function addClasses(Request $request, CoursePackage $package)
    {
        $validatedData = $request->validate([
            'class_ids' => 'required|array',
            'class_ids.*' => 'exists:course_classes,id',
        ]);

        DB::beginTransaction();

        try {
            $maxOrder = $package->classes()->max('course_package_classes.order') ?? 0;

            foreach ($validatedData['class_ids'] as $index => $classId) {
                $courseClass = CourseClass::find($classId);
                
                // Kiểm tra xem lớp học có cùng loại với gói không
                if ($courseClass->type !== $package->type) {
                    continue;
                }
                
                $order = $maxOrder + $index + 1;
                $package->addClass($courseClass, $order);
            }

            DB::commit();
            return back()->with('success', 'Đã thêm các lớp học vào gói thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Không thể thêm lớp học vào gói: ' . $e->getMessage());
        }
    }

    /**
     * Xóa lớp học khỏi gói
     */
    public function removeClass(CoursePackage $package, CourseClass $class)
    {
        $package->removeClass($class);

        return back()->with('success', 'Đã xóa lớp học khỏi gói!');
    }

    /**
     * Cập nhật thứ tự các lớp học trong gói
     */
    public function updateClassesOrder(Request $request, CoursePackage $package)
    {
        $validatedData = $request->validate([
            'orders' => 'required|array',
            'orders.*' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            foreach ($validatedData['orders'] as $classId => $order) {
                DB::table('course_package_classes')
                    ->where('package_id', $package->id)
                    ->where('course_class_id', $classId)
                    ->update(['order' => $order]);
            }

            DB::commit();
            return back()->with('success', 'Đã cập nhật thứ tự lớp học thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Không thể cập nhật thứ tự lớp học: ' . $e->getMessage());
        }
    }

    /**
     * Tự động tạo gói khóa học cho một khóa phức tạp
     */
    public function autoCreatePackage(Course $course, Request $request)
    {
        if (!$course->isComplexCourse()) {
            return back()->with('error', 'Chỉ có thể tạo gói cho khóa học phức tạp!');
        }

        $validatedData = $request->validate([
            'batch_number' => 'required|integer|min:1',
            'type' => 'required|in:online,offline',
        ]);

        DB::beginTransaction();

        try {
            // Tạo gói khóa học
            $package = $course->packages()->create([
                'name' => $course->name . ' khóa ' . $validatedData['batch_number'] . ' ' . strtoupper($validatedData['type']),
                'description' => 'Gói khóa học ' . $validatedData['type'] . ' cho ' . $course->name . ' khóa ' . $validatedData['batch_number'],
                'type' => $validatedData['type'],
                'batch_number' => $validatedData['batch_number'],
                'package_fee' => $course->fee,
                'active' => true
            ]);

            // Thêm các lớp học phù hợp vào gói
            $classes = CourseClass::where('course_id', $course->id)
                ->where('type', $validatedData['type'])
                ->where('batch_number', $validatedData['batch_number'])
                ->orderBy('name')
                ->get();

            foreach ($classes as $index => $class) {
                $order = $index + 1;
                $package->addClass($class, $order);
            }

            DB::commit();
            return redirect()->route('course-packages.show', $package)
                ->with('success', 'Tạo gói khóa học tự động thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Không thể tạo gói khóa học: ' . $e->getMessage());
        }
    }
} 