<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Major;
use App\Models\SubCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /**
     * Hiển thị danh sách khóa học
     */
    public function index(Request $request)
    {
        $query = Course::with(['major', 'subCourses', 'classes']);

        // Lọc theo ngành
        if ($request->filled('major_id')) {
            $query->where('major_id', $request->major_id);
        }

        // Lọc theo trạng thái
        if ($request->filled('active')) {
            $query->where('active', $request->active);
        }

        // Lọc theo loại khóa học
        if ($request->filled('course_type')) {
            $query->where('course_type', $request->course_type);
        }

        // Tìm kiếm theo tên
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $courses = $query->orderBy('name')->paginate(20);
        $majors = Major::where('active', true)->get();

        return view('courses.index', compact('courses', 'majors'));
    }

    /**
     * Hiển thị form tạo khóa học mới
     */
    public function create()
    {
        $majors = Major::where('active', true)->get();

        return view('courses.create', compact('majors'));
    }

    /**
     * Lưu khóa học mới
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'major_id' => 'required|exists:majors,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fee' => 'required|numeric|min:0',
            'duration' => 'nullable|integer|min:1',
            'course_type' => 'required|in:standard,complex',
            'active' => 'boolean',
        ]);

        // Set is_complex flag based on course_type
        $validatedData['is_complex'] = ($validatedData['course_type'] === 'complex');

        $course = Course::create($validatedData);

        return redirect()->route('courses.show', $course)
            ->with('success', 'Thêm khóa học mới thành công!');
    }

    /**
     * Hiển thị chi tiết khóa học
     */
    public function show(Course $course)
    {
        $course->load([
            'major',
            'subCourses' => function ($query) {
                $query->orderBy('order');
            },
            'classes' => function ($query) {
                $query->orderBy('batch_number')->orderBy('type');
            },
            'packages' => function ($query) {
                $query->orderBy('batch_number')->orderBy('type');
            }
        ]);

        return view('courses.show', compact('course'));
    }

    /**
     * Hiển thị form chỉnh sửa khóa học
     */
    public function edit(Course $course)
    {
        $majors = Major::where('active', true)->get();
        
        return view('courses.edit', compact('course', 'majors'));
    }

    /**
     * Cập nhật khóa học
     */
    public function update(Request $request, Course $course)
    {
        $validatedData = $request->validate([
            'major_id' => 'required|exists:majors,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fee' => 'required|numeric|min:0',
            'duration' => 'nullable|integer|min:1',
            'course_type' => 'required|in:standard,complex',
            'active' => 'boolean',
        ]);

        // Set is_complex flag based on course_type
        $validatedData['is_complex'] = ($validatedData['course_type'] === 'complex');

        $course->update($validatedData);

        return redirect()->route('courses.show', $course)
            ->with('success', 'Cập nhật khóa học thành công!');
    }

    /**
     * Xóa khóa học
     */
    public function destroy(Course $course)
    {
        try {
            $course->delete();
            return redirect()->route('courses.index')
                ->with('success', 'Xóa khóa học thành công!');
        } catch (\Exception $e) {
            return redirect()->route('courses.index')
                ->with('error', 'Không thể xóa khóa học này vì có dữ liệu liên quan!');
        }
    }

    /**
     * Hiển thị form thêm khóa con
     */
    public function createSubCourse(Course $course)
    {
        return view('courses.sub-courses.create', compact('course'));
    }

    /**
     * Lưu khóa con
     */
    public function storeSubCourse(Request $request, Course $course)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fee' => 'required|numeric|min:0',
            'order' => 'required|integer|min:1',
            'code' => 'nullable|string|max:50',
            'has_online' => 'boolean',
            'has_offline' => 'boolean',
            'active' => 'boolean',
        ]);

        $subCourse = $course->subCourses()->create($validatedData);

        return redirect()->route('courses.show', $course)
            ->with('success', 'Thêm khóa con thành công!');
    }

    /**
     * Hiển thị form chỉnh sửa khóa con
     */
    public function editSubCourse(Course $course, SubCourse $subCourse)
    {
        return view('courses.sub-courses.edit', compact('course', 'subCourse'));
    }

    /**
     * Cập nhật khóa con
     */
    public function updateSubCourse(Request $request, Course $course, SubCourse $subCourse)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fee' => 'required|numeric|min:0',
            'order' => 'required|integer|min:1',
            'code' => 'nullable|string|max:50',
            'has_online' => 'boolean',
            'has_offline' => 'boolean',
            'active' => 'boolean',
        ]);

        $subCourse->update($validatedData);

        return redirect()->route('courses.show', $course)
            ->with('success', 'Cập nhật khóa con thành công!');
    }

    /**
     * Xóa khóa con
     */
    public function destroySubCourse(Course $course, SubCourse $subCourse)
    {
        try {
            $subCourse->delete();
            return redirect()->route('courses.show', $course)
                ->with('success', 'Xóa khóa con thành công!');
        } catch (\Exception $e) {
            return redirect()->route('courses.show', $course)
                ->with('error', 'Không thể xóa khóa con này vì có dữ liệu liên quan!');
        }
    }

    /**
     * Tự động tạo các khóa con và lớp học cho khóa học phức tạp
     */
    public function setupComplexCourse(Request $request, Course $course)
    {
        if ($course->course_type !== 'complex') {
            return redirect()->route('courses.show', $course)
                ->with('error', 'Chỉ áp dụng cho khóa học phức tạp!');
        }

        $validatedData = $request->validate([
            'batch_number' => 'required|integer|min:1',
            'sub_course_codes' => 'required|array',
            'sub_course_codes.*' => 'string',
            'create_online' => 'boolean',
            'create_offline' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            // Tạo gói khóa học
            $onlinePackage = null;
            $offlinePackage = null;
            
            if ($request->create_online) {
                $onlinePackage = $course->packages()->create([
                    'name' => $course->name . ' khóa ' . $validatedData['batch_number'] . ' ONLINE',
                    'description' => 'Gói khóa học online cho ' . $course->name . ' khóa ' . $validatedData['batch_number'],
                    'type' => 'online',
                    'batch_number' => $validatedData['batch_number'],
                    'package_fee' => $course->fee,
                    'active' => true
                ]);
            }
            
            if ($request->create_offline) {
                $offlinePackage = $course->packages()->create([
                    'name' => $course->name . ' khóa ' . $validatedData['batch_number'] . ' OFFLINE',
                    'description' => 'Gói khóa học offline cho ' . $course->name . ' khóa ' . $validatedData['batch_number'],
                    'type' => 'offline',
                    'batch_number' => $validatedData['batch_number'],
                    'package_fee' => $course->fee,
                    'active' => true
                ]);
            }
            
            // Tạo các lớp học cho từng khóa con
            foreach ($validatedData['sub_course_codes'] as $code) {
                $subCourse = $course->subCourses()->where('code', $code)->first();
                
                if (!$subCourse) {
                    continue;
                }
                
                // Tạo lớp online
                if ($request->create_online && $subCourse->has_online) {
                    $onlineClass = $course->classes()->create([
                        'sub_course_id' => $subCourse->id,
                        'name' => $subCourse->name . ' khóa ' . $validatedData['batch_number'] . ' Online',
                        'type' => 'online',
                        'batch_number' => $validatedData['batch_number'],
                        'max_students' => 50,
                        'status' => 'planned',
                        'is_package' => false,
                    ]);
                    
                    if ($onlinePackage) {
                        $onlinePackage->addClass($onlineClass, $subCourse->order);
                    }
                }
                
                // Tạo lớp offline
                if ($request->create_offline && $subCourse->has_offline) {
                    $offlineClass = $course->classes()->create([
                        'sub_course_id' => $subCourse->id,
                        'name' => $subCourse->name . ' khóa ' . $validatedData['batch_number'] . ' Offline',
                        'type' => 'offline',
                        'batch_number' => $validatedData['batch_number'],
                        'max_students' => 30,
                        'status' => 'planned',
                        'is_package' => false,
                    ]);
                    
                    if ($offlinePackage) {
                        $offlinePackage->addClass($offlineClass, $subCourse->order);
                    }
                }
            }

            DB::commit();
            
            return redirect()->route('courses.show', $course)
                ->with('success', 'Thiết lập khóa học phức tạp thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('courses.show', $course)
                ->with('error', 'Lỗi khi thiết lập khóa học: ' . $e->getMessage());
        }
    }

    /**
     * Hiển thị danh sách khóa con của một khóa học
     */
    public function subCourses(Course $course)
    {
        $course->load(['subCourses' => function($query) {
            $query->orderBy('order');
        }]);
        
        return view('courses.sub-courses.index', compact('course'));
    }

    /**
     * API trả về danh sách khóa con dưới dạng JSON
     */
    public function apiSubCourses(Course $course)
    {
        $course->load(['subCourses' => function($query) {
            $query->orderBy('order');
        }]);
        
        return response()->json([
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
            ],
            'subCourses' => $course->subCourses->map(function($subCourse) {
                return [
                    'id' => $subCourse->id,
                    'name' => $subCourse->name,
                    'description' => $subCourse->description,
                    'code' => $subCourse->code,
                    'fee' => $subCourse->fee,
                    'order' => $subCourse->order,
                    'has_online' => $subCourse->has_online,
                    'has_offline' => $subCourse->has_offline,
                    'active' => $subCourse->active,
                    'formatted_fee' => number_format($subCourse->fee) . 'đ',
                ];
            })
        ]);
    }
}
