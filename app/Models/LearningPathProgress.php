<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningPathProgress extends Model
{
    use HasFactory;
    
    protected $table = 'learning_path_progress';
    
    protected $fillable = ['learning_path_id', 'enrollment_id', 'is_completed', 'completed_at'];
    
    public function learningPath()
    {
        return $this->belongsTo(LearningPath::class);
    }
    
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}
