<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StudentsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $students;
    protected $selectedColumns;
    protected $columnMappings;

    public function __construct($students, $selectedColumns = [])
    {
        $this->students = $students;
        $this->selectedColumns = $selectedColumns;
        $this->initializeColumnMappings();
    }

    protected function initializeColumnMappings()
    {
        $this->columnMappings = [
            'full_name' => 'Họ và tên',
            'first_name' => 'Họ',
            'last_name' => 'Tên',
            'phone' => 'Số điện thoại',
            'citizen_id' => 'Số CCCD/CMND',
            'email' => 'Email',
            'course_name' => 'Khóa học cụ thể',
            'course_path' => 'Đường dẫn khóa học',
            'date_of_birth' => 'Ngày sinh',
            'gender' => 'Giới tính',
            'province' => 'Tỉnh hiện tại',
            'place_of_birth_province' => 'Tỉnh nơi sinh',
            'ethnicity' => 'Dân tộc',
            'nation' => 'Quốc tịch',
            'address' => 'Địa chỉ',
            'current_workplace' => 'Nơi công tác',
            'accounting_experience_years' => 'Kinh nghiệm kế toán (năm)',
            'education_level' => 'Trình độ học vấn',
            'training_specialization' => 'Chuyên môn đào tạo',
            'hard_copy_documents' => 'Hồ sơ bản cứng',
            'company_name' => 'Tên công ty',
            'tax_code' => 'Mã số thuế',
            'invoice_email' => 'Email hóa đơn',
            'company_address' => 'Địa chỉ công ty',
            'source' => 'Nguồn',
            'notes' => 'Ghi chú',
            'created_at' => 'Ngày tạo',
            'enrollments_count' => 'Số khóa học',
            'total_paid' => 'Tổng đã thanh toán',
            'total_fee' => 'Tổng học phí',
            'payment_status' => 'Trạng thái thanh toán'
        ];
    }

    public function collection()
    {
        return $this->students;
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->selectedColumns as $column) {
            $headings[] = $this->columnMappings[$column] ?? $column;
        }
        return $headings;
    }

    public function map($student): array
    {
        $row = [];
        
        foreach ($this->selectedColumns as $column) {
            switch ($column) {
                case 'full_name':
                    $row[] = $student->full_name;
                    break;
                case 'first_name':
                    $row[] = $student->first_name;
                    break;
                case 'last_name':
                    $row[] = $student->last_name;
                    break;
                case 'phone':
                    $row[] = "'" . $student->phone; // Thêm ' để Excel hiểu là text
                    break;
                case 'citizen_id':
                    $row[] = "'" . $student->citizen_id; // Thêm ' để Excel hiểu là text
                    break;
                case 'email':
                    $row[] = $student->email;
                    break;
                case 'course_name':
                    // Lấy tên khóa học từ enrollment đầu tiên (nếu có filter course_item_id)
                    $enrollment = $student->enrollments->first();
                    $row[] = $enrollment && $enrollment->courseItem ? $enrollment->courseItem->name : '';
                    break;
                case 'course_path':
                    // Lấy đường dẫn khóa học từ enrollment đầu tiên
                    $enrollment = $student->enrollments->first();
                    $row[] = $enrollment && $enrollment->courseItem ? $enrollment->courseItem->path : '';
                    break;
                case 'date_of_birth':
                    $row[] = $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '';
                    break;
                case 'gender':
                    $row[] = $this->formatGender($student->gender);
                    break;
                case 'province':
                    $row[] = $student->province ? $student->province->name : '';
                    break;
                case 'place_of_birth_province':
                    $row[] = $student->placeOfBirthProvince ? $student->placeOfBirthProvince->name : '';
                    break;
                case 'ethnicity':
                    $row[] = $student->ethnicity ? $student->ethnicity->name : '';
                    break;
                case 'address':
                    $row[] = $student->address;
                    break;
                case 'current_workplace':
                    $row[] = $student->current_workplace;
                    break;
                case 'accounting_experience_years':
                    $row[] = $student->accounting_experience_years ? (string) $student->accounting_experience_years : '';
                    break;
                case 'education_level':
                    $row[] = $this->formatEducationLevel($student->education_level);
                    break;
                case 'training_specialization':
                    $row[] = $student->training_specialization;
                    break;
                case 'hard_copy_documents':
                    $row[] = $this->formatHardCopyDocuments($student->hard_copy_documents);
                    break;
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
                case 'source':
                    $row[] = $this->formatSource($student->source);
                    break;
                case 'notes':
                    $row[] = $student->notes;
                    break;
                case 'created_at':
                    $row[] = $student->created_at ? $student->created_at->format('d/m/Y H:i') : '';
                    break;
                case 'enrollments_count':
                    $row[] = $student->enrollments ? $student->enrollments->count() : 0;
                    break;
                case 'total_paid':
                    $row[] = "'" . number_format($student->getTotalPaidAmount(), 0, ',', '.'); // Format as text
                    break;
                case 'total_fee':
                    $row[] = "'" . number_format($student->getTotalFeeAmount(), 0, ',', '.'); // Format as text
                    break;
                case 'payment_status':
                    $row[] = $this->getPaymentStatus($student);
                    break;
                default:
                    $row[] = $student->{$column} ?? '';
            }
        }
        
        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E2E2']
                ]
            ],
        ];
    }

    protected function formatGender($gender)
    {
        switch ($gender) {
            case 'male':
                return 'Nam';
            case 'female':
                return 'Nữ';
            case 'other':
                return 'Khác';
            default:
                return '';
        }
    }

    protected function formatEducationLevel($level)
    {
        switch ($level) {
            case 'vocational':
                return 'Trung cấp';
            case 'associate':
                return 'Cao đẳng';
            case 'bachelor':
                return 'Đại học';
            case 'master':
                return 'Thạc sĩ';
            case 'secondary':
                return 'VB2';
            default:
                return '';
        }
    }

    protected function formatHardCopyDocuments($status)
    {
        switch ($status) {
            case 'submitted':
                return 'Đã nộp';
            case 'not_submitted':
                return 'Chưa nộp';
            default:
                return '';
        }
    }

    protected function formatSource($source)
    {
        switch ($source) {
            case 'facebook':
                return 'Facebook';
            case 'google':
                return 'Google';
            case 'website':
                return 'Website';
            case 'referral':
                return 'Giới thiệu';
            case 'other':
                return 'Khác';
            default:
                return '';
        }
    }

    protected function getPaymentStatus($student)
    {
        $totalFee = $student->getTotalFeeAmount();
        $paidAmount = $student->getTotalPaidAmount();
        
        if ($totalFee == 0) return 'Không có học phí';
        if ($paidAmount >= $totalFee) return 'Đã thanh toán đủ';
        if ($paidAmount > 0) return 'Thanh toán một phần';
        return 'Chưa thanh toán';
    }
}
