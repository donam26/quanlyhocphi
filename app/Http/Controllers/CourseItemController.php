<?php

namespace App\Http\Controllers;

use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\LearningPathProgress;
use Illuminate\Http\Request;
use App\Imports\StudentImport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Log;

class CourseItemController extends Controller
{
    /**
     * Hiển thị danh sách cây khóa học
     */
    public function index()
    {
        // Lấy các ngành học (cấp 1)
        $rootItems = CourseItem::whereNull('parent_id')
                            ->where('active', true)
                            ->orderBy('order_index')
                            ->get();
        
        return view('course-items.index', compact('rootItems'));
    }

    /**
     * Hiển thị form tạo mới item
     */
    public function create(Request $request)
    {
        // Nếu đang tạo con của item khác, lấy item cha
        $parentId = $request->query('parent_id');
        $parentItem = null;
        
        if ($parentId) {
            $parentItem = CourseItem::findOrFail($parentId);
        }
        
        // Lấy danh sách các item có thể làm cha (để hiển thị dropdown)
        $possibleParents = CourseItem::where('is_leaf', false)
                                ->where('active', true)
                                ->orderBy('level')
                                ->orderBy('name')
                                ->get();
        
        return view('course-items.create', compact('parentItem', 'possibleParents'));
    }

    /**
     * Lưu item mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:course_items,id',
            'fee' => 'nullable|numeric|min:0',
            'has_online' => 'required|boolean',
            'has_offline' => 'required|boolean',
            'active' => 'required|boolean',
            'is_leaf' => 'required|boolean',
        ]);
        
        // Xác định level dựa trên parent_id
        $level = 1; // Mặc định là cấp cao nhất
        $isLeaf = false;
        
        if ($request->parent_id) {
            $parentItem = CourseItem::findOrFail($request->parent_id);
            $level = $parentItem->level + 1;
            
            // Nếu có giá tiền, đánh dấu là nút lá
            if ($request->fee > 0) {
                $isLeaf = true;
            }
        }
        
        // Lấy order_index cao nhất trong cùng cấp và parent
        $maxOrder = CourseItem::where('level', $level)
                        ->when($request->parent_id, function($query) use ($request) {
                            return $query->where('parent_id', $request->parent_id);
                        })
                        ->max('order_index') ?? 0;
        
        // Nếu không phải nút lá, đảm bảo fee = 0
        $fee = $request->is_leaf ? ($request->fee ?? 0) : 0;
        
        $courseItem = CourseItem::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'fee' => $fee,
            'level' => $level,
            'is_leaf' => $request->is_leaf,
            'has_online' => $request->has_online,
            'has_offline' => $request->has_offline,
            'order_index' => $maxOrder + 1,
            'active' => $request->active,
        ]);
        
        // Nếu request từ modal trong trang tree, chuyển hướng về trang tree với tham số newly_added_id
        if ($request->ajax() || $request->header('X-Requested-With') == 'XMLHttpRequest' || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('course-items.tree', ['newly_added_id' => $courseItem->id])
            ]);
        }
        
        if ($request->parent_id) {
            return redirect()->route('course-items.tree', ['newly_added_id' => $courseItem->id])
                    ->with('success', 'Đã thêm thành công khóa học con mới!');
        }
        
        return redirect()->route('course-items.tree', ['newly_added_id' => $courseItem->id])
                ->with('success', 'Đã thêm thành công ngành học mới!');
    }

    /**
     * Hiển thị chi tiết và cây con của một item
     */
    public function show($id)
    {
        $courseItem = CourseItem::with(['children' => function($query) {
                            $query->where('active', true)->orderBy('order_index');
                        }, 'learningPaths' => function($query) {
                            $query->orderBy('order');
                        }])->findOrFail($id);
        
        // Lấy đường dẫn từ gốc đến item này
        $breadcrumbs = $courseItem->ancestors()->push($courseItem);
        
        return view('course-items.show', compact('courseItem', 'breadcrumbs'));
    }

