<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Attendance;

class CourseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'parent_id',
        'fee',
        'level',
        'is_leaf',
        'order_index',
        'active',
        'is_special',
        'custom_fields'
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'is_leaf' => 'boolean',
        'active' => 'boolean',
        'is_special' => 'boolean',
        'custom_fields' => 'array'
    ];

    /**
     * Lấy item cha
     */
    public function parent()
    {
        return $this->belongsTo(CourseItem::class, 'parent_id');
    }

    /**
     * Lấy các item con
     */
    public function children()
    {
        return $this->hasMany(CourseItem::class, 'parent_id')->orderBy('order_index');
    }

    /**
     * Lấy các item con đang hoạt động
     */
    public function activeChildren()
    {
        return $this->hasMany(CourseItem::class, 'parent_id')->where('active', true)->orderBy('order_index');
    }

    /**
     * Lấy danh sách các ghi danh vào khóa học này
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'course_item_id');
    }

    /**
     * Lấy danh sách chờ của khóa học
     */
    public function waitingList()
    {
        return $this->hasMany(Enrollment::class, 'course_item_id')
            ->where('status', 'waiting');
    }
    
    /**
     * Lấy danh sách lộ trình học tập của khóa học
     */
    public function learningPaths()
    {
        return $this->hasMany(LearningPath::class)->orderBy('order');
    }

    /**
     * Kiểm tra xem item có phải là nút gốc không
     */
    public function isRoot()
    {
        return $this->parent_id === null;
    }

    /**
     * Lấy tất cả tổ tiên của item này
     */
    public function ancestors()
    {
        $ancestors = collect([]);
        $parent = $this->parent;
        
        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }
        
        return $ancestors->reverse();
    }

    /**
     * Lấy tất cả hậu duệ của item này
     */
    public function descendants()
    {
        $descendants = $this->children;
        
        foreach ($this->children as $child) {
            $descendants = $descendants->merge($child->descendants());
        }
        
        return $descendants;
    }

    /**
     * Lấy đường dẫn đầy đủ (breadcrumb)
     */
    public function getPathAttribute()
    {
        $path = $this->ancestors()->pluck('name')->toArray();
        $path[] = $this->name;
        
        return implode(' > ', $path);
    }

    /**
     * Lấy các item cùng cấp
     */
    public function siblings()
    {
        if ($this->isRoot()) {
            return CourseItem::whereNull('parent_id')->where('id', '!=', $this->id)->get();
        }
        
        return CourseItem::where('parent_id', $this->parent_id)
                        ->where('id', '!=', $this->id)
                        ->get();
    }

    /**
     * Quan hệ với bảng điểm danh trực tiếp
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'course_item_id');
    }

    /**
     * Quan hệ với bảng lịch học
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'course_item_id');
    }

    /**
     * Lấy tất cả điểm danh cho khóa học này và các khóa học con
     */
    public function getAllAttendances()
    {
        // Lấy tất cả ID của khóa học này và các khóa học con
        $courseItemIds = [$this->id];
        
        // Thêm ID của tất cả các khóa học con
        foreach ($this->descendants() as $descendant) {
            $courseItemIds[] = $descendant->id;
        }
        
        // Trả về tất cả điểm danh liên quan đến các khóa học này
        return Attendance::whereIn('course_item_id', $courseItemIds)
            ->orWhereHas('enrollment', function ($query) use ($courseItemIds) {
                $query->whereIn('course_item_id', $courseItemIds);
            });
    }
}
