<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\Province;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StudentUpdateImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    protected $updateMode;
    protected $updatedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];

    /**
     * @param string $updateMode - 'update_only' hoặc 'create_and_update'
     */
    public function __construct($updateMode = 'update_only')
    {
        $this->updateMode = $updateMode;
    }

    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Tìm học viên theo số điện thoại (trường bắt buộc để identify)
        $phone = $this->cleanPhone($row['so_dien_thoai'] ?? '');
        
        if (empty($phone)) {
            $this->skippedCount++;
            return null;
        }

        $student = Student::where('phone', $phone)->first();

        // Nếu không tìm thấy học viên
        if (!$student) {
            if ($this->updateMode === 'update_only') {
                $this->skippedCount++;
                Log::info("Bỏ qua học viên mới: {$phone} (chế độ chỉ cập nhật)");
                return null;
            } else {
                // Tạo mới nếu ở chế độ create_and_update
                $student = new Student();
                $student->phone = $phone;
            }
        }

        // Chuẩn bị dữ liệu cập nhật (chỉ cập nhật các trường có dữ liệu)
        $updateData = [];

        // Xử lý họ tên
        if (!empty($row['ho_ten'])) {
            $student->full_name = trim($row['ho_ten']);
        }

        // Xử lý email
        if (!empty($row['email'])) {
            $updateData['email'] = trim($row['email']);
        }

        // Xử lý ngày sinh
        if (!empty($row['ngay_sinh'])) {
            try {
                $updateData['date_of_birth'] = Carbon::createFromFormat('d/m/Y', $row['ngay_sinh']);
            } catch (\Exception $e) {
                Log::warning("Ngày sinh không hợp lệ cho {$phone}: {$row['ngay_sinh']}");
            }
        }

        // Xử lý giới tính
        if (!empty($row['gioi_tinh'])) {
            $updateData['gender'] = $this->mapGender($row['gioi_tinh']);
        }

        // Xử lý địa chỉ
        if (!empty($row['dia_chi'])) {
            $updateData['address'] = trim($row['dia_chi']);
        }

        // Xử lý nơi sinh
        if (!empty($row['noi_sinh'])) {
            $updateData['place_of_birth'] = trim($row['noi_sinh']);
        }

        // Xử lý dân tộc
        if (!empty($row['dan_toc'])) {
            $updateData['nation'] = trim($row['dan_toc']);
        }

        // Xử lý tỉnh thành
        if (!empty($row['tinh_thanh_pho'])) {
            $provinceName = trim($row['tinh_thanh_pho']);
            $province = Province::where('name', 'like', "%{$provinceName}%")->first();
            if ($province) {
                $updateData['province_id'] = $province->id;
            }
        }

        // Xử lý thông tin nghề nghiệp (cho lớp kế toán trưởng)
        if (!empty($row['noi_cong_tac'])) {
            $updateData['current_workplace'] = trim($row['noi_cong_tac']);
        }

        if (!empty($row['kinh_nghiem_ke_toan'])) {
            $updateData['accounting_experience_years'] = (int)$row['kinh_nghiem_ke_toan'];
        }

        if (!empty($row['bang_cap'])) {
            $updateData['education_level'] = $this->mapEducationLevel($row['bang_cap']);
        }

        if (!empty($row['chuyen_mon_cong_tac'])) {
            $updateData['training_specialization'] = trim($row['chuyen_mon_cong_tac']);
        }

        if (!empty($row['ho_so_ban_cung'])) {
            $updateData['hard_copy_documents'] = $this->mapHardCopyDocuments($row['ho_so_ban_cung']);
        }

        // Xử lý ghi chú
        if (!empty($row['ghi_chu'])) {
            $updateData['notes'] = trim($row['ghi_chu']);
        }

        // Cập nhật dữ liệu
        foreach ($updateData as $field => $value) {
            $student->$field = $value;
        }

        $student->save();
        $this->updatedCount++;

        return $student;
    }

    /**
     * Làm sạch số điện thoại
     */
    private function cleanPhone($phone)
    {
        return preg_replace('/[^0-9]/', '', trim($phone));
    }

    /**
     * Map giới tính
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
     * Map bằng cấp
     */
    private function mapEducationLevel($level)
    {
        $level = strtolower(trim($level));
        
        $mapping = [
            'đại học' => 'bachelor',
            'dai hoc' => 'bachelor',
            'thạc sĩ' => 'master',
            'thac si' => 'master',
            'cao đẳng' => 'associate',
            'cao dang' => 'associate',
            'trung cấp' => 'vocational',
            'trung cap' => 'vocational',
            'vb2' => 'secondary',
        ];

        return $mapping[$level] ?? 'other';
    }

    /**
     * Map hồ sơ bản cứng
     */
    private function mapHardCopyDocuments($status)
    {
        $status = strtolower(trim($status));
        
        if (in_array($status, ['đã nộp', 'da nop', 'submitted', 'có', 'co', 'yes'])) {
            return 'submitted';
        }
        
        return 'not_submitted';
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'so_dien_thoai' => 'required',
            'ngay_sinh' => 'nullable|date_format:d/m/Y',
            'email' => 'nullable|email',
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'so_dien_thoai.required' => 'Số điện thoại là bắt buộc để xác định học viên',
            'ngay_sinh.date_format' => 'Ngày sinh phải có định dạng DD/MM/YYYY',
            'email.email' => 'Email không hợp lệ',
        ];
    }

    /**
     * Lấy thống kê import
     */
    public function getStats()
    {
        return [
            'updated_count' => $this->updatedCount,
            'skipped_count' => $this->skippedCount,
            'errors' => $this->errors
        ];
    }

    /**
     * Handle import failures
     */
    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $values = $failure->values();
            $phone = $values['so_dien_thoai'] ?? 'N/A';

            $errorDetail = "Dòng {$failure->row()}: SĐT {$phone} - " . implode(', ', $failure->errors());
            $this->errors[] = $errorDetail;
        }
    }
}
