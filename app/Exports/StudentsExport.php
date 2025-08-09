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
                case 'gender':
                    $headings[] = 'Giới tính';
                    break;
                case 'address':
                    $headings[] = 'Địa chỉ';
                    break;
                case 'province':
                    $headings[] = 'Tỉnh/Thành phố';
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
                case 'full_name':
                    $row[] = $student->full_name;
                    break;
                case 'phone':
                    $row[] = $student->phone;
                    break;
                case 'email':
                    $row[] = $student->email ?: '';
                    break;
                case 'date_of_birth':
                    $row[] = $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '';
                    break;
                case 'gender':
                    $row[] = $this->getGenderText($student->gender);
                    break;
                case 'address':
                    $row[] = $student->address ?: '';
                    break;
                case 'province':
                    $row[] = $student->province ? $student->province->name : '';
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
} 