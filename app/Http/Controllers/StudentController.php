<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    protected $studentService;
    
    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * Hiển thị danh sách học viên
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'student_id', 'course_item_id']);
        $students = $this->studentService->getStudents($filters);
        
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
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|unique:students,phone',
            'address' => 'nullable|string',
            'current_workplace' => 'nullable|string|max:255',
            'accounting_experience_years' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $student = $this->studentService->createStudent($validated);

        return redirect()->route('students.show', $student)
                        ->with('success', 'Thêm học viên thành công!');
    }

    /**
     * Hiển thị chi tiết học viên
     */
    public function show(Student $student)
    {
        $student = $this->studentService->getStudentWithRelations(
            $student->id, 
            ['enrollments.courseItem', 'enrollments.payments', 'waitingLists.courseItem', 'attendances']
        );
        
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
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|unique:students,phone,' . $student->id,
            'address' => 'nullable|string',
            'current_workplace' => 'nullable|string|max:255',
            'accounting_experience_years' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'gender' => 'nullable|string|in:male,female,other',
            'custom_field_keys.*' => 'nullable|string',
            'custom_field_values.*' => 'nullable|string',
        ]);

        $this->studentService->updateStudent($student, $validated);

        return redirect()->route('students.show', $student)
                        ->with('success', 'Cập nhật thông tin học viên thành công!');
    }

    /**
     * Xóa học viên
     */
    public function destroy(Student $student)
    {
        try {
            $this->studentService->deleteStudent($student);
            return redirect()->route('students.index')
                            ->with('success', 'Xóa học viên thành công!');
        } catch (\Exception $e) {
            return redirect()->route('students.index')
                            ->with('error', 'Không thể xóa học viên do có dữ liệu liên quan!');
        }
    }

    /**
     * Trang thống kê học viên
     */
    public function statistics()
    {
        $stats = $this->studentService->getStudentStatistics();
        return view('students.statistics', compact('stats'));
    }

    public function history($studentId)
    {
        $student = $this->studentService->getStudentWithRelations(
            $studentId, 
            ['enrollments.courseItem', 'enrollments.payments', 'waitingLists.courseItem']
        );
        
        return view('search.student-history', compact('student'));
    }
}
