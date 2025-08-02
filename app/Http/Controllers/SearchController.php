<?php

namespace App\Http\Controllers;

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
     * Hiển thị trang tìm kiếm
     */
    public function index()
    {
        return view('search.index');
    }
    
    /**
     * Xử lý tìm kiếm và hiển thị kết quả
     */
    public function search(Request $request)
    {
        // Validate dữ liệu nếu không có student_id
        if (!$request->filled('student_id') && !$request->filled('term')) {
            $request->validate([
                'term' => 'required|min:2',
            ]);
        }
        
        $term = $request->input('term');
        $studentId = $request->input('student_id');
        
        $result = $this->searchService->searchStudents($term, $studentId);
        
        return view('search.index', $result);
    }
    
    /**
     * Hiển thị lịch sử của học viên
     */
    public function studentHistory($studentId)
    {
        $data = $this->searchService->getStudentHistory($studentId);
        
        return view('search.student-history', $data);
    }
}
