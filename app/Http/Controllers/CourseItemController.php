<?php

namespace App\Http\Controllers;

use App\Models\CourseItem;
use App\Services\CourseItemService;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseItemController extends Controller
{
    protected $courseItemService;
    protected $importService;
    
    public function __construct(CourseItemService $courseItemService, ImportService $importService)
    {
        $this->courseItemService = $courseItemService;
        $this->importService = $importService;
    }
    
    /**
     * Hiển thị danh sách cây khóa học
     */
    public function index()
    {
        // Lấy các ngành học (cấp 1)
        $rootItems = $this->courseItemService->getRootCourseItems();
        
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
            $parentItem = $this->courseItemService->getCourseItem($parentId);
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:course_items,id',
            'fee' => 'nullable|numeric|min:0',
            'active' => 'required|boolean',
            'is_leaf' => 'required|boolean',
        ]);

        $courseItem = $this->courseItemService->createCourseItem($validated);
        
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
     * Hiển thị thông tin chi tiết về course item
     */
    public function show($id)
    {
        $courseItem = $this->courseItemService->getCourseItemWithRelations($id, [
            'children' => function($query) {
                $query->where('active', true)->orderBy('order_index');
            }, 
            'learningPaths' => function($query) {
                $query->orderBy('order');
            }
        ]);
        
        // Lấy đường dẫn từ gốc đến item này
        $breadcrumbs = $courseItem->ancestors()->push($courseItem);
        
        // Nếu là khóa học lá có lộ trình, chuẩn bị dữ liệu về tình trạng hoàn thành
        $pathCompletionStats = [];
        if ($courseItem->is_leaf && $courseItem->learningPaths->count() > 0) {
            // Đếm tổng số học viên đã đăng ký khóa học
            $totalStudents = \App\Models\Enrollment::where('course_item_id', $courseItem->id)
                ->where('status', 'enrolled')
                ->count();
                
            foreach ($courseItem->learningPaths as $path) {
                // Đếm số học viên đã hoàn thành path này
                $completedCount = \App\Models\LearningPathProgress::whereHas('enrollment', function($query) use ($courseItem) {
                    $query->where('course_item_id', $courseItem->id)
                        ->where('status', 'enrolled');
                })
                ->where('learning_path_id', $path->id)
                ->where('is_completed', true)
                ->count();
                
                // Một path được coi là hoàn thành nếu có ít nhất 1 học viên hoàn thành
                $isCompleted = $completedCount > 0;
                
                $pathCompletionStats[$path->id] = [
                    'completed_count' => $completedCount,
                    'is_completed' => $isCompleted
                ];
                
                // Lưu trạng thái vào session để đảm bảo đồng bộ với LearningProgressController
                session()->put("learning_path_{$path->id}_completed", $isCompleted);
            }
        }
        
        return view('course-items.show', compact('courseItem', 'breadcrumbs', 'pathCompletionStats'));
    }

    /**
     * Hiển thị form chỉnh sửa
     */
    public function edit($id)
    {
        $courseItem = $this->courseItemService->getCourseItem($id);
        
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
        $courseItem = $this->courseItemService->getCourseItem($id);

        // Kiểm tra không cho phép item là cha của chính nó
        if ($request->parent_id == $id) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false, 
                    'errors' => ['parent_id' => 'Không thể chọn chính nó làm cha']
                ], 422);
            }
            return back()->withErrors(['parent_id' => 'Không thể chọn chính nó làm cha']);
        }

        // Kiểm tra không cho phép chọn con làm cha
        $descendants = $courseItem->descendants()->pluck('id')->toArray();
        if (!empty($request->parent_id) && in_array($request->parent_id, $descendants)) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['parent_id' => 'Không thể chọn con làm cha']
                ], 422);
            }
            return back()->withErrors(['parent_id' => 'Không thể chọn con làm cha']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:course_items,id',
            'fee' => 'nullable|numeric|min:0',
            'active' => 'required|boolean',
            'is_leaf' => 'required|boolean',
            'make_root' => 'nullable|boolean',
            'is_special' => 'nullable|boolean',
            'custom_field_keys.*' => 'nullable|string',
            'custom_field_values.*' => 'nullable|string',
        ]);

        $courseItem = $this->courseItemService->updateCourseItem($courseItem, $validated);

        // Nếu là AJAX request, trả về JSON response
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật thành công!',
                'course_item' => $courseItem
            ]);
        }
        
        // Nếu không phải AJAX request, chuyển hướng như trước
        if ($courseItem->parent_id) {
            return redirect()->route('course-items.show', $courseItem->parent_id)
                    ->with('success', 'Đã cập nhật thành công!');
        }
        
        // Nếu là khóa chính, chuyển về trang cây khóa học
        return redirect()->route('course-items.tree')
                ->with('success', 'Đã cập nhật thành công!');
    }

    /**
     * Xóa item
     */
    public function destroy($id)
    {
        $courseItem = $this->courseItemService->getCourseItem($id);
        
        // Lấy ID của parent để redirect sau khi xóa
        $parentId = $courseItem->parent_id;
        
        // Xóa đệ quy tất cả các khóa con và lớp học liên quan
        $this->courseItemService->deleteCourseItem($courseItem);
        
        if ($parentId) {
            return redirect()->route('course-items.tree', ['newly_added_id' => $parentId])
                    ->with('success', 'Đã xóa thành công!');
        }
        
        return redirect()->route('course-items.tree')
                ->with('success', 'Đã xóa thành công!');
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
        
        $this->courseItemService->updateCourseItemOrder($request->items);
        
        return response()->json(['success' => true]);
    }

    /**
     * Bật/tắt trạng thái hoạt động
     */
    public function toggleActive($id)
    {
        $this->courseItemService->toggleCourseItemActive($id);
        
        return back()->with('success', 'Đã cập nhật trạng thái hoạt động!');
    }
    
    /**
     * Hiển thị cấu trúc cây khóa học
     */
    public function tree(Request $request)
    {
        // Lấy tất cả các ngành học (cấp 1)
        $rootItems = CourseItem::whereNull('parent_id')
                            ->where('active', true)
                            ->orderBy('order_index')
                            ->with(['children' => function($query) {
                                $query->where('active', true)->orderBy('order_index');
                            }])
                            ->get();
        
        // Kiểm tra xem có đang xem một tab cụ thể không
        $currentRootItem = null;
        $rootId = $request->query('root_id');
        if ($rootId) {
            $currentRootItem = $rootItems->where('id', $rootId)->first();
        }
        
        return view('course-items.tree', compact('rootItems', 'currentRootItem'));
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
        
        // Lấy phần trăm giảm giá (nếu có)
        $discountPercentage = $request->discount_percentage ?? 0;
        
        try {
            // Import từ Excel
            $result = $this->importService->importStudentsFromExcel(
                $request->file('excel_file'),
                $id,
                $discountPercentage
            );
            
            return redirect()->route('course-items.students', $id)
                    ->with('success', $result['message']);
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
            // Tạo và lưu template
            $filePath = $this->importService->exportStudentTemplate();
            
            return response()->download($filePath, 'template_import_hoc_vien.xlsx')
                    ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo template Excel: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return back()->withErrors(['download' => 'Có lỗi khi tạo template Excel: ' . $e->getMessage()]);
        }
    }

    /**
     * Hiển thị danh sách học viên theo ngành học
     */
    public function showStudents($id)
    {
        $courseItem = $this->courseItemService->getCourseItem($id);
        
        // Lấy tất cả ID của khóa học con thuộc ngành này
        $courseItemIds = [$id];
        
        // Lấy tất cả ID con sử dụng phương thức của repository
        $this->getAllChildrenIds($courseItem, $courseItemIds);
        
        // Lấy tất cả học viên đã đăng ký các khóa học này
        $enrollments = \App\Models\Enrollment::whereIn('course_item_id', $courseItemIds)
            ->with(['student', 'courseItem', 'payments' => function($query) {
                $query->orderBy('payment_date', 'desc');
            }])
            ->get();
        
        $students = $enrollments->map(function($enrollment) {
            // Lấy thông tin thanh toán mới nhất
            $latestPayment = $enrollment->payments->where('status', 'confirmed')->first();
            
            // Xác định trạng thái thanh toán
            $paymentStatus = $enrollment->isFullyPaid() ? 'Đã đóng đủ' : 'Chưa đóng đủ';
            
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
            
            // Trả về thông tin học viên kèm custom_fields từ đăng ký
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
                'paid_amount' => $enrollment->getTotalPaidAmount(),
                'remaining_amount' => $enrollment->getRemainingAmount(),
                'payment_notes' => $paymentNotes,
                'has_notes' => count($paymentNotes) > 0,
                'custom_fields' => $enrollment->custom_fields
            ];
        });
        
        return view('course-items.students', [
            'courseItem' => $courseItem,
            'students' => $students,
            'enrollmentCount' => $enrollments->count(),
            'studentCount' => $enrollments->pluck('student_id')->unique()->count(),
            'is_special' => $courseItem->is_special,
            'custom_fields' => $courseItem->is_special ? $courseItem->custom_fields : null
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
     * Tìm kiếm khóa học theo từ khóa
     */
    public function search(Request $request)
    {
        $term = $request->input('q');
        $rootId = $request->input('root_id');
        
        // Nếu từ khóa tìm kiếm quá ngắn, trả về mảng rỗng
        if (strlen($term) < 2) {
            return response()->json([]);
        }
        
        $results = $this->courseItemService->searchCourseItems($term, $rootId);
        
        return response()->json($results);
    }

    /**
     * Hiển thị form thêm học viên vào khóa học
     */
    public function addStudentForm($id)
    {
        $courseItem = $this->courseItemService->getCourseItem($id);
        $students = \App\Models\Student::orderBy('full_name')->get();
        
        return view('course-items.add-student', [
            'courseItem' => $courseItem,
            'students' => $students
        ]);
    }
    
    /**
     * Thêm học viên vào khóa học
     */
    public function addStudent(Request $request, $id)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'enrollment_date' => 'required|date',
            'final_fee' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:enrolled,completed,dropped',
            'notes' => 'nullable|string'
        ]);
        
        $courseItem = $this->courseItemService->getCourseItem($id);
        
        // Kiểm tra xem học viên đã ghi danh vào khóa học này chưa
        $existingEnrollment = \App\Models\Enrollment::where('student_id', $request->student_id)
                                      ->where('course_item_id', $id)
                                      ->first();
        
        if ($existingEnrollment) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Học viên này đã được ghi danh vào khóa học này rồi!']);
        }
        
        try {
            DB::beginTransaction();
            
            // Tạo ghi danh mới
            $enrollment = \App\Models\Enrollment::create([
                'student_id' => $request->student_id,
                'course_item_id' => $id,
                'enrollment_date' => $request->enrollment_date,
                'final_fee' => $request->final_fee,
                'discount_percentage' => $request->discount_percentage ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'status' => $request->status,
                'notes' => $request->notes
            ]);
            
            // Nếu có thanh toán ban đầu
            if ($request->filled('payment_amount') && $request->payment_amount > 0) {
                $payment = \App\Models\Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'amount' => $request->payment_amount,
                    'payment_method' => $request->payment_method,
                    'payment_date' => $request->payment_date ?? now(),
                    'status' => 'confirmed',
                    'transaction_id' => null,
                    'notes' => $request->payment_notes ?? 'Thanh toán khi ghi danh'
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('course-items.students', $id)
                           ->with('success', 'Thêm học viên vào khóa học thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Đã xảy ra lỗi: ' . $e->getMessage()]);
        }
    }

    /**
     * Hiển thị danh sách chờ theo khóa học
     */
    public function waitingList($id)
    {
        // Chuyển hướng đến trang ghi danh với filter theo course_item_id và status waiting
        return redirect()->route('enrollments.index', [
            'course_item_id' => $id,
            'status' => 'waiting'
        ]);
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