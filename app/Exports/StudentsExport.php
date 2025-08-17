<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

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
            'ho_va_ten' => 'ho_va_ten',
            'ho' => 'ho',
            'ten' => 'ten',
            'so_dien_thoai' => 'so_dien_thoai',
            'email' => 'email',
            'ngay_sinh' => 'ngay_sinh',
            'gioi_tinh' => 'gioi_tinh',
            'tinh_hien_tai' => 'tinh_hien_tai',
            'tinh_noi_sinh' => 'tinh_noi_sinh',
            'dan_toc' => 'dan_toc',
            'quoc_tich' => 'quoc_tich',
            'noi_cong_tac' => 'noi_cong_tac',
            'kinh_nghiem_ke_toan' => 'kinh_nghiem_ke_toan',
            'trinh_do_hoc_van' => 'trinh_do_hoc_van',
            'chuyen_mon_dao_tao' => 'chuyen_mon_dao_tao',
            'ho_so_ban_cung' => 'ho_so_ban_cung',
            'ten_cong_ty' => 'ten_cong_ty',
            'ma_so_thue' => 'ma_so_thue',
            'email_hoa_don' => 'email_hoa_don',
            'dia_chi_cong_ty' => 'dia_chi_cong_ty',
            'nguon' => 'nguon',
            'ghi_chu' => 'ghi_chu',
            'ngay_tao' => 'ngay_tao',
            'so_khoa_hoc' => 'so_khoa_hoc',
            'tong_da_thanh_toan' => 'tong_da_thanh_toan',
            'tong_hoc_phi' => 'tong_hoc_phi',
            'trang_thai_thanh_toan' => 'trang_thai_thanh_toan'
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
                    $row[] = $student->phone;
                    break;
                case 'email':
                    $row[] = $student->email;
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
                    $row[] = $student->accounting_experience_years;
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
                    $row[] = $student->tax_code;
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
                    $row[] = number_format($student->getTotalPaidAmount(), 0, ',', '.');
                    break;
                case 'total_fee':
                    $row[] = number_format($student->getTotalFeeAmount(), 0, ',', '.');
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
