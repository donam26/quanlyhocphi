<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\StudentService;
use App\Enums\EnrollmentStatus;
use App\Rules\DateDDMMYYYY;
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => ['required', new DateDDMMYYYY],
            'gender' => 'nullable|in:male,female,other',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|unique:students,phone',
            'province_id' => 'nullable|exists:provinces,id',
            'current_workplace' => 'nullable|string|max:255',
            'accounting_experience_years' => 'nullable|integer|min:0',
            'training_specialization' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'hard_copy_documents' => 'nullable|in:submitted,not_submitted',
            'education_level' => 'nullable|in:vocational,associate,bachelor,master,secondary',
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

    /**
     * Xuất danh sách học viên ra Excel
     */
    public function export(Request $request)
    {
        try {
            $filters = $request->only([
                'course_item_id', 'status', 'province_id', 'gender',
                'date_of_birth_from', 'date_of_birth_to', 'columns'
            ]);

            return $this->studentService->exportStudents($filters);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Có lỗi xảy ra khi xuất file: ' . $e->getMessage());
        }
    }

    /**
     * Xuất hóa đơn điện tử cho học viên
     */
    public function exportInvoice(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'enrollment_id' => 'required|exists:enrollments,id',
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            return $this->studentService->exportInvoice(
                $request->student_id,
                $request->enrollment_id,
                $request->invoice_date,
                $request->notes
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xuất hóa đơn: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import danh sách học viên từ Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // Max 10MB
        ]);

        try {
            // Mặc định sử dụng chế độ tạo mới và cập nhật
            $result = $this->studentService->importStudents(
                $request->file('file'),
                'create_and_update'
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'created_count' => $result['created_count'],
                    'updated_count' => $result['updated_count'],
                    'skipped_count' => $result['skipped_count'],
                    'errors' => $result['errors']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tải file mẫu Excel cho import học viên
     */
    public function downloadImportTemplate()
    {
        try {
            return $this->studentService->downloadImportTemplate();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Có lỗi xảy ra khi tải file mẫu: ' . $e->getMessage());
        }
    }

    /**
     * Lấy chi tiết học viên cho API
     */
    public function getStudentDetails($studentId)
    {
        try {
            $student = Student::with([
                'enrollments.courseItem',
                'enrollments.payments'
            ])->find($studentId);

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy học viên'
                ], 404);
            }

            // Tính toán thống kê
            $enrollments = $student->enrollments;
            $totalPaid = 0;
            $totalUnpaid = 0;
            $enrolledCount = 0;
            $waitingCount = 0;
            $completedCount = 0;

            $enrollmentHistory = [];

            foreach ($enrollments as $enrollment) {
                $paidAmount = $enrollment->payments->sum('amount');
                $totalPaid += $paidAmount;
                $totalUnpaid += max(0, $enrollment->final_fee - $paidAmount);

                switch ($enrollment->status) {
                    case EnrollmentStatus::ACTIVE:
                        $enrolledCount++;
                        break;
                    case EnrollmentStatus::WAITING:
                        $waitingCount++;
                        break;
                    case EnrollmentStatus::COMPLETED:
                        $completedCount++;
                        break;
                }

                $enrollmentHistory[] = [
                    'course_name' => $enrollment->courseItem->name,
                    'status' => $enrollment->status,
                    'enrollment_date' => $enrollment->formatted_enrollment_date ?: 'N/A',
                    'final_fee' => number_format($enrollment->final_fee) . ' VNĐ'
                ];
            }

            return response()->json([
                'success' => true,
                'student' => [
                    'full_name' => $student->full_name,
                    'phone' => $student->phone,
                    'email' => $student->email,
                    'date_of_birth' => $student->formatted_date_of_birth,
                    'address' => $student->address,
                    'created_at' => $student->created_at->format('d/m/Y H:i')
                ],
                'stats' => [
                    'total_enrollments' => $enrollments->count(),
                    'active_count' => $enrolledCount,
                    'waiting_count' => $waitingCount,
                    'completed_count' => $completedCount,
                    'total_paid' => number_format($totalPaid) . ' VNĐ',
                    'total_unpaid' => number_format($totalUnpaid) . ' VNĐ'
                ],
                'enrollments' => $enrollmentHistory
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
