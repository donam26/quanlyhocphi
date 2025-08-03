<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'region'
    ];
    
    /**
     * Lấy tất cả học viên thuộc tỉnh này
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }
    
    /**
     * Lấy tên hiển thị của khu vực
     */
    public function getRegionNameAttribute()
    {
        return match($this->region) {
            'north' => 'Miền Bắc',
            'central' => 'Miền Trung',
            'south' => 'Miền Nam',
            default => 'Không xác định',
        };
    }
    
    /**
     * Scope lọc theo miền
     */
    public function scopeNorthern($query)
    {
        return $query->where('region', 'north');
    }
    
    /**
     * Scope lọc theo miền Trung
     */
    public function scopeCentral($query)
    {
        return $query->where('region', 'central');
    }
    
    /**
     * Scope lọc theo miền Nam
     */
    public function scopeSouthern($query)
    {
        return $query->where('region', 'south');
    }
}
