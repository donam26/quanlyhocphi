<?php

namespace App\Http\Controllers;

use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\LearningPathProgress;
use App\Models\Student;
use App\Services\LearningProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearningProgressController extends Controller
{
    protected $learningProgressService;

    public function __construct(LearningProgressService $learningProgressService)
    {
        $this->learningProgressService = $learningProgressService;
    }

    /**
     * Hiển thị trang quản lý tiến độ học tập
     */
    public function index(Request $request)
    {
        $incompleteCoursesData = $this->learningProgressService->getIncompleteCoursesData();
        
        return view('learning-progress.index', compact('incompleteCoursesData'));
    }
}
