<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

class InvoiceExport implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function title(): string
    {
        return 'Hóa đơn';
    }

    public function styles(Worksheet $sheet)
    {
        // Style cho tiêu đề chính
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '000000']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Merge cells cho tiêu đề
        $sheet->mergeCells('A1:B1');

        // Style cho các tiêu đề section (bold)
        $sectionRows = [];
        foreach ($this->data as $index => $row) {
            if (isset($row[0]) && !empty($row[0]) && strpos($row[0], ':') !== false) {
                $sectionRows[] = $index + 1; // +1 vì Excel bắt đầu từ 1
            }
        }

        foreach ($sectionRows as $rowNumber) {
            $sheet->getStyle("A{$rowNumber}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => '2E5BBA']
                ]
            ]);
        }

        // Style cho toàn bộ sheet
        $sheet->getStyle('A:B')->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 11
            ]
        ]);

        // Đặt chiều rộng cột
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(40);

        // Căn chỉnh dữ liệu
        $sheet->getStyle('A:A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B:B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        return [];
    }
}