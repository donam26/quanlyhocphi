<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\Province;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
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

    public function __construct($importMode = 'update_only')
    {
        $this->importMode = $importMode;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            // Debug: Log raw Excel data
            Log::info("Import row data - Raw Excel keys: " . implode(', ', array_keys($row)));

            // Chuẩn hóa dữ liệu
            $data = $this->normalizeData($row);

            // Debug: Log company fields specifically
            Log::info("Import row data - Company fields: ", [
                'company_name' => $data['company_name'] ?? 'MISSING',
                'tax_code' => $data['tax_code'] ?? 'MISSING',
                'invoice_email' => $data['invoice_email'] ?? 'MISSING',
                'company_address' => $data['company_address'] ?? 'MISSING'
            ]);

            // Kiểm tra email
            if (empty($data['email'])) {
                $this->skippedCount++;
                $this->errors[] = "Dòng " . ($this->getRowNumber()) . ": Email không được để trống";
                return null;
            }

            // Tìm học viên theo email
            $student = Student::where('email', $data['email'])->first();

            if ($student) {
                // Cập nhật học viên đã có
                $student->update($data);
                $this->updatedCount++;
                Log::info("Updated student: " . $student->email);
                return $student;
            } else {
                // Tạo mới nếu chế độ cho phép
                if ($this->importMode === 'create_and_update') {
                    $student = Student::create($data);
                    $this->createdCount++;
                    Log::info("Created student: " . $student->email);
                    return $student;
                } else {
                    $this->skippedCount++;
                    $this->errors[] = "Dòng " . ($this->getRowNumber()) . ": Email {$data['email']} chưa tồn tại trong hệ thống";
                    return null;
                }
            }

        } catch (\Exception $e) {
            $this->skippedCount++;
            $this->errors[] = "Dòng " . ($this->getRowNumber()) . ": " . $e->getMessage();
            Log::error("Import error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Chuẩn hóa dữ liệu từ Excel
     */
    protected function normalizeData(array $row)
    {
        $data = [];

        // Mapping với Excel headers đã được normalize (snake_case, không dấu)
        $exactMapping = [
            'ho' => 'first_name',
            'ten' => 'last_name',
            'email' => 'email',
            'so_dien_thoai' => 'phone',
            'ngay_sinh_ddmmyyyy' => 'date_of_birth',
            'gioi_tinh_namnukhac' => 'gender',
            'dia_chi' => 'address',
            'tinh_thanh' => 'province_name',
            'noi_cong_tac_hien_tai' => 'current_workplace',
            'kinh_nghiem_ke_toan_nam' => 'accounting_experience_years',
            'noi_sinh' => 'place_of_birth',
            'dan_toc' => 'nation',
            'ho_so_ban_cung_da_nopchua_nop' => 'hard_copy_documents',
            'bang_cap_vb2trung_capcao_dangdai_hocthac_si' => 'education_level',
            'chuyen_mon_dao_tao' => 'training_specialization',
            'ten_don_vi_cho_hoa_don' => 'company_name',
            'ma_so_thue' => 'tax_code',
            'email_nhan_hoa_don' => 'invoice_email',
            'dia_chi_don_vi' => 'company_address',
            'ghi_chu' => 'notes'
        ];

        // Fallback mapping cho các tên cột khác
        $fallbackMapping = [
            'first_name' => ['ho', 'họ', 'first_name'],
            'last_name' => ['ten', 'tên', 'last_name'],
            'email' => ['email'],
            'phone' => ['sdt', 'phone'],
            'date_of_birth' => ['ngay_sinh', 'ngày sinh', 'date_of_birth'],
            'gender' => ['gioi_tinh', 'giới tính', 'gender'],
            'address' => ['dia_chi', 'địa chỉ', 'address'],
            'current_workplace' => ['noi_cong_tac', 'nơi công tác', 'current_workplace'],
            'accounting_experience_years' => ['kinh_nghiem', 'kinh nghiệm', 'accounting_experience_years'],
            'place_of_birth' => ['noi_sinh', 'nơi sinh', 'place_of_birth'],
            'nation' => ['dan_toc', 'dân tộc', 'nation'],
            'hard_copy_documents' => ['ho_so', 'hồ sơ', 'hard_copy_documents'],
            'education_level' => ['bang_cap', 'bằng cấp', 'education_level'],
            'training_specialization' => ['chuyen_mon', 'chuyên môn', 'training_specialization'],
            'company_name' => ['ten_cong_ty', 'tên công ty', 'company_name'],
            'tax_code' => ['ma_so_thue', 'mã số thuế', 'tax_code'],
            'invoice_email' => ['email_hoa_don', 'email hóa đơn', 'invoice_email'],
            'company_address' => ['dia_chi_cong_ty', 'địa chỉ công ty', 'company_address'],
            'notes' => ['ghi_chu', 'ghi chú', 'notes']
        ];

        // Bước 1: Exact mapping trước
        foreach ($exactMapping as $excelHeader => $dbField) {
            if (isset($row[$excelHeader]) && !empty($row[$excelHeader])) {
                $data[$dbField] = $this->cleanValue($row[$excelHeader]);
            }
        }

        // Bước 2: Fallback mapping cho các cột chưa được map
        foreach ($fallbackMapping as $field => $possibleKeys) {
            if (isset($data[$field])) continue; // Đã được map ở bước 1

            foreach ($possibleKeys as $key) {
                // Tìm key chính xác hoặc key có chứa pattern
                $foundKey = null;
                foreach (array_keys($row) as $rowKey) {
                    $normalizedRowKey = strtolower(trim($rowKey));
                    $normalizedSearchKey = strtolower(trim($key));

                    if ($normalizedRowKey === $normalizedSearchKey ||
                        strpos($normalizedRowKey, $normalizedSearchKey) !== false) {
                        $foundKey = $rowKey;
                        break;
                    }
                }

                if ($foundKey && isset($row[$foundKey]) && !empty($row[$foundKey])) {
                    $data[$field] = $this->cleanValue($row[$foundKey]);
                    break;
                }
            }
        }

        // Xử lý đặc biệt cho một số trường
        if (isset($data['date_of_birth'])) {
            $data['date_of_birth'] = $this->parseDate($data['date_of_birth']);
        }

        if (isset($data['gender'])) {
            $data['gender'] = $this->normalizeGender($data['gender']);
        }

        if (isset($data['hard_copy_documents'])) {
            $data['hard_copy_documents'] = $this->normalizeDocumentStatus($data['hard_copy_documents']);
        }

        if (isset($data['education_level'])) {
            $data['education_level'] = $this->normalizeEducationLevel($data['education_level']);
        }

        // Xử lý province_id từ province_name
        if (isset($row['province_name']) || isset($row['tinh_thanh']) || isset($row['tỉnh thành'])) {
            $provinceName = $row['province_name'] ?? $row['tinh_thanh'] ?? $row['tỉnh thành'] ?? null;
            if ($provinceName) {
                $province = Province::where('name', 'like', '%' . trim($provinceName) . '%')->first();
                if ($province) {
                    $data['province_id'] = $province->id;
                }
            }
        }

        return $data;
    }

    /**
     * Làm sạch giá trị
     */
    protected function cleanValue($value)
    {
        return is_string($value) ? trim($value) : $value;
    }

    /**
     * Parse ngày tháng
     */
    protected function parseDate($date)
    {
        if (empty($date)) return null;

        try {
            // Thử các định dạng khác nhau
            $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'];
            
            foreach ($formats as $format) {
                $parsed = Carbon::createFromFormat($format, $date);
                if ($parsed) {
                    return $parsed->format('Y-m-d');
                }
            }
            
            // Nếu không parse được, thử Carbon::parse
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Chuẩn hóa giới tính
     */
    protected function normalizeGender($gender)
    {
        $gender = mb_strtolower(trim($gender), 'UTF-8');

        if (in_array($gender, ['nam', 'male', 'm'])) {
            return 'male';
        } elseif (in_array($gender, ['nữ', 'nu', 'female', 'f'])) {
            return 'female';
        } else {
            return 'other';
        }
    }

    /**
     * Chuẩn hóa trạng thái hồ sơ
     */
    protected function normalizeDocumentStatus($status)
    {
        $status = mb_strtolower(trim($status), 'UTF-8');

        if (in_array($status, ['đã nộp', 'da nop', 'submitted', 'yes', 'có', 'đã nop'])) {
            return 'submitted';
        } else {
            return 'not_submitted';
        }
    }

    /**
     * Chuẩn hóa trình độ học vấn
     */
    protected function normalizeEducationLevel($level)
    {
        $level = mb_strtolower(trim($level), 'UTF-8');

        $mapping = [
            'vb2' => 'secondary',
            'trung cấp' => 'vocational',
            'trung cap' => 'vocational',
            'cao đẳng' => 'associate',
            'cao dang' => 'associate',
            'đại học' => 'bachelor',
            'dai hoc' => 'bachelor',
            'thạc sĩ' => 'master',
            'thac si' => 'master'
        ];

        return $mapping[$level] ?? $level;
    }

    /**
     * Validation rules - sử dụng header Excel
     */
    public function rules(): array
    {
        return [
            'email_*' => 'required|email',
            'ho_*' => 'required|string',
            'ten_*' => 'required|string',
            'so_dien_thoai_*' => 'nullable|string',
        ];
    }

    /**
     * Get row number for error reporting
     */
    protected function getRowNumber()
    {
        return $this->createdCount + $this->updatedCount + $this->skippedCount + 2; // +2 for header row
    }

    // Getter methods
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
