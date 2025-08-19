<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\Province;
use App\Models\Ethnicity;
use App\Enums\EnrollmentStatus;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CourseStudentsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    protected $courseItem;
    protected $enrollmentStatus;
    protected $discountPercentage;
    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];

    public function __construct(CourseItem $courseItem, $enrollmentStatus = 'active', $discountPercentage = 0)
    {
        $this->courseItem = $courseItem;
        $this->enrollmentStatus = $enrollmentStatus;
        $this->discountPercentage = $discountPercentage;
    }

    public function model(array $row)
    {
        try {
            DB::beginTransaction();
            
            // Chuẩn hóa dữ liệu học viên
            $studentData = $this->normalizeStudentData($row);
            
            // Tìm hoặc tạo học viên
            $student = $this->findOrCreateStudent($studentData);
            
            // Kiểm tra đã ghi danh chưa
            $existingEnrollment = Enrollment::where('student_id', $student->id)
                ->where('course_item_id', $this->courseItem->id)
                ->first();
            
            if ($existingEnrollment) {
                $this->skippedCount++;
                DB::rollBack();
                return null;
            }
            
            // Tạo ghi danh mới
            $enrollment = $this->createEnrollment($student, $row);
            
            $this->importedCount++;
            DB::commit();
            
            return $enrollment;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors[] = "Dòng {$this->getRowNumber()}: " . $e->getMessage();
            return null;
        }
    }

    protected function normalizeStudentData(array $row)
    {
        $data = [];

        // Xử lý họ tên - header đơn giản
        $data['first_name'] = $row['ho'] ?? '';
        $data['last_name'] = $row['ten'] ?? '';

        // Thông tin bắt buộc
        $data['phone'] = $this->normalizePhone($row['so_dien_thoai'] ?? '');
        $data['email'] = $row['email'] ?? null;

        // Ngày sinh
        if (isset($row['ngay_sinh']) && !empty($row['ngay_sinh'])) {
            $data['date_of_birth'] = $this->parseDate($row['ngay_sinh']);
        }

        // Thông tin khác - cập nhật theo cấu trúc database mới
        $data['nation'] = $row['quoc_tich'] ?? 'Việt Nam';
        $data['gender'] = $this->normalizeGender($row['gioi_tinh'] ?? null);
        
        // Tỉnh thành hiện tại
        if (isset($row['tinh_hien_tai']) && !empty($row['tinh_hien_tai'])) {
            $provinceName = trim($row['tinh_hien_tai']);
            $province = Province::where('name', $provinceName)->first();
            if (!$province) {
                $province = Province::where('name', 'like', '%' . $provinceName . '%')->first();
            }
            $data['province_id'] = $province ? $province->id : null;
        }

        // Tỉnh nơi sinh
        if (isset($row['tinh_noi_sinh']) && !empty($row['tinh_noi_sinh'])) {
            $placeOfBirthProvinceName = trim($row['tinh_noi_sinh']);
            $placeOfBirthProvince = Province::where('name', $placeOfBirthProvinceName)->first();
            if (!$placeOfBirthProvince) {
                $placeOfBirthProvince = Province::where('name', 'like', '%' . $placeOfBirthProvinceName . '%')->first();
            }
            $data['place_of_birth_province_id'] = $placeOfBirthProvince ? $placeOfBirthProvince->id : null;
        }

        // Dân tộc
        if (isset($row['dan_toc']) && !empty($row['dan_toc'])) {
            $ethnicityName = trim($row['dan_toc']);
            $ethnicity = Ethnicity::where('name', $ethnicityName)->first();
            if (!$ethnicity) {
                $ethnicity = Ethnicity::where('name', 'like', '%' . $ethnicityName . '%')->first();
            }
            $data['ethnicity_id'] = $ethnicity ? $ethnicity->id : null;
        }
        
        // Thông tin bổ sung - header đơn giản
        $data['current_workplace'] = $row['noi_cong_tac'] ?? null;
        $data['accounting_experience_years'] = isset($row['kinh_nghiem_ke_toan']) && is_numeric($row['kinh_nghiem_ke_toan'])
            ? (int)$row['kinh_nghiem_ke_toan'] : null;
        $data['education_level'] = $this->normalizeEducationLevel($row['trinh_do_hoc_van'] ?? null);
        $data['hard_copy_documents'] = $this->normalizeHardCopyDocuments($row['ho_so_ban_cung'] ?? null);
        $data['training_specialization'] = $row['chuyen_mon_dao_tao'] ?? null;

        // Thông tin công ty
        $data['company_name'] = $row['ten_cong_ty'] ?? null;
        $data['tax_code'] = $row['ma_so_thue'] ?? null;
        $data['invoice_email'] = $row['email_hoa_don'] ?? null;
        $data['company_address'] = $row['dia_chi_cong_ty'] ?? null;

        $data['notes'] = $row['ghi_chu'] ?? null;
        $data['source'] = $this->normalizeSource($row['nguon'] ?? null);

        return array_filter($data, function($value) {
            return $value !== null && $value !== '';
        });
    }

    protected function findOrCreateStudent(array $studentData)
    {
        // Tìm theo số điện thoại
        $student = Student::where('phone', $studentData['phone'])->first();
        
        if ($student) {
            // Cập nhật thông tin nếu có dữ liệu mới
            $student->update($studentData);
            return $student;
        }
        
        // Tạo mới
        return Student::create($studentData);
    }

    protected function createEnrollment(Student $student, array $row)
    {
        // Tính toán học phí
        $originalFee = $this->courseItem->fee;
        $discountAmount = ($originalFee * $this->discountPercentage) / 100;
        $finalFee = $originalFee - $discountAmount;
        
        // Ngày ghi danh
        $enrollmentDate = now();
        if (isset($row['ngay_ghi_danh']) || isset($row['enrollment_date'])) {
            $dateStr = $row['ngay_ghi_danh'] ?? $row['enrollment_date'];
            $parsedDate = $this->parseDate($dateStr);
            if ($parsedDate) {
                $enrollmentDate = Carbon::parse($parsedDate);
            }
        }
        
        return Enrollment::create([
            'student_id' => $student->id,
            'course_item_id' => $this->courseItem->id,
            'enrollment_date' => $enrollmentDate,
            'status' => $this->enrollmentStatus,
            'discount_percentage' => $this->discountPercentage,
            'discount_amount' => $discountAmount,
            'final_fee' => $finalFee,
            'notes' => $row['ghi_chu_ghi_danh'] ?? $row['enrollment_notes'] ?? 'Đăng ký qua import Excel'
        ]);
    }

    // Các helper methods giống như StudentsImport
    protected function normalizePhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return $phone;
        } elseif (strlen($phone) === 9) {
            return '0' . $phone;
        } elseif (strlen($phone) === 12 && substr($phone, 0, 2) === '84') {
            return '0' . substr($phone, 2);
        }
        
        return $phone;
    }

    protected function parseDate($dateStr)
    {
        if (empty($dateStr)) return null;
        
        try {
            $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'];
            
            foreach ($formats as $format) {
                $date = Carbon::createFromFormat($format, $dateStr);
                if ($date) {
                    return $date->format('Y-m-d');
                }
            }
            
            return Carbon::parse($dateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function normalizeGender($gender)
    {
        if (empty($gender)) return null;
        
        $gender = strtolower(trim($gender));
        
        if (in_array($gender, ['nam', 'male', 'm'])) {
            return 'male';
        } elseif (in_array($gender, ['nữ', 'nu', 'female', 'f'])) {
            return 'female';
        } elseif (in_array($gender, ['khác', 'khac', 'other'])) {
            return 'other';
        }
        
        return null;
    }

    protected function normalizeEducationLevel($level)
    {
        if (empty($level)) return null;
        
        $level = strtolower(trim($level));
        
        if (in_array($level, ['đại học', 'dai hoc', 'bachelor'])) {
            return 'bachelor';
        } elseif (in_array($level, ['cao đẳng', 'cao dang', 'associate'])) {
            return 'associate';
        } elseif (in_array($level, ['trung cấp', 'trung cap', 'vocational'])) {
            return 'vocational';
        } elseif (in_array($level, ['thạc sĩ', 'thac si', 'master'])) {
            return 'master';
        } elseif (in_array($level, ['vb2', 'secondary'])) {
            return 'secondary';
        }
        
        return null;
    }

    protected function normalizeHardCopyDocuments($status)
    {
        if (empty($status)) return null;
        
        $status = strtolower(trim($status));
        
        if (in_array($status, ['đã nộp', 'da nop', 'submitted'])) {
            return 'submitted';
        } elseif (in_array($status, ['chưa nộp', 'chua nop', 'not_submitted'])) {
            return 'not_submitted';
        }
        
        return null;
    }

    protected function normalizeSource($source)
    {
        if (empty($source)) return null;

        $source = strtolower(trim($source));

        // Map các giá trị tiếng Việt và tiếng Anh
        $sourceMap = [
            'facebook' => 'facebook',
            'fb' => 'facebook',
            'zalo' => 'zalo',
            'website' => 'website',
            'web' => 'website',
            'trang web' => 'website',
            'linkedin' => 'linkedin',
            'tiktok' => 'tiktok',
            'tik tok' => 'tiktok',
            'bạn bè' => 'friend_referral',
            'ban be' => 'friend_referral',
            'bạn bè giới thiệu' => 'friend_referral',
            'ban be gioi thieu' => 'friend_referral',
            'friend_referral' => 'friend_referral',
            'giới thiệu' => 'friend_referral',
            'gioi thieu' => 'friend_referral'
        ];

        return $sourceMap[$source] ?? null;
    }

    public function rules(): array
    {
        return [
            'so_dien_thoai' => 'required|string',
            'phone' => 'required_without:so_dien_thoai|string',
        ];
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
