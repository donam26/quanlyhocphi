<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StudentImportTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $templateData;

    public function __construct($templateData = [])
    {
        $this->templateData = $templateData;
    }

    public function array(): array
    {
        if (empty($this->templateData)) {
            // Trả về dữ liệu mẫu với header đơn giản
            return [
                [
                    "'Nguyễn Văn", // ho - format as text
                    "'A", // ten - format as text
                    "'0901234567", // so_dien_thoai - format as text
                    "'123456789012", // so_cccd_cmnd - format as text
                    'nguyenvana@example.com', // email
                    "'12/2/1990", // ngay_sinh - format as text
                    'Nam', // gioi_tinh
                    '123 Đường ABC, Quận 1', // dia_chi
                    'Hồ Chí Minh', // tinh_hien_tai
                    'Hà Nội', // tinh_noi_sinh
                    'Kinh', // dan_toc
                    'Việt Nam', // quoc_tich
                    'Công ty ABC', // noi_cong_tac
                    "'5", // kinh_nghiem_ke_toan - format as text
                    'Kế toán', // chuyen_mon_dao_tao
                    'Đã nộp', // ho_so_ban_cung
                    'Đại học', // trinh_do_hoc_van
                    'Công ty TNHH ABC', // ten_cong_ty
                    "'0123456789", // ma_so_thue - format as text
                    'ketoan@abc.com', // email_hoa_don
                    '456 Đường XYZ, Quận 2', // dia_chi_cong_ty
                    'facebook', // nguon
                    'Ghi chú mẫu' // ghi_chu
                ]
            ];
        }

        return $this->templateData;
    }

    public function headings(): array
    {
        return [
            'ho',
            'ten',
            'so_dien_thoai',
            'so_cccd_cmnd',
            'email',
            'ngay_sinh',
            'gioi_tinh',
            'dia_chi',
            'tinh_hien_tai',
            'tinh_noi_sinh',
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
        $sheet->setCellValue('A6', '✅ Các cột khác có thể bỏ trống: so_dien_thoai, email, dia_chi, v.v.');
        $sheet->setCellValue('A7', '📧 Email sẽ được tự động tạo nếu bỏ trống (dạng: ten.ho.random@gmail.com)');
        $sheet->setCellValue('A8', '📅 ngay_sinh: Hỗ trợ nhiều format: 12/2/2002, 12/02/2002, 2/2/2002, 2002-02-12');
        $sheet->setCellValue('A9', '• gioi_tinh: Nam, Nữ hoặc để trống');
        $sheet->setCellValue('A10', '• ho_so_ban_cung: "Đã nộp", "Chưa nộp" hoặc để trống');
        $sheet->setCellValue('A11', '• trinh_do_hoc_van: "Đại học", "Cao đẳng", "Trung cấp", "Thạc sĩ", "VB2"');
        $sheet->setCellValue('A12', '• nguon: "facebook", "zalo", "website", "linkedin", "tiktok", "friends"');
        $sheet->setCellValue('A13', '• tinh_hien_tai, tinh_noi_sinh: Tên đầy đủ (ví dụ: "Hồ Chí Minh", "Hà Nội")');
        $sheet->setCellValue('A14', '• Tất cả số điện thoại, CCCD, MST sẽ được format về text để tránh lỗi hiển thị');

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
            'A4:A12' => [
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
            'H' => 30, // Địa chỉ
            'I' => 20, // Tỉnh/TP hiện tại
            'J' => 20, // Tỉnh/TP nơi sinh
            'K' => 10, // Dân tộc
            'L' => 12, // Quốc tịch
            'M' => 25, // Nơi công tác
            'N' => 15, // Kinh nghiệm
            'O' => 20, // Chuyên môn
            'P' => 15, // Hồ sơ
            'Q' => 15, // Trình độ
            'R' => 25, // Tên công ty
            'S' => 15, // Mã số thuế
            'T' => 25, // Email hóa đơn
            'U' => 30, // Địa chỉ công ty
            'V' => 15, // Nguồn
            'W' => 20, // Ghi chú
        ];
    }
}
