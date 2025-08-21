<?php

namespace App\Services;

use App\Models\CourseItem;

class CourseHierarchyService
{
    /**
     * Lấy tất cả IDs của khóa học và các khóa học con (descendants)
     * 
     * @param int $courseItemId
     * @return array
     */
    public static function getAllCourseItemIds($courseItemId)
    {
        $courseItem = CourseItem::find($courseItemId);
        
        if (!$courseItem) {
            return [];
        }
        
        $courseItemIds = [$courseItem->id];
        
        // Thêm ID của tất cả các khóa học con
        foreach ($courseItem->descendants() as $descendant) {
            $courseItemIds[] = $descendant->id;
        }
        
        return $courseItemIds;
    }
    
    /**
     * Lấy tất cả IDs của khóa học và các khóa học cha (ancestors)
     * 
     * @param int $courseItemId
     * @return array
     */
    public static function getAllAncestorIds($courseItemId)
    {
        $courseItem = CourseItem::find($courseItemId);
        
        if (!$courseItem) {
            return [];
        }
        
        $courseItemIds = [$courseItem->id];
        
        // Thêm ID của tất cả các khóa học cha
        foreach ($courseItem->ancestors() as $ancestor) {
            $courseItemIds[] = $ancestor->id;
        }
        
        return $courseItemIds;
    }
    
    /**
     * Kiểm tra xem một khóa học có phải là khóa cha của khóa học khác không
     * 
     * @param int $parentId
     * @param int $childId
     * @return bool
     */
    public static function isParentOf($parentId, $childId)
    {
        $childCourse = CourseItem::find($childId);
        
        if (!$childCourse) {
            return false;
        }
        
        foreach ($childCourse->ancestors() as $ancestor) {
            if ($ancestor->id == $parentId) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Lấy thông tin hierarchy của khóa học
     * 
     * @param int $courseItemId
     * @return array
     */
    public static function getCourseHierarchyInfo($courseItemId)
    {
        $courseItem = CourseItem::find($courseItemId);
        
        if (!$courseItem) {
            return null;
        }
        
        $descendants = $courseItem->descendants();
        $ancestors = $courseItem->ancestors();
        
        return [
            'course' => $courseItem,
            'level' => $courseItem->level,
            'is_leaf' => $courseItem->is_leaf,
            'path' => $courseItem->path,
            'ancestors_count' => $ancestors->count(),
            'descendants_count' => $descendants->count(),
            'total_descendants_ids' => self::getAllCourseItemIds($courseItemId),
            'has_children' => $descendants->count() > 0,
            'is_root' => $courseItem->isRoot()
        ];
    }
    
    /**
     * Lấy tất cả học viên từ khóa học và các khóa con
     * 
     * @param int $courseItemId
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllStudentsFromCourseHierarchy($courseItemId, $filters = [])
    {
        $courseItemIds = self::getAllCourseItemIds($courseItemId);
        
        if (empty($courseItemIds)) {
            return collect();
        }
        
        $query = \App\Models\Student::whereHas('enrollments', function($q) use ($courseItemIds) {
            $q->whereIn('course_item_id', $courseItemIds);
        })->with(['enrollments' => function($q) use ($courseItemIds) {
            $q->whereIn('course_item_id', $courseItemIds)->with('courseItem');
        }, 'province', 'ethnicity']);
        
        // Apply additional filters
        if (!empty($filters['status'])) {
            $query->whereHas('enrollments', function($q) use ($filters, $courseItemIds) {
                $q->whereIn('course_item_id', $courseItemIds)
                  ->where('status', $filters['status']);
            });
        }
        
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        return $query->get();
    }
    
    /**
     * Lấy thống kê học viên theo hierarchy
     * 
     * @param int $courseItemId
     * @return array
     */
    public static function getCourseHierarchyStats($courseItemId)
    {
        $courseItemIds = self::getAllCourseItemIds($courseItemId);
        
        if (empty($courseItemIds)) {
            return [
                'total_courses' => 0,
                'total_students' => 0,
                'total_enrollments' => 0,
                'total_revenue' => 0
            ];
        }
        
        $enrollments = \App\Models\Enrollment::whereIn('course_item_id', $courseItemIds)->get();
        $students = \App\Models\Student::whereHas('enrollments', function($q) use ($courseItemIds) {
            $q->whereIn('course_item_id', $courseItemIds);
        })->get();
        
        return [
            'total_courses' => count($courseItemIds),
            'total_students' => $students->count(),
            'total_enrollments' => $enrollments->count(),
            'total_revenue' => $enrollments->sum('final_fee'),
            'course_breakdown' => \App\Models\CourseItem::whereIn('id', $courseItemIds)
                ->withCount('enrollments')
                ->get()
                ->map(function($course) {
                    return [
                        'id' => $course->id,
                        'name' => $course->name,
                        'path' => $course->path,
                        'level' => $course->level,
                        'enrollments_count' => $course->enrollments_count
                    ];
                })
        ];
    }
}
