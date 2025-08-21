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
    protected $columnMappings;

    public function __construct(CourseItem $courseItem, $selectedColumns = [], $startDate = null, $endDate = null)
    {
        $this->courseItem = $courseItem;
        $this->selectedColumns = $selectedColumns;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->initializeColumnMappings();
        $this->loadAttendances();
    }

    protected function initializeColumnMappings()
    {
        $this->columnMappings = [
            'student_name' => 'Họ và tên',
            'student_phone' => 'Số điện thoại',
            'student_email' => 'Email',
            'attendance_date' => 'Ngày điểm danh',
            'attendance_status' => 'Trạng thái',
            'course_name' => 'Khóa học',
            'notes' => 'Ghi chú',
            'enrollment_date' => 'Ngày ghi danh',
            'student_address' => 'Địa chỉ',
            'student_workplace' => 'Nơi công tác'
        ];
    }

    protected function loadAttendances()
    {
        // Lấy tất cả course IDs bao gồm cả khóa con
        $courseIds = $this->courseItem->getAllDescendantIds();
        $courseIds[] = $this->courseItem->id; // Thêm chính khóa này

        $query = Attendance::with(['enrollment.student.province', 'enrollment.courseItem'])
            ->whereIn('course_item_id', $courseIds);

        if ($this->startDate) {
            $query->whereDate('attendance_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('attendance_date', '<=', $this->endDate);
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
                case 'attendance_status':
                    $row[] = $this->formatAttendanceStatus($attendance->status);
                    break;
                case 'course_name':
                    $row[] = $attendance->enrollment->courseItem->name;
                    break;
                case 'notes':
                    $row[] = $attendance->notes;
                    break;
                case 'enrollment_date':
                    $row[] = $attendance->enrollment->enrollment_date ? 
                        $attendance->enrollment->enrollment_date->format('d/m/Y') : '';
                    break;
                case 'student_address':
                    $row[] = $student->address;
                    break;
                case 'student_workplace':
                    $row[] = $student->current_workplace;
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
}
