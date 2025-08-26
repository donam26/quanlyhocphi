<?php

namespace App\Exports;

use App\Models\CourseItem;
use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $courseItem;
    protected $attendances;
    protected $selectedColumns;
    protected $startDate;
    protected $endDate;
    protected $filters;
    protected $columnMappings;

    public function __construct(CourseItem $courseItem, $selectedColumns = [], $startDate = null, $endDate = null, $filters = [])
    {
        $this->courseItem = $courseItem;
        $this->selectedColumns = $selectedColumns;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->filters = $filters;
        $this->initializeColumnMappings();
        $this->loadAttendances();
    }

    protected function initializeColumnMappings()
    {
        $this->columnMappings = [
            'student_name' => 'Họ và tên học viên',
            'student_phone' => 'Số điện thoại',
            'student_email' => 'Email',
            'attendance_date' => 'Ngày điểm danh',
            'status' => 'Trạng thái điểm danh',
            'notes' => 'Ghi chú điểm danh',

            // Thông tin học viên bổ sung
            'student_address' => 'Địa chỉ học viên',
            'student_workplace' => 'Nơi công tác',
            'student_province' => 'Tỉnh/Thành phố',
            'student_gender' => 'Giới tính',
            'student_date_of_birth' => 'Ngày sinh',
            'education_level' => 'Trình độ học vấn',
            'training_specialization' => 'Chuyên môn đào tạo',
            'accounting_experience_years' => 'Kinh nghiệm kế toán (năm)',

            // Thông tin công ty
            'company_name' => 'Tên công ty',
            'company_address' => 'Địa chỉ công ty',
            'tax_code' => 'Mã số thuế',
            'invoice_email' => 'Email hóa đơn',

            // Thông tin ghi danh
            'enrollment_date' => 'Ngày ghi danh',
            'enrollment_status' => 'Trạng thái ghi danh',
            'final_fee' => 'Học phí',
            'paid_amount' => 'Đã thanh toán',
            'remaining_amount' => 'Còn lại',
            'payment_status' => 'Trạng thái thanh toán'
        ];
    }

    protected function loadAttendances()
    {
        // Lấy tất cả course IDs bao gồm cả khóa con
        $courseIds = $this->courseItem->getAllDescendantIds();
        $courseIds[] = $this->courseItem->id; // Thêm chính khóa này

        $query = Attendance::with([
            'enrollment.student.province',
            'enrollment.courseItem',
            'enrollment.payments'
        ])->whereIn('course_item_id', $courseIds);

        // Date filters
        if ($this->startDate) {
            $query->whereDate('attendance_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('attendance_date', '<=', $this->endDate);
        }

        // Status filter
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        // Enrollment status filter
        if (!empty($this->filters['enrollmentStatus'])) {
            $query->whereHas('enrollment', function($q) {
                $q->where('status', $this->filters['enrollmentStatus']);
            });
        }

        // Payment status filter
        if (!empty($this->filters['paymentStatus'])) {
            $query->whereHas('enrollment', function($q) {
                $q->where('payment_status', $this->filters['paymentStatus']);
            });
        }

        $this->attendances = $query->orderBy('attendance_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function collection()
    {
        return $this->attendances;
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->selectedColumns as $column) {
            $headings[] = $this->columnMappings[$column] ?? $column;
        }
        return $headings;
    }

    public function map($attendance): array
    {
        $row = [];
        $student = $attendance->enrollment->student;
        $enrollment = $attendance->enrollment;

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
                case 'attendance_date':
                    $row[] = $attendance->attendance_date ?
                        Carbon::parse($attendance->attendance_date)->format('d/m/Y') : '';
                    break;
                case 'status':
                    $row[] = $this->formatAttendanceStatus($attendance->status);
                    break;
                case 'notes':
                    $row[] = $attendance->notes;
                    break;

                // Thông tin học viên bổ sung
                case 'student_address':
                    $row[] = $student->address;
                    break;
                case 'student_workplace':
                    $row[] = $student->current_workplace;
                    break;
                case 'student_province':
                    $row[] = $student->province ? $student->province->name : '';
                    break;
                case 'student_gender':
                    $row[] = $this->formatGender($student->gender);
                    break;
                case 'student_date_of_birth':
                    $row[] = $student->date_of_birth ?
                        Carbon::parse($student->date_of_birth)->format('d/m/Y') : '';
                    break;
                case 'education_level':
                    $row[] = $student->education_level;
                    break;
                case 'training_specialization':
                    $row[] = $student->training_specialization;
                    break;
                case 'accounting_experience_years':
                    $row[] = $student->accounting_experience_years;
                    break;

                // Thông tin công ty
                case 'company_name':
                    $row[] = $student->company_name;
                    break;
                case 'company_address':
                    $row[] = $student->company_address;
                    break;
                case 'tax_code':
                    $row[] = $student->tax_code;
                    break;
                case 'invoice_email':
                    $row[] = $student->invoice_email;
                    break;

                // Thông tin ghi danh
                case 'enrollment_date':
                    $row[] = $enrollment->enrollment_date ?
                        $enrollment->enrollment_date->format('d/m/Y') : '';
                    break;
                case 'enrollment_status':
                    $row[] = $this->formatEnrollmentStatus($enrollment->status);
                    break;
                case 'final_fee':
                    $row[] = number_format($enrollment->final_fee, 0, ',', '.');
                    break;
                case 'paid_amount':
                    $paidAmount = $enrollment->payments->where('status', 'confirmed')->sum('amount');
                    $row[] = number_format($paidAmount, 0, ',', '.');
                    break;
                case 'remaining_amount':
                    $paidAmount = $enrollment->payments->where('status', 'confirmed')->sum('amount');
                    $remaining = $enrollment->final_fee - $paidAmount;
                    $row[] = number_format($remaining, 0, ',', '.');
                    break;
                case 'payment_status':
                    $row[] = $this->formatPaymentStatus($enrollment->payment_status);
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
        $title = 'Điểm danh - ' . $this->courseItem->name;
        
        if ($this->startDate || $this->endDate) {
            $title .= ' (';
            if ($this->startDate) {
                $title .= 'Từ ' . Carbon::parse($this->startDate)->format('d/m/Y');
            }
            if ($this->endDate) {
                if ($this->startDate) $title .= ' ';
                $title .= 'Đến ' . Carbon::parse($this->endDate)->format('d/m/Y');
            }
            $title .= ')';
        }
        
        return $title;
    }

    protected function formatAttendanceStatus($status)
    {
        switch ($status) {
            case 'present':
                return 'Có mặt';
            case 'absent':
                return 'Vắng mặt';
            case 'late':
                return 'Muộn';
            default:
                return $status;
        }
    }

    protected function formatGender($gender)
    {
        switch ($gender) {
            case 'male':
                return 'Nam';
            case 'female':
                return 'Nữ';
            default:
                return $gender;
        }
    }

    protected function formatEnrollmentStatus($status)
    {
        switch ($status) {
            case 'waiting':
                return 'Chờ xác nhận';
            case 'active':
                return 'Đang học';
            case 'completed':
                return 'Hoàn thành';
            case 'cancelled':
                return 'Đã hủy';
            default:
                return $status;
        }
    }

    protected function formatPaymentStatus($status)
    {
        switch ($status) {
            case 'unpaid':
                return 'Chưa thanh toán';
            case 'partial':
                return 'Thanh toán một phần';
            case 'paid':
                return 'Đã thanh toán';
            case 'no_fee':
                return 'Miễn phí';
            default:
                return $status;
        }
    }
}
