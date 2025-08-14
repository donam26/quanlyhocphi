<?php

namespace App\Exports;

use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\Attendance;
use App\Enums\EnrollmentStatus;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class AttendanceMatrixExport implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    private $courseItem;
    private $enrollments;
    private $attendanceDates;
    private $attendanceMap;
    private $totalColumns;

    public function __construct(CourseItem $courseItem)
    {
        $this->courseItem = $courseItem->load('children.children.children');
        $this->prepareData();
    }

    private function prepareData()
    {
        // Lấy danh sách học viên đã ghi danh (bao gồm cả khóa con nếu có)
        $courseItemIds = $this->getAllCourseItemIds($this->courseItem);
        
        $this->enrollments = Enrollment::whereIn('course_item_id', $courseItemIds)
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->with(['student', 'courseItem'])
            ->orderBy('id')
            ->get();

        // Lấy tất cả điểm danh của các khóa học này
        $allAttendances = Attendance::whereIn('course_item_id', $courseItemIds)
            ->orderBy('attendance_date')
            ->get();

        // Lấy tất cả ngày đã điểm danh (unique)
        $this->attendanceDates = $allAttendances->map(function($attendance) {
                return $attendance->attendance_date instanceof Carbon 
                    ? $attendance->attendance_date->format('Y-m-d')
                    : $attendance->attendance_date;
            })
            ->unique()
            ->sort()
            ->values();

        // Tạo map điểm danh: [enrollment_id][date] = attendance_object
        $this->attendanceMap = [];
        foreach ($allAttendances as $attendance) {
            $dateKey = $attendance->attendance_date instanceof Carbon 
                ? $attendance->attendance_date->format('Y-m-d')
                : $attendance->attendance_date;
            $this->attendanceMap[$attendance->enrollment_id][$dateKey] = $attendance;
        }

        // Tính tổng số cột (1 cột tên + số ngày * 2 cột cho mỗi ngày)
        $this->totalColumns = 1 + $this->attendanceDates->count() * 2;
    }

    private function getAllCourseItemIds(CourseItem $courseItem)
    {
        $ids = [$courseItem->id];
        
        if ($courseItem->children && $courseItem->children->count() > 0) {
            foreach ($courseItem->children as $child) {
                $ids = array_merge($ids, $this->getAllCourseItemIds($child));
            }
        }
        
        return $ids;
    }

    public function array(): array
    {
        $data = [];
        
        // Row 1: Tiêu đề chính
        $titleRow = ['ĐIỂM DANH KHÓA HỌC: ' . strtoupper($this->courseItem->name)];
        for ($i = 1; $i < $this->totalColumns; $i++) {
            $titleRow[] = '';
        }
        $data[] = $titleRow;

        // Row 2: Trống
        $data[] = array_fill(0, $this->totalColumns, '');

        // Row 3: Header ngày
        $dateHeaderRow = ['Ngày'];
        foreach ($this->attendanceDates as $date) {
            $formattedDate = Carbon::parse($date)->format('d/m');
            $dateHeaderRow[] = $formattedDate;
            $dateHeaderRow[] = 'Ghi chú';
        }
        $data[] = $dateHeaderRow;

        // Rows 4+: Dữ liệu học viên
        foreach ($this->enrollments as $enrollment) {
            $studentRow = [$enrollment->student->full_name];
            
            foreach ($this->attendanceDates as $date) {
                $attendance = $this->attendanceMap[$enrollment->id][$date] ?? null;
                
                if ($attendance) {
                    // Cột trạng thái
                    $statusText = $this->getStatusText($attendance->status);
                    $studentRow[] = $statusText;
                    
                    // Cột ghi chú
                    $studentRow[] = $attendance->notes ?? '';
                } else {
                    // Chưa điểm danh
                    $studentRow[] = '';
                    $studentRow[] = '';
                }
            }
            
            $data[] = $studentRow;
        }

        return $data;
    }

    private function getStatusText($status)
    {
        return match($status) {
            'present' => 'Có mặt',
            'absent' => 'Vắng',
            'late' => 'Đi muộn', 
            'excused' => 'Có phép',
            default => ''
        };
    }

    public function title(): string
    {
        return 'Điểm danh';
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 25]; // Cột tên học viên rộng hơn
        
        $column = 'B';
        foreach ($this->attendanceDates as $date) {
            $widths[$column] = 12; // Cột trạng thái
            $column++;
            $widths[$column] = 15; // Cột ghi chú
            $column++;
        }
        
        return $widths;
    }



    private function getColumnLetter($columnNumber)
    {
        $letter = '';
        while ($columnNumber > 0) {
            $columnNumber--;
            $letter = chr(65 + ($columnNumber % 26)) . $letter;
            $columnNumber = intval($columnNumber / 26);
        }
        return $letter;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->getColumnLetter($this->totalColumns);
        $lastRow = 3 + $this->enrollments->count();

        return [
            // Tiêu đề chính
            'A1' => [
                'font' => [
                    'bold' => true,
                    'size' => 16,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD'],
                ],
            ],
            
            // Header ngày
            "A3:{$lastColumn}3" => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F5F5F5'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ],
            
            // Tất cả dữ liệu
            "A1:{$lastColumn}{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ],
            
            // Cột tên học viên
            "A4:A{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                ],
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }
}
