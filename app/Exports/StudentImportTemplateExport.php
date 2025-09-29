<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class StudentImportTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected $templateData;

    public function __construct($templateData = [])
    {
        $this->templateData = $templateData;
    }

    public function array(): array
    {
        // Trả về một mảng rỗng, chỉ để export header và styles
        return [];
    }

    public function headings(): array
    {
        return [
            'ho',
            'ten',
            'so_dien_thoai',
            'cccd',
            'email',
            'ngay_sinh',
            'gioi_tinh',
            'dia_chi_hien_tai',
            'noi_sinh',
            'dan_toc',
            'quoc_tich',
            'noi_cong_tac',
            'kinh_nghiem_ke_toan',
            'chuyen_mon_dao_tao',
            'ho_so_ban_cung',
            'trinh_do_hoc_van',
            'ten_cong_ty',
            'ma_so_thue',
            'email_hoa_don',
            'dia_chi_cong_ty',
            'nguon',
            'ghi_chu'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Thêm ghi chú hướng dẫn
        $sheet->setCellValue('A4', 'HƯỚNG DẪN NHẬP LIỆU:');
        $sheet->setCellValue('A5', '🔴 Các cột BẮT BUỘC: ho, ten');
        $sheet->setCellValue('A6', '✅ Các cột khác có thể bỏ trống: so_dien_thoai, email, v.v.');
        $sheet->setCellValue('A7', '📧 Email sẽ được tự động tạo nếu bỏ trống (dạng: ten.ho.random@gmail.com)');
        $sheet->setCellValue('A8', '📅 ngay_sinh: Hỗ trợ nhiều format: 12/2/2002, 12/02/2002, 2/2/2002, 2002-02-12');
        $sheet->setCellValue('A9', '• gioi_tinh: Nam, Nữ hoặc để trống');
        $sheet->setCellValue('A10', '• ho_so_ban_cung: "Đã nộp", "Chưa nộp" hoặc để trống');
        $sheet->setCellValue('A11', '• trinh_do_hoc_van: "Đại học", "Cao đẳng", "Trung cấp", "Thạc sĩ", "VB2"');
        $sheet->setCellValue('A12', '• kinh_nghiem_ke_toan: Số năm (ví dụ: 5, 10) hoặc để trống');
        $sheet->setCellValue('A13', '• nguon: "facebook", "zalo", "website", "linkedin", "tiktok", "friends"');
        $sheet->setCellValue('A14', '• tinh_hien_tai, tinh_noi_sinh: Tên đầy đủ (ví dụ: "Hồ Chí Minh", "Hà Nội")');
        $sheet->setCellValue('A15', '• Tất cả số điện thoại, CCCD, MST sẽ được format về text để tránh lỗi hiển thị');

        return [
            // Style cho header
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ]
            ],
            // Style cho dữ liệu mẫu
            2 => [
                'font' => [
                    'italic' => true,
                    'color' => ['rgb' => '666666']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8F9FA']
                ]
            ],
            // Style cho hướng dẫn
            'A4:A15' => [
                'font' => [
                    'size' => 10,
                    'color' => ['rgb' => '0066CC']
                ]
            ],
            'A4' => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['rgb' => '0066CC']
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Họ
            'B' => 15, // Tên
            'C' => 15, // Số điện thoại
            'D' => 18, // Số CCCD/CMND
            'E' => 25, // Email
            'F' => 15, // Ngày sinh
            'G' => 10, // Giới tính
            'H' => 20, // Địa chỉ hiện tại
            'I' => 20, // Nơi sinh
            'J' => 10, // Dân tộc
            'K' => 12, // Quốc tịch
            'L' => 25, // Nơi công tác
            'M' => 15, // Kinh nghiệm
            'N' => 20, // Chuyên môn
            'O' => 15, // Hồ sơ
            'P' => 15, // Trình độ
            'Q' => 25, // Tên công ty
            'R' => 15, // Mã số thuế
            'S' => 25, // Email hóa đơn
            'T' => 30, // Địa chỉ công ty
            'U' => 15, // Nguồn
            'V' => 20, // Ghi chú
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Thêm dữ liệu mẫu vào dòng 2
                $event->sheet->setCellValue('A2', 'Nguyễn Văn');
                $event->sheet->setCellValue('B2', 'An');
                $event->sheet->setCellValue('C2', '0901234567');
                $event->sheet->setCellValue('D2', '123456789012');
                $event->sheet->setCellValue('E2', 'nguyen.van.an@gmail.com');
                $event->sheet->setCellValue('F2', '01/01/1990');
                $event->sheet->setCellValue('G2', 'Nam');
                $event->sheet->setCellValue('H2', '123 Đường ABC, Quận 1, TP.HCM');
                $event->sheet->setCellValue('I2', 'Hồ Chí Minh');
                $event->sheet->setCellValue('J2', 'Kinh');
                $event->sheet->setCellValue('K2', 'Việt Nam');
                $event->sheet->setCellValue('L2', 'Công ty TNHH ABC');
                $event->sheet->setCellValue('M2', '5');
                $event->sheet->setCellValue('N2', 'Kế toán tài chính');
                $event->sheet->setCellValue('O2', 'Đã nộp');
                $event->sheet->setCellValue('P2', 'Đại học');
                $event->sheet->setCellValue('Q2', 'Công ty TNHH XYZ');
                $event->sheet->setCellValue('R2', '0123456789');
                $event->sheet->setCellValue('S2', 'ketoan@company.com');
                $event->sheet->setCellValue('T2', '456 Đường DEF, Quận 2, TP.HCM');
                $event->sheet->setCellValue('U2', 'website');
                $event->sheet->setCellValue('V2', 'Ghi chú mẫu');

                // Freeze header row
                $event->sheet->freezePane('A2');

                // Auto-fit columns
                foreach(range('A','V') as $column) {
                    $event->sheet->getColumnDimension($column)->setAutoSize(false);
                }

                // Thêm data validation cho các cột có giá trị cố định
                $sheet = $event->sheet->getDelegate();

                // Dropdown cho cột Giới tính (G)
                $genderValidation = $sheet->getCell('G2')->getDataValidation();
                $genderValidation->setType(DataValidation::TYPE_LIST);
                $genderValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $genderValidation->setAllowBlank(true);
                $genderValidation->setShowInputMessage(true);
                $genderValidation->setShowErrorMessage(true);
                $genderValidation->setShowDropDown(true);
                $genderValidation->setErrorTitle('Lỗi nhập liệu');
                $genderValidation->setError('Vui lòng chọn một trong các tùy chọn hoặc để trống');
                $genderValidation->setPromptTitle('Giới tính');
                $genderValidation->setPrompt('Chọn giới tính hoặc để trống');
                $genderValidation->setFormula1('"(Để trống),Nam,Nữ"');

                // Dropdown cho cột Hồ sơ bản cứng (O)
                $documentValidation = $sheet->getCell('O2')->getDataValidation();
                $documentValidation->setType(DataValidation::TYPE_LIST);
                $documentValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $documentValidation->setAllowBlank(true);
                $documentValidation->setShowInputMessage(true);
                $documentValidation->setShowErrorMessage(true);
                $documentValidation->setShowDropDown(true);
                $documentValidation->setErrorTitle('Lỗi nhập liệu');
                $documentValidation->setError('Vui lòng chọn một trong các tùy chọn hoặc để trống');
                $documentValidation->setPromptTitle('Hồ sơ bản cứng');
                $documentValidation->setPrompt('Chọn tình trạng hồ sơ hoặc để trống');
                $documentValidation->setFormula1('"(Để trống),Đã nộp,Chưa nộp"');

                // Dropdown cho cột Trình độ học vấn (P)
                $educationValidation = $sheet->getCell('P2')->getDataValidation();
                $educationValidation->setType(DataValidation::TYPE_LIST);
                $educationValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $educationValidation->setAllowBlank(true);
                $educationValidation->setShowInputMessage(true);
                $educationValidation->setShowErrorMessage(true);
                $educationValidation->setShowDropDown(true);
                $educationValidation->setErrorTitle('Lỗi nhập liệu');
                $educationValidation->setError('Vui lòng chọn một trong các tùy chọn hoặc để trống');
                $educationValidation->setPromptTitle('Trình độ học vấn');
                $educationValidation->setPrompt('Chọn trình độ học vấn hoặc để trống');
                $educationValidation->setFormula1('"(Để trống),Đại học,Cao đẳng,Trung cấp,Thạc sĩ,VB2"');

                // Dropdown cho cột Nguồn (U) - ĐÂY LÀ PHẦN QUAN TRỌNG
                $sourceValidation = $sheet->getCell('U2')->getDataValidation();
                $sourceValidation->setType(DataValidation::TYPE_LIST);
                $sourceValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $sourceValidation->setAllowBlank(true);
                $sourceValidation->setShowInputMessage(true);
                $sourceValidation->setShowErrorMessage(true);
                $sourceValidation->setShowDropDown(true);
                $sourceValidation->setErrorTitle('Lỗi nhập liệu');
                $sourceValidation->setError('Vui lòng chọn một trong các nguồn hoặc để trống');
                $sourceValidation->setPromptTitle('Nguồn khách hàng');
                $sourceValidation->setPrompt('Chọn nguồn khách hàng hoặc để trống');
                $sourceValidation->setFormula1('"(Để trống),Facebook,Zalo,Website,LinkedIn,TikTok,Bạn bè"');

                // Áp dụng validation cho toàn bộ cột (từ dòng 2 đến 1000)
                $sheet->setDataValidation('G2:G1000', clone $genderValidation);
                $sheet->setDataValidation('O2:O1000', clone $documentValidation);
                $sheet->setDataValidation('P2:P1000', clone $educationValidation);
                $sheet->setDataValidation('U2:U1000', clone $sourceValidation);
            }
        ];
    }
}
