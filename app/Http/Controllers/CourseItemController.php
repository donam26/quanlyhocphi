<?php

namespace App\Http\Controllers;

use App\Models\CourseItem;
use App\Models\Classes;
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
        
        // Nếu là nút lá, lấy các lớp học liên quan
        $classes = null;
        if ($courseItem->is_leaf) {
            $classes = Classes::where('course_item_id', $id)
                            ->orderBy('status')
                            ->orderBy('batch_number')
                            ->get();
        }
        
        // Lấy đường dẫn từ gốc đến item này
        $breadcrumbs = $courseItem->ancestors()->push($courseItem);
        
        return view('course-items.show', compact('courseItem', 'classes', 'breadcrumbs'));
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
        
        // Nếu là nút lá, xóa các lớp học liên quan
        if ($courseItem->is_leaf) {
            $classes = $courseItem->classes;
            foreach ($classes as $class) {
                // Xóa tất cả các ghi danh và thanh toán liên quan
                foreach ($class->enrollments as $enrollment) {
                    // Xóa các thanh toán liên quan
                    $enrollment->payments()->delete();
                    
                    // Xóa các điểm danh liên quan
                    $enrollment->attendances()->delete();
                    
                    // Xóa ghi danh
                    $enrollment->delete();
                }
                
                // Xóa lớp học
                $class->delete();
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
}