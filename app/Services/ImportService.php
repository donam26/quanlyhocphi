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
                // Kiểm tra dòng có đủ thông tin cần thiết (Họ, Tên, SĐT)
                $firstName = isset($mappedHeader['Họ']) ? trim($row[$mappedHeader['Họ']]) : '';
                $lastName = isset($mappedHeader['Tên']) ? trim($row[$mappedHeader['Tên']]) : '';
                $phone = isset($mappedHeader['Số điện thoại']) ? trim($row[$mappedHeader['Số điện thoại']]) : '';
                
                if (empty($firstName) || empty($lastName) || empty($phone)) {
                    continue; // Bỏ qua nếu thiếu thông tin bắt buộc
                }
                
                // Xử lý ngày sinh
                $dateOfBirth = null;
                if (isset($mappedHeader['Ngày sinh']) && !empty($row[$mappedHeader['Ngày sinh']])) {
                    try {
                        $dateOfBirth = Carbon::createFromFormat('d/m/Y', $row[$mappedHeader['Ngày sinh']]);
                    } catch (\Exception $e) {
                        $dateOfBirth = null; // Bỏ qua nếu định dạng ngày không hợp lệ
                    }
                }
                
                // Xử lý tỉnh thành (tìm kiếm theo tên)
                $provinceId = null;
                if (isset($mappedHeader['Tỉnh/Thành phố']) && !empty($row[$mappedHeader['Tỉnh/Thành phố']])) {
                    $provinceName = trim($row[$mappedHeader['Tỉnh/Thành phố']]);
                    $province = \App\Models\Province::where('name', 'like', "%{$provinceName}%")->first();
                    if ($province) {
                        $provinceId = $province->id;
                    }
                }
                
                // Tạo dữ liệu từ row
                $data = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $phone,
                    'email' => isset($mappedHeader['Email']) ? trim($row[$mappedHeader['Email']]) : null,
                    'date_of_birth' => $dateOfBirth,
                    'place_of_birth' => isset($mappedHeader['Nơi sinh']) ? trim($row[$mappedHeader['Nơi sinh']]) : null,
                    'nation' => isset($mappedHeader['Dân tộc']) ? trim($row[$mappedHeader['Dân tộc']]) : null,
                    'gender' => isset($mappedHeader['Giới tính']) ? 
                        $this->mapGender($row[$mappedHeader['Giới tính']]) : null,
                    'province_id' => $provinceId,
                    'address' => isset($mappedHeader['Địa chỉ cụ thể']) ? trim($row[$mappedHeader['Địa chỉ cụ thể']]) : null,
                    'current_workplace' => isset($mappedHeader['Nơi công tác']) ? trim($row[$mappedHeader['Nơi công tác']]) : null,
                    'accounting_experience_years' => isset($mappedHeader['Kinh nghiệm kế toán']) ? 
                        (int)$row[$mappedHeader['Kinh nghiệm kế toán']] : null,
                    'notes' => isset($mappedHeader['Ghi chú']) ? trim($row[$mappedHeader['Ghi chú']]) : null,
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
        if (empty($gender)) {
            return null;
        }
        
        $gender = strtolower(trim($gender));
        
        if (in_array($gender, ['nam', 'male', 'boy', 'm', 'nam giới'])) {
            return 'male';
        }
        
        if (in_array($gender, ['nữ', 'nu', 'female', 'girl', 'f', 'nữ giới'])) {
            return 'female';
        }
        
        return 'other';
    }
} 