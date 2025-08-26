<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\Province;
use App\Models\Ethnicity;
use App\Services\DataNormalizer;
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
        $data['first_name'] = DataNormalizer::normalizeText($row['ho'] ?? '');
        $data['last_name'] = DataNormalizer::normalizeText($row['ten'] ?? '');

        // Thông tin cơ bản - header đơn giản
        $data['phone'] = DataNormalizer::normalizePhone($row['so_dien_thoai'] ?? '');
        $data['email'] = DataNormalizer::normalizeEmail($row['email'] ?? null);
        $data['citizen_id'] = DataNormalizer::normalizeCitizenId($row['cccd'] ?? $row['cmnd'] ?? $row['so_cccd'] ?? $row['so_cmnd'] ?? null);

        // Ngày sinh - header đơn giản
        if (isset($row['ngay_sinh']) && !empty($row['ngay_sinh'])) {
            $data['date_of_birth'] = DataNormalizer::normalizeDate($row['ngay_sinh']);
        }
        
        // Tỉnh nơi sinh - header đơn giản
        $placeOfBirthProvinceName = DataNormalizer::normalizeText($row['tinh_noi_sinh'] ?? null);
        if ($placeOfBirthProvinceName) {
            $placeOfBirthProvince = Province::where('name', $placeOfBirthProvinceName)->first();
            if (!$placeOfBirthProvince) {
                $placeOfBirthProvince = Province::where('name', 'like', '%' . $placeOfBirthProvinceName . '%')->first();
            }
            $data['place_of_birth_province_id'] = $placeOfBirthProvince ? $placeOfBirthProvince->id : null;
        }

        // Dân tộc - header đơn giản
        $ethnicityName = DataNormalizer::normalizeText($row['dan_toc'] ?? null);
        if ($ethnicityName) {
            $ethnicity = Ethnicity::where('name', $ethnicityName)->first();
            if (!$ethnicity) {
                $ethnicity = Ethnicity::where('name', 'like', '%' . $ethnicityName . '%')->first();
            }
            $data['ethnicity_id'] = $ethnicity ? $ethnicity->id : null;
        }

        // Quốc tịch - header đơn giản
        $data['nation'] = DataNormalizer::normalizeText($row['quoc_tich'] ?? null) ?: 'Việt Nam';

        // Giới tính - header đơn giản
        $data['gender'] = DataNormalizer::normalizeGender($row['gioi_tinh'] ?? null);

        // Tỉnh thành hiện tại - header đơn giản
        $provinceName = DataNormalizer::normalizeText($row['tinh_hien_tai'] ?? null);
        if ($provinceName) {
            $province = Province::where('name', $provinceName)->first();
            if (!$province) {
                $province = Province::where('name', 'like', '%' . $provinceName . '%')->first();
            }
            $data['province_id'] = $province ? $province->id : null;
        }
        
        // Thông tin bổ sung cho kế toán - header đơn giản
        $data['current_workplace'] = DataNormalizer::normalizeText($row['noi_cong_tac'] ?? null);

        // Xử lý kinh nghiệm kế toán
        $data['accounting_experience_years'] = DataNormalizer::normalizeNumber($row['kinh_nghiem_ke_toan'] ?? null);

        $data['education_level'] = DataNormalizer::normalizeEducationLevel($row['trinh_do_hoc_van'] ?? null);
        $data['hard_copy_documents'] = DataNormalizer::normalizeHardCopyDocuments($row['ho_so_ban_cung'] ?? null);
        $data['training_specialization'] = DataNormalizer::normalizeText($row['chuyen_mon_dao_tao'] ?? null);

        // Thông tin công ty - header đơn giản
        $data['company_name'] = DataNormalizer::normalizeText($row['ten_cong_ty'] ?? null);
        $data['tax_code'] = DataNormalizer::normalizeText($row['ma_so_thue'] ?? null);
        $data['invoice_email'] = DataNormalizer::normalizeEmail($row['email_hoa_don'] ?? null);
        $data['company_address'] = DataNormalizer::normalizeText($row['dia_chi_cong_ty'] ?? null);

        // Ghi chú và nguồn
        $data['notes'] = DataNormalizer::normalizeText($row['ghi_chu'] ?? null);
        $data['source'] = DataNormalizer::normalizeSource($row['nguon'] ?? null);

        return array_filter($data, function($value) {
            return $value !== null && $value !== '';
        });
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
