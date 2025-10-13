<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Log;

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
            'student_last_name' => 'Họ',
            'student_first_name' => 'Tên',
            'student_phone' => 'Số điện thoại',
            'citizen_id' => 'Số CCCD/CMND',
            'student_email' => 'Email',
            'course_name' => 'Khóa học cụ thể',
            'course_path' => 'Đường dẫn khóa học',
            'student_date_of_birth' => 'Ngày sinh',
            'student_gender' => 'Giới tính',
            'student_province' => 'Địa chỉ hiện tại',
            'place_of_birth_province' => 'Nơi sinh',
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
            'sources' => 'Nguồn',
            'notes' => 'Ghi chú',
            'created_at' => 'Ngày tạo',
            'enrollments_count' => 'Số khóa học',
            'total_paid' => 'Tổng đã thanh toán',
            'total_fee' => 'Tổng học phí',
            'remaining_amount' => 'Số tiền còn lại',
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
                case 'student_last_name':
                    $row[] = $student->first_name;
                    break;
                case 'student_first_name':
                    $row[] = $student->last_name;
                    break;
                case 'student_phone':
                    $row[] = "'" . $student->phone; // Thêm ' để Excel hiểu là text
                    break;
                case 'citizen_id':
                    $row[] = "'" . $student->citizen_id; // Thêm ' để Excel hiểu là text
                    break;
                case 'student_email':
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
                case 'student_date_of_birth':
                    $row[] = $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '';
                    break;
                case 'student_gender':
                    $row[] = $this->formatGender($student->gender);
                    break;
                case 'student_province':
                    $row[] = $student->province ? $student->province->name : '';
                    break;
                case 'place_of_birth_province':
                    $row[] = $student->placeOfBirthProvince ? $student->placeOfBirthProvince->name : '';
                    break;
                case 'ethnicity':
                    $ethnicityName = '';
                    if ($student->ethnicity_id && $student->ethnicity) {
                        $ethnicityName = $student->ethnicity->name;
                    } elseif ($student->ethnicity_id && !$student->ethnicity) {
                        // Nếu có ethnicity_id nhưng không load được relationship
                        $ethnicity = \App\Models\Ethnicity::find($student->ethnicity_id);
                        $ethnicityName = $ethnicity ? $ethnicity->name : 'Không xác định';
                    }

                    $row[] = $ethnicityName;
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
                case 'sources':
                    $row[] = $this->formatSources($student->sources);
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
                case 'remaining_amount':
                    $row[] = "'" . number_format($student->getRemainingAmount(), 0, ',', '.'); // Format as text
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
            case 'secondary':
                return 'Trung học';
            case 'vocational':
                return 'Trung cấp';
            case 'associate':
                return 'Cao đẳng';
            case 'bachelor':
                return 'Đại học';
            case 'second_degree':
                return 'Văn bằng 2';
            case 'master':
                return 'Thạc sĩ';
            case 'phd':
                return 'Tiến sĩ';
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

    protected function formatSources($sources)
    {
        if (empty($sources) || !is_array($sources)) {
            return '';
        }

        $formattedSources = [];
        foreach ($sources as $source) {
            switch ($source) {
                case 'facebook':
                    $formattedSources[] = 'Facebook';
                    break;
                case 'google':
                    $formattedSources[] = 'Google';
                    break;
                case 'website':
                    $formattedSources[] = 'Website';
                    break;
                case 'zalo':
                    $formattedSources[] = 'Zalo';
                    break;
                case 'linkedin':
                    $formattedSources[] = 'LinkedIn';
                    break;
                case 'tiktok':
                    $formattedSources[] = 'TikTok';
                    break;
                case 'friend_referral':
                    $formattedSources[] = 'Bạn bè giới thiệu';
                    break;
                case 'other':
                    $formattedSources[] = 'Khác';
                    break;
                default:
                    $formattedSources[] = $source ?? '';
                    break;
            }
        }

        return implode(', ', $formattedSources);
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
