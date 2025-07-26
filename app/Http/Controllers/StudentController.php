<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Enrollment;
use App\Models\WaitingList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * Hiển thị danh sách học viên
     */
    public function index(Request $request)
    {
        $query = Student::with(['enrollments']);

        // Tìm kiếm theo tên hoặc số điện thoại
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Lọc theo khóa học
        if ($request->filled('course_item_id')) {
            $query->whereHas('enrollments', function($q) use ($request) {
                $q->where('course_item_id', $request->course_item_id);
            });
        }

        $students = $query->latest()->paginate(20)->appends($request->except('page'));
        
        return view('students.index', compact('students'));
    }

    /**
     * Hiển thị form tạo học viên mới
     */
    public function create()
    {
        return view('students.create');
    }

    /**
     * Lưu học viên mới
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|unique:students,phone',
            'address' => 'nullable|string',
            'current_workplace' => 'nullable|string|max:255',
            'accounting_experience_years' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $student = Student::create($validatedData);

        return redirect()->route('students.show', $student)
                        ->with('success', 'Thêm học viên thành công!');
    }

    /**
     * Hiển thị chi tiết học viên
     */
    public function show(Student $student)
    {
        $student->load([
            'enrollments.courseItem',
            'enrollments.payments',
            'waitingLists.course',
            'attendances'
        ]);
        
        return view('students.show', compact('student'));
    }

    /**
     * Hiển thị form chỉnh sửa học viên
     */
    public function edit(Student $student)
    {
        return view('students.edit', compact('student'));
    }

    /**
     * Cập nhật thông tin học viên
     */
    public function update(Request $request, Student $student)
    {
        $validatedData = $request->validate([
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|unique:students,phone,' . $student->id,
            'address' => 'nullable|string',
            'current_workplace' => 'nullable|string|max:255',
            'accounting_experience_years' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive,potential',
            'notes' => 'nullable|string',
        ]);

        $student->update($validatedData);

        return redirect()->route('students.show', $student)
                        ->with('success', 'Cập nhật thông tin học viên thành công!');
    }

    /**
     * Xóa học viên
     */
    public function destroy(Student $student)
    {
        try {
            $student->delete();
            return redirect()->route('students.index')
                            ->with('success', 'Xóa học viên thành công!');
        } catch (\Exception $e) {
            return redirect()->route('students.index')
                            ->with('error', 'Không thể xóa học viên do có dữ liệu liên quan!');
        }
    }

    /**
     * Tìm kiếm học viên (API)
     */
    public function search(Request $request)
    {
        $term = $request->get('term');
        
        $students = Student::search($term)
                          ->with(['enrollments.courseClass.course', 'waitingLists.course'])
                          ->limit(10)
                          ->get();

        return response()->json($students->map(function($student) {
            return [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'phone' => $student->phone,
                'status' => $student->status,
                'current_classes' => $student->enrollments->where('status', 'enrolled')->map(function($enrollment) {
                    return $enrollment->courseClass->name;
                })->toArray(),
                'waiting_courses' => $student->waitingLists->where('status', 'waiting')->map(function($waiting) {
                    return $waiting->course->name;
                })->toArray()
            ];
        }));
    }

    /**
     * Trang thống kê học viên
     */
    public function statistics()
    {
        $stats = [
            'total_students' => Student::count(),
            'active_students' => Student::where('status', 'active')->count(),
            'potential_students' => Student::where('status', 'potential')->count(),
            'students_with_unpaid_fees' => Student::whereHas('enrollments', function($q) {
                $q->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE enrollment_id = enrollments.id AND status = "confirmed") < final_fee');
            })->count(),
            'recent_registrations' => Student::where('created_at', '>=', now()->subDays(30))->count()
        ];

        return view('students.statistics', compact('stats'));
    }
}
