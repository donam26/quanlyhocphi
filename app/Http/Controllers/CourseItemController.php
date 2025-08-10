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
            'is_leaf' => 'required|boolean',
        ]);

        // Lưu root_id hiện tại để preserve tab state
        $currentRootId = $request->input('current_root_id');
        
        // Nếu không có current_root_id và có parent_id, tìm root_id từ parent
        if (!$currentRootId && $validated['parent_id']) {
            $parent = CourseItem::find($validated['parent_id']);
            if ($parent) {
                $currentRootId = $this->findRootId($parent);
            }
        }

        $courseItem = $this->courseItemService->createCourseItem($validated);

        // Xác định root_id để redirect
        $redirectRootId = $currentRootId;
        
        // Nếu tạo mới một root item (không có parent), redirect đến tab của chính nó
        if (!$validated['parent_id']) {
            $redirectRootId = $courseItem->id;
        }

        // Nếu request từ modal trong trang tree, chuyển hướng về trang tree với tham số newly_added_id
        if ($request->ajax() || $request->header('X-Requested-With') == 'XMLHttpRequest' || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('course-items.tree', [
                    'root_id' => $redirectRootId,
                    'newly_added_id' => $courseItem->id
                ])
            ]);
        }

        $message = $request->parent_id ? 'Đã thêm thành công khóa học con mới!' : 'Đã thêm thành công ngành học mới!';

        return redirect()->route('course-items.tree', [
            'root_id' => $redirectRootId,
            'newly_added_id' => $courseItem->id
        ])->with('success', $message);
    }

    /**
     * API lấy thông tin chi tiết khóa học
     */
    public function getCourseDetails($id)
    {
        $courseItem = $this->courseItemService->getCourseItemWithRelations($id, [
            'children' => function($query) {
                $query->where('active', true)->orderBy('order_index');
            },
            'learningPaths' => function($query) {
                $query->orderBy('order');
            },
            'enrollments',
            'parent'
        ]);

        // Tính toán đường dẫn
        $path = '';
        $ancestors = $courseItem->ancestors();
        if ($ancestors->count() > 0) {
            $path = $ancestors->pluck('name')->implode(' > ');
        }

        // Tính tổng số học viên đã đăng ký
        $enrollmentCount = $courseItem->enrollments->count();

        // Tính tổng doanh thu từ khóa học
        $totalRevenue = $courseItem->enrollments->sum('final_fee');

        // Chuẩn bị dữ liệu lộ trình học tập
        $learningPaths = $courseItem->learningPaths->map(function($path) use ($courseItem) {
            // Đếm số học viên đã hoàn thành path này
            $completedCount = \App\Models\LearningPathProgress::whereHas('enrollment', function($query) use ($courseItem) {
                $query->where('course_item_id', $courseItem->id)
                    ->where('status', 'enrolled');
            })
            ->where('learning_path_id', $path->id)
            ->where('is_completed', true)
            ->count();

            return [
                'id' => $path->id,
                'title' => $path->title,
                'description' => $path->description,
                'order' => $path->order,
                'completed_count' => $completedCount
            ];
        });

        // Trả về dữ liệu chi tiết
        return response()->json([
            'id' => $courseItem->id,
            'name' => $courseItem->name,
            'parent_id' => $courseItem->parent_id,
            'level' => $courseItem->level,
            'is_leaf' => $courseItem->is_leaf,
            'fee' => $courseItem->fee,
            'active' => $courseItem->active,
            'is_special' => $courseItem->is_special,
            'custom_fields' => $courseItem->custom_fields,
            'path' => $path,
            'enrollment_count' => $enrollmentCount,
            'total_revenue' => $totalRevenue,
            'learning_paths' => $learningPaths,
            'children' => $courseItem->children
        ]);
    }

    /**
     * Cập nhật khóa học
     */
    public function update(Request $request, CourseItem $courseItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:course_items,id',
            'fee' => 'nullable|numeric|min:0',
            'active' => 'required|boolean',
            'is_leaf' => 'required|boolean',
            'is_special' => 'nullable|boolean',
            'custom_field_keys' => 'nullable|array',
        ]);

        // Lưu root_id hiện tại để preserve tab state
        $currentRootId = $request->input('current_root_id');
        
        // Nếu không có current_root_id, tìm root_id từ courseItem hiện tại
        if (!$currentRootId) {
            $currentRootId = $this->findRootId($courseItem);
        }

        $updatedCourseItem = $this->courseItemService->updateCourseItem($courseItem, $validated);

        // Giữ nguyên tab hiện tại, trừ khi item được chuyển sang root tree khác
        $newRootId = $this->findRootId($updatedCourseItem);
        
        // Nếu item vẫn thuộc cùng root tree, giữ nguyên tab hiện tại
        // Nếu item được chuyển sang root tree khác, chuyển đến tab mới
        $redirectRootId = ($currentRootId && $newRootId == $currentRootId) ? $currentRootId : $newRootId;

        // Nếu request từ AJAX, trả về JSON response
        if ($request->ajax() || $request->header('X-Requested-With') == 'XMLHttpRequest' || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật khóa học thành công!',
                'redirect' => route('course-items.tree', [
                    'root_id' => $redirectRootId,
                    'updated_id' => $updatedCourseItem->id
                ])
            ]);
        }

        return redirect()->route('course-items.tree', [
            'root_id' => $redirectRootId,
            'updated_id' => $updatedCourseItem->id
        ])->with('success', 'Đã cập nhật khóa học thành công!');
    }

    /**
     * Tìm root_id của một course item
     */
    private function findRootId(CourseItem $courseItem)
    {
        // Nếu đã là root item
        if (!$courseItem->parent_id) {
            return $courseItem->id;
        }

        // Tìm root ancestor
        $current = $courseItem;
        while ($current && $current->parent_id) {
            $current = $current->parent;
        }
        
        return $current ? $current->id : $courseItem->id;
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
     * Hiển thị cây khóa học
     */
    public function tree(Request $request)
    {
        // Lấy tất cả các ngành học (cấp 1) với children đầy đủ
        $rootItems = CourseItem::whereNull('parent_id')
                            ->orderBy('order_index')
                            ->with(['children' => function($query) {
                                $query->where('active', true)
                                      ->orderBy('order_index')
                                      ->with(['children' => function($subQuery) {
                                          $subQuery->where('active', true)->orderBy('order_index');
                                      }]);
                            }])
                            ->get();

        // Xác định root_id để active tab
        $activeRootId = null;
        $rootId = $request->query('root_id');
        
        // Nếu có updated_id hoặc newly_added_id, tìm root_id tương ứng
        $updatedId = $request->query('updated_id');
        $newlyAddedId = $request->query('newly_added_id');
        $targetId = $updatedId ?: $newlyAddedId;
        
        if ($targetId && !$rootId) {
            $targetItem = CourseItem::find($targetId);
            if ($targetItem) {
                $rootId = $this->findRootId($targetItem);
            }
        }
        
        // Xác định tab active
        if ($rootId) {
            // Kiểm tra root_id có tồn tại trong danh sách không
            $foundRootItem = $rootItems->where('id', $rootId)->first();
            if ($foundRootItem) {
                $activeRootId = $rootId;
            }
        }
        
        // Nếu không có root_id hợp lệ, mặc định là item đầu tiên
        if (!$activeRootId && $rootItems->isNotEmpty()) {
            $activeRootId = $rootItems->first()->id;
        }

        return view('course-items.tree', compact('rootItems', 'activeRootId'));
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

        $totalStudents = $enrollments->pluck('student_id')->unique()->count();

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
                'custom_fields' => $enrollment->custom_fields,
            ];
        });

        return view('course-items.students', [
            'courseItem' => $courseItem,
            'students' => $students,
            'enrollmentCount' => $enrollments->count(),
            'studentCount' => $enrollments->pluck('student_id')->unique()->count(),
            'is_special' => $courseItem->is_special,
            'custom_fields' => $courseItem->is_special ? $courseItem->custom_fields : null,
            'totalStudents' => $totalStudents,
        ]);
    }

    /**
     * Trả về danh sách học viên theo ngành/khoá ở dạng JSON để hiển thị trong modal
     */
    public function getStudentsJson($id)
    {
        $courseItem = $this->courseItemService->getCourseItem($id);

        // Thu thập tất cả ID con thuộc ngành/khoá này
        $courseItemIds = [$id];
        $this->getAllChildrenIds($courseItem, $courseItemIds);

        $enrollments = \App\Models\Enrollment::whereIn('course_item_id', $courseItemIds)
            ->whereNotIn('status', ['waiting', 'cancelled'])
            ->with(['student', 'courseItem', 'payments' => function($query) {
                $query->orderBy('payment_date', 'desc');
            }])
            ->get();

        $totalStudents = $enrollments->pluck('student_id')->unique()->count();

        $students = $enrollments->map(function($enrollment) {
            $latestPayment = $enrollment->payments->where('status', 'confirmed')->first();
            $paymentStatus = $enrollment->isFullyPaid() ? 'Đã đóng đủ' : 'Chưa đóng đủ';

            $paymentNotes = [];
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

            return [
                'student' => [
                    'id' => $enrollment->student->id,
                    'full_name' => $enrollment->student->full_name,
                    'phone' => $enrollment->student->phone,
                    'email' => $enrollment->student->email,
                ],
                'course_item' => $enrollment->courseItem ? $enrollment->courseItem->name : 'N/A',
                'enrollment_id' => $enrollment->id,
                'status' => $enrollment->status,
                'final_fee' => $enrollment->final_fee,
                'paid_amount' => $enrollment->getTotalPaidAmount(),
                'remaining_amount' => $enrollment->getRemainingAmount(),
                'payment_status' => $paymentStatus,
                'payment_method' => $latestPayment ? $this->getPaymentMethodText($latestPayment->payment_method) : 'Chưa thanh toán',
                'has_notes' => count($paymentNotes) > 0,
                'payment_notes' => $paymentNotes,
                'custom_fields' => $enrollment->custom_fields,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'course' => [
                'id' => $courseItem->id,
                'name' => $courseItem->name,
                'path' => $courseItem->path,
                'is_special' => $courseItem->is_special,
                'custom_fields' => $courseItem->is_special ? $courseItem->custom_fields : null,
            ],
            'total_students' => $totalStudents,
            'enrollment_count' => $enrollments->count(),
            'students' => $students,
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
        // Nếu là AJAX request, trả về JSON data
        if (request()->ajax() || request()->expectsJson()) {
            $courseItem = CourseItem::findOrFail($id);
            
            // Lấy tất cả ID của khóa học và các khóa con
            $courseIds = [$id];
            $this->getAllChildrenIds($courseItem, $courseIds);
            
            // Lấy danh sách học viên đang chờ
            $enrollments = \App\Models\Enrollment::whereIn('course_item_id', $courseIds)
                ->where('status', 'waiting')
                ->with(['student', 'courseItem'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            $students = $enrollments->map(function($enrollment) {
                return [
                    'enrollment_id' => $enrollment->id,
                    'student_id' => $enrollment->student->id,
                    'full_name' => $enrollment->student->full_name,
                    'phone' => $enrollment->student->phone,
                    'email' => $enrollment->student->email,
                    'request_date' => $enrollment->created_at->format('d/m/Y'),
                    'notes' => $enrollment->notes,
                    'course_name' => $enrollment->courseItem->name,
                    'final_fee' => number_format($enrollment->final_fee)
                ];
            });
            
            return response()->json([
                'success' => true,
                'students' => $students
            ]);
        }
        
        // Nếu không phải AJAX, chuyển hướng đến trang ghi danh
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

    /**
     * Hiển thị trang cây danh sách chờ
     */
    public function waitingTree(Request $request)
    {
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

        return view('course-items.waiting-tree', compact('rootItems', 'currentRootItem'));
    }

    /**
     * Lấy số lượng học viên đang chờ của một khóa học
     */
    public function getWaitingCount($courseItemId)
    {
        $count = \App\Models\Enrollment::where('course_item_id', $courseItemId)
                    ->where('status', 'waiting')
                    ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Lấy danh sách khóa học lá (có thể ghi danh) cho dropdown
     */
    public function getLeafCourses()
    {
        $courses = CourseItem::where('is_leaf', true)
                            ->where('active', true)
                            ->orderBy('name')
                            ->get()
                            ->map(function($course) {
                                return [
                                    'id' => $course->id,
                                    'name' => $course->name,
                                    'path' => $this->getCoursePath($course)
                                ];
                            });

        return response()->json([
            'success' => true,
            'courses' => $courses
        ]);
    }

    /**
     * Lấy đường dẫn đầy đủ của khóa học
     */
    private function getCoursePath($courseItem)
    {
        $path = [];
        $current = $courseItem;
        
        while ($current) {
            array_unshift($path, $current->name);
            $current = $current->parent;
        }
        
        return implode(' > ', $path);
    }
}
