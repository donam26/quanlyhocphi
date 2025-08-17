<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\Province;
use App\Models\Ethnicity;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    protected $importMode;
    protected $createdCount = 0;
    protected $updatedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];

    public function __construct($importMode = 'create_and_update')
    {
        $this->importMode = $importMode; // 'create_only', 'update_only', 'create_and_update'
    }

    public function model(array $row)
    {
        // Chuẩn hóa dữ liệu
        $data = $this->normalizeData($row);
        
        // Tìm học viên theo số điện thoại
        $existingStudent = Student::where('phone', $data['phone'])->first();
        
        if ($existingStudent) {
            // Nếu đã tồn tại
            if ($this->importMode === 'create_only') {
                $this->skippedCount++;
                return null;
            }
            
            // Cập nhật thông tin
            $existingStudent->update($data);
            $this->updatedCount++;
            return $existingStudent;
        } else {
            // Tạo mới
            if ($this->importMode === 'update_only') {
                $this->skippedCount++;
                return null;
            }
            
            $student = new Student($data);
            $this->createdCount++;
            return $student;
        }
    }

    protected function normalizeData(array $row)
    {
        // Skip empty rows
        if (empty(array_filter($row))) {
            return [];
        }

        $data = [];
        
        // Xử lý họ tên - header đơn giản
        $data['first_name'] = $row['ho'] ?? '';
        $data['last_name'] = $row['ten'] ?? '';
        
        // Thông tin cơ bản - header đơn giản
        $data['phone'] = $this->normalizePhone($row['so_dien_thoai'] ?? '');
        $data['email'] = $row['email'] ?? null;
        
        // Ngày sinh - header đơn giản
        if (isset($row['ngay_sinh']) && !empty($row['ngay_sinh'])) {
            $data['date_of_birth'] = $this->parseDate($row['ngay_sinh']);
        }
        
        // Tỉnh nơi sinh - header đơn giản
        if (isset($row['tinh_noi_sinh']) && !empty($row['tinh_noi_sinh'])) {
            $placeOfBirthProvinceName = trim($row['tinh_noi_sinh']);
            $placeOfBirthProvince = Province::where('name', $placeOfBirthProvinceName)->first();
            if (!$placeOfBirthProvince) {
                $placeOfBirthProvince = Province::where('name', 'like', '%' . $placeOfBirthProvinceName . '%')->first();
            }
            $data['place_of_birth_province_id'] = $placeOfBirthProvince ? $placeOfBirthProvince->id : null;
        }

        // Dân tộc - header đơn giản
        if (isset($row['dan_toc']) && !empty($row['dan_toc'])) {
            $ethnicityName = trim($row['dan_toc']);
            $ethnicity = Ethnicity::where('name', $ethnicityName)->first();
            if (!$ethnicity) {
                $ethnicity = Ethnicity::where('name', 'like', '%' . $ethnicityName . '%')->first();
            }
            $data['ethnicity_id'] = $ethnicity ? $ethnicity->id : null;
        }

        // Quốc tịch - header đơn giản
        $data['nation'] = $row['quoc_tich'] ?? 'Việt Nam';

        // Giới tính - header đơn giản
        $data['gender'] = $this->normalizeGender($row['gioi_tinh'] ?? null);

        // Tỉnh thành hiện tại - header đơn giản
        if (isset($row['tinh_hien_tai']) && !empty($row['tinh_hien_tai'])) {
            $provinceName = trim($row['tinh_hien_tai']);
            $province = Province::where('name', $provinceName)->first();
            if (!$province) {
                $province = Province::where('name', 'like', '%' . $provinceName . '%')->first();
            }
            $data['province_id'] = $province ? $province->id : null;
        }
        
        // Thông tin bổ sung cho kế toán - header đơn giản
        $data['current_workplace'] = $row['noi_cong_tac'] ?? null;
        $data['accounting_experience_years'] = isset($row['kinh_nghiem_ke_toan']) && is_numeric($row['kinh_nghiem_ke_toan'])
            ? (int)$row['kinh_nghiem_ke_toan'] : null;
        $data['education_level'] = $this->normalizeEducationLevel($row['trinh_do_hoc_van'] ?? null);
        $data['hard_copy_documents'] = $this->normalizeHardCopyDocuments($row['ho_so_ban_cung'] ?? null);
        $data['training_specialization'] = $row['chuyen_mon_dao_tao'] ?? null;

        // Thông tin công ty - header đơn giản
        $data['company_name'] = $row['ten_cong_ty'] ?? null;
        $data['tax_code'] = $row['ma_so_thue'] ?? null;
        $data['invoice_email'] = $row['email_hoa_don'] ?? null;
        $data['company_address'] = $row['dia_chi_cong_ty'] ?? null;

        // Ghi chú và nguồn
        $data['notes'] = $row['ghi_chu'] ?? null;
        $data['source'] = $this->normalizeSource($row['nguon'] ?? null);

        return array_filter($data, function($value) {
            return $value !== null && $value !== '';
        });
    }

    protected function normalizePhone($phone)
    {
        // Loại bỏ ký tự không phải số
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Chuẩn hóa số điện thoại Việt Nam
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

        // Chuẩn hóa chuỗi ngày
        $dateStr = trim($dateStr);

        try {
            // Nếu là số (Excel date serial), chuyển đổi
            if (is_numeric($dateStr)) {
                $date = Carbon::createFromFormat('Y-m-d', '1900-01-01')->addDays($dateStr - 2);
                return $date->format('Y-m-d');
            }

            // Thử các format khác nhau
            $formats = [
                'd/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y',
                'd/m/y', 'y-m-d', 'd-m-y', 'm/d/y',
                'Y/m/d', 'y/m/d'
            ];

            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $dateStr);
                    if ($date && $date->year >= 1900 && $date->year <= 2100) {
                        return $date->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Thử parse tự động
            $date = Carbon::parse($dateStr);
            if ($date && $date->year >= 1900 && $date->year <= 2100) {
                return $date->format('Y-m-d');
            }

            return null;
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

        // Map các giá trị tiếng Việt và tiếng Anh theo enum StudentSource
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
            'email' => 'nullable|email',
        ];
    }

    public function getCreatedCount()
    {
        return $this->createdCount;
    }

    public function getUpdatedCount()
    {
        return $this->updatedCount;
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
