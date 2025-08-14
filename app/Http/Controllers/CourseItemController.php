<?php

namespace App\Http\Controllers;

use App\Models\CourseItem;
use App\Services\CourseItemService;
use App\Services\ImportService;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use Illuminate\Http\Request;
use App\Rules\DateDDMMYYYY;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InvoiceExport;

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
            'is_leaf' => 'nullable|boolean',
            'active' => 'nullable|boolean',
            'is_special' => 'nullable|boolean',
            'learning_method' => 'nullable|in:online,offline',
            'custom_field_keys' => 'nullable|array',
        ]);
        
        // Đảm bảo is_leaf và active có giá trị mặc định
        $validated['is_leaf'] = $validated['is_leaf'] ?? false;
        $validated['active'] = $validated['active'] ?? true;

        // Debug: Log dữ liệu được gửi (chỉ khi cần debug)
        // \Log::info('CourseItem Store Request Data:', [
        //     'all_request' => $request->all(),
        //     'validated' => $validated,
        //     'fee_raw' => $request->input('fee'),
        //     'is_leaf' => $request->input('is_leaf')
        // ]);

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
                    ->where('status', \App\Enums\EnrollmentStatus::ACTIVE->value);
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
            'learning_method' => $courseItem->learning_method?->value,
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
            'parent_id' => [
                'nullable',
                'exists:course_items,id',
                function ($attribute, $value, $fail) use ($courseItem) {
                    if ($value && $value == $courseItem->id) {
                        $fail('Không thể chọn chính nó làm khóa cha.');
                    }

                    // Kiểm tra circular reference
                    if ($value && $this->wouldCreateCircularReference($courseItem->id, $value)) {
                        $fail('Không thể chọn khóa con làm khóa cha.');
                    }
                }
            ],
            'fee' => 'nullable|numeric|min:0',
            'active' => 'required|boolean',
            'is_leaf' => 'required|boolean',
            'is_special' => 'nullable|boolean',
            'learning_method' => 'nullable|in:online,offline',
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
     * Import học viên vào danh sách chờ từ file Excel
     */
    public function importStudentsToWaiting(Request $request, $id)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            // Import vào danh sách chờ
            $result = $this->importService->importStudentsToWaitingList(
                $request->file('excel_file'),
                $id,
                $request->notes
            );

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'imported_count' => $result['imported_count']
                ]);
            }

            return redirect()->route('course-items.waiting-tree')
                    ->with('success', $result['message']);
        } catch (\Exception $e) {
            Log::error('Import students to waiting list error: ' . $e->getMessage());
            
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi khi import: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withErrors(['excel_file' => 'Có lỗi khi import: ' . $e->getMessage()]);
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
                        'date' => $payment->formatted_payment_date,
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
            ->whereNotIn('status', [EnrollmentStatus::WAITING, EnrollmentStatus::CANCELLED])
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
                        'date' => $payment->formatted_payment_date,
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
        $students = \App\Models\Student::orderBy('first_name')->orderBy('last_name')->get();

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
            'enrollment_date' => ['required', new DateDDMMYYYY],
            'final_fee' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:waiting,active,completed,cancelled',
            'notes' => 'nullable|string',
            // Validation cho payment fields (nếu có)
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_date' => ['nullable', new DateDDMMYYYY],
            'payment_method' => 'nullable|string',
            'payment_notes' => 'nullable|string'
        ]);

        $courseItem = $this->courseItemService->getCourseItem($id);

        // Kiểm tra khóa học phải có học phí > 0
        if (!$courseItem->fee || $courseItem->fee <= 0) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể đăng ký khóa học không có học phí. Khóa học "' . $courseItem->name . '" chưa được thiết lập học phí.'
                ], 422);
            }
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Không thể đăng ký khóa học không có học phí. Khóa học "' . $courseItem->name . '" chưa được thiết lập học phí.']);
        }

        // Kiểm tra xem học viên đã ghi danh vào khóa học này chưa
        $existingEnrollment = \App\Models\Enrollment::where('student_id', $request->student_id)
                                      ->where('course_item_id', $id)
                                      ->first();

        if ($existingEnrollment) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Học viên này đã được ghi danh vào khóa học này rồi!'
                ], 422);
            }
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Học viên này đã được ghi danh vào khóa học này rồi!']);
        }

        try {
            DB::beginTransaction();

            // Xử lý custom_fields cho khóa học đặc biệt
            $customFields = null;
            if ($courseItem->is_special && $courseItem->custom_fields) {
                $customFields = $courseItem->custom_fields;
            }

            // Tạo ghi danh mới
            $enrollment = \App\Models\Enrollment::create([
                'student_id' => $request->student_id,
                'course_item_id' => $id,
                'enrollment_date' => $request->enrollment_date,
                'final_fee' => $request->final_fee,
                'discount_percentage' => $request->discount_percentage ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'status' => $request->status,
                'notes' => $request->notes,
                'custom_fields' => $customFields
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

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thêm học viên vào khóa học thành công!',
                    'data' => [
                        'enrollment_id' => $enrollment->id,
                        'student_id' => $enrollment->student_id,
                        'course_item_id' => $enrollment->course_item_id,
                        'status' => $enrollment->status
                    ]
                ]);
            }

            return redirect()->route('course-items.students', $id)
                           ->with('success', 'Thêm học viên vào khóa học thành công!');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()
                ], 500);
            }

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
                ->where('status', EnrollmentStatus::WAITING)
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
                    'request_date' => $enrollment->created_at->format(config('app.date_format', 'd/m/Y')),
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
            'status' => EnrollmentStatus::WAITING->value
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
                    ->where('status', EnrollmentStatus::WAITING)
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

    /**
     * Kiểm tra xem việc đặt parent_id có tạo circular reference không
     */
    private function wouldCreateCircularReference($courseItemId, $proposedParentId)
    {
        // Lấy tất cả descendants của course item hiện tại
        $descendants = $this->getAllDescendants($courseItemId);
        
        // Nếu proposed parent là một trong các descendants, thì sẽ tạo circular reference
        return in_array($proposedParentId, $descendants);
    }

    /**
     * Lấy tất cả descendants của một course item
     */
    private function getAllDescendants($courseItemId)
    {
        $descendants = [];
        $children = CourseItem::where('parent_id', $courseItemId)->pluck('id')->toArray();
        
        foreach ($children as $childId) {
            $descendants[] = $childId;
            $descendants = array_merge($descendants, $this->getAllDescendants($childId));
        }
        
        return $descendants;
    }

    /**
     * Thay đổi trạng thái khóa học (active <-> completed)
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $courseItem = CourseItem::findOrFail($id);
            
            // Chỉ cho phép thay đổi trạng thái với khóa học lá (is_leaf = true)
            if (!$courseItem->is_leaf) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể thay đổi trạng thái của khóa học cụ thể (không phải nhóm khóa học)'
                ], 400);
            }

            if ($courseItem->isActive()) {
                // Chuyển từ active -> completed
                $success = $courseItem->completeCourse();
                $message = $success ? 'Đã kết thúc khóa học và cập nhật trạng thái tất cả học viên thành hoàn thành!' : 'Có lỗi xảy ra khi kết thúc khóa học';
                $newStatus = EnrollmentStatus::COMPLETED->value;
            } else {
                // Chuyển từ completed -> active  
                $success = $courseItem->reopenCourse();
                $message = $success ? 'Đã mở lại khóa học!' : 'Có lỗi xảy ra khi mở lại khóa học';
                $newStatus = EnrollmentStatus::ACTIVE->value;
            }

            if ($success) {
                // Log thay đổi
                Log::info("Course status changed", [
                    'course_id' => $courseItem->id,
                    'course_name' => $courseItem->name,
                    'new_status' => $newStatus,
                    'user_id' => auth()->id()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'id' => $courseItem->id,
                        'status' => $newStatus,
                        'status_badge' => $courseItem->fresh()->status_badge,
                        'status_label' => $courseItem->fresh()->getStatusEnum()->label()
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error toggling course status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kết thúc khóa học
     */
    public function completeCourse(Request $request, $id)
    {
        try {
            $courseItem = CourseItem::findOrFail($id);
            
            if (!$courseItem->is_leaf) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể kết thúc khóa học cụ thể'
                ], 400);
            }

            if ($courseItem->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Khóa học đã được kết thúc rồi'
                ], 400);
            }

            $success = $courseItem->completeCourse();
            
            if ($success) {
                // Đếm số học viên bị ảnh hưởng
                $affectedCount = $courseItem->enrollments()->where('status', EnrollmentStatus::COMPLETED)->count();
                
                return response()->json([
                    'success' => true,
                    'message' => "Đã kết thúc khóa học! {$affectedCount} học viên đã được cập nhật trạng thái thành hoàn thành.",
                    'data' => [
                        'affected_students' => $affectedCount,
                        'course_status' => CourseStatus::COMPLETED->value
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi kết thúc khóa học'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error completing course: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mở lại khóa học
     */
    public function reopenCourse(Request $request, $id)
    {
        try {
            $courseItem = CourseItem::findOrFail($id);
            
            if (!$courseItem->is_leaf) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể mở lại khóa học cụ thể'
                ], 400);
            }

            if ($courseItem->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Khóa học đang hoạt động rồi'
                ], 400);
            }

            // Đếm số học viên sẽ bị ảnh hưởng trước khi mở lại
            $affectedCount = $courseItem->enrollments()
                ->where('status', EnrollmentStatus::COMPLETED)
                ->count();

            $success = $courseItem->reopenCourse();
            
            if ($success) {
                $message = $affectedCount > 0 
                    ? "Đã mở lại khóa học! {$affectedCount} học viên đã được chuyển từ 'Hoàn thành' về 'Đang học'."
                    : 'Đã mở lại khóa học!';
                    
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'course_status' => CourseStatus::ACTIVE->value,
                        'affected_students' => $affectedCount
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi mở lại khóa học'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error reopening course: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export danh sách học viên của khóa học ra Excel
     */
    public function exportStudents($id)
    {
        try {
            $courseItem = $this->courseItemService->getCourseItem($id);

            // Lấy tất cả ID của khóa học con thuộc ngành này
            $courseItemIds = [$id];
            $this->getAllChildrenIds($courseItem, $courseItemIds);

            // Lấy tất cả học viên đã đăng ký các khóa học này
            $enrollments = \App\Models\Enrollment::whereIn('course_item_id', $courseItemIds)
                ->with(['student.province', 'courseItem', 'payments' => function($query) {
                    $query->where('status', 'confirmed')->orderBy('payment_date', 'desc');
                }])
                ->get();

            // Chuẩn bị dữ liệu export
            $exportData = [];
            $exportData[] = [
                'STT',
                'Họ và tên',
                'Số điện thoại',
                'Email',
                'Ngày sinh',
                'Giới tính',
                'Địa chỉ',
                'Tỉnh/Thành phố',
                'Khóa học',
                'Ngày đăng ký',
                'Trạng thái',
                'Học phí',
                'Đã thanh toán',
                'Còn thiếu',
                'Trạng thái thanh toán',
                'Ghi chú'
            ];

            $stt = 1;
            foreach ($enrollments as $enrollment) {
                $student = $enrollment->student;
                $totalPaid = $enrollment->getTotalPaidAmount();
                $remaining = $enrollment->getRemainingAmount();
                $paymentStatus = $remaining <= 0 ? 'Đã đóng đủ' : 'Chưa đóng đủ';

                $exportData[] = [
                    $stt++,
                    $student->full_name,
                    $student->phone,
                    $student->email ?: '',
                    $student->formatted_date_of_birth,
                    $this->getGenderText($student->gender),
                    $student->address ?: '',
                    $student->province ? $student->province->name : '',
                    $enrollment->courseItem->name,
                    $enrollment->formatted_enrollment_date,
                    $enrollment->getStatusEnum() ? $enrollment->getStatusEnum()->label() : $enrollment->status,
                    number_format($enrollment->final_fee, 0, ',', '.') . ' VNĐ',
                    number_format($totalPaid, 0, ',', '.') . ' VNĐ',
                    number_format($remaining, 0, ',', '.') . ' VNĐ',
                    $paymentStatus,
                    $enrollment->notes ?: ''
                ];
            }

            // Tạo file Excel
            $fileName = 'danh_sach_hoc_vien_' . Str::slug($courseItem->name) . '_' . date('Y_m_d_H_i_s') . '.xlsx';

            return Excel::download(new class($exportData, $courseItem->name) implements
                \Maatwebsite\Excel\Concerns\FromArray,
                \Maatwebsite\Excel\Concerns\WithTitle,
                \Maatwebsite\Excel\Concerns\WithStyles,
                \Maatwebsite\Excel\Concerns\ShouldAutoSize {

                private $data;
                private $courseName;

                public function __construct($data, $courseName) {
                    $this->data = $data;
                    $this->courseName = $courseName;
                }

                public function array(): array {
                    return $this->data;
                }

                public function title(): string {
                    return 'Danh sách học viên';
                }

                public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet) {
                    return [
                        1 => ['font' => ['bold' => true]],
                    ];
                }
            }, $fileName);

        } catch (\Exception $e) {
            Log::error('Export students error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Có lỗi xảy ra khi xuất file: ' . $e->getMessage());
        }
    }

    /**
     * Helper method để chuyển đổi giới tính
     */
    private function getGenderText($gender)
    {
        switch ($gender) {
            case 'male':
                return 'Nam';
            case 'female':
                return 'Nữ';
            case 'other':
                return 'Khác';
            default:
                return '';
        }
    }

    /**
     * Xuất hóa đơn điện tử cho tất cả học viên trong khóa học
     */
    public function exportInvoices(Request $request)
    {
        try {
            $request->validate([
                'course_id' => 'required|exists:course_items,id',
                'invoice_date' => 'required|date',
                'invoice_type' => 'required|in:company,personal',
                'notes' => 'nullable|string'
            ]);

            $courseItem = CourseItem::with(['enrollments.student', 'enrollments.payments'])
                ->findOrFail($request->course_id);

            // Lấy danh sách học viên đã đăng ký
            $enrollments = $courseItem->enrollments()
                ->with(['student', 'payments'])
                ->whereHas('student')
                ->get();

            if ($enrollments->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có học viên nào trong khóa học này!'
                ]);
            }

            $downloadUrls = [];
            $fileCount = 0;

            foreach ($enrollments as $enrollment) {
                $student = $enrollment->student;

                // Tính toán thông tin thanh toán
                $totalPaid = $enrollment->payments->where('status', 'completed')->sum('amount');
                $remaining = $enrollment->final_fee - $totalPaid;

                // Tạo dữ liệu hóa đơn cho từng học viên
                $invoiceData = [
                    // Thông tin khóa học
                    'course_name' => $courseItem->name,
                    'course_fee' => $enrollment->final_fee,

                    // Thông tin học viên
                    'student_name' => $student->full_name,
                    'student_phone' => $student->phone,
                    'student_email' => $student->email,
                    'student_address' => $student->address,

                    // Thông tin hóa đơn từ học viên
                    'company_name' => $student->company_name,
                    'tax_code' => $student->tax_code,
                    'invoice_email' => $student->invoice_email,
                    'company_address' => $student->company_address,

                    // Thông tin hóa đơn
                    'invoice_date' => $request->invoice_date,
                    'notes' => $request->notes,

                    // Thông tin thanh toán
                    'total_paid' => $totalPaid,
                    'remaining' => $remaining,
                    'enrollment_date' => $enrollment->enrollment_date,
                ];

                // Tạo file Excel cho từng học viên
                $fileName = 'hoa_don_' . Str::slug($student->name) . '_' . Str::slug($courseItem->name) . '_' . date('Y_m_d_H_i_s') . '.xlsx';

                // Tạo file Excel theo loại hóa đơn
                if ($request->invoice_type === 'personal') {
                    $excelFile = $this->createPersonalInvoiceExcel($invoiceData, $fileName);
                } else {
                    $excelFile = $this->createInvoiceExcelDirect($invoiceData, $fileName);
                }

                if ($excelFile) {
                    $downloadUrls[] = [
                        'filename' => $fileName,
                        'content' => base64_encode($excelFile)
                    ];
                    $fileCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Đã tạo thành công {$fileCount} file hóa đơn",
                'file_count' => $fileCount,
                'download_urls' => $downloadUrls
            ]);

        } catch (\Exception $e) {
            Log::error('Export invoices error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo file Excel hóa đơn cho một học viên
     */
    private function createInvoiceExcel($invoiceData, $fileName)
    {
        try {
            // Kiểm tra và làm sạch dữ liệu
            $courseName = $invoiceData['course_name'] ?? 'N/A';
            $courseFee = $invoiceData['course_fee'] ?? 0;
            $studentName = $invoiceData['student_name'] ?? 'N/A';
            $studentPhone = $invoiceData['student_phone'] ?? 'N/A';
            $studentEmail = $invoiceData['student_email'] ?? 'N/A';
            $studentAddress = $invoiceData['student_address'] ?? 'N/A';
            $totalPaid = $invoiceData['total_paid'] ?? 0;
            $remaining = $invoiceData['remaining'] ?? 0;
            $enrollmentDate = $invoiceData['enrollment_date'] ?? now();
            $invoiceDate = $invoiceData['invoice_date'] ?? now();
            $notes = $invoiceData['notes'] ?? '';

            // Tạo dữ liệu cho Excel
            $data = [
                ['HÓA ĐƠN ĐIỆN TỬ', ''],
                ['', ''],
                ['Thông tin khóa học:', ''],
                ['Tên khóa học:', $courseName],
                ['Học phí:', number_format($courseFee, 0, ',', '.') . ' VNĐ'],
                ['', ''],
                ['Thông tin học viên:', ''],
                ['Họ và tên:', $studentName],
                ['Số điện thoại:', $studentPhone],
                ['Email:', $studentEmail],
                ['Địa chỉ:', $studentAddress],
                ['', ''],
            ];

            // Thêm thông tin doanh nghiệp nếu có
            if (isset($invoiceData['invoice_type']) && $invoiceData['invoice_type'] === 'company') {
                $companyName = $invoiceData['company_name'] ?? 'N/A';
                $taxCode = $invoiceData['tax_code'] ?? 'N/A';
                $companyAddress = $invoiceData['company_address'] ?? 'N/A';

                $data = array_merge($data, [
                    ['Thông tin doanh nghiệp:', ''],
                    ['Tên đơn vị:', $companyName],
                    ['Mã số thuế:', $taxCode],
                    ['Địa chỉ:', $companyAddress],
                    ['', ''],
                ]);
            }

            // Thêm thông tin thanh toán
            $data = array_merge($data, [
                ['Thông tin thanh toán:', ''],
                ['Ngày đăng ký:', date('d/m/Y', strtotime($enrollmentDate))],
                ['Tổng học phí:', number_format($courseFee, 0, ',', '.') . ' VNĐ'],
                ['Đã thanh toán:', number_format($totalPaid, 0, ',', '.') . ' VNĐ'],
                ['Còn lại:', number_format($remaining, 0, ',', '.') . ' VNĐ'],
                ['', ''],
                ['Ngày xuất hóa đơn:', date('d/m/Y', strtotime($invoiceDate))],
                ['Ghi chú:', $notes],
            ]);

            // Tạo file Excel với class riêng biệt
            $export = new InvoiceExport($data);

            // Lưu file vào storage
            $filePath = 'invoices/' . $fileName;
            Excel::store($export, $filePath, 'public');

            return $filePath;

        } catch (\Exception $e) {
            Log::error('Create invoice Excel error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Tạo file Excel hóa đơn doanh nghiệp theo mẫu giống cá nhân
     */
    private function createInvoiceExcelDirect($invoiceData, $fileName)
    {
        try {
            // Tạo export class cho hóa đơn doanh nghiệp theo mẫu giống cá nhân
            $export = new class($invoiceData) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithTitle, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\ShouldAutoSize {
                private $invoiceData;

                public function __construct($invoiceData) {
                    $this->invoiceData = $invoiceData;
                }

                public function array(): array {
                    return $this->buildCompanyInvoiceData($this->invoiceData);
                }

                public function title(): string {
                    return 'Giấy đề nghị xuất hóa đơn';
                }

                public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet) {
                    // Header styling (rows 1-2)
                    $sheet->getStyle('A1:H2')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
                    ]);

                    // Title styling (row 4)
                    $sheet->mergeCells('A4:H4');
                    $sheet->getStyle('A4')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 16],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
                    ]);

                    // Table headers styling (row 12)
                    $sheet->getStyle('A12:H12')->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                            ]
                        ],
                        'font' => ['bold' => true],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
                    ]);

                    // Data rows styling (rows 13-16)
                    $sheet->getStyle('A13:H16')->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                            ]
                        ]
                    ]);

                    // Center align data in table
                    $sheet->getStyle('A13')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('H13')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                    // Footer row (Cộng) - row 18
                    $sheet->getStyle('A18:H18')->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                            ]
                        ],
                        'font' => ['bold' => true]
                    ]);

                    // Signature row - row 19
                    $sheet->getStyle('A19:H19')->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                            ]
                        ],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                        'font' => ['bold' => true]
                    ]);

                    // Set row heights for better spacing
                    $sheet->getRowDimension(3)->setRowHeight(10);  // Empty row
                    $sheet->getRowDimension(5)->setRowHeight(10);  // Empty row
                    $sheet->getRowDimension(7)->setRowHeight(10);  // Empty row
                    $sheet->getRowDimension(11)->setRowHeight(10); // Empty row
                    $sheet->getRowDimension(17)->setRowHeight(10); // Empty row

                    return [];
                }

                private function buildCompanyInvoiceData($invoiceData) {
                    // Tách họ và tên
                    $lastName = $this->getLastName($invoiceData['student_name']);
                    $firstName = $this->getFirstName($invoiceData['student_name']);

                    // Tạo nội dung học phí
                    $courseContent = 'Học phí lớp ' . $invoiceData['course_name'];

                    // Định dạng số tiền (giữ nguyên phần thập phân nếu có)
                    $courseFee = floatval($invoiceData['course_fee']);
                    $formattedAmount = number_format($courseFee, 2, ',', '.');
                    // Loại bỏ .00 nếu là số nguyên
                    if (fmod($courseFee, 1) == 0) {
                        $formattedAmount = number_format($courseFee, 0, ',', '.');
                    }

                    // Thông tin doanh nghiệp
                    $companyName = $invoiceData['company_name'] ?: 'Chưa cập nhật';
                    $taxCode = $invoiceData['tax_code'] ?: '';
                    $companyAddress = $invoiceData['company_address'] ?: '';

                    return [
                        // Row 1: Headers
                        ['TRƯỜNG ĐẠI HỌC KINH TẾ QUỐC DÂN', '', '', '', 'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM', '', '', ''],
                        // Row 2: Sub headers
                        ['Đơn vị: Trung tâm Đào tạo Liên tục', '', '', '', 'Độc lập - Tự do - Hạnh phúc', '', '', ''],
                        // Row 3: Empty
                        ['', '', '', '', '', '', '', ''],
                        // Row 4: Title
                        ['GIẤY ĐỀ NGHỊ XUẤT HÓA ĐƠN', '', '', '', '', '', '', ''],
                        // Row 5: Empty
                        ['', '', '', '', '', '', '', ''],
                        // Row 6: Kính gửi
                        ['Kính gửi: Ban giám hiệu Trường Đại học Kinh tế Quốc dân', '', '', '', '', '', '', ''],
                        // Row 7: Empty
                        ['', '', '', '', '', '', '', ''],
                        // Row 8: Tên đơn vị
                        ['Tên đơn vị đề nghị:', '', '', '', '', '', '', ''],
                        // Row 9: Địa chỉ
                        ['Địa chỉ: Trung tâm Đào tạo Liên tục - ĐH Kinh tế Quốc dân', '', '', '', '', '', '', ''],
                        // Row 10: Đề nghị
                        ['Đề nghị phòng TC-KT xuất cho chúng tôi hóa đơn GTGT với nội dung như sau:', '', '', '', '', '', '', ''],
                        // Row 11: Empty
                        ['', '', '', '', '', '', '', ''],
                        // Row 12: Table headers
                        ['TT', 'Họ', 'Tên', 'Tên đơn vị', 'Mã số thuế', 'Địa chỉ', 'Nội dung', 'Thành tiền'],
                        // Row 13: Data row với thông tin thực của học viên
                        ['1', $lastName, $firstName, $companyName, $taxCode, $companyAddress, $courseContent, $formattedAmount],
                        // Row 14: Data row 2 (email)
                        ['', '', '', '(Email: ' . ($invoiceData['invoice_email'] ?: $invoiceData['student_email']) . ')', '', '', '', ''],
                        // Row 15: Empty row
                        ['', '', '', '', '', '', '', ''],
                        // Row 16: Empty data row
                        ['', '', '', '', '', '', '', ''],
                        // Row 17: Empty
                        ['', '', '', '', '', '', '', ''],
                        // Row 18: Cộng row
                        ['', 'Cộng', '', '', '', '', '-', $formattedAmount],
                        // Row 19: Signature row
                        ['Thủ trưởng đơn vị', '', 'Kế toán Trường', '', 'Xác nhận bộ phận', '', 'Người đề nghị thanh toán', '']
                    ];
                }

                private function getLastName($fullName) {
                    // Lấy họ và tên đệm (tất cả trừ từ cuối cùng)
                    $parts = explode(' ', trim($fullName));
                    if (count($parts) <= 1) {
                        return $fullName;
                    }
                    array_pop($parts); // Remove tên (từ cuối)
                    return implode(' ', $parts);
                }

                private function getFirstName($fullName) {
                    // Lấy tên (từ cuối cùng)
                    $parts = explode(' ', trim($fullName));
                    return end($parts) ?: '';
                }
            };

            // Tạo file Excel và trả về content
            return \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);

        } catch (\Exception $e) {
            Log::error('Create company invoice Excel error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Tạo file Excel hóa đơn cá nhân theo mẫu
     */
    private function createPersonalInvoiceExcel($invoiceData, $fileName)
    {
        try {
            // Tạo export class cho hóa đơn cá nhân
            $export = new class($invoiceData) implements
                \Maatwebsite\Excel\Concerns\FromArray,
                \Maatwebsite\Excel\Concerns\WithTitle,
                \Maatwebsite\Excel\Concerns\WithStyles,
                \Maatwebsite\Excel\Concerns\ShouldAutoSize {

                private $data;

                public function __construct($invoiceData) {
                    $this->data = $this->buildPersonalInvoiceData($invoiceData);
                }

                public function array(): array {
                    return $this->data;
                }

                public function title(): string {
                    return 'Hóa đơn cá nhân';
                }

                public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet) {
                    // Set column widths
                    $sheet->getColumnDimension('A')->setWidth(5);   // TT
                    $sheet->getColumnDimension('B')->setWidth(15);  // Họ
                    $sheet->getColumnDimension('C')->setWidth(15);  // Tên
                    $sheet->getColumnDimension('D')->setWidth(20);  // Tên đơn vị
                    $sheet->getColumnDimension('E')->setWidth(15);  // Mã số thuế
                    $sheet->getColumnDimension('F')->setWidth(20);  // Địa chỉ
                    $sheet->getColumnDimension('G')->setWidth(20);  // Nội dung
                    $sheet->getColumnDimension('H')->setWidth(15);  // Thành tiền

                    // Header row 1 - TRƯỜNG ĐẠI HỌC... và CỘNG HÒA...
                    $sheet->mergeCells('A1:D1');
                    $sheet->mergeCells('E1:H1');
                    $sheet->getStyle('A1')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
                    ]);
                    $sheet->getStyle('E1')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]
                    ]);

                    // Header row 2 - Đơn vị và Độc lập...
                    $sheet->mergeCells('A2:D2');
                    $sheet->mergeCells('E2:H2');
                    $sheet->getStyle('E2')->applyFromArray([
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]
                    ]);

                    // Title - GIẤY ĐỀ NGHỊ XUẤT HÓA ĐƠN
                    $sheet->mergeCells('A4:H4');
                    $sheet->getStyle('A4')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 16],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
                    ]);

                    // Kính gửi
                    $sheet->mergeCells('A6:H6');

                    // Tên đơn vị đề nghị
                    $sheet->mergeCells('A8:H8');

                    // Địa chỉ
                    $sheet->mergeCells('A9:H9');

                    // Đề nghị phòng
                    $sheet->mergeCells('A10:H10');

                    // Table header styling (row 12)
                    $sheet->getStyle('A12:H12')->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E6E6FA']
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                            ]
                        ],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
                    ]);

                    // Data rows styling (rows 13-16)
                    $sheet->getStyle('A13:H16')->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                            ]
                        ]
                    ]);

                    // Center align data in table
                    $sheet->getStyle('A13')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('H13')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                    // Footer row (Cộng) - row 18
                    $sheet->getStyle('A18:H18')->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                            ]
                        ],
                        'font' => ['bold' => true]
                    ]);

                    // Signature row - row 19
                    $sheet->getStyle('A19:H19')->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                            ]
                        ],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                        'font' => ['bold' => true]
                    ]);

                    // Set row heights for better spacing
                    $sheet->getRowDimension(3)->setRowHeight(10);  // Empty row
                    $sheet->getRowDimension(5)->setRowHeight(10);  // Empty row
                    $sheet->getRowDimension(7)->setRowHeight(10);  // Empty row
                    $sheet->getRowDimension(11)->setRowHeight(10); // Empty row
                    $sheet->getRowDimension(17)->setRowHeight(10); // Empty row

                    return [];
                }

                private function buildPersonalInvoiceData($invoiceData) {
                    // Tách họ và tên
                    $lastName = $this->getLastName($invoiceData['student_name']);
                    $firstName = $this->getFirstName($invoiceData['student_name']);

                    // Tạo nội dung học phí
                    $courseContent = 'Học phí lớp ' . $invoiceData['course_name'];

                    // Định dạng số tiền (giữ nguyên phần thập phân nếu có)
                    $courseFee = floatval($invoiceData['course_fee']);
                    $formattedAmount = number_format($courseFee, 2, ',', '.');
                    // Loại bỏ .00 nếu là số nguyên
                    if (fmod($courseFee, 1) == 0) {
                        $formattedAmount = number_format($courseFee, 0, ',', '.');
                    }

                    return [
                        // Row 1: Headers
                        ['TRƯỜNG ĐẠI HỌC KINH TẾ QUỐC DÂN', '', '', '', 'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM', '', '', ''],
                        // Row 2: Sub headers
                        ['Đơn vị: Trung tâm Đào tạo Liên tục', '', '', '', 'Độc lập - Tự do - Hạnh phúc', '', '', ''],
                        // Row 3: Empty
                        ['', '', '', '', '', '', '', ''],
                        // Row 4: Title
                        ['GIẤY ĐỀ NGHỊ XUẤT HÓA ĐƠN', '', '', '', '', '', '', ''],
                        // Row 5: Empty
                        ['', '', '', '', '', '', '', ''],
                        // Row 6: Kính gửi
                        ['Kính gửi: Ban giám hiệu Trường Đại học Kinh tế Quốc dân', '', '', '', '', '', '', ''],
                        // Row 7: Empty
                        ['', '', '', '', '', '', '', ''],
                        // Row 8: Tên đơn vị
                        ['Tên đơn vị đề nghị:', '', '', '', '', '', '', ''],
                        // Row 9: Địa chỉ
                        ['Địa chỉ: Trung tâm Đào tạo Liên tục - ĐH Kinh tế Quốc dân', '', '', '', '', '', '', ''],
                        // Row 10: Đề nghị
                        ['Đề nghị phòng TC-KT xuất cho chúng tôi hóa đơn GTGT với nội dung như sau:', '', '', '', '', '', '', ''],
                        // Row 11: Empty
                        ['', '', '', '', '', '', '', ''],
                        // Row 12: Table headers
                        ['TT', 'Họ', 'Tên', 'Tên đơn vị', 'Mã số thuế', 'Địa chỉ', 'Nội dung', 'Thành tiền'],
                        // Row 13: Data row với thông tin thực của học viên
                        ['1', $lastName, $firstName, 'Cá nhân học', '', $invoiceData['student_address'] ?: '', $courseContent, $formattedAmount],
                        // Row 14: Data row 2 (email)
                        ['', '', '', '(Nhập email)', '', '', '', ''],
                        // Row 15: Instruction row
                        ['', '', '', '', '', '', '', ''],
                        // Row 16: Empty data row
                        ['', '', '', '', '', '', '', ''],
                        // Row 17: Empty
                        ['', '', '', '', '', '', '', ''],
                        // Row 18: Cộng row
                        ['', 'Cộng', '', '', '', '', '-', $formattedAmount],
                        // Row 19: Signature row
                        ['Thủ trưởng đơn vị', '', 'Kế toán Trường', '', 'Xác nhận bộ phận', '', 'Người đề nghị thanh toán', '']
                    ];
                }

                private function getLastName($fullName) {
                    // Lấy họ và tên đệm (tất cả trừ từ cuối cùng)
                    $parts = explode(' ', trim($fullName));
                    if (count($parts) <= 1) {
                        return $fullName;
                    }
                    array_pop($parts); // Remove tên (từ cuối)
                    return implode(' ', $parts);
                }

                private function getFirstName($fullName) {
                    // Lấy tên (từ cuối cùng)
                    $parts = explode(' ', trim($fullName));
                    return end($parts) ?: '';
                }
            };

            // Tạo file Excel và trả về content
            return \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);

        } catch (\Exception $e) {
            Log::error('Create personal invoice Excel error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }
}
