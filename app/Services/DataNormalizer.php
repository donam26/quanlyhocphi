<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DataNormalizer
{
    /**
     * Chuẩn hóa ngày tháng từ nhiều định dạng khác nhau
     */
    public static function normalizeDate($dateValue)
    {
        if (empty($dateValue)) {
            return null;
        }

        // Nếu đã là Carbon instance
        if ($dateValue instanceof Carbon) {
            return $dateValue->format('Y-m-d');
        }

        // Nếu là số (Excel date serial)
        if (is_numeric($dateValue)) {
            try {
                // Excel date serial number (days since 1900-01-01)
                $excelEpoch = Carbon::create(1900, 1, 1);
                $date = $excelEpoch->addDays($dateValue - 2); // -2 để điều chỉnh Excel bug
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        // Chuẩn hóa string
        $dateString = trim((string) $dateValue);

        // Các định dạng ngày phổ biến
        $formats = [
            'd/m/Y',     // 12/2/2004, 12/02/2004
            'd-m-Y',     // 12-2-2004, 12-02-2004
            'd.m.Y',     // 12.2.2004, 12.02.2004
            'Y-m-d',     // 2004-02-12
            'Y/m/d',     // 2004/02/12
            'm/d/Y',     // 2/12/2004 (US format)
            'd/m/y',     // 12/2/04
            'd-m-y',     // 12-2-04
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateString);
                if ($date && $date->year >= 1900 && $date->year <= 2100) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Chuẩn hóa số điện thoại.
     * Luôn xử lý giá trị như một chuỗi để bảo toàn số 0 ở đầu.
     */
    public static function normalizePhone($phoneValue)
    {
        if (is_null($phoneValue) || $phoneValue === '') {
            return null;
        }

        // Luôn xử lý đầu vào như một chuỗi
        $phone = trim((string) $phoneValue);

        // Xử lý trường hợp scientific notation từ Excel
        if (stripos($phone, 'E+') !== false) {
            $phone = sprintf('%.0f', (float) $phone);
        }

        // Loại bỏ tất cả các ký tự không phải số
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Nếu bắt đầu bằng '84' (mã quốc gia), chuyển thành '0'
        if (strlen($phone) > 9 && str_starts_with($phone, '84')) {
            $phone = '0' . substr($phone, 2);
        }

        // Trả về chuỗi số đã được làm sạch, hoặc null nếu rỗng
        return $phone ?: null;
    }

    /**
     * Chuẩn hóa số CCCD/CMND.
     * Luôn xử lý giá trị như một chuỗi để bảo toàn số 0 ở đầu.
     */
    public static function normalizeCitizenId($citizenIdValue)
    {
        if (is_null($citizenIdValue) || $citizenIdValue === '') {
            return null;
        }

        // Chuyển đổi giá trị thành chuỗi và loại bỏ khoảng trắng
        $citizenId = trim((string) $citizenIdValue);

        // Xử lý trường hợp scientific notation từ Excel (ví dụ: 1.23E+11)
        // Bằng cách kiểm tra sự tồn tại của 'E+' hoặc 'e+'
        if (stripos($citizenId, 'E+') !== false) {
            // Chuyển đổi an toàn sang chuỗi số đầy đủ
            $citizenId = sprintf('%.0f', (float) $citizenId);
        }

        // Loại bỏ tất cả các ký tự không phải là số
        $citizenId = preg_replace('/[^0-9]/', '', $citizenId);

        // Trả về chuỗi số đã được làm sạch, hoặc null nếu rỗng
        return $citizenId ?: null;
    }

    /**
     * Chuẩn hóa số (kinh nghiệm, tuổi, etc.)
     */
    public static function normalizeNumber($numberValue)
    {
        if (empty($numberValue)) {
            return null;
        }

        // Nếu đã là số
        if (is_numeric($numberValue)) {
            return (int) $numberValue;
        }

        // Chuyển về string và loại bỏ khoảng trắng
        $number = trim((string) $numberValue);

        // Loại bỏ các ký tự không phải số
        $number = preg_replace('/[^0-9]/', '', $number);

        return $number ? (int) $number : null;
    }

    /**
     * Chuẩn hóa email
     */
    public static function normalizeEmail($emailValue)
    {
        if (empty($emailValue)) {
            return null;
        }

        $email = trim((string) $emailValue);

        // Kiểm tra định dạng email cơ bản
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return strtolower($email);
        }

        return null;
    }

    /**
     * Chuẩn hóa text (tên, địa chỉ, etc.)
     */
    public static function normalizeText($textValue)
    {
        if (empty($textValue)) {
            return null;
        }

        $text = trim((string) $textValue);

        // Loại bỏ khoảng trắng thừa
        $text = preg_replace('/\s+/', ' ', $text);

        return $text ?: null;
    }

    /**
     * Chuẩn hóa giới tính
     */
    public static function normalizeGender($genderValue)
    {
        if (empty($genderValue)) {
            return null;
        }

        $gender = strtolower(trim((string) $genderValue));

        // Mapping các giá trị
        $maleValues = ['nam', 'male', 'boy', 'm', '1', 'true'];
        $femaleValues = ['nữ', 'nu', 'female', 'girl', 'f', '0', 'false'];

        if (in_array($gender, $maleValues)) {
            return 'male';
        }

        if (in_array($gender, $femaleValues)) {
            return 'female';
        }

        return 'other';
    }

    /**
     * Chuẩn hóa trình độ học vấn
     */
    public static function normalizeEducationLevel($educationValue)
    {
        if (empty($educationValue)) {
            return null;
        }

        Log::debug('normalizeEducationLevel - Input:', [$educationValue]);
        $normalized = self::removeAccents(trim((string) $educationValue));
        Log::debug('normalizeEducationLevel - Normalized value:', [$normalized]);

        if (str_contains($normalized, 'trung hoc') || str_contains($normalized, 'secondary')) {
            return 'secondary';
        }
        if (str_contains($normalized, 'trung cap')) {
            return 'vocational';
        }
        if (str_contains($normalized, 'cao dang') || str_contains($normalized, 'college')) {
            return 'associate';
        }
        if (str_contains($normalized, 'dai hoc') || str_contains($normalized, 'university') || str_contains($normalized, 'cu nhan')) {
            return 'bachelor';
        }
        if (str_contains($normalized, 'van bang 2') || str_contains($normalized, 'vb2') || str_contains($normalized, 'second_degree')) {
            return 'second_degree';
        }
        if (str_contains($normalized, 'thac si') || str_contains($normalized, 'master')) {
            return 'master';
        }
        if (str_contains($normalized, 'tien si') || str_contains($normalized, 'phd') || str_contains($normalized, 'doctor')) {
            return 'phd';
        }

        Log::warning('normalizeEducationLevel - No match found for:', [$normalized]);
        return null;
    }

    /**
     * Chuẩn hóa nguồn học viên
     */
    public static function normalizeSource($sourceValue)
    {
        if (empty($sourceValue)) {
            return null;
        }

        $source = strtolower(trim((string) $sourceValue));

        // Mapping các giá trị
        $mappings = [
            'facebook' => ['facebook', 'fb'],
            'zalo' => ['zalo'],
            'website' => ['website', 'web', 'trang web'],
            'linkedin' => ['linkedin'],
            'tiktok' => ['tiktok', 'tik tok'],
            'friend_referral' => ['bạn bè', 'giới thiệu', 'friend', 'referral']
        ];

        foreach ($mappings as $key => $values) {
            foreach ($values as $value) {
                if (str_contains($source, $value)) {
                    return $key;
                }
            }
        }

        return null;
    }

    /**
     * Chuẩn hóa trạng thái hồ sơ bản cứng
     */
    public static function normalizeHardCopyDocuments($statusValue)
    {
        if (is_null($statusValue) || $statusValue === '') {
            return null;
        }

        Log::debug('normalizeHardCopyDocuments - Input:', [$statusValue]);
        $normalized = self::removeAccents(trim((string) $statusValue));
        Log::debug('normalizeHardCopyDocuments - Normalized value:', [$normalized]);

        $submittedValues = ['da nop', 'submitted', 'co', 'yes', '1', 'true', 'roi'];
        if (in_array($normalized, $submittedValues) || str_contains($normalized, 'da nop')) {
            return 'submitted';
        }

        $notSubmittedValues = ['chua nop', 'not submitted', 'khong', 'no', '0', 'false', 'chua'];
        if (in_array($normalized, $notSubmittedValues) || str_contains($normalized, 'chua nop')) {
            return 'not_submitted';
        }

        Log::warning('normalizeHardCopyDocuments - No match found for:', [$normalized]);
        return null;
    }

    /**
     * Loại bỏ dấu tiếng Việt khỏi chuỗi
     */
    public static function removeAccents($str)
    {
        if (empty($str)) return '';

        // 1. Chuyển tất cả về chữ thường
        $str = mb_strtolower((string) $str, 'UTF-8');

        // 2. Tạo bảng thay thế ký tự
        $map = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        ];

        foreach ($map as $replacement => $pattern) {
            $str = preg_replace("/($pattern)/", $replacement, $str);
        }

        return $str;
    }
}


