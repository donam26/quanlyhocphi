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
use App\Imports\StudentUpdateImport;

class ImportService
{
    /**
     * Import để cập nhật thông tin học viên hiện có
     */
    public function updateStudentsFromExcel(UploadedFile $file, string $updateMode = 'update_only')
    {
        try {
            $import = new StudentUpdateImport($updateMode);
            Excel::import($import, $file);

            $stats = $import->getStats();

            return [
                'success' => true,
                'message' => "Đã cập nhật thành công {$stats['updated_count']} học viên. Bỏ qua {$stats['skipped_count']} học viên.",
                'updated_count' => $stats['updated_count'],
                'skipped_count' => $stats['skipped_count'],
                'errors' => $stats['errors']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
                'updated_count' => 0,
                'skipped_count' => 0,
                'errors' => [$e->getMessage()]
            ];
        }
    }
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
                    'accounting_experience_years' => isset($mappedHeader['Kinh nghiệm kế toán (năm)']) ? 
                        (int)$row[$mappedHeader['Kinh nghiệm kế toán (năm)']] : null,
                    'hard_copy_documents' => isset($mappedHeader['Hồ sơ bản cứng']) ? 
                        $this->mapHardCopyDocuments($row[$mappedHeader['Hồ sơ bản cứng']]) : null,
                    'education_level' => isset($mappedHeader['Bằng cấp']) ? 
                        $this->mapEducationLevel($row[$mappedHeader['Bằng cấp']]) : null,
                    'notes' => isset($mappedHeader['Ghi chú']) ? trim($row[$mappedHeader['Ghi chú']]) : null,
                ];
                
                // Tìm hoặc tạo học viên mới
                $student = Student::firstOrCreate(
                    ['phone' => $data['phone']],
                    $data
                );
                
                // Kiểm tra khóa học phải có học phí > 0
                if (!$courseItem->fee || $courseItem->fee <= 0) {
                    continue; // Bỏ qua học viên này và tiếp tục import những học viên khác
                }
                
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
                    // Xử lý custom_fields cho khóa học đặc biệt
                    $customFields = null;
                    if ($courseItem->is_special && $courseItem->custom_fields) {
                        $customFields = $courseItem->custom_fields;
                    }

                    Enrollment::create([
                        'student_id' => $student->id,
                        'course_item_id' => $courseItemId,
                        'enrollment_date' => now(),
                        'status' => EnrollmentStatus::ACTIVE,
                        'discount_percentage' => $discountPercentage,
                        'discount_amount' => $discountAmount,
                        'final_fee' => $finalFee,
                        'notes' => 'Đăng ký qua import Excel',
                        'custom_fields' => $customFields
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
        
        // Đặt tiêu đề cột theo cấu trúc database hiện tại (đồng bộ với UnifiedStudentImport)
        $headings = [
            'ho', 'ten', 'so_dien_thoai', 'cccd', 'email', 'ngay_sinh',
            'gioi_tinh', 'tinh_hien_tai', 'tinh_noi_sinh',
            'dan_toc', 'quoc_tich', 'noi_cong_tac', 'kinh_nghiem_ke_toan',
            'chuyen_mon_dao_tao', 'ho_so_ban_cung', 'trinh_do_hoc_van',
            'ten_cong_ty', 'ma_so_thue', 'email_hoa_don', 'nguon', 'ghi_chu'
        ];
        $sheet->fromArray($headings, NULL, 'A1');
        
        // Thêm dữ liệu mẫu (format về text để tránh lỗi hiển thị)
        $sampleData = [
            ["'Nguyễn Văn", "'A", "'0901234567", "'123456789012", 'nguyenvana@example.com', "'12/2/1990", 'Nam', 'Hồ Chí Minh', 'Hà Nội', 'Kinh', 'Việt Nam', 'Công ty ABC', "'5", 'Kế toán', 'Đã nộp', 'Đại học', 'Công ty TNHH ABC', "'0123456789", 'ketoan@abc.com', 'facebook', 'Ghi chú mẫu'],
            ["'Trần Thị", "'B", "'0909876543", "'987654321098", 'tranthib@example.com', "'15/5/1995", 'Nữ', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Tày', 'Việt Nam', 'Công ty XYZ', "'3", 'Tài chính', 'Chưa nộp', 'Cao đẳng', 'Công ty CP XYZ', "'9876543210", 'taichinh@xyz.com', 'zalo', 'Ghi chú mẫu 2'],
        ];
        $sheet->fromArray($sampleData, NULL, 'A2');
        
        // Định dạng tiêu đề (cập nhật cho 21 cột)
        $sheet->getStyle('A1:U1')->getFont()->setBold(true);
        $sheet->getStyle('A1:U1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDDDDD');

        // Tự động điều chỉnh độ rộng cột
        foreach(range('A','U') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Thêm note về format dữ liệu (cập nhật theo hướng dẫn mới)
        $sheet->setCellValue('A5', 'HƯỚNG DẪN NHẬP LIỆU:');
        $sheet->setCellValue('A6', '🔴 Các cột BẮT BUỘC: ho, ten');
        $sheet->setCellValue('A7', '✅ Các cột khác có thể bỏ trống: so_dien_thoai, email, dia_chi, v.v.');
        $sheet->setCellValue('A8', '📧 Email sẽ được tự động tạo nếu bỏ trống (dạng: ten.ho.random@gmail.com)');
        $sheet->setCellValue('A9', '📅 ngay_sinh: Hỗ trợ nhiều format: 12/2/2002, 12/02/2002, 2/2/2002, 2002-02-12');
        $sheet->setCellValue('A10', '• gioi_tinh: Nam, Nữ hoặc để trống');
        $sheet->setCellValue('A11', '• ho_so_ban_cung: "Đã nộp", "Chưa nộp" hoặc để trống');
        $sheet->setCellValue('A12', '• trinh_do_hoc_van: "Đại học", "Cao đẳng", "Trung cấp", "Thạc sĩ", "VB2"');
        $sheet->setCellValue('A13', '• nguon: "facebook", "zalo", "website", "linkedin", "tiktok", "friends"');
        $sheet->setCellValue('A14', '• Tất cả số điện thoại, CCCD, MST sẽ được format về text để tránh lỗi hiển thị');
        $sheet->getStyle('A5:A14')->getFont()->setBold(true)->setSize(10);
        
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
    
    /**
     * Chuyển đổi hồ sơ bản cứng từ text sang giá trị trong DB
     */
    private function mapHardCopyDocuments($status)
    {
        if (empty($status)) {
            return null;
        }
        
        $status = strtolower(trim($status));
        
        if (in_array($status, ['đã nộp', 'da nop', 'submitted', 'nộp rồi', 'có'])) {
            return 'submitted';
        }
        
        if (in_array($status, ['chưa nộp', 'chua nop', 'not_submitted', 'chưa có', 'chưa', 'không'])) {
            return 'not_submitted';
        }
        
        return null;
    }
    
    /**
     * Chuyển đổi bằng cấp từ text sang giá trị trong DB
     */
    private function mapEducationLevel($level)
    {
        if (empty($level)) {
            return null;
        }
        
        $level = strtolower(trim($level));
        
        if (in_array($level, ['trung cấp', 'trung cap', 'vocational', 'tc'])) {
            return 'vocational';
        }
        
        if (in_array($level, ['cao đẳng', 'cao dang', 'associate', 'cd'])) {
            return 'associate';
        }
        
        if (in_array($level, ['đại học', 'dai hoc', 'bachelor', 'đh', 'dh'])) {
            return 'bachelor';
        }
        
        if (in_array($level, ['thạc sĩ', 'thac si', 'master', 'ths'])) {
            return 'master';
        }
        
        if (in_array($level, ['vb2', 'secondary', 'văn bằng 2', 'van bang 2'])) {
            return 'secondary';
        }
        
        return null;
    }

    /**
     * Import học viên vào danh sách chờ từ file Excel
     */
    public function importStudentsToWaitingList(UploadedFile $file, int $courseItemId, string $notes = null)
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
        
        // Kiểm tra các cột bắt buộc
        if (!isset($mappedHeader['ho_ten']) && (!isset($mappedHeader['Họ']) || !isset($mappedHeader['Tên']))) {
            throw new \Exception('File Excel thiếu cột bắt buộc: ho_ten hoặc (Họ + Tên)');
        }
        if (!isset($mappedHeader['so_dien_thoai']) && !isset($mappedHeader['Số điện thoại'])) {
            throw new \Exception('File Excel thiếu cột bắt buộc: so_dien_thoai hoặc Số điện thoại');
        }
        
        DB::beginTransaction();
        
        try {
            $importedCount = 0;
            
            foreach($rows as $row) {
                // Bỏ qua dòng trống
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Lấy tên và số điện thoại
                if (isset($mappedHeader['ho_ten'])) {
                    $fullName = trim($row[$mappedHeader['ho_ten']]);
                    $nameParts = explode(' ', $fullName);
                    $firstName = array_shift($nameParts);
                    $lastName = implode(' ', $nameParts);
                } else {
                    $firstName = isset($mappedHeader['Họ']) ? trim($row[$mappedHeader['Họ']]) : '';
                    $lastName = isset($mappedHeader['Tên']) ? trim($row[$mappedHeader['Tên']]) : '';
                }
                
                $phone = isset($mappedHeader['so_dien_thoai']) ? 
                    trim($row[$mappedHeader['so_dien_thoai']]) : 
                    (isset($mappedHeader['Số điện thoại']) ? trim($row[$mappedHeader['Số điện thoại']]) : '');
                
                // Kiểm tra dữ liệu bắt buộc
                if (empty($firstName) || empty($phone)) {
                    continue; // Bỏ qua dòng này
                }
                
                // Xử lý ngày sinh
                $dateOfBirth = null;
                if (isset($mappedHeader['ngay_sinh'])) {
                    $dateOfBirth = $this->parseDate($row[$mappedHeader['ngay_sinh']]);
                } elseif (isset($mappedHeader['Ngày sinh'])) {
                    $dateOfBirth = $this->parseDate($row[$mappedHeader['Ngày sinh']]);
                }
                
                // Xử lý province_id
                $provinceId = null;
                if (isset($mappedHeader['Tỉnh/Thành phố']) && !empty(trim($row[$mappedHeader['Tỉnh/Thành phố']]))) {
                    $provinceName = trim($row[$mappedHeader['Tỉnh/Thành phố']]);
                    $province = \App\Models\Province::where('name', 'like', '%' . $provinceName . '%')->first();
                    $provinceId = $province ? $province->id : null;
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
                    'accounting_experience_years' => isset($mappedHeader['Kinh nghiệm kế toán (năm)']) ? 
                        (int)$row[$mappedHeader['Kinh nghiệm kế toán (năm)']] : null,
                    'hard_copy_documents' => isset($mappedHeader['Hồ sơ bản cứng']) ? 
                        $this->mapHardCopyDocuments($row[$mappedHeader['Hồ sơ bản cứng']]) : null,
                    'education_level' => isset($mappedHeader['Bằng cấp']) ? 
                        $this->mapEducationLevel($row[$mappedHeader['Bằng cấp']]) : null,
                    'notes' => isset($mappedHeader['Ghi chú']) ? trim($row[$mappedHeader['Ghi chú']]) : null,
                ];
                
                // Tìm hoặc tạo học viên mới
                $student = Student::firstOrCreate(
                    ['phone' => $data['phone']],
                    $data
                );
                
                // Kiểm tra xem học viên đã có trong danh sách chờ của khóa học này chưa
                $existingEnrollment = Enrollment::where('student_id', $student->id)
                                              ->where('course_item_id', $courseItemId)
                                              ->first();
                
                // Nếu chưa có, tạo đăng ký mới với status WAITING
                if (!$existingEnrollment) {
                    $enrollmentNotes = 'Thêm vào danh sách chờ qua import Excel';
                    if ($notes) {
                        $enrollmentNotes .= '. ' . $notes;
                    }

                    // Xử lý custom_fields cho khóa học đặc biệt
                    $customFields = null;
                    if ($courseItem->is_special && $courseItem->custom_fields) {
                        $customFields = $courseItem->custom_fields;
                    }

                    Enrollment::create([
                        'student_id' => $student->id,
                        'course_item_id' => $courseItemId,
                        'enrollment_date' => now(),
                        'status' => EnrollmentStatus::WAITING,
                        'discount_percentage' => 0,
                        'discount_amount' => 0,
                        'final_fee' => $courseItem->fee ?? 0, // Đặt học phí gốc, sẽ điều chỉnh khi xác nhận
                        'notes' => $enrollmentNotes,
                        'custom_fields' => $customFields
                    ]);

                    $importedCount++;
                }
            }
            
            DB::commit();
            return [
                'success' => true,
                'message' => 'Đã import thành công ' . $importedCount . ' học viên vào danh sách chờ.',
                'imported_count' => $importedCount
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
} 