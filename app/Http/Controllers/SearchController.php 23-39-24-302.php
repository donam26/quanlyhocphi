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
     * API: Autocomplete tìm kiếm học viên
     */
    public function autocomplete(Request $request)
    {
        $term = $request->get('q', '');
        
        if (strlen($term) < 2) {
            return response()->json([]);
        }
        
        $students = \App\Models\Student::search($term)
            ->select('id', 'first_name', 'last_name', 'phone', 'email')
            ->limit(10)
            ->get();
        
        $results = $students->map(function ($student) {
            return [
                'id' => $student->id,
                'text' => $student->full_name . ' - ' . $student->phone,
                'full_name' => $student->full_name,
                'phone' => $student->phone,
                'email' => $student->email
            ];
        });
        
        return response()->json($results);
    }
    
    /**
     * API: Lấy chi tiết học viên cho modal
     */
    public function getStudentDetails(Request $request)
    {
        $term = $request->input('term');
        $studentId = $request->input('student_id');
        
        if (!$term && !$studentId) {
            return response()->json(['error' => 'Thiếu thông tin tìm kiếm'], 400);
        }
        
        $result = $this->searchService->searchStudents($term, $studentId);
        
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
    
    /**
     * API: Lấy lịch sử học viên cho modal
     */
    public function getStudentHistory($studentId)
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
                'message' => 'Không tìm thấy thông tin học viên'
            ], 404);
        }
    }
    
    /**
     * Hiển thị lịch sử của học viên (legacy - giữ lại cho backward compatibility)
     */
    public function studentHistory($studentId)
    {
        $data = $this->searchService->getStudentHistory($studentId);
        
        return view('search.student-history', $data);
    }
}
