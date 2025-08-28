<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;

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
     * Chuẩn hóa số điện thoại
     * Chấp nhận cả text và number từ Excel, format lại thành chuỗi số
     */
    public static function normalizePhone($phoneValue)
    {
        if (empty($phoneValue)) {
            return null;
        }

        // Xử lý trường hợp Excel format số thành scientific notation
        if (is_numeric($phoneValue)) {
            // Chuyển về string với định dạng đầy đủ (không scientific notation)
            $phone = sprintf('%.0f', (float) $phoneValue);
        } else {
            // Chuyển về string và loại bỏ khoảng trắng
            $phone = trim((string) $phoneValue);
        }

        // Loại bỏ các ký tự không phải số (dấu phẩy, dấu chấm, khoảng trắng, dấu gạch ngang, etc.)
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Nếu bắt đầu bằng +84, chuyển thành 0
        if (str_starts_with($phone, '84') && strlen($phone) >= 10) {
            $phone = '0' . substr($phone, 2);
        }

        // Kiểm tra độ dài hợp lệ (10-11 số)
        if (strlen($phone) >= 10 && strlen($phone) <= 11) {
            return $phone;
        }

        // Nếu không hợp lệ nhưng có dữ liệu, vẫn trả về để người dùng có thể kiểm tra
        return $phone ?: null;
    }

    /**
     * Chuẩn hóa số CCCD/CMND
     * Chấp nhận cả text và number từ Excel, format lại thành chuỗi số
     */
    public static function normalizeCitizenId($citizenIdValue)
    {
        if (empty($citizenIdValue)) {
            return null;
        }

        // Xử lý trường hợp Excel format số thành scientific notation (1.23E+11)
        if (is_numeric($citizenIdValue)) {
            // Chuyển về string với định dạng đầy đủ (không scientific notation)
            $citizenId = sprintf('%.0f', (float) $citizenIdValue);
        } else {
            // Chuyển về string và loại bỏ khoảng trắng
            $citizenId = trim((string) $citizenIdValue);
        }

        // Loại bỏ các ký tự không phải số (dấu phẩy, dấu chấm, khoảng trắng, etc.)
        $citizenId = preg_replace('/[^0-9]/', '', $citizenId);

        // Kiểm tra độ dài hợp lệ (CMND: 9 số, CCCD: 12 số)
        if (strlen($citizenId) >= 9 && strlen($citizenId) <= 12) {
            return $citizenId;
        }

        // Nếu không hợp lệ nhưng có dữ liệu, vẫn trả về để người dùng có thể kiểm tra
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

        $education = strtolower(trim((string) $educationValue));
        
        // Mapping các giá trị
        $mappings = [
            'vocational' => ['trung cấp', 'tc', 'vocational'],
            'associate' => ['cao đẳng', 'cđ', 'associate'],
            'bachelor' => ['đại học', 'đh', 'bachelor', 'cử nhân'],
            'master' => ['thạc sĩ', 'ths', 'master'],
            'secondary' => ['trung học', 'thpt', 'secondary', 'vb2']
        ];
        
        foreach ($mappings as $key => $values) {
            foreach ($values as $value) {
                if (str_contains($education, $value)) {
                    return $key;
                }
            }
        }
        
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
        if (empty($statusValue)) {
            return null;
        }

        $status = strtolower(trim((string) $statusValue));
        
        $submittedValues = ['đã nộp', 'submitted', 'có', 'yes', '1', 'true'];
        $notSubmittedValues = ['chưa nộp', 'not_submitted', 'không', 'no', '0', 'false'];
        
        if (in_array($status, $submittedValues)) {
            return 'submitted';
        }
        
        if (in_array($status, $notSubmittedValues)) {
            return 'not_submitted';
        }
        
        return null;
    }
}
