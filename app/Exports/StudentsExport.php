<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    protected $students;
    protected $columns;

    public function __construct($students, $columns = [])
    {
        $this->students = $students;
        $this->columns = $columns;
    }

    public function collection()
    {
        return $this->students;
    }

    public function headings(): array
    {
        $headings = [];
        
        foreach ($this->columns as $column) {
            switch ($column) {
                case 'first_name':
                    $headings[] = 'Họ';
                    break;
                case 'last_name':
                    $headings[] = 'Tên';
                    break;
                case 'full_name':
                    $headings[] = 'Họ và tên';
                    break;
                case 'phone':
                    $headings[] = 'Số điện thoại';
                    break;
                case 'email':
                    $headings[] = 'Email';
                    break;
                case 'date_of_birth':
                    $headings[] = 'Ngày sinh';
                    break;
                case 'place_of_birth':
                    $headings[] = 'Nơi sinh';
                    break;
                case 'nation':
                    $headings[] = 'Dân tộc';
                    break;
                case 'gender':
                    $headings[] = 'Giới tính';
                    break;
                case 'province':
                    $headings[] = 'Tỉnh/Thành phố';
                    break;
                case 'address':
                    $headings[] = 'Địa chỉ cụ thể';
                    break;
                case 'current_workplace':
                    $headings[] = 'Nơi công tác';
                    break;
                case 'accounting_experience_years':
                    $headings[] = 'Kinh nghiệm kế toán (năm)';
                    break;
                case 'notes':
                    $headings[] = 'Ghi chú';
                    break;
                case 'hard_copy_documents':
                    $headings[] = 'Hồ sơ bản cứng';
                    break;
                case 'education_level':
                    $headings[] = 'Bằng cấp';
                    break;
                case 'workplace':
                    $headings[] = 'Nơi làm việc';
                    break;
                case 'experience_years':
                    $headings[] = 'Kinh nghiệm (năm)';
                    break;
                case 'enrollments':
                    $headings[] = 'Khóa học đã đăng ký';
                    break;
            }
        }
        
        return $headings;
    }

    public function map($student): array
    {
        $row = [];
        
        foreach ($this->columns as $column) {
            switch ($column) {
                case 'first_name':
                    $row[] = $student->first_name ?: '';
                    break;
                case 'last_name':
                    $row[] = $student->last_name ?: '';
                    break;
                case 'full_name':
                    $row[] = $student->full_name;
                    break;
                case 'phone':
                    $row[] = $student->phone ?: '';
                    break;
                case 'email':
                    $row[] = $student->email ?: '';
                    break;
                case 'date_of_birth':
                    $row[] = $student->formatted_date_of_birth;
                    break;
                case 'place_of_birth':
                    $row[] = $student->place_of_birth ?: '';
                    break;
                case 'nation':
                    $row[] = $student->nation ?: '';
                    break;
                case 'gender':
                    $row[] = $this->getGenderText($student->gender);
                    break;
                case 'province':
                    $row[] = $student->province ? $student->province->name : '';
                    break;
                case 'address':
                    $row[] = $student->address ?: '';
                    break;
                case 'current_workplace':
                    $row[] = $student->current_workplace ?: '';
                    break;
                case 'accounting_experience_years':
                    $row[] = $student->accounting_experience_years ?: '';
                    break;
                case 'notes':
                    $row[] = $student->notes ?: '';
                    break;
                case 'hard_copy_documents':
                    $row[] = $this->getHardCopyDocumentsText($student->hard_copy_documents);
                    break;
                case 'education_level':
                    $row[] = $this->getEducationLevelText($student->education_level);
                    break;
                case 'workplace':
                    $row[] = $student->workplace ?: '';
                    break;
                case 'experience_years':
                    $row[] = $student->experience_years ?: '';
                    break;
                case 'enrollments':
                    $enrollments = $student->enrollments->pluck('courseItem.name')->filter()->implode(', ');
                    $row[] = $enrollments ?: 'Chưa có khóa học';
                    break;
            }
        }
        
        return $row;
    }

    private function getGenderText($gender)
    {
        $genderMap = [
            'male' => 'Nam',
            'female' => 'Nữ',
            'other' => 'Khác'
        ];
        return $genderMap[$gender] ?? '';
    }

    private function getHardCopyDocumentsText($status)
    {
        $statusMap = [
            'submitted' => 'Đã nộp',
            'not_submitted' => 'Chưa nộp'
        ];
        return $statusMap[$status] ?? '';
    }

    private function getEducationLevelText($level)
    {
        $levelMap = [
            'vocational' => 'Trung cấp',
            'associate' => 'Cao đẳng',
            'bachelor' => 'Đại học',
            'master' => 'Thạc sĩ',
            'secondary' => 'VB2'
        ];
        return $levelMap[$level] ?? '';
    }
} 