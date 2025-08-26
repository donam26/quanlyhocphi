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
                    'Nguyễn Văn', // ho
                    'A', // ten
                    '0901234567', // so_dien_thoai
                    '123456789012', // cccd
                    'nguyenvana@example.com', // email
                    '01/01/1990', // ngay_sinh
                    'Nam', // gioi_tinh
                    'Hồ Chí Minh', // tinh_hien_tai
                    'Hà Nội', // tinh_noi_sinh
                    'Kinh', // dan_toc
                    'Việt Nam', // quoc_tich
                    'Công ty ABC', // noi_cong_tac
                    '5', // kinh_nghiem_ke_toan
                    'Kế toán', // chuyen_mon_dao_tao
                    'Đã nộp', // ho_so_ban_cung
                    'Đại học', // trinh_do_hoc_van
                    'Công ty TNHH ABC', // ten_cong_ty
                    '0123456789', // ma_so_thue
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
            'ghi_chu'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Thêm ghi chú hướng dẫn
        $sheet->setCellValue('A4', 'HƯỚNG DẪN NHẬP LIỆU:');
        $sheet->setCellValue('A5', '• ho, ten, so_dien_thoai là bắt buộc');
        $sheet->setCellValue('A6', '• ngay_sinh: DD/MM/YYYY (ví dụ: 01/01/1990)');
        $sheet->setCellValue('A7', '• gioi_tinh: Nam, Nữ hoặc để trống');
        $sheet->setCellValue('A8', '• ho_so_ban_cung: "Đã nộp", "Chưa nộp" hoặc để trống');
        $sheet->setCellValue('A9', '• trinh_do_hoc_van: "Đại học", "Cao đẳng", "Trung cấp", "Thạc sĩ", "VB2"');
        $sheet->setCellValue('A10', '• nguon: "facebook", "zalo", "website", "linkedin", "tiktok", "friend_referral"');
        $sheet->setCellValue('A11', '• tinh_hien_tai, tinh_noi_sinh: Tên đầy đủ (ví dụ: "Hồ Chí Minh", "Hà Nội")');
        $sheet->setCellValue('A12', '• dan_toc: Tên dân tộc (ví dụ: "Kinh", "Tày", "Thái")');

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
            'D' => 25, // Email
            'E' => 15, // Ngày sinh
            'F' => 10, // Giới tính
            'G' => 20, // Tỉnh/TP hiện tại
            'H' => 20, // Tỉnh/TP nơi sinh
            'I' => 10, // Dân tộc
            'J' => 12, // Quốc tịch
            'K' => 25, // Nơi công tác
            'L' => 15, // Kinh nghiệm
            'M' => 20, // Chuyên môn
            'N' => 15, // Hồ sơ
            'O' => 15, // Trình độ
            'P' => 25, // Tên công ty
            'Q' => 15, // Mã số thuế
            'R' => 25, // Email hóa đơn
            'S' => 30, // Địa chỉ công ty
            'T' => 15, // Nguồn
            'U' => 20, // Ghi chú
        ];
    }
}
