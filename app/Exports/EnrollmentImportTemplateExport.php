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
                    'Nguyễn Văn', // ho
                    'A', // ten
                    '0901234567', // so_dien_thoai
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
                    '456 Đường XYZ', // dia_chi_cong_ty
                    'facebook', // nguon
                    'Ghi chú mẫu', // ghi_chu
                    '01/01/2024', // ngay_ghi_danh
                    'waiting', // trang_thai
                    '5000000', // hoc_phi
                    '2000000', // da_dong
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
        $sheet->setCellValue('A5', '• ho, ten, so_dien_thoai là bắt buộc');
        $sheet->setCellValue('A6', '• ngay_sinh, ngay_ghi_danh: DD/MM/YYYY (ví dụ: 01/01/1990)');
        $sheet->setCellValue('A7', '• gioi_tinh: Nam, Nữ hoặc để trống');
        $sheet->setCellValue('A8', '• trang_thai: "waiting", "active", "completed", "cancelled"');
        $sheet->setCellValue('A9', '• ho_so_ban_cung: "Đã nộp", "Chưa nộp" hoặc để trống');
        $sheet->setCellValue('A10', '• trinh_do_hoc_van: "Đại học", "Cao đẳng", "Trung cấp", "Thạc sĩ", "VB2"');
        $sheet->setCellValue('A11', '• nguon: "facebook", "zalo", "website", "linkedin", "tiktok", "friend_referral"');
        $sheet->setCellValue('A12', '• hoc_phi, da_dong: Số tiền (ví dụ: 5000000)');

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
            'D' => 25, // email
            'E' => 12, // ngay_sinh
            'F' => 10, // gioi_tinh
            'G' => 15, // tinh_hien_tai
            'H' => 15, // tinh_noi_sinh
            'I' => 10, // dan_toc
            'J' => 12, // quoc_tich
            'K' => 20, // noi_cong_tac
            'L' => 15, // kinh_nghiem_ke_toan
            'M' => 20, // chuyen_mon_dao_tao
            'N' => 15, // ho_so_ban_cung
            'O' => 15, // trinh_do_hoc_van
            'P' => 20, // ten_cong_ty
            'Q' => 15, // ma_so_thue
            'R' => 25, // email_hoa_don
            'S' => 25, // dia_chi_cong_ty
            'T' => 12, // nguon
            'U' => 20, // ghi_chu
            'V' => 15, // ngay_ghi_danh
            'W' => 12, // trang_thai
            'X' => 15, // hoc_phi
            'Y' => 15, // da_dong
            'Z' => 20, // ghi_chu_ghi_danh
        ];
    }
}
