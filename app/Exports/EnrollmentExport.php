<?php

namespace App\Exports;

use App\Models\Enrollment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class EnrollmentExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $enrollments;
    protected $selectedColumns;
    protected $columnMappings;

    public function __construct($selectedColumns = [], $filters = [])
    {
        $this->selectedColumns = $selectedColumns;
        $this->initializeColumnMappings();
        $this->loadEnrollments($filters);
    }

    protected function initializeColumnMappings()
    {
        $this->columnMappings = [
            'student_name' => 'Họ và tên học viên',
            'student_phone' => 'Số điện thoại',
            'student_email' => 'Email',
            'course_name' => 'Khóa học',
            'enrollment_date' => 'Ngày ghi danh',
            'status' => 'Trạng thái ghi danh',
            'final_fee' => 'Học phí (VNĐ)',
            'paid_amount' => 'Đã thanh toán (VNĐ)',
            'remaining_amount' => 'Còn lại (VNĐ)',
            'payment_status' => 'Trạng thái thanh toán',
            'student_address' => 'Địa chỉ học viên',
            'student_workplace' => 'Nơi công tác',
            'student_province' => 'Tỉnh/Thành phố',
            'notes' => 'Ghi chú'
        ];
    }

    protected function loadEnrollments($filters)
    {
        $query = Enrollment::with(['student.province', 'courseItem']);

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhereHas('courseItem', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['course_item_id'])) {
            $courseItem = \App\Models\CourseItem::find($filters['course_item_id']);
            if ($courseItem) {
                // Lấy tất cả ID của khóa học này và các khóa học con
                $courseItemIds = [$courseItem->id];
                foreach ($courseItem->descendants() as $descendant) {
                    $courseItemIds[] = $descendant->id;
                }

                $query->whereIn('course_item_id', $courseItemIds);
            }
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('enrollment_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('enrollment_date', '<=', $filters['end_date']);
        }

        $this->enrollments = $query->orderBy('enrollment_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function collection()
    {
        return $this->enrollments;
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->selectedColumns as $column) {
            $headings[] = $this->columnMappings[$column] ?? $column;
        }
        return $headings;
    }

    public function map($enrollment): array
    {
        $row = [];
        $student = $enrollment->student ?? null;
        $courseItem = $enrollment->courseItem ?? null;
        
        foreach ($this->selectedColumns as $column) {
            switch ($column) {
                case 'student_name':
                    $row[] = $student->full_name ?? '';
                    break;
                case 'student_phone':
                    $row[] = $student->phone ?? '';
                    break;
                case 'student_email':
                    $row[] = $student->email ?? '';
                    break;
                case 'course_name':
                    $row[] = $courseItem->name ?? '';
                    break;
                case 'enrollment_date':
                    $row[] = $enrollment->enrollment_date ? 
                        $enrollment->enrollment_date->format('d/m/Y') : '';
                    break;
                case 'status':
                    $row[] = $this->formatStatus($enrollment->status->value ?? $enrollment->status);
                    break;
                case 'final_fee':
                    $row[] = number_format($enrollment->final_fee, 0, ',', '.');
                    break;
                case 'paid_amount':
                    $row[] = number_format($enrollment->paid_amount ?? 0, 0, ',', '.');
                    break;
                case 'remaining_amount':
                    $remaining = ($enrollment->final_fee ?? 0) - ($enrollment->paid_amount ?? 0);
                    $row[] = number_format($remaining, 0, ',', '.');
                    break;
                case 'payment_status':
                    $row[] = $this->formatPaymentStatus($enrollment->payment_status->value ?? $enrollment->payment_status);
                    break;
                case 'student_address':
                    $row[] = $student->address ?? '';
                    break;
                case 'student_workplace':
                    $row[] = $student->current_workplace ?? '';
                    break;
                case 'student_province':
                    $row[] = $student->province->name ?? '';
                    break;
                case 'notes':
                    $row[] = $enrollment->notes ?? '';
                    break;
                default:
                    $row[] = '';
                    break;
            }
        }
        
        return $row;
    }

    protected function formatStatus($status)
    {
        $statuses = [
            'waiting' => 'Chờ xác nhận',
            'active' => 'Đang học',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy'
        ];

        return $statuses[$status] ?? $status;
    }

    protected function formatPaymentStatus($status)
    {
        $statuses = [
            'unpaid' => 'Chưa thanh toán',
            'partial' => 'Thanh toán một phần',
            'paid' => 'Đã thanh toán',
            'no_fee' => 'Miễn phí'
        ];

        return $statuses[$status] ?? $status;
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Header style
        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Data rows style
        if ($highestRow > 1) {
            $sheet->getStyle('A2:' . $highestColumn . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        return [];
    }

    public function title(): string
    {
        return 'Danh sách ghi danh';
    }
}
