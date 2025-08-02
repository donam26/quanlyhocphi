<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected $searchService;
    
    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
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
                'message' => 'Không tìm thấy thông tin học viên',
                'error' => $e->getMessage()
            ], 404);
        }
    }
} 