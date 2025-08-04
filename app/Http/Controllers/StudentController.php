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
        $provinces = \App\Models\Province::orderBy('name')->get();
        return view('students.create', compact('provinces'));
    }

    /**
     * Lưu học viên mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'nullable|in:male,female,other',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|unique:students,phone',
            'province_id' => 'nullable|exists:provinces,id',
            'current_workplace' => 'nullable|string|max:255',
            'accounting_experience_years' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'hard_copy_documents' => 'nullable|in:submitted,not_submitted',
            'education_level' => 'nullable|in:vocational,associate,bachelor,master,secondary',
            'workplace' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0',
        ]);

        $student = $this->studentService->createStudent($validated);

        return redirect()->route('students.index')
                        ->with('success', 'Thêm học viên thành công!');
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
