<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Province;
use App\Rules\DateDDMMYYYY;
use App\Models\User;
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
     * Lấy chi tiết học viên cho modal (tương thích với getStudentDetails)
     */
    public function getStudentDetailsForModal($studentId)
    {
        $student = Student::with(['province', 'enrollments.courseItem', 'enrollments.payments'])->find($studentId);

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
