<?php

namespace App\Exports;

use App\Models\CourseItem;
use App\Models\Enrollment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CourseStudentsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $courseItem;
    protected $enrollments;
    protected $selectedColumns;
    protected $status;
    protected $columnMappings;

    public function __construct(CourseItem $courseItem, $selectedColumns = [], $status = null)
    {
        $this->courseItem = $courseItem;
        $this->selectedColumns = $selectedColumns;
        $this->status = $status;
        $this->initializeColumnMappings();
        $this->loadEnrollments();
    }

    protected function initializeColumnMappings()
    {
        $this->columnMappings = [
            'student_name' => 'Họ và tên',
            'student_phone' => 'Số điện thoại',
            'student_email' => 'Email',
            'student_date_of_birth' => 'Ngày sinh',
            'student_gender' => 'Giới tính',
            'student_address' => 'Địa chỉ',
            'student_province' => 'Tỉnh/Thành phố',
            'student_workplace' => 'Nơi công tác',
            'student_experience' => 'Kinh nghiệm (năm)',
            'student_education' => 'Trình độ học vấn',
            'enrollment_date' => 'Ngày ghi danh',
            'enrollment_status' => 'Trạng thái',
            'final_fee' => 'Học phí',
            'discount_percentage' => 'Chiết khấu (%)',
            'discount_amount' => 'Số tiền chiết khấu',
            'total_paid' => 'Đã thanh toán',
            'remaining_amount' => 'Còn lại',
            'payment_status' => 'Trạng thái thanh toán',
            'notes' => 'Ghi chú'
        ];
    }

    protected function loadEnrollments()
    {
        $query = $this->courseItem->enrollments()
            ->with(['student.province', 'payments']);

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $this->enrollments = $query->get();
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
        $student = $enrollment->student;
        
        foreach ($this->selectedColumns as $column) {
            switch ($column) {
                case 'student_name':
                    $row[] = $student->full_name;
                    break;
                case 'student_phone':
                    $row[] = $student->phone;
                    break;
                case 'student_email':
                    $row[] = $student->email;
                    break;
                case 'student_date_of_birth':
                    $row[] = $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '';
                    break;
                case 'student_gender':
                    $row[] = $this->formatGender($student->gender);
                    break;
                case 'student_address':
                    $row[] = $student->address;
                    break;
                case 'student_province':
                    $row[] = $student->province ? $student->province->name : '';
                    break;
                case 'student_workplace':
                    $row[] = $student->current_workplace;
                    break;
                case 'student_experience':
                    $row[] = $student->accounting_experience_years;
                    break;
                case 'student_education':
                    $row[] = $this->formatEducationLevel($student->education_level);
                    break;
                case 'enrollment_date':
                    $row[] = $enrollment->enrollment_date ? $enrollment->enrollment_date->format('d/m/Y') : '';
                    break;
                case 'enrollment_status':
                    $row[] = $this->formatEnrollmentStatus($enrollment->status);
                    break;
                case 'final_fee':
                    $row[] = number_format($enrollment->final_fee, 0, ',', '.');
                    break;
                case 'discount_percentage':
                    $row[] = $enrollment->discount_percentage . '%';
                    break;
                case 'discount_amount':
                    $row[] = number_format($enrollment->discount_amount, 0, ',', '.');
                    break;
                case 'total_paid':
                    $row[] = number_format($enrollment->getTotalPaidAmount(), 0, ',', '.');
                    break;
                case 'remaining_amount':
                    $row[] = number_format($enrollment->getRemainingAmount(), 0, ',', '.');
                    break;
                case 'payment_status':
                    $row[] = $this->getPaymentStatus($enrollment);
                    break;
                case 'notes':
                    $row[] = $enrollment->notes;
                    break;
                default:
                    $row[] = '';
            }
        }
        
        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E2E2']
                ]
            ],
        ];
    }

    public function title(): string
    {
        return 'Học viên - ' . $this->courseItem->name;
    }

    protected function formatGender($gender)
    {
        switch ($gender) {
            case 'male': return 'Nam';
            case 'female': return 'Nữ';
            case 'other': return 'Khác';
            default: return '';
        }
    }

    protected function formatEducationLevel($level)
    {
        switch ($level) {
            case 'vocational': return 'Trung cấp';
            case 'associate': return 'Cao đẳng';
            case 'bachelor': return 'Đại học';
            case 'master': return 'Thạc sĩ';
            case 'secondary': return 'VB2';
            default: return '';
        }
    }

    protected function formatEnrollmentStatus($status)
    {
        switch ($status) {
            case 'waiting': return 'Danh sách chờ';
            case 'active': return 'Đang học';
            case 'completed': return 'Đã hoàn thành';
            case 'cancelled': return 'Đã hủy';
            default: return $status;
        }
    }

    protected function getPaymentStatus($enrollment)
    {
        $remaining = $enrollment->getRemainingAmount();
        $totalPaid = $enrollment->getTotalPaidAmount();
        
        if ($enrollment->final_fee == 0) return 'Không có học phí';
        if ($remaining <= 0) return 'Đã thanh toán đủ';
        if ($totalPaid > 0) return 'Thanh toán một phần';
        return 'Chưa thanh toán';
    }
}
