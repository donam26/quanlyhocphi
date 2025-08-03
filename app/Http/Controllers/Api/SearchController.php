<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;
use App\Models\Student;

class SearchController extends Controller
{
    protected $searchService;
    
    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }
    
    /**
     * Tìm kiếm gợi ý tự động cho select2
     */
    public function autocomplete(Request $request)
    {
        $q = $request->get('q');
        
        if (empty($q) || strlen($q) < 2) {
            return response()->json([]);
        }
        
        $students = Student::where('full_name', 'like', "%{$q}%")
            ->orWhere('phone', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->limit(10)
            ->get();
        
        $results = $students->map(function ($student) {
            return [
                'id' => $student->id, 
                'text' => $student->full_name . ' - ' . $student->phone
            ];
        });
        
        return response()->json($results);
    }
    
    /**
     * Tìm kiếm chung
     */
    public function search(Request $request)
    {
        $term = $request->input('term');
        if (empty($term) || strlen($term) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Từ khóa tìm kiếm quá ngắn',
                'data' => []
            ], 400);
        }

        $studentId = $request->input('student_id');
        $result = $this->searchService->searchStudents($term, $studentId);
        
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
    
    /**
     * Lấy lịch sử học viên
     */
    public function studentHistory($studentId)
    {
        try {
            $data = $this->searchService->getStudentHistory($studentId);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy học viên hoặc có lỗi xảy ra: ' . $e->getMessage()
            ], 404);
        }
    }
} 