    /**
     * Hiển thị form chỉnh sửa
     */
    public function edit($id)
    {
        $courseItem = CourseItem::findOrFail($id);
        
        // Lấy danh sách các item có thể làm cha (để hiển thị dropdown)
        $possibleParents = CourseItem::where('is_leaf', false)
                                ->where('id', '!=', $id) // Không thể là cha của chính nó
                                ->where('active', true)
                                ->orderBy('level')
                                ->orderBy('name')
                                ->get();
        
        return view('course-items.edit', compact('courseItem', 'possibleParents'));
    }

    /**
     * Cập nhật item
     */
    public function update(Request $request, $id)
    {
        $validator = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:course_items,id',
            'fee' => 'nullable|numeric|min:0',
            'has_online' => 'required|boolean',
            'has_offline' => 'required|boolean',
            'active' => 'required|boolean',
            'is_leaf' => 'required|boolean',
        ]);
        
        $courseItem = CourseItem::findOrFail($id);
        
        // Kiểm tra không cho phép item là cha của chính nó hoặc con của nó
        if ($request->parent_id == $id) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => ['parent_id' => 'Không thể chọn chính nó làm cha']], 422);
            }
            return back()->withErrors(['parent_id' => 'Không thể chọn chính nó làm cha']);
        }
        
        // Kiểm tra không cho phép chọn con làm cha
        $descendants = $courseItem->descendants()->pluck('id')->toArray();
        if (in_array($request->parent_id, $descendants)) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => ['parent_id' => 'Không thể chọn con làm cha']], 422);
            }
            return back()->withErrors(['parent_id' => 'Không thể chọn con làm cha']);
        }
        
        // Xác định level dựa trên parent_id
        $level = 1; // Mặc định là cấp cao nhất
        $isLeaf = $courseItem->is_leaf; // Giữ nguyên trạng thái leaf
        
        if ($request->parent_id) {
            $parentItem = CourseItem::findOrFail($request->parent_id);
            $level = $parentItem->level + 1;
            
            // Nếu có giá tiền, đánh dấu là nút lá
            if ($request->fee > 0) {
                $isLeaf = true;
            }
        }
        
        // Nếu không phải nút lá, đảm bảo fee = 0
        $fee = $request->is_leaf ? ($request->fee ?? 0) : 0;
        
        $courseItem->update([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'fee' => $fee,
            'level' => $level,
            'is_leaf' => $request->is_leaf,
            'has_online' => $request->has_online,
            'has_offline' => $request->has_offline,
            'active' => $request->active,
        ]);

        // Nếu là AJAX request, trả về JSON response
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật thành công!',
                'course_item' => $courseItem
            ]);
        }
        
        // Nếu không phải AJAX request, chuyển hướng như trước
        if ($request->parent_id) {
            return redirect()->route('course-items.show', $request->parent_id)
                    ->with('success', 'Đã cập nhật thành công!');
        }
        
        return redirect()->route('course-items.index')
                ->with('success', 'Đã cập nhật thành công!');
    }

    /**
     * Xóa item
     */
    public function destroy($id)
    {
        $courseItem = CourseItem::findOrFail($id);
        
        // Lấy ID của parent để redirect sau khi xóa
        $parentId = $courseItem->parent_id;
        
        // Xóa đệ quy tất cả các khóa con và lớp học liên quan
        $this->deleteRecursively($courseItem);
        
        if ($parentId) {
            return redirect()->route('course-items.tree', ['newly_added_id' => $parentId])
                    ->with('success', 'Đã xóa thành công!');
        }
        
        return redirect()->route('course-items.tree')
                ->with('success', 'Đã xóa thành công!');
    }
    
    /**
     * Xóa đệ quy một item và tất cả con của nó
     */
    private function deleteRecursively($courseItem)
    {
        // Lấy tất cả các con trực tiếp
        $children = $courseItem->children;
        
        // Xóa đệ quy từng con
        foreach ($children as $child) {
            $this->deleteRecursively($child);
        }
        
        // Xóa các lộ trình học tập và tiến độ liên quan
        $learningPaths = LearningPath::where('course_item_id', $courseItem->id)->get();
        foreach ($learningPaths as $path) {
            LearningPathProgress::where('learning_path_id', $path->id)->delete();
            $path->delete();
        }
        
        // Xóa các ghi danh liên quan đến khóa học này nếu là nút lá
        if ($courseItem->is_leaf) {
            $enrollments = \App\Models\Enrollment::where('course_item_id', $courseItem->id)->get();
            foreach ($enrollments as $enrollment) {
                // Xóa các thanh toán liên quan
                $enrollment->payments()->delete();
                
                // Xóa các điểm danh liên quan
                $enrollment->attendances()->delete();
                
                // Xóa tiến độ lộ trình liên quan
                $enrollment->learningPathProgress()->delete();
                
                // Xóa ghi danh
                $enrollment->delete();
            }
        }
        
        // Xóa item hiện tại
        $courseItem->delete();
        
        return true;
    }

    /**
     * Cập nhật thứ tự hiển thị
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:course_items,id',
            'items.*.order' => 'required|integer|min:0',
        ]);
        
        foreach ($request->items as $item) {
            CourseItem::where('id', $item['id'])->update(['order_index' => $item['order']]);
        }
        
        return response()->json(['success' => true]);
    }

    /**
     * Bật/tắt trạng thái hoạt động
     */
    public function toggleActive($id)
    {
        $courseItem = CourseItem::findOrFail($id);
        $courseItem->active = !$courseItem->active;
        $courseItem->save();
        
        return back()->with('success', 'Đã cập nhật trạng thái hoạt động!');
    }
    
    /**
     * Hiển thị cấu trúc cây khóa học
     */
    public function tree()
    {
        // Lấy tất cả các ngành học (cấp 1)
        $rootItems = CourseItem::whereNull('parent_id')
                            ->where('active', true)
                            ->orderBy('order_index')
                            ->with(['children' => function($query) {
                                $query->where('active', true)->orderBy('order_index');
                            }])
                            ->get();
        
        return view('course-items.tree', compact('rootItems'));
    }

    /**
     * Hiển thị danh sách học viên theo ngành học
     */
    public function showStudents($id)
    {
        $courseItem = CourseItem::findOrFail($id);
        
        // Lấy tất cả ID của khóa học con thuộc ngành này
        $courseItemIds = [$id];
        $this->getAllChildrenIds($courseItem, $courseItemIds);
        
        // Lấy tất cả học viên đã đăng ký các khóa học này
        $enrollments = Enrollment::whereIn('course_item_id', $courseItemIds)
            ->with(['student', 'courseItem', 'payments' => function($query) {
                $query->orderBy('payment_date', 'desc');
            }])
            ->get();
        
        $students = $enrollments->map(function($enrollment) {
            // Lấy thông tin thanh toán mới nhất
            $latestPayment = $enrollment->payments->where('status', 'confirmed')->first();
            
            // Xác định trạng thái thanh toán
            $paymentStatus = $enrollment->getIsFullyPaidAttribute() ? 'Đã đóng đủ' : 'Chưa đóng đủ';
            
            // Xác định phương thức thanh toán và người thu
            $paymentMethod = 'Chưa thanh toán';
            $collector = 'N/A';
            $paymentNotes = [];
            
            // Lấy tất cả ghi chú từ các khoản thanh toán
            foreach ($enrollment->payments as $payment) {
                if ($payment->notes) {
                    $paymentNotes[] = [
                        'date' => $payment->payment_date->format('d/m/Y'),
                        'amount' => $payment->amount,
                        'method' => $this->getPaymentMethodText($payment->payment_method),
                        'status' => $payment->status,
                        'notes' => $payment->notes
                    ];
                }
            }
            
            if ($latestPayment) {
                // Nếu có thanh toán, lấy thông tin phương thức
                $paymentMethod = $this->getPaymentMethodText($latestPayment->payment_method);
                
                // Xác định người thu tiền
                $collector = $latestPayment->payment_method == 'sepay' ? 'SEPAY' : 
                           ($latestPayment->notes && str_contains($latestPayment->notes, 'Người thu:') ? 
                             substr($latestPayment->notes, strpos($latestPayment->notes, 'Người thu:') + 11) : 'N/A');
            }
            
            return [
                'student' => $enrollment->student,
                'course_item' => $enrollment->courseItem ? $enrollment->courseItem->name : 'N/A',
                'enrollment_date' => $enrollment->enrollment_date,
                'status' => $enrollment->status,
                'enrollment_id' => $enrollment->id,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'collector' => $collector,
                'final_fee' => $enrollment->final_fee,
                'paid_amount' => $enrollment->getPaidAmountAttribute(),
                'remaining_amount' => $enrollment->getRemainingAmountAttribute(),
                'payment_notes' => $paymentNotes,
                'has_notes' => count($paymentNotes) > 0
            ];
        });
        
        return view('course-items.students', [
            'courseItem' => $courseItem,
            'students' => $students,
            'enrollmentCount' => $enrollments->count(),
            'studentCount' => $enrollments->pluck('student_id')->unique()->count()
        ]);
    }
    
    /**
     * Chuyển đổi mã phương thức thanh toán thành text hiển thị
     */
    private function getPaymentMethodText($method)
    {
        switch ($method) {
            case 'cash':
                return 'Tiền mặt';
            case 'bank_transfer':
                return 'Chuyển khoản';
            case 'card':
                return 'Thẻ tín dụng';
            case 'qr_code':
                return 'Quét QR';
            case 'sepay':
                return 'SEPAY';
            default:
                return 'Không xác định';
        }
    }

    /**
     * Import học viên từ file Excel
     */
    public function importStudents(Request $request, $id)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
            'discount_percentage' => 'nullable|numeric|min:0|max:100'
        ]);
        
        // Lấy thông tin khóa học
        $courseItem = CourseItem::findOrFail($id);
        
        // Lấy phần trăm giảm giá (nếu có)
        $discountPercentage = $request->discount_percentage ?? 0;
        
        try {
            // Import từ Excel
            Excel::import(new StudentImport($id, $discountPercentage), $request->file('excel_file'));
            
            return redirect()->route('course-items.students', $id)
                    ->with('success', 'Đã import học viên thành công!');
        } catch (\Exception $e) {
            return back()->withErrors(['excel_file' => 'Có lỗi khi import: ' . $e->getMessage()]);
        }
    }

    /**
     * Xuất file template Excel mẫu cho import học viên
     */
    public function exportTemplate()
    {
        try {
            // Log để debug
            Log::info('Đang tạo template Excel');
            
            // Tạo file Excel mới
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
            
            // Log kết quả
            Log::info('Đã tạo xong template Excel, đang gửi về client');
            
            // Tạo đối tượng Writer
            $writer = new Xlsx($spreadsheet);
            
            // Đặt header để tải xuống
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="template_import_hoc_vien.xlsx"');
            header('Cache-Control: max-age=0');
            
            // Gửi file
            $writer->save('php://output');
            exit;
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo template Excel: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return back()->withErrors(['download' => 'Có lỗi khi tạo template Excel: ' . $e->getMessage()]);
        }
    }

    /**
     * Lấy tất cả ID của các khóa học con
     */
    private function getAllChildrenIds($courseItem, &$ids)
    {
        foreach ($courseItem->children as $child) {
            $ids[] = $child->id;
            if ($child->children->count() > 0) {
                $this->getAllChildrenIds($child, $ids);
            }
        }
    }
}