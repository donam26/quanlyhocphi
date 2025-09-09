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
            ->where('status', 'active')
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
        
        // Xử lý các trường thông tin tùy chỉnh
        $customFields = [];
        if (isset($data['is_special']) && $data['is_special']) {
            // Tự động thêm các trường mặc định cho khóa học đặc biệt
            $defaultFields = [
                'Đơn vị công tác' => '',
                'Bằng cấp' => '',
                'Chuyên môn công tác' => '',
                'Số năm kinh nghiệm' => '',
                'Hồ sơ bản cứng' => ''
            ];

            $customFields = $defaultFields;

            // Thêm các trường tùy chỉnh khác nếu có
            if (isset($data['custom_field_keys'])) {
                $keys = $data['custom_field_keys'];

                foreach ($keys as $key) {
                    if (!empty($key) && !array_key_exists($key, $defaultFields)) {
                        $customFields[$key] = ""; // Lưu với giá trị rỗng
                    }
                }
            }
        }
        
        $courseItemData = [
            'name' => $data['name'],
            'parent_id' => $parentId,
            'fee' => $fee,
            'level' => $level,
            'is_leaf' => $data['is_leaf'] ?? false,
            'order_index' => $maxOrder + 1,
            'status' => $data['status'] ?? 'active',
            'is_special' => $data['is_special'] ?? false,
            'custom_fields' => !empty($customFields) ? $customFields : null,
        ];
        
        return CourseItem::create($courseItemData);
    }

    public function updateCourseItem(CourseItem $courseItem, array $data)
    {
        // Xác định parent_id dựa vào dữ liệu gửi lên
        // Nếu parent_id là empty string hoặc null, đặt thành null (khóa chính)
        $parentId = isset($data['parent_id']) && $data['parent_id'] !== '' ? $data['parent_id'] : null;
        
        // Xử lý các trường thông tin tùy chỉnh
        $customFields = [];
        if (isset($data['is_special']) && $data['is_special']) {
            // Tự động thêm các trường mặc định cho khóa học đặc biệt
            $defaultFields = [
                'Đơn vị công tác' => '',
                'Bằng cấp' => '',
                'Chuyên môn công tác' => '',
                'Số năm kinh nghiệm' => '',
                'Hồ sơ bản cứng' => ''
            ];

            $customFields = $defaultFields;

            // Thêm các trường tùy chỉnh khác nếu có
            if (isset($data['custom_field_keys'])) {
                $keys = $data['custom_field_keys'];

                foreach ($keys as $key) {
                    if (!empty($key) && !array_key_exists($key, $defaultFields)) {
                        $customFields[$key] = ""; // Lưu với giá trị rỗng
                    }
                }
            }
        }
        
        // Xác định level dựa trên parent_id
        $level = 1; // Mặc định là cấp cao nhất (khóa chính)
        
        if ($parentId) {
            $parentItem = CourseItem::findOrFail($parentId);
            $level = $parentItem->level + 1;
        }
        
        // Nếu không phải nút lá, đảm bảo fee = 0
        $fee = ($data['is_leaf'] ?? false) ? ($data['fee'] ?? 0) : 0;
        
        $updateData = [
            'name' => $data['name'],
            'parent_id' => $parentId,
            'fee' => $fee,
            'level' => $level,
            'is_leaf' => $data['is_leaf'] ?? false,
            'status' => $data['status'] ?? 'active',
            'is_special' => $data['is_special'] ?? false,
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
            ->where('status', 'active');
                          
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

            // Xóa mềm đệ quy từng con
            foreach ($children as $child) {
                $this->deleteRecursively($child);
            }

            // Xóa mềm các ghi danh liên quan. Vì Enrollment, Payment, Attendance
            // đã dùng SoftDeletes, các lệnh delete() sẽ là xóa mềm.
            $enrollments = Enrollment::where('course_item_id', $courseItem->id)->get();
            foreach ($enrollments as $enrollment) {
                $enrollment->payments()->delete();
                $enrollment->attendances()->delete();
                $enrollment->delete();
            }

            // Cuối cùng, xóa mềm khóa học
            $courseItem->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
} 