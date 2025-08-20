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
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    protected $importMode;
    protected $createdCount = 0;
    protected $updatedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];
    protected $totalRowsProcessed = 0;

    public function __construct($importMode = 'create_and_update')
    {
        $this->importMode = $importMode; // 'create_only', 'update_only', 'create_and_update'
    }

    public function model(array $row)
    {
        $this->totalRowsProcessed++;

        try {
            // Chuẩn hóa dữ liệu
            $data = $this->normalizeData($row);

            // Skip empty rows
            if (empty($data) || empty($data['phone'])) {
                return null;
            }

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

        } catch (\Exception $e) {
            $this->errors[] = "Dòng {$this->totalRowsProcessed}: " . $e->getMessage();
            return null;
        }
    }

    protected function normalizeData(array $row)
    {
        // Skip empty rows
        $filteredRow = array_filter($row, function($value) {
            return $value !== null && $value !== '' && $value !== 0;
        });

        if (empty($filteredRow)) {
            return [];
        }

        $data = [];

        // Xử lý họ tên - header đơn giản
        $data['first_name'] = $this->normalizeString($row['ho'] ?? '');
        $data['last_name'] = $this->normalizeString($row['ten'] ?? '');

        // Thông tin cơ bản - header đơn giản
        $data['phone'] = $this->normalizePhone($row['so_dien_thoai'] ?? '');
        $data['email'] = $this->normalizeEmail($row['email'] ?? null);
        
        // Ngày sinh - header đơn giản
        if (isset($row['ngay_sinh']) && !empty($row['ngay_sinh'])) {
            $data['date_of_birth'] = $this->parseDate($row['ngay_sinh']);
        }
        
        // Tỉnh nơi sinh - header đơn giản
        $placeOfBirthProvinceName = $this->normalizeString($row['tinh_noi_sinh'] ?? null);
        if ($placeOfBirthProvinceName) {
            $placeOfBirthProvince = Province::where('name', $placeOfBirthProvinceName)->first();
            if (!$placeOfBirthProvince) {
                $placeOfBirthProvince = Province::where('name', 'like', '%' . $placeOfBirthProvinceName . '%')->first();
            }
            $data['place_of_birth_province_id'] = $placeOfBirthProvince ? $placeOfBirthProvince->id : null;
        }

        // Dân tộc - header đơn giản
        $ethnicityName = $this->normalizeString($row['dan_toc'] ?? null);
        if ($ethnicityName) {
            $ethnicity = Ethnicity::where('name', $ethnicityName)->first();
            if (!$ethnicity) {
                $ethnicity = Ethnicity::where('name', 'like', '%' . $ethnicityName . '%')->first();
            }
            $data['ethnicity_id'] = $ethnicity ? $ethnicity->id : null;
        }

        // Quốc tịch - header đơn giản
        $data['nation'] = $this->normalizeString($row['quoc_tich'] ?? null) ?: 'Việt Nam';

        // Giới tính - header đơn giản
        $data['gender'] = $this->normalizeGender($this->normalizeString($row['gioi_tinh'] ?? null));

        // Tỉnh thành hiện tại - header đơn giản
        $provinceName = $this->normalizeString($row['tinh_hien_tai'] ?? null);
        if ($provinceName) {
            $province = Province::where('name', $provinceName)->first();
            if (!$province) {
                $province = Province::where('name', 'like', '%' . $provinceName . '%')->first();
            }
            $data['province_id'] = $province ? $province->id : null;
        }
        
        // Thông tin bổ sung cho kế toán - header đơn giản
        $data['current_workplace'] = $this->normalizeString($row['noi_cong_tac'] ?? null);

        // Xử lý kinh nghiệm kế toán
        $experienceValue = $this->normalizeString($row['kinh_nghiem_ke_toan'] ?? null);
        $data['accounting_experience_years'] = is_numeric($experienceValue) ? (int)$experienceValue : null;

        $data['education_level'] = $this->normalizeEducationLevel($this->normalizeString($row['trinh_do_hoc_van'] ?? null));
        $data['hard_copy_documents'] = $this->normalizeHardCopyDocuments($this->normalizeString($row['ho_so_ban_cung'] ?? null));
        $data['training_specialization'] = $this->normalizeString($row['chuyen_mon_dao_tao'] ?? null);

        // Thông tin công ty - header đơn giản
        $data['company_name'] = $this->normalizeString($row['ten_cong_ty'] ?? null);
        $data['tax_code'] = $this->normalizeString($row['ma_so_thue'] ?? null);
        $data['invoice_email'] = $this->normalizeString($row['email_hoa_don'] ?? null);
        $data['company_address'] = $this->normalizeString($row['dia_chi_cong_ty'] ?? null);

        // Ghi chú và nguồn
        $data['notes'] = $this->normalizeString($row['ghi_chu'] ?? null);
        $data['source'] = $this->normalizeSource($this->normalizeString($row['nguon'] ?? null));

        return array_filter($data, function($value) {
            return $value !== null && $value !== '';
        });
    }

    protected function normalizePhone($phone)
    {
        if (empty($phone)) {
            return '';
        }

        // Xử lý tất cả kiểu dữ liệu từ Excel
        if (is_object($phone)) {
            // Nếu là object (như RichText), lấy plain text
            $phone = method_exists($phone, '__toString') ? (string) $phone : '';
        } elseif (is_numeric($phone)) {
            // Nếu là số, chuyển thành string và thêm số 0 ở đầu nếu cần
            $phone = (string) $phone;
            // Nếu số có 9 chữ số, thêm 0 ở đầu
            if (strlen($phone) === 9) {
                $phone = '0' . $phone;
            }
        } else {
            // Chuyển đổi thành string
            $phone = trim((string) $phone);
        }

        // Loại bỏ dấu ' ở đầu (từ Excel khi format text)
        $phone = ltrim($phone, "'\"");

        // Loại bỏ tất cả ký tự không phải số
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Chuẩn hóa số điện thoại Việt Nam
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return $phone;
        } elseif (strlen($phone) === 9) {
            return '0' . $phone;
        } elseif (strlen($phone) === 12 && substr($phone, 0, 2) === '84') {
            return '0' . substr($phone, 2);
        } elseif (strlen($phone) === 11 && substr($phone, 0, 3) === '840') {
            return '0' . substr($phone, 3);
        }

        return $phone;
    }

    /**
     * Chuẩn hóa string từ Excel
     */
    protected function normalizeString($value)
    {
        if (empty($value)) {
            return null;
        }

        // Xử lý tất cả kiểu dữ liệu từ Excel
        if (is_object($value)) {
            // Nếu là object (như RichText), lấy plain text
            $value = method_exists($value, '__toString') ? (string) $value : '';
        } elseif (is_numeric($value)) {
            // Nếu là số, chuyển thành string
            $value = (string) $value;
        } else {
            // Chuyển đổi thành string
            $value = (string) $value;
        }

        // Loại bỏ dấu ' ở đầu và cuối
        $value = trim($value, "'\"");

        // Trim whitespace
        $value = trim($value);

        return empty($value) ? null : $value;
    }

    /**
     * Chuẩn hóa email từ Excel
     */
    protected function normalizeEmail($value)
    {
        if (empty($value)) {
            return null;
        }

        // Xử lý tất cả kiểu dữ liệu từ Excel
        if (is_object($value)) {
            $value = method_exists($value, '__toString') ? (string) $value : '';
        } else {
            $value = (string) $value;
        }

        // Loại bỏ dấu ' ở đầu và cuối
        $value = trim($value, "'\"");

        // Trim whitespace và loại bỏ khoảng trắng thừa
        $value = trim($value);
        $value = preg_replace('/\s+/', '', $value); // Loại bỏ tất cả khoảng trắng

        // Validate email format
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return null; // Trả về null nếu email không hợp lệ
        }

        return empty($value) ? null : $value;
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
            'so_dien_thoai' => 'required', // Chấp nhận cả string và number
            'phone' => 'required_without:so_dien_thoai',
            'email' => 'nullable|email',
            'ho' => 'required',
            'ten' => 'required',
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

    public function getTotalRowsProcessed()
    {
        return $this->totalRowsProcessed;
    }

    /**
     * Handle validation failures
     */
    public function onError(\Throwable $e)
    {
        Log::error('StudentsImport: Validation error', [
            'error' => $e->getMessage(),
            'row' => $this->totalRowsProcessed
        ]);

        $this->errors[] = "Dòng {$this->totalRowsProcessed}: " . $e->getMessage();
    }

    /**
     * Handle import failures
     */
    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $values = $failure->values();
            $studentName = ($values['ho'] ?? '') . ' ' . ($values['ten'] ?? '');
            $phone = $values['so_dien_thoai'] ?? 'N/A';
            $email = $values['email'] ?? 'N/A';

            Log::error('StudentsImport: Import failure', [
                'row' => $failure->row(),
                'student_name' => trim($studentName),
                'phone' => $phone,
                'email' => $email,
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $values
            ]);

            $errorDetail = "Dòng {$failure->row()}: " . trim($studentName) . " (SĐT: {$phone}) - " . implode(', ', $failure->errors());
            $this->errors[] = $errorDetail;
        }
    }
}
