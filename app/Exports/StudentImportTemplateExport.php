<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class StudentImportTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithTitle
{
    protected $data;

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        if (empty($this->data)) {
            // Trả về dữ liệu mẫu nếu không có data
            return [
                [
                    'Nguyễn Văn',
                    'A',
                    'nguyenvana@example.com',
                    '0901234567',
                    '01/01/1990',
                    'Nam',
                    '123 Đường ABC, Quận 1',
                    'Hồ Chí Minh',
                    'Công ty ABC',
                    '5',
                    'Hồ Chí Minh',
                    'Kinh',
                    'Đã nộp',
                    'Đại học',
                    'Kế toán',
                    'Công ty TNHH ABC',
                    '0123456789',
                    'ketoan@abc.com',
                    '456 Đường XYZ, Quận 2',
                    'Ghi chú mẫu'
                ],
                [
                    'Trần Thị',
                    'B',
                    'tranthib@example.com',
                    '0987654321',
                    '15/05/1985',
                    'Nữ',
                    '789 Đường DEF, Quận 3',
                    'Hà Nội',
                    'Công ty XYZ',
                    '8',
                    'Hà Nội',
                    'Kinh',
                    'Chưa nộp',
                    'Thạc sĩ',
                    'Tài chính',
                    'Công ty CP XYZ',
                    '9876543210',
                    'taichinh@xyz.com',
                    '321 Đường GHI, Quận 4',
                    'Học viên ưu tú'
                ]
            ];
        }

        // Chuyển đổi data thành array
        $result = [];
        foreach ($this->data as $item) {
            $result[] = [
                $item['first_name'] ?? '',
                $item['last_name'] ?? '',
                $item['email'] ?? '',
                $item['phone'] ?? '',
                $item['date_of_birth'] ?? '',
                $this->formatGender($item['gender'] ?? ''),
                $item['address'] ?? '',
                $item['province_name'] ?? '',
                $item['current_workplace'] ?? '',
                $item['accounting_experience_years'] ?? '',
                $item['place_of_birth'] ?? '',
                $item['nation'] ?? '',
                $this->formatDocumentStatus($item['hard_copy_documents'] ?? ''),
                $this->formatEducationLevel($item['education_level'] ?? ''),
                $item['training_specialization'] ?? '',
                $item['company_name'] ?? '',
                $item['tax_code'] ?? '',
                $item['invoice_email'] ?? '',
                $item['company_address'] ?? '',
                $item['notes'] ?? ''
            ];
        }

        return $result;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Họ *',
            'Tên *',
            'Email *',
            'Số điện thoại *',
            'Ngày sinh (dd/mm/yyyy)',
            'Giới tính (Nam/Nữ/Khác)',
            'Địa chỉ',
            'Tỉnh thành',
            'Nơi công tác hiện tại',
            'Kinh nghiệm kế toán (năm)',
            'Nơi sinh',
            'Dân tộc',
            'Hồ sơ bản cứng (Đã nộp/Chưa nộp)',
            'Bằng cấp (VB2/Trung cấp/Cao đẳng/Đại học/Thạc sĩ)',
            'Chuyên môn đào tạo',
            'Tên đơn vị (cho hóa đơn)',
            'Mã số thuế',
            'Email nhận hóa đơn',
            'Địa chỉ đơn vị',
            'Ghi chú'
        ];
    }



    /**
     * @return string
     */
    public function title(): string
    {
        return 'Mẫu Import Học viên';
    }

    /**
     * Format gender for display
     */
    protected function formatGender($gender)
    {
        $mapping = [
            'male' => 'Nam',
            'female' => 'Nữ',
            'other' => 'Khác'
        ];

        return $mapping[$gender] ?? $gender;
    }

    /**
     * Format document status for display
     */
    protected function formatDocumentStatus($status)
    {
        $mapping = [
            'submitted' => 'Đã nộp',
            'not_submitted' => 'Chưa nộp'
        ];

        return $mapping[$status] ?? $status;
    }

    /**
     * Format education level for display
     */
    protected function formatEducationLevel($level)
    {
        $mapping = [
            'secondary' => 'VB2',
            'vocational' => 'Trung cấp',
            'associate' => 'Cao đẳng',
            'bachelor' => 'Đại học',
            'master' => 'Thạc sĩ'
        ];

        return $mapping[$level] ?? $level;
    }
}
