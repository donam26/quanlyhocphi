<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class EnrollmentImportTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
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
                    "'123456789012", // cccd - format as text
                    'nguyenvana@example.com', // email
                    "'12/2/1990", // ngay_sinh - format as text
                    'Nam', // gioi_tinh
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
                    '456 Đường XYZ', // dia_chi_cong_ty
                    'facebook', // nguon
                    'Ghi chú mẫu', // ghi_chu
                    "'1/1/2024", // ngay_ghi_danh - format as text
                    'waiting', // trang_thai
                    "'5000000", // hoc_phi - format as text
                    "'2000000", // da_dong - format as text
                    'Ghi chú ghi danh' // ghi_chu_ghi_danh
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
            'cccd',
            'email',
            'ngay_sinh',
            'gioi_tinh',
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
            'ghi_chu',
            'ngay_ghi_danh',
            'trang_thai',
            'hoc_phi',
            'da_dong',
            'ghi_chu_ghi_danh'
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
        $sheet->setCellValue('A12', '• nguon: "facebook", "zalo", "website", "linkedin", "tiktok", "friends"');
        $sheet->setCellValue('A13', '• trang_thai: "waiting", "enrolled", "completed", "cancelled"');
        $sheet->setCellValue('A14', '• hoc_phi, da_dong: Số tiền (ví dụ: 5000000)');
        $sheet->setCellValue('A15', '• ngay_ghi_danh: Hỗ trợ nhiều format: 1/1/2024, 01/01/2024, 2024-01-01');
        $sheet->setCellValue('A16', '• Tất cả số điện thoại, CCCD, MST sẽ được format về text để tránh lỗi hiển thị');

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
            'A' => 12, // ho
            'B' => 12, // ten
            'C' => 15, // so_dien_thoai
            'D' => 15, // cccd
            'E' => 25, // email
            'F' => 12, // ngay_sinh
            'G' => 10, // gioi_tinh
            'H' => 15, // tinh_hien_tai
            'I' => 15, // tinh_noi_sinh
            'J' => 10, // dan_toc
            'K' => 12, // quoc_tich
            'L' => 20, // noi_cong_tac
            'M' => 15, // kinh_nghiem_ke_toan
            'N' => 20, // chuyen_mon_dao_tao
            'O' => 15, // ho_so_ban_cung
            'P' => 15, // trinh_do_hoc_van
            'Q' => 20, // ten_cong_ty
            'R' => 15, // ma_so_thue
            'S' => 25, // email_hoa_don
            'T' => 25, // dia_chi_cong_ty
            'U' => 12, // nguon
            'V' => 20, // ghi_chu
            'W' => 15, // ngay_ghi_danh
            'X' => 12, // trang_thai
            'Y' => 15, // hoc_phi
            'Z' => 15, // da_dong
            'AA' => 20 // ghi_chu_ghi_danh
        ];
    }
}
