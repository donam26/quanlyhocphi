<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\CourseItem;
use App\Models\Enrollment;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UnifiedStudentImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    protected $courseItem;
    protected $enrollmentStatus;
    protected $discountPercentage;
    protected $importMode;
    protected $autoEnroll;

    // Counters
    protected $createdCount = 0;
    protected $updatedCount = 0;
    protected $enrolledCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];
    protected $totalRowsProcessed = 0;

    /**
     * Constructor
     *
     * @param string $importMode 'create_only', 'update_only', 'create_and_update'
     * @param CourseItem|null $courseItem Nếu có thì sẽ tự động ghi danh
     * @param string $enrollmentStatus 'active', 'waiting'
     * @param float $discountPercentage
     */
    public function __construct(
        $importMode = 'create_and_update',
        CourseItem $courseItem = null,
        $enrollmentStatus = 'active',
        $discountPercentage = 0
    ) {
        $this->importMode = $importMode;
        $this->courseItem = $courseItem;
        $this->enrollmentStatus = $enrollmentStatus;
        $this->discountPercentage = $discountPercentage;
        $this->autoEnroll = !is_null($courseItem);
    }

    public function model(array $row)
    {
        $this->totalRowsProcessed++;

        try {
            DB::beginTransaction();

            // Bỏ qua các dòng hướng dẫn hoặc dòng trống
            $ho_value = $row['ho'] ?? null;
            if (empty($ho_value) || is_string($ho_value) && (
                str_contains($ho_value, 'HƯỚNG DẪN NHẬP LIỆU:') ||
                str_contains($ho_value, 'Các cột BẮT BUỘC:') ||
                str_contains($ho_value, 'Các cột khác có thể bỏ trống:') ||
                str_contains($ho_value, 'Email sẽ được tự động tạo nếu bỏ trống')
            )) {
                DB::rollBack();
                return null; // Bỏ qua dòng này
            }

            // Kiểm tra thủ công sau khi đã bỏ qua các dòng hướng dẫn
            if (empty($row['ho']) || empty($row['ten'])) {
                $this->skippedCount++;
                DB::rollBack();
                return null; // Bỏ qua vì thiếu dữ liệu bắt buộc
            }

            Log::debug('Raw row data:', $row);

            // Chuẩn hóa dữ liệu học viên
            $studentData = $this->normalizeStudentData($row);

            Log::debug('Normalized student data:', $studentData);


            // Tự động generate email nếu không có
            if (empty($studentData['email'])) {
                $studentData['email'] = $this->generateFakeEmail($studentData['first_name'], $studentData['last_name']);
            }

            // Tìm hoặc tạo học viên và xử lý ghi danh
            $student = $this->processStudentAndEnrollment($studentData, $row);

            DB::commit();
            return $student;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors[] = "Dòng {$this->totalRowsProcessed}: " . $e->getMessage();
            return null;
        }
    }

    protected function processStudentAndEnrollment(array $studentData, array $row)
    {
        // 1. Tìm học viên (loại trừ đã xóa)
        $student = null;
        if (!empty($studentData['phone'])) {
            $student = Student::where('phone', $studentData['phone'])
                             ->whereNull('deleted_at')
                             ->first();
        }
        if (!$student && !empty($studentData['email'])) {
            $student = Student::where('email', $studentData['email'])
                             ->whereNull('deleted_at')
                             ->first();
        }

        // 2. Xử lý tạo/cập nhật học viên
        if ($student) {
            // Học viên đã tồn tại
            if ($this->importMode === 'create_only') {
                $this->skippedCount++;
            } else {
                $student->update($studentData);
                $this->updatedCount++;
            }
        } else {
            // Học viên mới
            if ($this->importMode === 'update_only') {
                $this->skippedCount++;
                return null; // Không tạo mới và không ghi danh
            } else {
                $student = Student::create($studentData);
                $this->createdCount++;
            }
        }

        // 3. Xử lý ghi danh (luôn chạy nếu có courseItem và học viên hợp lệ)
        if ($this->autoEnroll && $this->courseItem && $student) {
            $this->handleEnrollment($student, $row);
        }

        return $student;
    }

    protected function handleEnrollment(Student $student, array $row)
    {
        if (!$student) return;

        // Kiểm tra đã ghi danh chưa
        $existingEnrollment = Enrollment::where('student_id', $student->id)
            ->where('course_item_id', $this->courseItem->id)
            ->first();

        if ($existingEnrollment) {
            $this->skippedCount++;
            return;
        }

        // Tạo ghi danh mới
        $this->createEnrollment($student, $row);
        $this->enrolledCount++;
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

    /**
     * Generate fake email for identification purposes
     */
    protected function generateFakeEmail($firstName, $lastName)
    {
        // Chuyển đổi tiếng Việt sang không dấu
        $firstName = $this->removeVietnameseAccents($firstName);
        $lastName = $this->removeVietnameseAccents($lastName);

        // Tạo email với format: ten.ho.random@gmail.com
        $randomNumber = rand(1000, 9999);
        $email = strtolower($lastName . '.' . $firstName . '.' . $randomNumber . '@gmail.com');

        // Đảm bảo email unique
        while (Student::where('email', $email)->exists()) {
            $randomNumber = rand(1000, 9999);
            $email = strtolower($lastName . '.' . $firstName . '.' . $randomNumber . '@gmail.com');
        }

        return $email;
    }

    /**
     * Remove Vietnamese accents
     */
    protected function removeVietnameseAccents($str)
    {
        $accents = [
            'à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ',
            'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ',
            'ì', 'í', 'ị', 'ỉ', 'ĩ',
            'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ',
            'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ',
            'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ',
            'đ'
        ];

        $noAccents = [
            'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
            'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
            'i', 'i', 'i', 'i', 'i',
            'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
            'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
            'y', 'y', 'y', 'y', 'y',
            'd'
        ];

        return str_replace($accents, $noAccents, $str);
    }

    public function rules(): array
    {
        return [
            // Nới lỏng rule để validator không chặn các dòng hướng dẫn
            'ho' => 'nullable|string',
            'ten' => 'nullable|string',
            'so_dien_thoai' => 'nullable',
            'phone' => 'nullable',
            'email' => 'nullable|email',
            'ngay_sinh' => 'nullable',
            'date_of_birth' => 'nullable',
        ];
    }

    // Getters
    public function getCreatedCount() { return $this->createdCount; }
    public function getUpdatedCount() { return $this->updatedCount; }
    public function getEnrolledCount() { return $this->enrolledCount; }
    public function getSkippedCount() { return $this->skippedCount; }
    public function getErrors() { return $this->errors; }
    public function getTotalRowsProcessed() { return $this->totalRowsProcessed; }

    // Alias methods for backward compatibility
    public function getImportedCount() { return $this->enrolledCount; }

    protected function normalizeStudentData(array $row)
    {
        $data = [];

        // Thông tin cơ bản - header đơn giản
        $data['first_name'] = DataNormalizer::normalizeText($row['ho'] ?? null);
        $data['last_name'] = DataNormalizer::normalizeText($row['ten'] ?? null);
        $data['phone'] = DataNormalizer::normalizePhone($row['so_dien_thoai'] ?? $row['phone'] ?? null);
        $data['email'] = DataNormalizer::normalizeEmail($row['email'] ?? null);
        $data['citizen_id'] = DataNormalizer::normalizeCitizenId($row['cccd'] ?? $row['citizen_id'] ?? null);

        // Ngày sinh
        $data['date_of_birth'] = $this->parseDate($row['ngay_sinh'] ?? $row['date_of_birth'] ?? null);

        // Giới tính
        $data['gender'] = $this->normalizeGender($row['gioi_tinh'] ?? $row['gender'] ?? null);

        // Địa chỉ
        $data['address'] = DataNormalizer::normalizeText($row['dia_chi_hien_tai'] ?? $row['dia_chi'] ?? $row['address'] ?? null);

        // Tỉnh hiện tại
        $currentProvinceName = $row['tinh_hien_tai'] ?? $row['province'] ?? $row['dia_chi_hien_tai'] ?? null;
        if (!empty($currentProvinceName)) {
            $provinceName = trim($currentProvinceName);
            $province = Province::where('name', $provinceName)->first();
            if (!$province) {
                $province = Province::where('name', 'like', "%{$provinceName}%")->first();
            }
            $data['province_id'] = $province?->id;
        }

        // Tỉnh nơi sinh
        if (!empty($row['noi_sinh']) || !empty($row['tinh_noi_sinh']) || !empty($row['place_of_birth_province'])) {
            $placeOfBirthProvinceName = trim($row['noi_sinh'] ?? $row['tinh_noi_sinh'] ?? $row['place_of_birth_province']);
            $placeOfBirthProvince = Province::where('name', $placeOfBirthProvinceName)->first();
            if (!$placeOfBirthProvince) {
                $placeOfBirthProvince = Province::where('name', 'like', "%{$placeOfBirthProvinceName}%")->first();
            }
            $data['place_of_birth_province_id'] = $placeOfBirthProvince?->id;
        }

        // Dân tộc
        if (!empty($row['dan_toc']) || !empty($row['ethnicity'])) {
            $ethnicityName = trim($row['dan_toc'] ?? $row['ethnicity']);
            $ethnicity = Ethnicity::where('name', $ethnicityName)->first();
            if (!$ethnicity) {
                $ethnicity = Ethnicity::where('name', 'like', "%{$ethnicityName}%")->first();
            }
            $data['ethnicity_id'] = $ethnicity?->id;
        }

        // Quốc tịch
        $data['nation'] = DataNormalizer::normalizeText($row['quoc_tich'] ?? $row['nation'] ?? null);

        // Thông tin bổ sung
        $data['current_workplace'] = DataNormalizer::normalizeText($row['noi_cong_tac'] ?? $row['current_workplace'] ?? null);
        $data['accounting_experience_years'] = $this->normalizeAccountingExperience(
            $row['kinh_nghiem_ke_toan'] ?? $row['accounting_experience_years'] ?? null
        );
        $data['education_level'] = DataNormalizer::normalizeEducationLevel($row['trinh_do_hoc_van'] ?? $row['education_level'] ?? null);
        $data['hard_copy_documents'] = DataNormalizer::normalizeHardCopyDocuments($row['ho_so_ban_cung'] ?? $row['hard_copy_documents'] ?? null);
        $data['training_specialization'] = DataNormalizer::normalizeText($row['chuyen_mon_dao_tao'] ?? $row['training_specialization'] ?? null);

        // Thông tin công ty
        $data['company_name'] = DataNormalizer::normalizeText($row['ten_cong_ty'] ?? $row['company_name'] ?? null);
        $data['tax_code'] = DataNormalizer::normalizeText($row['ma_so_thue'] ?? $row['tax_code'] ?? null);
        $data['invoice_email'] = DataNormalizer::normalizeEmail($row['email_hoa_don'] ?? $row['invoice_email'] ?? null);
        $data['company_address'] = DataNormalizer::normalizeText($row['dia_chi_cong_ty'] ?? $row['company_address'] ?? null);

        // Ghi chú và nguồn
        $data['notes'] = DataNormalizer::normalizeText($row['ghi_chu'] ?? $row['notes'] ?? null);
        $data['source'] = $this->normalizeSource($row['nguon'] ?? $row['source'] ?? null);

        return array_filter($data, function($value) {
            return $value !== null && $value !== '';
        });
    }

    protected function parseDate($dateStr)
    {
        if (empty($dateStr)) return null;

        // Chuẩn hóa chuỗi ngày
        $dateStr = trim($dateStr);

        try {
            // Nếu là số (Excel date serial number)
            if (is_numeric($dateStr)) {
                // Excel date serial number (1 = 1/1/1900)
                $excelEpoch = Carbon::create(1900, 1, 1)->subDays(2); // Excel bug: treats 1900 as leap year
                $date = $excelEpoch->addDays($dateStr);
                return $date->format('Y-m-d');
            }

            // Chuẩn hóa format ngày tháng
            $dateStr = $this->normalizeDateString($dateStr);

            // Thử các format khác nhau
            $formats = [
                'd/m/Y',     // 12/02/2002
                'd/n/Y',     // 12/2/2002
                'j/n/Y',     // 2/2/2002
                'j/m/Y',     // 2/02/2002
                'd-m-Y',     // 12-02-2002
                'd-n-Y',     // 12-2-2002
                'j-n-Y',     // 2-2-2002
                'j-m-Y',     // 2-02-2002
                'Y-m-d',     // 2002-02-12
                'Y-n-j',     // 2002-2-2
                'm/d/Y',     // 02/12/2002 (US format)
                'n/j/Y',     // 2/2/2002 (US format)
                'd.m.Y',     // 12.02.2002
                'j.n.Y',     // 2.2.2002
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

            // Thử parse tự động với Carbon
            try {
                $date = Carbon::parse($dateStr);
                if ($date && $date->year >= 1900 && $date->year <= 2100) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Continue to manual parsing
            }

            // Thử parse thủ công với regex
            return $this->parseManualDate($dateStr);

        } catch (\Exception $e) {
            // Log error for debugging
            Log::warning('Date parsing failed', [
                'input' => $dateStr,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Normalize date string for better parsing
     */
    protected function normalizeDateString($dateStr)
    {
        // Remove extra spaces
        $dateStr = preg_replace('/\s+/', ' ', trim($dateStr));

        // Replace various separators with /
        $dateStr = preg_replace('/[-.\s]/', '/', $dateStr);

        // Remove any non-digit, non-slash characters
        $dateStr = preg_replace('/[^\d\/]/', '', $dateStr);

        return $dateStr;
    }

    /**
     * Manual date parsing with regex
     */
    protected function parseManualDate($dateStr)
    {
        // Pattern: day/month/year hoặc month/day/year
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/', $dateStr, $matches)) {
            $part1 = (int)$matches[1];
            $part2 = (int)$matches[2];
            $year = (int)$matches[3];

            // Convert 2-digit year to 4-digit
            if ($year < 100) {
                $year += ($year <= 30) ? 2000 : 1900;
            }

            // Determine if it's day/month or month/day
            // If part1 > 12, it must be day/month
            // If part2 > 12, it must be month/day
            // Otherwise, assume day/month (Vietnamese format)

            if ($part1 > 12 && $part2 <= 12) {
                // Must be day/month/year
                $day = $part1;
                $month = $part2;
            } elseif ($part2 > 12 && $part1 <= 12) {
                // Must be month/day/year
                $day = $part2;
                $month = $part1;
            } else {
                // Ambiguous, assume day/month/year (Vietnamese format)
                $day = $part1;
                $month = $part2;
            }

            // Validate date
            if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                try {
                    $date = Carbon::create($year, $month, $day);
                    return $date->format('Y-m-d');
                } catch (\Exception $e) {
                    // Invalid date
                }
            }
        }

        return null;
    }

    protected function normalizeGender($gender)
    {
        if (empty($gender)) return null;

        $gender = strtolower(trim($gender));

        if (in_array($gender, ['nam', 'male', 'boy', 'm', '1'])) {
            return 'male';
        }

        if (in_array($gender, ['nữ', 'nu', 'female', 'girl', 'f', '0'])) {
            return 'female';
        }

        return 'other';
    }



    protected function normalizeAccountingExperience($experience)
    {
        if (empty($experience)) return null;

        // Nếu đã là số thì return luôn
        if (is_numeric($experience)) {
            $years = (int)$experience;
            return $years >= 0 ? $years : null;
        }

        // Xử lý chuỗi text
        $experience = strtolower(trim($experience));

        // Loại bỏ các ký tự không cần thiết và extract số
        $experience = preg_replace('/[^\d\s]/', ' ', $experience);
        $numbers = preg_split('/\s+/', trim($experience));

        foreach ($numbers as $num) {
            if (is_numeric($num)) {
                $years = (int)$num;
                // Chỉ chấp nhận số hợp lý (0-50 năm)
                if ($years >= 0 && $years <= 50) {
                    return $years;
                }
            }
        }

        return null;
    }

    protected function normalizeSource($source)
    {
        if (empty($source)) return null;

        $source = strtolower(trim($source));

        $mapping = [
            'facebook' => 'facebook',
            'zalo' => 'zalo',
            'website' => 'website',
            'linkedin' => 'linkedin',
            'tiktok' => 'tiktok',
            'bạn bè' => 'friends',
            'ban be' => 'friends'
        ];

        return $mapping[$source] ?? $source;
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'ho.required' => 'Họ học viên là bắt buộc',
            'ho.string' => 'Họ phải là chuỗi ký tự',
            'ho.min' => 'Họ không được để trống',
            'ten.required' => 'Tên học viên là bắt buộc',
            'ten.string' => 'Tên phải là chuỗi ký tự',
            'ten.min' => 'Tên không được để trống',
            'email.email' => 'Email không đúng định dạng',
        ];
    }

    /**
     * Handle import failures
     */
    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $values = $failure->values();
            $studentName = ($values['ho'] ?? '') . ' ' . ($values['ten'] ?? '');
            $phone = $values['so_dien_thoai'] ?? $values['phone'] ?? 'N/A';

            Log::error('UnifiedStudentImport: Import failure', [
                'row' => $failure->row(),
                'student_name' => trim($studentName),
                'phone' => $phone,
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $values
            ]);

            $errorDetail = "Dòng {$failure->row()}: " . trim($studentName) . " (SĐT: {$phone}) - " . implode(', ', $failure->errors());
            $this->errors[] = $errorDetail;
        }
    }
}
