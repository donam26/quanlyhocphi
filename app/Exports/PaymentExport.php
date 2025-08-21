<?php

namespace App\Exports;

use App\Models\Payment;
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

class PaymentExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $payments;
    protected $selectedColumns;
    protected $columnMappings;

    public function __construct($selectedColumns = [], $filters = [])
    {
        $this->selectedColumns = $selectedColumns;
        $this->initializeColumnMappings();
        $this->loadPayments($filters);
    }

    protected function initializeColumnMappings()
    {
        $this->columnMappings = [
            'student_name' => 'Họ và tên học viên',
            'student_phone' => 'Số điện thoại',
            'student_email' => 'Email',
            'course_name' => 'Khóa học',
            'payment_date' => 'Ngày thanh toán',
            'amount' => 'Số tiền (VNĐ)',
            'payment_method' => 'Phương thức thanh toán',
            'status' => 'Trạng thái',
            'transaction_reference' => 'Mã giao dịch',
            'enrollment_date' => 'Ngày ghi danh',
            'final_fee' => 'Học phí (VNĐ)',
            'notes' => 'Ghi chú',
            'student_address' => 'Địa chỉ học viên',
            'student_workplace' => 'Nơi công tác'
        ];
    }

    protected function loadPayments($filters)
    {
        $query = Payment::with(['enrollment.student.province', 'enrollment.courseItem']);

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('enrollment.student', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(IFNULL(first_name, ''), ' ', IFNULL(last_name, '')) LIKE ?", ["%{$search}%"])
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhereHas('enrollment.courseItem', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('payment_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('payment_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['course_item_id'])) {
            $courseItem = \App\Models\CourseItem::find($filters['course_item_id']);
            if ($courseItem) {
                // Lấy tất cả ID của khóa học này và các khóa học con
                $courseItemIds = [$courseItem->id];
                foreach ($courseItem->descendants() as $descendant) {
                    $courseItemIds[] = $descendant->id;
                }

                $query->whereHas('enrollment', function($q) use ($courseItemIds) {
                    $q->whereIn('course_item_id', $courseItemIds);
                });
            }
        }

        $this->payments = $query->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function collection()
    {
        return $this->payments;
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->selectedColumns as $column) {
            $headings[] = $this->columnMappings[$column] ?? $column;
        }
        return $headings;
    }

    public function map($payment): array
    {
        $row = [];
        $student = $payment->enrollment->student ?? null;
        $courseItem = $payment->enrollment->courseItem ?? null;
        
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
                case 'payment_date':
                    $row[] = $payment->payment_date ? 
                        Carbon::parse($payment->payment_date)->format('d/m/Y') : '';
                    break;
                case 'amount':
                    $row[] = number_format($payment->amount, 0, ',', '.');
                    break;
                case 'payment_method':
                    $row[] = $this->formatPaymentMethod($payment->payment_method);
                    break;
                case 'status':
                    $row[] = $this->formatStatus($payment->status);
                    break;
                case 'transaction_reference':
                    $row[] = $payment->transaction_reference ?? '';
                    break;
                case 'enrollment_date':
                    $row[] = $payment->enrollment->enrollment_date ? 
                        $payment->enrollment->enrollment_date->format('d/m/Y') : '';
                    break;
                case 'final_fee':
                    $row[] = $payment->enrollment ? 
                        number_format($payment->enrollment->final_fee, 0, ',', '.') : '';
                    break;
                case 'notes':
                    $row[] = $payment->notes ?? '';
                    break;
                case 'student_address':
                    $row[] = $student->address ?? '';
                    break;
                case 'student_workplace':
                    $row[] = $student->current_workplace ?? '';
                    break;
                default:
                    $row[] = '';
                    break;
            }
        }
        
        return $row;
    }

    protected function formatPaymentMethod($method)
    {
        $methods = [
            'cash' => 'Tiền mặt',
            'bank_transfer' => 'Chuyển khoản',
            'card' => 'Thẻ tín dụng',
            'qr_code' => 'Quét QR',
            'sepay' => 'SePay'
        ];

        return $methods[$method] ?? $method;
    }

    protected function formatStatus($status)
    {
        $statuses = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'cancelled' => 'Đã hủy'
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
        return 'Danh sách thanh toán';
    }
}
