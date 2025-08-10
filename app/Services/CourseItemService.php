<?php

namespace App\Services;

use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\LearningPathProgress;
use Illuminate\Support\Facades\DB;

class CourseItemService
{
    public function getCourseItems()
    {
        return CourseItem::all();
    }

    public function getRootCourseItems()
    {
        return CourseItem::whereNull('parent_id')
            ->where('active', true)
            ->orderBy('order_index')
            ->get();
    }

    public function getCourseItem($id)
    {
        return CourseItem::findOrFail($id);
    }

    public function getCourseItemWithRelations($id, array $relations = [])
    {
        return CourseItem::with($relations)->findOrFail($id);
    }

    public function createCourseItem(array $data)
    {
        // Xác định level dựa trên parent_id
        $level = 1; // Mặc định là cấp cao nhất
        $isLeaf = false;
        
        // Sử dụng null coalescing operator để tránh lỗi undefined key
        $parentId = $data['parent_id'] ?? null;
        
        if ($parentId) {
            $parentItem = CourseItem::findOrFail($parentId);
            $level = $parentItem->level + 1;
            
            // Nếu có giá tiền, đánh dấu là nút lá
            if (isset($data['fee']) && $data['fee'] > 0) {
                $isLeaf = true;
            }
        }
        
        // Lấy order_index cao nhất trong cùng cấp và parent
        $maxOrder = CourseItem::where('level', $level)
                        ->when($parentId, function($query) use ($parentId) {
                            return $query->where('parent_id', $parentId);
                        })
                        ->max('order_index') ?? 0;
        
        // Nếu không phải nút lá, đảm bảo fee = 0
        $fee = ($data['is_leaf'] ?? false) ? ($data['fee'] ?? 0) : 0;
        
        $courseItemData = [
            'name' => $data['name'],
            'parent_id' => $parentId,
            'fee' => $fee,
            'level' => $level,
            'is_leaf' => $data['is_leaf'] ?? false,
            'order_index' => $maxOrder + 1,
            'active' => $data['active'] ?? true,
        ];
        
        return CourseItem::create($courseItemData);
    }

    public function updateCourseItem(CourseItem $courseItem, array $data)
    {
        // Xác định parent_id dựa vào dữ liệu gửi lên (không còn make_root)
        $parentId = $data['parent_id'] ?? $courseItem->parent_id;
        
        // Xử lý các trường thông tin tùy chỉnh
        $customFields = [];
        if (isset($data['is_special']) && $data['is_special'] && isset($data['custom_field_keys'])) {
            $keys = $data['custom_field_keys'];
            
            foreach ($keys as $key) {
                if (!empty($key)) {
                    $customFields[$key] = ""; // Lưu với giá trị rỗng
                }
            }
        }
        
        // Xác định level dựa trên parent_id
        $level = 1; // Mặc định là cấp cao nhất
        $isLeaf = $courseItem->is_leaf; // Giữ nguyên trạng thái leaf
        
        if ($parentId) {
            $parentItem = CourseItem::findOrFail($parentId);
            $level = $parentItem->level + 1;
            
            // Nếu có giá tiền, đánh dấu là nút lá
            if (isset($data['fee']) && $data['fee'] > 0) {
                $isLeaf = true;
            }
        }
        
        // Nếu không phải nút lá, đảm bảo fee = 0
        $fee = ($data['is_leaf'] ?? false) ? ($data['fee'] ?? 0) : 0;
        
        $updateData = [
            'name' => $data['name'],
            'parent_id' => $parentId,
            'fee' => $fee,
            'level' => $level,
            'is_leaf' => $data['is_leaf'] ?? false,
            'active' => true,
            'is_special' => true,
            'custom_fields' => !empty($customFields) ? $customFields : null,
        ];
        
        $courseItem->update($updateData);

        // Cập nhật các trường thông tin tùy chỉnh cho enrollments hiện tại nếu khóa học đặc biệt
        if (isset($data['is_special']) && $data['is_special'] && !empty($customFields)) {
            $enrollments = $courseItem->enrollments;
            foreach ($enrollments as $enrollment) {
                $enrollment->update([
                    'custom_fields' => $customFields
                ]);
            }
        }
        
        return $courseItem;
    }

    public function deleteCourseItem(CourseItem $courseItem)
    {
        // Xóa đệ quy tất cả các khóa con và lớp học liên quan
        return $this->deleteRecursively($courseItem);
    }

    public function updateCourseItemOrder(array $items)
    {
        foreach ($items as $item) {
            CourseItem::where('id', $item['id'])->update(['order_index' => $item['order']]);
        }
        
        return true;
    }

    public function searchCourseItems($term, $rootId = null)
    {
        $query = CourseItem::where('name', 'like', "%{$term}%")
            ->where('active', true);
                          
        if ($rootId) {
            $rootItem = CourseItem::findOrFail($rootId);
            $ids = [$rootId];
            $this->getAllChildrenIds($rootItem, $ids);
            
            $query->whereIn('id', $ids);
        }
        
        $courses = $query->orderBy('level')
            ->orderBy('order_index')
            ->limit(10)
            ->get();
        
        return $courses->map(function($course) {
            $path = $this->getCoursePath($course);
            
            return [
                'id' => $course->id,
                'text' => $course->name,
                'path' => $path,
                'is_leaf' => $course->is_leaf,
                'level' => $course->level,
                'fee' => $course->fee
            ];
        });
    }

    // Phương thức hỗ trợ
    private function getCoursePath($course)
    {
        $path = [];
        $current = $course;
        
        while ($current->parent_id) {
            $current = $current->parent;
            $path[] = $current->name;
        }
        
        return implode(' > ', array_reverse($path));
    }

    public function getAllChildrenIds($courseItem, &$ids)
    {
        foreach ($courseItem->children as $child) {
            $ids[] = $child->id;
            if ($child->children->count() > 0) {
                $this->getAllChildrenIds($child, $ids);
            }
        }
    }

    private function deleteRecursively($courseItem)
    {
        DB::beginTransaction();
        
        try {
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
                $enrollments = Enrollment::where('course_item_id', $courseItem->id)->get();
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
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
} 