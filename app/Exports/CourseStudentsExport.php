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
    protected $filters;
    protected $columnMappings;

    public function __construct(CourseItem $courseItem, $selectedColumns = [], $filters = [])
    {
        $this->courseItem = $courseItem;
        $this->selectedColumns = $selectedColumns;
        $this->filters = $filters;
        $this->initializeColumnMappings();
        $this->loadEnrollments();
    }

    protected function initializeColumnMappings()
    {
        $this->columnMappings = [
            // Thông tin cơ bản
            'student_name' => 'Họ và tên',
            'student_phone' => 'Số điện thoại',
            'student_email' => 'Email',
            'citizen_id' => 'Số CCCD/CMND',
            'course_name' => 'Khóa học cụ thể',
            'course_path' => 'Đường dẫn khóa học',
            'student_date_of_birth' => 'Ngày sinh',
            'student_gender' => 'Giới tính',
            'student_province' => 'Địa chỉ hiện tại',
            'place_of_birth_province' => 'Nơi sinh',
            'ethnicity' => 'Dân tộc',
            'student_address' => 'Địa chỉ',
            'current_workplace' => 'Nơi công tác',

            // Thông tin học vấn và kinh nghiệm
            'accounting_experience_years' => 'Kinh nghiệm kế toán (năm)',
            'education_level' => 'Trình độ học vấn',
            'training_specialization' => 'Chuyên môn đào tạo',
            'hard_copy_documents' => 'Hồ sơ bản cứng',

            // Thông tin công ty và hóa đơn
            'company_name' => 'Tên công ty',
            'tax_code' => 'Mã số thuế',
            'invoice_email' => 'Email hóa đơn',
            'company_address' => 'Địa chỉ công ty',

            // Thông tin ghi danh và thanh toán
            'enrollment_date' => 'Ngày ghi danh',
            'enrollment_status' => 'Trạng thái ghi danh',
            'course_fee' => 'Học phí khóa học',
            'final_fee' => 'Học phí sau chiết khấu',
            'discount_percentage' => 'Chiết khấu (%)',
            'discount_amount' => 'Số tiền chiết khấu',
            'total_paid' => 'Đã thanh toán',
            'remaining_amount' => 'Còn lại',
            'payment_status' => 'Trạng thái thanh toán',

            // Thông tin khác
            'notes' => 'Ghi chú',
            'created_at' => 'Ngày tạo hồ sơ'
        ];
    }

    protected function loadEnrollments()
    {
        // Lấy tất cả ID của khóa học này và các khóa học con
        $courseItemIds = [$this->courseItem->id];

        // Thêm ID của tất cả các khóa học con (descendants)
        foreach ($this->courseItem->descendants() as $descendant) {
            $courseItemIds[] = $descendant->id;
        }

        // Query enrollments từ tất cả khóa học (cha + con)
        $query = Enrollment::whereIn('course_item_id', $courseItemIds)
            ->with(['student.province', 'student.placeOfBirthProvince', 'student.ethnicity', 'payments', 'courseItem']);

        // Apply filters
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['payment_status'])) {
            $query->where('payment_status', $this->filters['payment_status']);
        }

        if (!empty($this->filters['from_date'])) {
            $query->whereDate('enrollment_date', '>=', $this->filters['from_date']);
        }

        if (!empty($this->filters['to_date'])) {
            $query->whereDate('enrollment_date', '<=', $this->filters['to_date']);
        }

        $this->enrollments = $query->orderBy('course_item_id')->get();
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
                    $row[] = "'" . $student->phone; // Thêm ' để Excel hiểu là text
                    break;
                case 'student_email':
                    $row[] = $student->email;
                    break;
                case 'citizen_id':
                    $row[] = "'" . $student->citizen_id; // Thêm ' để Excel hiểu là text
                    break;
                case 'course_name':
                    $row[] = $enrollment->courseItem->name ?? '';
                    break;
                case 'course_path':
                    $row[] = $enrollment->courseItem->path ?? '';
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
                case 'place_of_birth_province':
                    $row[] = $student->placeOfBirthProvince ? $student->placeOfBirthProvince->name : '';
                    break;
                case 'ethnicity':
                    $row[] = $student->ethnicity ? $student->ethnicity->name : '';
                    break;
                case 'current_workplace':
                    $row[] = $student->current_workplace ?? '';
                    break;
                case 'accounting_experience_years':
                    $row[] = $student->accounting_experience_years ?? '';
                    break;
                case 'education_level':
                    $row[] = $this->formatEducationLevel($student->education_level);
                    break;
                case 'training_specialization':
                    $row[] = $student->training_specialization;
                    break;
                case 'hard_copy_documents':
                    $row[] = $student->hard_copy_documents ? 'Có' : 'Không';
                    break;

                // Thông tin công ty và hóa đơn
                case 'company_name':
                    $row[] = $student->company_name;
                    break;
                case 'tax_code':
                    $row[] = "'" . $student->tax_code; // Thêm ' để Excel hiểu là text
                    break;
                case 'invoice_email':
                    $row[] = $student->invoice_email;
                    break;
                case 'company_address':
                    $row[] = $student->company_address;
                    break;

                case 'enrollment_date':
                    $row[] = $enrollment->enrollment_date ? $enrollment->enrollment_date->format('d/m/Y') : '';
                    break;
                case 'enrollment_status':
                    $row[] = $this->formatEnrollmentStatus($enrollment->status->value ?? $enrollment->status);
                    break;
                case 'course_fee':
                    $row[] = "'" . number_format($enrollment->courseItem->fee, 0, ',', '.'); // Học phí gốc của khóa học
                    break;
                case 'final_fee':
                    $row[] = "'" . number_format($enrollment->final_fee, 0, ',', '.'); // Học phí sau chiết khấu
                    break;
                case 'discount_percentage':
                    $row[] = "'" . $enrollment->discount_percentage . '%'; // Format as text
                    break;
                case 'discount_amount':
                    $row[] = "'" . number_format($enrollment->discount_amount, 0, ',', '.'); // Format as text
                    break;
                case 'total_paid':
                    $row[] = "'" . number_format($enrollment->getTotalPaidAmount(), 0, ',', '.'); // Format as text
                    break;
                case 'remaining_amount':
                    $row[] = "'" . number_format($enrollment->getRemainingAmount(), 0, ',', '.'); // Format as text
                    break;
                case 'payment_status':
                    $row[] = $this->getPaymentStatus($enrollment);
                    break;
                case 'notes':
                    $row[] = $enrollment->notes;
                    break;
                case 'created_at':
                    $row[] = $student->created_at ? $student->created_at->format('d/m/Y H:i') : '';
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
