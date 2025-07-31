<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\Enrollment;
use App\Models\CourseItem;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Carbon\Carbon;

class StudentImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $courseItemId;
    protected $discountPercentage;
    
    public function __construct($courseItemId, $discountPercentage = 0)
    {
        $this->courseItemId = $courseItemId;
        $this->discountPercentage = $discountPercentage;
    }
    
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Kiểm tra khóa học có tồn tại không
        $courseItem = CourseItem::find($this->courseItemId);
        if (!$courseItem) {
            return null;
        }
        
        // Tìm hoặc tạo học viên mới
        $student = Student::firstOrCreate(
            ['phone' => $row['so_dien_thoai']],
            [
                'full_name' => $row['ho_ten'],
                'email' => $row['email'] ?? null,
                'date_of_birth' => isset($row['ngay_sinh']) ? Carbon::createFromFormat('d/m/Y', $row['ngay_sinh']) : null,
                'gender' => $this->mapGender($row['gioi_tinh'] ?? ''),
                'address' => $row['dia_chi'] ?? null,
                'current_workplace' => $row['noi_cong_tac'] ?? null,
                'accounting_experience_years' => $row['kinh_nghiem'] ?? null,
                'notes' => $row['ghi_chu'] ?? null,
            ]
        );
        
        // Tính học phí
        $finalFee = $courseItem->fee;
        $discountAmount = 0;
        
        if ($this->discountPercentage > 0) {
            $discountAmount = $finalFee * ($this->discountPercentage / 100);
            $finalFee = $finalFee - $discountAmount;
        }
        
        // Kiểm tra xem học viên đã đăng ký khóa học này chưa
        $existingEnrollment = Enrollment::where('student_id', $student->id)
                                      ->where('course_item_id', $this->courseItemId)
                                      ->first();
        
        // Nếu chưa đăng ký, tạo đăng ký mới
        if (!$existingEnrollment) {
            Enrollment::create([
                'student_id' => $student->id,
                'course_item_id' => $this->courseItemId,
                'enrollment_date' => now(),
                'discount_percentage' => $this->discountPercentage,
                'discount_amount' => $discountAmount,
                'final_fee' => $finalFee,
                'notes' => 'Đăng ký qua import Excel'
            ]);
        }
        
        return $student;
    }
    
    /**
     * Chuyển đổi giới tính từ text sang giá trị trong DB
     */
    private function mapGender($gender)
    {
        $gender = strtolower(trim($gender));
        
        if (in_array($gender, ['nam', 'male', 'boy', 'm'])) {
            return 'male';
        }
        
        if (in_array($gender, ['nữ', 'nu', 'female', 'girl', 'f'])) {
            return 'female';
        }
        
        return 'other';
    }
    
    /**
     * Quy tắc xác thực
     */
    public function rules(): array
    {
        return [
            'ho_ten' => 'required',
            'so_dien_thoai' => 'required',
            'ngay_sinh' => 'nullable|date_format:d/m/Y',
        ];
    }
    
    /**
     * Thông báo lỗi tùy chỉnh
     */
    public function customValidationMessages()
    {
        return [
            'ho_ten.required' => 'Họ tên học viên là bắt buộc',
            'so_dien_thoai.required' => 'Số điện thoại học viên là bắt buộc',
            'ngay_sinh.date_format' => 'Ngày sinh phải có định dạng DD/MM/YYYY',
        ];
    }
}

