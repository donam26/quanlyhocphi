<?php

namespace App\Services;

use App\Models\CourseItem;
use App\Models\Student;
use App\Models\Enrollment;
use App\Enums\EnrollmentStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ImportService
{
    public function importStudentsFromExcel(UploadedFile $file, int $courseItemId, float $discountPercentage = 0)
    {
        // Kiểm tra khóa học tồn tại
        $courseItem = CourseItem::find($courseItemId);
        if (!$courseItem) {
            throw new \Exception('Khóa học không tồn tại');
        }
        
        $rows = Excel::toArray([], $file)[0];
        $header = array_shift($rows); // Lấy header
        
        // Map header vào các key
        $mappedHeader = [];
        foreach($header as $index => $column) {
            $mappedHeader[$column] = $index;
        }
        
        DB::beginTransaction();
        
        try {
            $importedCount = 0;
            
            foreach ($rows as $row) {
                // Kiểm tra dòng có đủ thông tin cần thiết
                if (!isset($row[$mappedHeader['ho_ten']]) || !isset($row[$mappedHeader['so_dien_thoai']])) {
                    continue;
                }
                
                // Tạo dữ liệu từ row
                $data = [
                    'full_name' => $row[$mappedHeader['ho_ten']],
                    'phone' => $row[$mappedHeader['so_dien_thoai']],
                    'email' => isset($mappedHeader['email']) ? $row[$mappedHeader['email']] : null,
                    'date_of_birth' => isset($mappedHeader['ngay_sinh']) ? 
                        Carbon::createFromFormat('d/m/Y', $row[$mappedHeader['ngay_sinh']]) : null,
                    'gender' => isset($mappedHeader['gioi_tinh']) ? 
                        $this->mapGender($row[$mappedHeader['gioi_tinh']]) : null,
                    'address' => isset($mappedHeader['dia_chi']) ? $row[$mappedHeader['dia_chi']] : null,
                    'current_workplace' => isset($mappedHeader['noi_cong_tac']) ? $row[$mappedHeader['noi_cong_tac']] : null,
                    'accounting_experience_years' => isset($mappedHeader['kinh_nghiem']) ? $row[$mappedHeader['kinh_nghiem']] : null,
                    'notes' => isset($mappedHeader['ghi_chu']) ? $row[$mappedHeader['ghi_chu']] : null,
                ];
                
                // Tìm hoặc tạo học viên mới
                $student = Student::firstOrCreate(
                    ['phone' => $data['phone']],
                    $data
                );
                
                // Tính học phí
                $finalFee = $courseItem->fee;
                $discountAmount = 0;
                
                if ($discountPercentage > 0) {
                    $discountAmount = $finalFee * ($discountPercentage / 100);
                    $finalFee = $finalFee - $discountAmount;
                }
                
                // Kiểm tra xem học viên đã đăng ký khóa học này chưa
                $existingEnrollment = Enrollment::where('student_id', $student->id)
                                              ->where('course_item_id', $courseItemId)
                                              ->first();
                
                // Nếu chưa đăng ký, tạo đăng ký mới
                if (!$existingEnrollment) {
                    Enrollment::create([
                        'student_id' => $student->id,
                        'course_item_id' => $courseItemId,
                        'enrollment_date' => now(),
                        'status' => EnrollmentStatus::ACTIVE,
                        'discount_percentage' => $discountPercentage,
                        'discount_amount' => $discountAmount,
                        'final_fee' => $finalFee,
                        'notes' => 'Đăng ký qua import Excel'
                    ]);
                    
                    $importedCount++;
                }
            }
            
            DB::commit();
            return [
                'success' => true,
                'message' => 'Đã import thành công ' . $importedCount . ' học viên.',
                'imported_count' => $importedCount
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function exportStudentTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Đặt tiêu đề cột
        $sheet->setCellValue('A1', 'ho_ten');
        $sheet->setCellValue('B1', 'so_dien_thoai');
        $sheet->setCellValue('C1', 'email');
        $sheet->setCellValue('D1', 'ngay_sinh');
        $sheet->setCellValue('E1', 'gioi_tinh');
        $sheet->setCellValue('F1', 'dia_chi');
        $sheet->setCellValue('G1', 'noi_cong_tac');
        $sheet->setCellValue('H1', 'kinh_nghiem');
        $sheet->setCellValue('I1', 'ghi_chu');
        
        // Thêm dữ liệu mẫu dòng đầu tiên
        $sheet->setCellValue('A2', 'Nguyễn Văn A');
        $sheet->setCellValue('B2', '0901234567');
        $sheet->setCellValue('C2', 'nguyenvana@example.com');
        $sheet->setCellValue('D2', '01/01/1990');
        $sheet->setCellValue('E2', 'nam');
        $sheet->setCellValue('F2', 'Hà Nội');
        $sheet->setCellValue('G2', 'Công ty ABC');
        $sheet->setCellValue('H2', '5');
        $sheet->setCellValue('I2', 'Học viên VIP');
        
        // Thêm dữ liệu mẫu dòng thứ hai
        $sheet->setCellValue('A3', 'Trần Thị B');
        $sheet->setCellValue('B3', '0909876543');
        $sheet->setCellValue('C3', 'tranthib@example.com');
        $sheet->setCellValue('D3', '15/05/1995');
        $sheet->setCellValue('E3', 'nữ');
        $sheet->setCellValue('F3', 'TP. Hồ Chí Minh');
        $sheet->setCellValue('G3', 'Công ty XYZ');
        $sheet->setCellValue('H3', '3');
        $sheet->setCellValue('I3', 'Học viên mới');
        
        // Định dạng tiêu đề
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDDDDD');
        
        // Tự động điều chỉnh độ rộng cột
        foreach(range('A','I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Tạo đối tượng Writer
        $writer = new Xlsx($spreadsheet);
        
        $filename = 'template_import_hoc_vien.xlsx';
        $tempFilePath = storage_path('app/public/' . $filename);
        
        // Lưu file
        $writer->save($tempFilePath);
        
        return $tempFilePath;
    }
    
    /**
     * Chuyển đổi giới tính từ text sang giá trị trong DB
     */
    private function mapGender($gender)
    {
        $gender = strtolower(trim($gender));
        
        if (in_array($gender, ['nam', 'male', 'boy', 'm'])) {
            return 'male';
        }
        
        if (in_array($gender, ['nữ', 'nu', 'female', 'girl', 'f'])) {
            return 'female';
        }
        
        return 'other';
    }
} 