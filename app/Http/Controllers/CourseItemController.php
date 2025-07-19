<?php

namespace App\Http\Controllers;

use App\Models\CourseItem;
use App\Models\Enrollment;
use Illuminate\Http\Request;

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
        
        // Xóa các ghi danh liên quan đến khóa học này nếu là nút lá
        if ($courseItem->is_leaf) {
            $enrollments = \App\Models\Enrollment::where('course_item_id', $courseItem->id)->get();
            foreach ($enrollments as $enrollment) {
                // Xóa các thanh toán liên quan
                $enrollment->payments()->delete();
                
                // Xóa các điểm danh liên quan
                $enrollment->attendances()->delete();
                
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
            ->with('student', 'courseItem')
            ->get();
        
        $students = $enrollments->map(function($enrollment) {
            return [
                'student' => $enrollment->student,
                'course_item' => $enrollment->courseItem ? $enrollment->courseItem->name : 'N/A',
                'enrollment_date' => $enrollment->enrollment_date,
                'status' => $enrollment->status
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
     * Import học viên từ file Excel/CSV
     */
    public function importStudents(Request $request, $id)
    {
        $courseItem = CourseItem::findOrFail($id);
        
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // Max 10MB
            'auto_enroll' => 'boolean'
        ]);

        $autoEnroll = $request->has('auto_enroll');
        
        // Import dữ liệu từ file
        try {
            // Sử dụng Queue nếu cần xử lý batch import lớn
            // (new \App\Imports\StudentImport($id, $autoEnroll))->queue($request->file('import_file'));
            
            // Sử dụng cách import đồng bộ cho import nhỏ
            $import = new \App\Imports\StudentImport($id, $autoEnroll);
            $import->import($request->file('import_file'));
            
            $summary = $import->getSummary();
            $message = "Import thành công {$summary['imported']} học viên mới, {$summary['existing']} học viên đã tồn tại, {$summary['enrolled']} được đăng ký vào khóa học.";
            
            if ($summary['failures'] > 0) {
                $message .= " Có {$summary['failures']} dòng không hợp lệ, kiểm tra lại định dạng dữ liệu.";
            }
            
            return redirect()->route('course-items.students', $id)->with('success', $message);
        } catch (\Illuminate\Database\QueryException $e) {
            // Xử lý lỗi cơ sở dữ liệu cụ thể
            if ($e->getCode() === '23000') {  // Mã lỗi Integrity constraint violation
                return redirect()->route('course-items.students', $id)
                    ->with('error', 'Lỗi khi import: Xung đột dữ liệu trong cơ sở dữ liệu. Có thể do ID trùng lặp hoặc vi phạm ràng buộc unique.');
            }
            
            return redirect()->route('course-items.students', $id)
                ->with('error', 'Lỗi cơ sở dữ liệu: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Xử lý các lỗi khác
            return redirect()->route('course-items.students', $id)
                ->with('error', 'Lỗi khi import: ' . $e->getMessage());
        }
    }

    /**
     * Tải mẫu file import học viên
     */
    public function downloadImportTemplate()
    {
        // Tạo file Excel mẫu
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Thiết lập tiêu đề
        $headers = ['TT', 'Họ', 'Tên', 'Ng.sinh', 'Giới', 'Ghi chú'];
        $sheet->fromArray([$headers], NULL, 'A1');
        
        // Thêm một số dữ liệu mẫu
        $sampleData = [
            [1, 'Nguyễn Văn', 'An', '15/05/2000', 'Nam', 'ON'],
            [2, 'Trần Thị', 'Bình', '20/06/2001', 'Nữ', 'OFF'],
        ];
        $sheet->fromArray($sampleData, NULL, 'A2');
        
        // Định dạng cột cho đẹp
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(10);
        
        // Tạo style cho header
        $headerStyle = [
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E0E0E0',
                ],
            ],
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        
        // Tạo Writer để xuất file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Xuất file
        $filename = 'mau_import_hoc_vien_' . date('Ymd_His') . '.xlsx';
        $path = storage_path('app/public/' . $filename);
        
        // Đảm bảo thư mục tồn tại
        if (!file_exists(storage_path('app/public'))) {
            mkdir(storage_path('app/public'), 0755, true);
        }
        
        $writer->save($path);
        
        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
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