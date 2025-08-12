<?php

namespace App\Http\Controllers;

use App\Contracts\StudentRepositoryInterface;
use App\Services\Student\StudentCreationService;
use App\Services\Student\StudentUpdateService;
use App\Services\StatusFactory;
use App\Models\Student;
use App\Enums\EnrollmentStatus;
use App\Rules\DateDDMMYYYY;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * StudentController - Refactored theo SOLID principles
 * Tuân thủ Single Responsibility và Dependency Injection
 */
class StudentController extends Controller
{
    protected StudentRepositoryInterface $studentRepository;
    protected StudentCreationService $creationService;
    protected StudentUpdateService $updateService;

    public function __construct(
        StudentRepositoryInterface $studentRepository,
        StudentCreationService $creationService,
        StudentUpdateService $updateService
    ) {
        $this->studentRepository = $studentRepository;
        $this->creationService = $creationService;
        $this->updateService = $updateService;
    }

    /**
     * Hiển thị danh sách học viên
     */
    public function index(Request $request): View
    {
        $perPage = $request->get('per_page', 15);
        $students = $this->studentRepository->paginate($perPage);

        return view('students.index', compact('students'));
    }

    /**
     * Hiển thị form tạo học viên mới
     */
    public function create(): View
    {
        $provinces = \App\Models\Province::orderBy('name')->get();
        $statusOptions = StatusFactory::getOptions('student');

        return view('students.create', compact('provinces', 'statusOptions'));
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

        $student = $this->creationService->createStudent($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thêm học viên thành công!',
                'student' => $student
            ]);
        }

        return redirect()->route('students.index')
                        ->with('success', 'Thêm học viên thành công!');
    }





    /**
     * Xóa học viên
     */
    public function destroy(Student $student): RedirectResponse
    {
        try {
            $this->studentRepository->delete($student);

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
    public function search(Request $request): JsonResponse
    {
        $term = $request->get('term', '');
        $limit = $request->get('limit', 10);

        if (empty($term)) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập từ khóa tìm kiếm'
            ]);
        }

        $students = $this->studentRepository->search($term, $limit);

        return response()->json([
            'success' => true,
            'students' => $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'phone' => $student->phone,
                    'email' => $student->email,
                    'status' => $student->status,
                    'status_badge' => $student->status_badge
                ];
            })
        ]);
    }

    /**
     * Trang thống kê học viên
     */
    public function statistics(): View
    {
        $stats = [
            'total_students' => $this->studentRepository->count(),
            'gender_stats' => $this->studentRepository->getGenderStatistics(),
            'age_stats' => $this->studentRepository->getAgeStatistics(),
            'top_students' => $this->studentRepository->getTopStudentsByEnrollments(5)
        ];

        return view('students.statistics', compact('stats'));
    }

    /**
     * Xem lịch sử học viên
     */
    public function history(int $studentId): View
    {
        $student = $this->studentRepository->findWithRelations(
            $studentId,
            ['enrollments.courseItem', 'enrollments.payments']
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
