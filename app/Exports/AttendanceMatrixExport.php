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
    protected $selectedColumns;
    protected $filters;
    protected $columnMappings;

    public function __construct(CourseItem $courseItem, $startDate = null, $endDate = null, $selectedColumns = [], $filters = [])
    {
        $this->courseItem = $courseItem->load('children.children.children');
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->selectedColumns = !empty($selectedColumns) ? $selectedColumns : ['student_last_name', 'student_first_name', 'student_phone'];
        $this->filters = $filters;
        $this->initializeColumnMappings();
        $this->prepareData();
    }

    protected function initializeColumnMappings()
    {
        // Đồng bộ với AttendanceExport để đảm bảo tính nhất quán
        $this->columnMappings = [
            // Thông tin học viên cơ bản
            'student_last_name' => 'Họ',
            'student_first_name' => 'Tên',
            'student_name' => 'Họ và tên', // Backward compatibility
            'student_phone' => 'Số điện thoại',
            'student_email' => 'Email',
            'citizen_id' => 'Số CCCD/CMND',
            'student_date_of_birth' => 'Ngày sinh',
            'date_of_birth' => 'Ngày sinh', // Backward compatibility
            'student_gender' => 'Giới tính',
            'gender' => 'Giới tính', // Backward compatibility

            // Thông tin địa chỉ
            'student_address' => 'Địa chỉ học viên',
            'student_workplace' => 'Nơi công tác',
            'current_workplace' => 'Nơi công tác hiện tại',
            'student_province' => 'Tỉnh/Thành phố',
            'province' => 'Tỉnh/Thành phố', // Backward compatibility
            'place_of_birth_province' => 'Nơi sinh',
            'ethnicity' => 'Dân tộc',
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
            'course_name' => 'Tên khóa học',

            // Thông tin thanh toán
            'total_paid' => 'Đã thanh toán',
            'remaining_amount' => 'Còn lại',
            'payment_status' => 'Trạng thái thanh toán'
        ];
    }

    private function prepareData()
    {
        // Lấy danh sách học viên đã ghi danh (bao gồm cả khóa con nếu có)
        $courseItemIds = $this->getAllCourseItemIds($this->courseItem);

        $enrollmentsQuery = Enrollment::whereIn('course_item_id', $courseItemIds)
            ->with([
                'student.province',
                'student.placeOfBirthProvince',
                'student.ethnicity',
                'courseItem',
                'payments'
            ]);

        // Áp dụng bộ lọc
        if (!empty($this->filters['enrollmentStatus'])) {
            $enrollmentsQuery->where('status', $this->filters['enrollmentStatus']);
        } else {
            // Mặc định chỉ lấy học viên đang học
            $enrollmentsQuery->where('status', EnrollmentStatus::ACTIVE->value);
        }

        if (!empty($this->filters['paymentStatus'])) {
            $enrollmentsQuery->where('payment_status', $this->filters['paymentStatus']);
        }

        $this->enrollments = $enrollmentsQuery->orderBy('id')->get();

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

        // Tính tổng số cột (số cột thông tin học viên + số ngày * 2 vì có cột ghi chú)
        $this->totalColumns = count($this->selectedColumns) + ($this->attendanceDates->count() * 2);
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

    public function getPreviewData()
    {
        $data = $this->array(); // Reuse the array generation logic

        if (empty($data)) {
            return [
                'headers' => [],
                'rows' => [],
                'course_name' => $this->courseItem->name,
            ];
        }

        $headers = array_shift($data); // Extract header row
        $rows = $data;

        return [
            'headers' => $headers,
            'rows' => $rows,
            'course_name' => $this->courseItem->name,
        ];
    }

    public function array(): array
    {
        $data = [];

        // Row 1: Header
        $headerRow = [];
        foreach ($this->selectedColumns as $column) {
            $headerRow[] = $this->columnMappings[$column] ?? $column;
        }
        foreach ($this->attendanceDates as $date) {
            $formattedDate = $date instanceof \Carbon\Carbon
                ? $date->format('d/m/Y')
                : \Carbon\Carbon::parse($date)->format('d/m/Y');
            $headerRow[] = $formattedDate;
            $headerRow[] = 'Ghi chú ' . $formattedDate;
        }
        $data[] = $headerRow;

        // Rows 2+: Dữ liệu học viên
        foreach ($this->enrollments as $enrollment) {
            $studentRow = [];
            // Thêm các cột thông tin học viên đã chọn
            foreach ($this->selectedColumns as $column) {
                $studentRow[] = $this->getStudentColumnValue($enrollment, $column);
            }

            // Thêm dữ liệu điểm danh
            foreach ($this->attendanceDates as $date) {
                $attendance = $this->attendanceMap[$enrollment->id][$date] ?? null;
                if ($attendance && $attendance->status === 'present') {
                    $studentRow[] = 'x';
                } else {
                    $studentRow[] = '';
                }
                $studentRow[] = $attendance->notes ?? '';
            }

            $data[] = $studentRow;
        }

        return $data;
    }

    private function getStudentColumnValue(Enrollment $enrollment, $column)
    {
        $student = $enrollment->student;

        switch ($column) {
            // Thông tin học viên cơ bản
            case 'student_last_name':
                return $student->first_name ?? '';
            case 'student_first_name':
                return $student->last_name ?? '';
            case 'student_name':
                return trim($student->first_name . ' ' . $student->last_name);
            case 'student_phone':
                return "'" . $student->phone;
            case 'student_email':
                return $student->email;
            case 'citizen_id':
                return "'" . $student->citizen_id;
            case 'student_date_of_birth':
            case 'date_of_birth':
                return $student->date_of_birth ? Carbon::parse($student->date_of_birth)->format('d/m/Y') : '';
            case 'student_gender':
            case 'gender':
                return $this->formatGender($student->gender);

            // Thông tin địa chỉ
            case 'student_address':
                return $student->address;
            case 'student_workplace':
            case 'current_workplace':
                return $student->current_workplace;
            case 'student_province':
            case 'province':
                return $student->province ? $student->province->name : '';
            case 'place_of_birth_province':
                return $student->placeOfBirthProvince ? $student->placeOfBirthProvince->name : '';
            case 'ethnicity':
                // Workaround: Query ethnicity directly since relationship has issues
                $ethnicity = $student->ethnicity_id ? \App\Models\Ethnicity::find($student->ethnicity_id) : null;
                return $ethnicity ? $ethnicity->name : '';
            case 'education_level':
                return $this->formatEducationLevel($student->education_level);
            case 'training_specialization':
                return $student->training_specialization;
            case 'accounting_experience_years':
                return $student->accounting_experience_years;

            // Thông tin công ty
            case 'company_name':
                return $student->company_name;
            case 'company_address':
                return $student->company_address;
            case 'tax_code':
                return $student->tax_code;
            case 'invoice_email':
                return $student->invoice_email;

            // Thông tin ghi danh
            case 'enrollment_date':
                return $enrollment->enrollment_date ? $enrollment->enrollment_date->format('d/m/Y') : '';
            case 'enrollment_status':
                return $this->formatEnrollmentStatus($enrollment->status);
            case 'course_name':
                return $enrollment->courseItem->name;

            // Thông tin thanh toán
            case 'total_paid':
                return "'" . number_format($enrollment->getTotalPaidAmount(), 0, ',', '.');
            case 'remaining_amount':
                return "'" . number_format($enrollment->getRemainingAmount(), 0, ',', '.');
            case 'payment_status':
                return $this->getPaymentStatus($enrollment);

            default:
                return '';
        }
    }

    public function title(): string
    {
        return 'Điểm danh';
    }

    public function columnWidths(): array
    {
        $widths = [];

        // Đặt width cho các cột thông tin học viên
        $columnIndex = 1;
        foreach ($this->selectedColumns as $column) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);

            // Đặt width dựa trên loại cột
            switch ($column) {
                case 'student_name':
                case 'student_last_name':
                case 'student_first_name':
                    $widths[$columnLetter] = 20;
                    break;
                case 'student_phone':
                case 'citizen_id':
                    $widths[$columnLetter] = 15;
                    break;
                case 'student_email':
                case 'company_name':
                case 'course_name':
                    $widths[$columnLetter] = 25;
                    break;
                case 'student_province':
                case 'place_of_birth_province':
                case 'ethnicity':
                    $widths[$columnLetter] = 18;
                    break;
                default:
                    $widths[$columnLetter] = 15;
            }
            $columnIndex++;
        }

        // Các cột ngày điểm danh và ghi chú
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
        $columnIndex = count($this->selectedColumns) + 1; // Bắt đầu sau các cột thông tin học viên
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

    /**
     * Helper methods - đồng bộ với AttendanceExport
     */
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
        $statusValue = $status instanceof \App\Enums\EnrollmentStatus ? $status->value : $status;
        switch ($statusValue) {
            case 'waiting':
                return 'Chờ xác nhận';
            case 'active':
                return 'Đang học';
            case 'completed':
                return 'Hoàn thành';
            case 'cancelled':
                return 'Đã hủy';
            default:
                return $statusValue;
        }
    }

    protected function getPaymentStatus($enrollment)
    {
        if (!$enrollment) {
            return 'Không có thông tin';
        }

        $remaining = $enrollment->getRemainingAmount();
        $totalPaid = $enrollment->getTotalPaidAmount();

        if ($enrollment->final_fee == 0) {
            return 'Không có học phí';
        }
        if ($remaining <= 0) {
            return 'Đã thanh toán đủ';
        }
        if ($totalPaid > 0) {
            return 'Thanh toán một phần';
        }
        return 'Chưa thanh toán';
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
                return $level;
        }
    }
}
