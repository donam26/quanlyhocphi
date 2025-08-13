<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ImportService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class StudentUpdateController extends Controller
{
    protected $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Hiển thị form import để cập nhật thông tin học viên
     */
    public function showUpdateForm()
    {
        return view('students.update-import');
    }

    /**
     * Xử lý import file để cập nhật thông tin học viên
     */
    public function updateFromExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // Max 10MB
            'update_mode' => 'required|in:update_only,create_and_update'
        ]);

        try {
            $result = $this->importService->updateStudentsFromExcel(
                $request->file('file'),
                $request->input('update_mode', 'update_only')
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'updated_count' => $result['updated_count'],
                        'skipped_count' => $result['skipped_count'],
                        'errors' => $result['errors']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'errors' => $result['errors']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật học viên từ Excel: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xử lý file. Vui lòng thử lại.',
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    /**
     * Tải template Excel để cập nhật thông tin học viên
     */
    public function downloadUpdateTemplate()
    {
        $templatePath = storage_path('app/templates/student_update_template.xlsx');
        
        if (!file_exists($templatePath)) {
            // Tạo template nếu chưa có
            $this->createUpdateTemplate($templatePath);
        }

        return response()->download($templatePath, 'Mau_cap_nhat_thong_tin_hoc_vien.xlsx');
    }

    /**
     * Tạo template Excel để cập nhật thông tin học viên
     */
    private function createUpdateTemplate($templatePath)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = [
            'A1' => 'so_dien_thoai',
            'B1' => 'ho_ten', 
            'C1' => 'email',
            'D1' => 'ngay_sinh',
            'E1' => 'gioi_tinh',
            'F1' => 'dia_chi',
            'G1' => 'noi_sinh',
            'H1' => 'dan_toc',
            'I1' => 'tinh_thanh_pho',
            'J1' => 'noi_cong_tac',
            'K1' => 'kinh_nghiem_ke_toan',
            'L1' => 'bang_cap',
            'M1' => 'chuyen_mon_cong_tac',
            'N1' => 'ho_so_ban_cung',
            'O1' => 'ghi_chu'
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        // Thêm dòng hướng dẫn
        $instructions = [
            'A2' => '0987654321',
            'B2' => 'Nguyễn Văn A',
            'C2' => 'email@example.com',
            'D2' => '01/01/1990',
            'E2' => 'Nam',
            'F2' => '123 Đường ABC',
            'G2' => 'Hà Nội',
            'H2' => 'Kinh',
            'I2' => 'Hà Nội',
            'J2' => 'Công ty XYZ',
            'K2' => '5',
            'L2' => 'Đại học',
            'M2' => 'Kế toán',
            'N2' => 'Đã nộp',
            'O2' => 'Ghi chú'
        ];

        foreach ($instructions as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getFont()->setItalic(true);
        }

        // Thêm ghi chú
        $sheet->setCellValue('A4', 'GHI CHÚ:');
        $sheet->setCellValue('A5', '- Số điện thoại là BẮT BUỘC để xác định học viên');
        $sheet->setCellValue('A6', '- Chỉ điền thông tin cần cập nhật, để trống nếu không muốn thay đổi');
        $sheet->setCellValue('A7', '- Ngày sinh định dạng: DD/MM/YYYY');
        $sheet->setCellValue('A8', '- Giới tính: Nam/Nữ');
        $sheet->setCellValue('A9', '- Bằng cấp: Đại học/Thạc sĩ/Cao đẳng/Trung cấp');
        $sheet->setCellValue('A10', '- Hồ sơ bản cứng: Đã nộp/Chưa nộp');

        // Auto-size columns
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Tạo thư mục nếu chưa có
        $directory = dirname($templatePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($templatePath);
    }

    /**
     * Xem preview dữ liệu trước khi import
     */
    public function previewUpdateData(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            $file = $request->file('file');
            $data = Excel::toArray([], $file)[0];
            
            // Lấy header và 5 dòng đầu để preview
            $preview = array_slice($data, 0, 6);
            
            return response()->json([
                'success' => true,
                'preview' => $preview,
                'total_rows' => count($data) - 1 // Trừ header
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể đọc file: ' . $e->getMessage()
            ], 400);
        }
    }
}
