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
    private $startDate;
    private $endDate;

    public function __construct(CourseItem $courseItem, $startDate = null, $endDate = null)
    {
        $this->courseItem = $courseItem->load('children.children.children');
        $this->startDate = $startDate;
        $this->endDate = $endDate;
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
        $attendanceQuery = Attendance::whereIn('course_item_id', $courseItemIds);

        if ($this->startDate) {
            $attendanceQuery->whereDate('attendance_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $attendanceQuery->whereDate('attendance_date', '<=', $this->endDate);
        }

        $allAttendances = $attendanceQuery->orderBy('attendance_date')->get();

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

        // Tính tổng số cột (1 cột tên + số ngày * 2 vì có cột ghi chú)
        $this->totalColumns = 1 + ($this->attendanceDates->count() * 2);
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

        // Row 1: Header với ngày điểm danh thực tế và cột ghi chú
        $headerRow = ['Tên học viên'];
        foreach ($this->attendanceDates as $date) {
            // Format ngày thành dd/mm/yyyy
            $formattedDate = $date instanceof \Carbon\Carbon
                ? $date->format('d/m/Y')
                : \Carbon\Carbon::parse($date)->format('d/m/Y');
            $headerRow[] = $formattedDate;
            $headerRow[] = 'Ghi chú ' . $formattedDate;
        }
        $data[] = $headerRow;

        // Rows 2+: Dữ liệu học viên
        foreach ($this->enrollments as $enrollment) {
            $studentName = trim($enrollment->student->first_name . ' ' . $enrollment->student->last_name);
            $studentRow = [$studentName]; // Tên học viên

            foreach ($this->attendanceDates as $date) {
                $attendance = $this->attendanceMap[$enrollment->id][$date] ?? null;

                // Cột điểm danh
                if ($attendance && $attendance->status === 'present') {
                    $studentRow[] = 'x'; // Có mặt: dấu x
                } else {
                    $studentRow[] = ''; // Vắng mặt hoặc chưa điểm danh: để trống
                }

                // Cột ghi chú
                $notes = $attendance ? ($attendance->notes ?? '') : '';
                $studentRow[] = $notes;
            }

            $data[] = $studentRow;
        }

        return $data;
    }

    public function title(): string
    {
        return 'Điểm danh';
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 20]; // Cột tên học viên

        // Các cột ngày điểm danh và ghi chú
        $columnIndex = 2; // Bắt đầu từ cột B
        foreach ($this->attendanceDates as $date) {
            // Cột điểm danh (hẹp)
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $widths[$column] = 8; // Cột ngày hẹp
            $columnIndex++;

            // Cột ghi chú (rộng hơn)
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $widths[$column] = 25; // Cột ghi chú rộng hơn
            $columnIndex++;
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
        $lastRow = 1 + $this->enrollments->count();

        $baseStyles = [
            // Header row (hàng đầu tiên)
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],

            // All cells
            "A1:{$lastColumn}{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],

            // Student names column (cột A)
            "A2:A{$lastRow}" => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ]
            ],

            // Attendance data columns (center align for attendance, left align for notes)
            "B2:{$lastColumn}{$lastRow}" => [
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]
        ];

        // Thêm styles riêng cho từng loại cột
        $styles = $baseStyles;

        // Style cho các cột điểm danh (center align)
        $columnIndex = 2; // Bắt đầu từ cột B
        foreach ($this->attendanceDates as $date) {
            $attendanceColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $styles["{$attendanceColumn}2:{$attendanceColumn}{$lastRow}"] = [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ];
            $columnIndex++;

            // Cột ghi chú (left align)
            $notesColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $styles["{$notesColumn}2:{$notesColumn}{$lastRow}"] = [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ];
            $columnIndex++;
        }

        return $styles;
    }
}
