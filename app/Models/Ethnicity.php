<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ethnicity extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code'
    ];
    
    /**
     * Lấy tất cả học viên thuộc dân tộc này
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'nation', 'name');
    }
    
    /**
     * Scope tìm kiếm theo tên
     */
    public function scopeSearch($query, $term)
    {
        if ($term) {
            return $query->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
        }
        return $query;
    }
}
