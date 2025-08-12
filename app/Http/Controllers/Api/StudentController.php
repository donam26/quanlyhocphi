<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Province;
use App\Rules\DateDDMMYYYY;
use App\Models\User;
use App\Enums\EnrollmentStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    /**
     * Lấy danh sách học viên
     */
    public function index()
    {
        $students = Student::with('province')->get();

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    /**
     * Lấy chi tiết học viên
     */
    public function show($id)
    {
        $student = Student::with('province')->find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy học viên'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }

    /**
     * Lấy thông tin chi tiết học viên (alias cho show)
     */
    public function getInfo($id)
    {
        return $this->show($id);
    }

    /**
     * Lấy thông tin chi tiết học viên với enrollment
     */
    public function getStudentDetails($id)
    {
        $student = Student::with(['province', 'enrollments.courseItem', 'enrollments.payments'])->find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy học viên'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }

    /**
     * Tạo học viên mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => ['nullable', new DateDDMMYYYY],
            'place_of_birth' => 'nullable|string|max:255',
            'nation' => 'nullable|string|max:255',
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

        $student = Student::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo học viên thành công',
            'data' => $student->load('province')
        ], 201);
    }

    /**
     * Cập nhật thông tin học viên
     */
    public function update(Request $request, $id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy học viên'
            ], 404);
        }

        // Debug thông tin request
        Log::info('Student update request data:', $request->all());

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'place_of_birth' => 'nullable|string|max:255',
            'nation' => 'nullable|string|max:255',
            'date_of_birth' => ['nullable', new DateDDMMYYYY],
            'gender' => 'nullable|in:male,female,other',
            'email' => 'nullable|email|max:255',
            'phone' => ['nullable', 'string', Rule::unique('students')->ignore($student->id)],
            'province_id' => 'nullable|exists:provinces,id',
            'current_workplace' => 'nullable|string|max:255',
            'accounting_experience_years' => 'nullable|integer|min:0',
            'training_specialization' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'hard_copy_documents' => 'nullable|in:submitted,not_submitted',
            'education_level' => 'nullable|in:vocational,associate,bachelor,master,secondary',
        ]);

        // Debug thông tin đã validate
        Log::info('Student update validated data:', $validated);

        // Cập nhật học viên (full_name sẽ được tự động tính toán từ first_name + last_name)
        $student->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật học viên thành công',
            'data' => $student->fresh('province')
        ]);
    }

    /**
     * Lấy thông tin học viên theo tỉnh thành
     */
    public function getByProvince($provinceId)
    {
        $province = Province::find($provinceId);

        if (!$province) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tỉnh thành'
            ], 404);
        }

        $students = Student::where('province_id', $provinceId)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'province' => $province,
                'students' => $students
            ]
        ]);
    }

    /**
     * Lấy thông tin học viên theo vùng miền
     */
    public function getByRegion($region)
    {
        if (!in_array($region, ['north', 'central', 'south'])) {
            return response()->json([
                'success' => false,
                'message' => 'Vùng miền không hợp lệ'
            ], 400);
        }

        $students = Student::whereHas('province', function ($query) use ($region) {
            $query->where('region', $region);
        })->with('province')->get();

        return response()->json([
            'success' => true,
            'region' => $region,
            'region_name' => match($region) {
                'north' => 'Miền Bắc',
                'central' => 'Miền Trung',
                'south' => 'Miền Nam',
                default => 'Không xác định'
            },
            'count' => $students->count(),
            'data' => $students
        ]);
    }

    /**
     * Lấy thông tin chi tiết ghi danh của học viên
     */
    public function getEnrollmentInfo($id)
    {
        $student = Student::with(['enrollments.courseItem', 'enrollments.payments'])
            ->find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy học viên'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }

    /**
     * Lấy thông tin cơ bản của học viên
     */
    public function getInfo($id)
    {
        $student = Student::with([
            'province',
            'enrollments.courseItem',
            'enrollments.payments'
        ])->find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy học viên'
            ], 404);
        }

                    // Thêm thông tin bổ sung cho mỗi ghi danh
            foreach ($student->enrollments as $enrollment) {
                $enrollment->formatted_enrollment_date = $enrollment->formatted_enrollment_date;
            $enrollment->is_fully_paid = $enrollment->getRemainingAmount() <= 0;
            $enrollment->total_paid = $enrollment->getTotalPaidAmount();
            $enrollment->remaining_amount = $enrollment->getRemainingAmount();
            
            // Thêm thông tin chi tiết để hiển thị trong popup
            $enrollment->discount_percentage = $enrollment->discount_percentage ?? 0;
            $enrollment->discount_amount = $enrollment->discount_amount ?? 0;
            $enrollment->notes = $enrollment->notes ?? '';
            $enrollment->status_label = $enrollment->getStatusEnum() ? $enrollment->getStatusEnum()->label() : $enrollment->status;
            
            // Thêm thông tin về course item để hiển thị đầy đủ
            if ($enrollment->courseItem) {
                $enrollment->course_item_name = $enrollment->courseItem->name;
                $enrollment->course_item_fee = $enrollment->courseItem->fee;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }

    /**
     * Lấy chi tiết học viên cho modal
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
                'data' => [
                    'id' => $student->id,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'full_name' => $student->full_name,
                    'phone' => $student->phone,
                    'email' => $student->email,
                    'date_of_birth' => $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : null,
                    'place_of_birth' => $student->place_of_birth,
                    'nation' => $student->nation,
                    'gender' => $student->gender,
                    'province_id' => $student->province_id,
                    'address' => $student->address,
                    'current_workplace' => $student->current_workplace,
                    'accounting_experience_years' => $student->accounting_experience_years,
                    'hard_copy_documents' => $student->hard_copy_documents,
                    'education_level' => $student->education_level,
                    'notes' => $student->notes,
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

    /**
     * Xóa học viên
     */
    public function destroy($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy học viên'
            ], 404);
        }

        // Kiểm tra xem học viên có dữ liệu liên quan không
        $enrollmentCount = $student->enrollments()->count();
        $paymentCount = $student->payments()->count();

        if ($enrollmentCount > 0 || $paymentCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Không thể xóa học viên này vì đã có {$enrollmentCount} ghi danh và {$paymentCount} thanh toán liên quan. Vui lòng xóa các dữ liệu liên quan trước."
            ], 422);
        }

        try {
            $studentName = $student->full_name;
            $student->delete();

            return response()->json([
                'success' => true,
                'message' => "Đã xóa học viên '{$studentName}' thành công"
            ]);
        } catch (\Exception $e) {
            Log::error('Delete student error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa học viên: ' . $e->getMessage()
            ], 500);
        }
    }
}
