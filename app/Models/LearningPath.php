<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningPath extends Model
{
    use HasFactory;
    
    protected $fillable = ['course_item_id', 'title', 'description', 'order'];
    
    public function courseItem()
    {
        return $this->belongsTo(CourseItem::class);
    }
    
    public function progress()
    {
        return $this->hasMany(LearningPathProgress::class);
    }
}
