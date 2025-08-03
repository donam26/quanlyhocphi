<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Province;
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
     * Tạo học viên mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date_format:Y-m-d',
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
            'full_name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date_format:Y-m-d',
            'gender' => 'nullable|in:male,female,other',
            'email' => 'nullable|email|max:255',
            'phone' => ['nullable', 'string', Rule::unique('students')->ignore($student->id)],
            'province_id' => 'nullable|exists:provinces,id',
            'current_workplace' => 'nullable|string|max:255',
            'accounting_experience_years' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'hard_copy_documents' => 'nullable|in:submitted,not_submitted',
            'education_level' => 'nullable|in:vocational,associate,bachelor,master,secondary',
            'workplace' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0',
        ]);
        
        // Debug thông tin đã validate
        Log::info('Student update validated data:', $validated);
        
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
            $enrollment->formatted_enrollment_date = $enrollment->enrollment_date ? $enrollment->enrollment_date->format('d/m/Y') : null;
            $enrollment->is_fully_paid = $enrollment->getRemainingAmount() <= 0;
            $enrollment->total_paid = $enrollment->getTotalPaidAmount();
            $enrollment->remaining_amount = $enrollment->getRemainingAmount();
        }
        
        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }
} 