<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseItem;
use App\Models\Classes;
use App\Models\LearningPath;
use App\Enums\EnrollmentStatus;
use App\Enums\CourseStatus;
use App\Services\LearningPathService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CourseItemController extends Controller
{
    protected $learningPathService;

    public function __construct(LearningPathService $learningPathService)
    {
        $this->learningPathService = $learningPathService;
    }

    /**
     * Lấy danh sách các item cấp cao nhất
     */
    public function index(Request $request)
    {
        $query = CourseItem::query();
        
        // Lọc theo parent_id nếu có
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } else {
            $query->whereNull('parent_id');
        }
        
        // Lọc theo active
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }
        
        $items = $query->orderBy('order_index')->get();
        
        return response()->json($items);
    }

    /**
     * Lưu item mới
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:course_items,id',
            'description' => 'nullable|string',
            'fee' => 'nullable|numeric|min:0',
            'code' => 'nullable|string|max:50',
            'active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
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
        
        $courseItem = CourseItem::create([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'fee' => $request->fee,
            'level' => $level,
            'is_leaf' => $isLeaf,
            'code' => $request->code,
            'order_index' => $maxOrder + 1,
            'active' => $request->active ?? true,
        ]);
        
        return response()->json($courseItem, 201);
    }

        /**
     * Hiển thị chi tiết một item
     */
    public function show($id)
    {
        $courseItem = CourseItem::with([
            'children' => function($query) {
                $query->orderBy('order_index');
            },
            'learningPaths' => function($query) {
                $query->orderBy('order');
            }
        ])->findOrFail($id);
        
        // Đảm bảo custom_fields được trả về
        if (!$courseItem->is_special || empty($courseItem->custom_fields)) {
            $courseItem->custom_fields = [];
        }
        
        // Tính toán thống kê enrollment nếu là khóa học lá
        $enrollmentCount = 0;
        $totalRevenue = 0;
        
        if ($courseItem->is_leaf) {
            $enrollmentCount = $courseItem->enrollments()
                ->whereNotIn('status', [EnrollmentStatus::CANCELLED])
                ->count();
                
            $totalRevenue = $courseItem->enrollments()
                ->whereNotIn('status', [EnrollmentStatus::CANCELLED])
                ->with('payments')
                ->get()
                ->sum(function($enrollment) {
                    return $enrollment->payments->where('status', 'confirmed')->sum('amount');
                });
        }
        
        // Tạo đường dẫn breadcrumb
        $path = '';
        if ($courseItem->parent_id) {
            $ancestors = [];
            $current = $courseItem->parent;
            while ($current) {
                array_unshift($ancestors, $current->name);
                $current = $current->parent;
            }
            $path = implode(' / ', $ancestors);
        }
        
        // Chuẩn bị dữ liệu learning paths
        $learningPaths = $courseItem->learningPaths->map(function($path) {
            return [
                'id' => $path->id,
                'title' => $path->title,
                'description' => $path->description,
                'order' => $path->order,
                'is_completed' => $path->is_completed ?? false
            ];
        });

        // Tính số lộ trình đã hoàn thành
        $completedPathsCount = $learningPaths->where('is_completed', true)->count();
        $totalPathsCount = $learningPaths->count();

        return response()->json([
            'id' => $courseItem->id,
            'name' => $courseItem->name,
            'description' => $courseItem->description,
            'code' => $courseItem->code,
            'fee' => $courseItem->fee,
            'level' => $courseItem->level,
            'is_leaf' => $courseItem->is_leaf,
            'is_special' => $courseItem->is_special,
            'active' => $courseItem->active,
            'status' => $courseItem->status,
            'status_badge' => $courseItem->status_badge,
            'custom_fields' => $courseItem->custom_fields,
            'enrollment_count' => $enrollmentCount,
            'total_revenue' => $totalRevenue,
            'path' => $path,
            'children' => $courseItem->children,
            'learning_paths' => $learningPaths,
            'learning_paths_count' => $totalPathsCount,
            'learning_paths_completed' => $completedPathsCount,
        ]);
    }

    /**
     * Cập nhật item
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:course_items,id',
            'description' => 'nullable|string',
            'fee' => 'nullable|numeric|min:0',
            'code' => 'nullable|string|max:50',
            'active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $courseItem = CourseItem::findOrFail($id);
        
        // Kiểm tra không cho phép item là cha của chính nó
        if ($request->parent_id == $id) {
            return response()->json(['error' => 'Không thể chọn chính nó làm cha'], 422);
        }
        
        // Kiểm tra không cho phép chọn con làm cha
        $descendants = $courseItem->descendants()->pluck('id')->toArray();
        if (in_array($request->parent_id, $descendants)) {
            return response()->json(['error' => 'Không thể chọn con làm cha'], 422);
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
        
        $courseItem->update([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'fee' => $request->fee,
            'level' => $level,
            'is_leaf' => $isLeaf,
            'code' => $request->code,
            'active' => $request->active ?? $courseItem->active,
        ]);
        
        return response()->json($courseItem);
    }

    /**
     * Xóa item
     */
    public function destroy($id)
    {
        $courseItem = CourseItem::findOrFail($id);
        
        // Kiểm tra xem có item con không
        if ($courseItem->children()->count() > 0) {
            return response()->json(['error' => 'Không thể xóa vì còn chứa khóa con'], 422);
        }
        
        // Kiểm tra xem có lớp học liên quan không
        if ($courseItem->is_leaf && $courseItem->classes()->count() > 0) {
            return response()->json(['error' => 'Không thể xóa vì có lớp học liên quan'], 422);
        }
        
        $courseItem->delete();
        
        return response()->json(['message' => 'Đã xóa thành công']);
    }
    
    /**
     * Hiển thị cấu trúc cây khóa học
     */
    public function tree()
    {
        $rootItems = CourseItem::whereNull('parent_id')
                            ->where('active', true)
                            ->orderBy('order_index')
                            ->get();
                            
        $treeData = [];
        foreach ($rootItems as $item) {
            $treeData[] = $this->buildTreeNode($item);
        }
        
        return response()->json($treeData);
    }
    
    /**
     * Xây dựng cây đệ quy từ một nút
     */
    private function buildTreeNode(CourseItem $item)
    {
        $node = [
            'id' => $item->id,
            'name' => $item->name,
            'url' => route('course-items.tree', ['newly_added_id' => $item->id]),
            'code' => $item->code,
            'fee' => $item->fee,
            'is_leaf' => $item->is_leaf,
            'children' => []
        ];
        
        // Nếu không phải là nút lá, lấy các con
        if (!$item->is_leaf) {
            $children = $item->activeChildren()->get();
            foreach ($children as $child) {
                $node['children'][] = $this->buildTreeNode($child);
            }
        }
        
        return $node;
    }

    /**
     * Lấy danh sách các khóa học có thể đăng ký (các lớp, không phải danh mục)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function available()
    {
        try {
            $courseItems = CourseItem::where('is_leaf', true)
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'fee']);
            
            return response()->json([
                'success' => true,
                'data' => $courseItems
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
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
     * Tìm kiếm khóa học
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $rootId = $request->get('root_id');
        $limit = $request->get('limit', 10);

        $searchQuery = CourseItem::where('active', true);

        // Nếu có query, tìm kiếm theo tên
        if (!empty($query) && strlen($query) >= 2) {
            $searchQuery->where('name', 'like', "%{$query}%");
        } else if (empty($query)) {
            // Nếu không có query, lấy các khóa học lá (có thể đăng ký) mặc định
            $searchQuery->where('is_leaf', true);
        } else {
            // Query quá ngắn, trả về rỗng
            return response()->json([]);
        }

        // Nếu có root_id, chỉ tìm trong phạm vi đó
        if ($rootId) {
            // Lấy tất cả descendants của root_id
            $rootItem = CourseItem::find($rootId);
            if ($rootItem) {
                $descendantIds = $rootItem->descendants()->pluck('id')->toArray();
                $descendantIds[] = $rootId; // Bao gồm cả root item

                $searchQuery->whereIn('id', $descendantIds);
            }
        }

        $results = $searchQuery->orderBy('name')
                              ->limit($limit)
                              ->get()
                              ->map(function($item) {
                                  return [
                                      'id' => $item->id,
                                      'text' => $item->name,
                                      'name' => $item->name,
                                      'path' => $this->getCoursePath($item),
                                      'is_leaf' => $item->is_leaf,
                                      'fee' => $item->fee
                                  ];
                              });

        return response()->json($results);
    }

    /**
     * Tìm kiếm khóa học chỉ những khóa đang học (status = active)
     */
    public function searchActiveCourses(Request $request)
    {
        $query = $request->get('q', '');
        $rootId = $request->get('root_id');
        $preload = $request->get('preload', 'false');

        // Nếu là preload (chưa search), trả về một số khóa học mặc định
        if ($preload === 'true' || (empty($query) && $preload !== 'false')) {
            $searchQuery = CourseItem::where('active', true)
                                    ->where('status', CourseStatus::ACTIVE->value)
                                    ->where('is_leaf', true);
        } else if (strlen($query) < 2) {
            return response()->json([]);
        } else {
            $searchQuery = CourseItem::where('active', true)
                                    ->where('status', CourseStatus::ACTIVE->value)
                                    ->where('is_leaf', true)
                                    ->where('name', 'like', "%{$query}%");
        }

        // Nếu có root_id, chỉ tìm trong phạm vi đó
        if ($rootId) {
            // Lấy tất cả descendants của root_id
            $rootItem = CourseItem::find($rootId);
            if ($rootItem) {
                $descendantIds = $rootItem->descendants()->pluck('id')->toArray();
                $descendantIds[] = $rootId; // Bao gồm cả root item

                $searchQuery->whereIn('id', $descendantIds);
            }
        }

        // Giới hạn số lượng kết quả: preload ít hơn, search nhiều hơn
        $limit = ($preload === 'true' || empty($query)) ? 10 : 20;

        $results = $searchQuery->orderBy('name')
                              ->limit($limit)
                              ->get()
                              ->map(function($item) {
                                  return [
                                      'id' => $item->id,
                                      'text' => $item->name,
                                      'name' => $item->name,
                                      'path' => $this->getCoursePath($item),
                                      'is_leaf' => $item->is_leaf,
                                      'fee' => $item->fee,
                                      'status' => $item->status->value,
                                      'status_label' => $item->status->label(),
                                      'status_badge' => $item->status_badge
                                  ];
                              });

        return response()->json($results);
    }

    /**
     * Lấy danh sách khóa học đang học (cho dropdown)
     */
    public function getActiveLeafCourses()
    {
        $courses = CourseItem::where('is_leaf', true)
                            ->where('active', true)
                            ->where('status', CourseStatus::ACTIVE->value) // Chỉ lấy khóa đang học
                            ->orderBy('name')
                            ->get()
                            ->map(function($course) {
                                return [
                                    'id' => $course->id,
                                    'name' => $course->name,
                                    'path' => $this->getCoursePath($course),
                                    'fee' => $course->fee,
                                    'status' => $course->status->value,
                                    'status_label' => $course->status->label()
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
     * Lấy danh sách lộ trình học tập của khóa học
     */
    public function getLearningPaths($id)
    {
        try {
            $courseItem = CourseItem::findOrFail($id);
            $paths = $this->learningPathService->getLearningPathsByCourse($courseItem);
            
            return response()->json([
                'success' => true,
                'course_name' => $courseItem->name,
                'paths' => $paths->map(function($path) {
                    return [
                        'id' => $path->id,
                        'title' => $path->title,
                        'description' => $path->description,
                        'order' => $path->order,
                        'is_required' => $path->is_required,
                        'is_completed' => $path->is_completed
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Learning paths get error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải lộ trình: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lưu lộ trình học tập cho khóa học
     */
    public function saveLearningPaths(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'paths' => 'required|array',
                'paths.*.id' => 'nullable|exists:learning_paths,id',
                'paths.*.title' => 'required|string|max:255',
                'paths.*.description' => 'nullable|string',
                'paths.*.order' => 'required|integer|min:1',
                'paths.*.is_required' => 'required|boolean'
            ]);

            $courseItem = CourseItem::findOrFail($id);
            
            // Xóa các lộ trình không còn trong danh sách
            $existingPathIds = collect($validated['paths'])->pluck('id')->filter()->toArray();
            $currentPaths = $this->learningPathService->getLearningPathsByCourse($courseItem);
            
            foreach ($currentPaths as $path) {
                if (!in_array($path->id, $existingPathIds)) {
                    $this->learningPathService->deleteLearningPath($path);
                }
            }
                
            // Cập nhật hoặc tạo mới lộ trình
            foreach ($validated['paths'] as $pathData) {
                if (!empty($pathData['id'])) {
                    // Cập nhật lộ trình hiện có
                    $learningPath = $this->learningPathService->getLearningPath($pathData['id']);
                    $this->learningPathService->updateLearningPath($learningPath, [
                        'title' => $pathData['title'],
                        'description' => $pathData['description'] ?? null,
                        'order' => $pathData['order'],
                        'is_required' => $pathData['is_required']
                    ]);
                } else {
                    // Tạo mới lộ trình
                    $this->learningPathService->createLearningPath([
                        'course_item_id' => $courseItem->id,
                        'title' => $pathData['title'],
                        'description' => $pathData['description'] ?? null,
                        'order' => $pathData['order'],
                        'is_required' => $pathData['is_required']
                    ]);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Đã lưu lộ trình học tập thành công!'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Learning paths save error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lưu lộ trình: ' . $e->getMessage()
            ], 500);
        }
    }
} 