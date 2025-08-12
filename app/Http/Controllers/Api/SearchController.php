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
        $type = $request->get('type', 'student'); // Mặc định tìm học viên
        $limit = $request->get('limit', 10);

        // Nếu không có query và yêu cầu preload, trả về một số kết quả mặc định
        if (empty($q)) {
            if ($request->get('preload') === 'true') {
                return $this->getPreloadData($type, $limit);
            }
            return response()->json([]);
        }

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        switch ($type) {
            case 'course':
                return $this->searchCourses($q, $limit);
            case 'student':
            default:
                return $this->searchStudents($q, $limit);
        }
    }

    /**
     * Tìm kiếm học viên
     */
    private function searchStudents($q, $limit = 10)
    {
        $students = Student::where('first_name', 'like', "%{$q}%")
            ->orWhere('last_name', 'like', "%{$q}%")
            ->orWhereRaw("CONCAT(IFNULL(first_name, ''), ' ', IFNULL(last_name, '')) LIKE ?", ["%{$q}%"])
            ->orWhere('phone', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->limit($limit)
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
     * Tìm kiếm khóa học
     */
    private function searchCourses($q, $limit = 10)
    {
        $courses = \App\Models\CourseItem::where('active', true)
            ->where('name', 'like', "%{$q}%")
            ->limit($limit)
            ->get();

        $results = $courses->map(function ($course) {
            return [
                'id' => $course->id,
                'text' => $course->name,
                'name' => $course->name,
                'fee' => $course->fee
            ];
        });

        return response()->json($results);
    }

    /**
     * Lấy dữ liệu preload cho Select2
     */
    private function getPreloadData($type, $limit = 10)
    {
        switch ($type) {
            case 'course':
                $courses = \App\Models\CourseItem::where('active', true)
                    ->where('is_leaf', true)
                    ->orderBy('name')
                    ->limit($limit)
                    ->get();

                $results = $courses->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'text' => $course->name,
                        'name' => $course->name,
                        'fee' => $course->fee
                    ];
                });
                break;

            case 'student':
            default:
                $students = Student::orderBy('created_at', 'desc')
                    ->limit($limit)
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
                break;
        }

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
     * API: Lấy chi tiết học viên cho modal
     */
    public function getStudentDetails(Request $request)
    {
        $term = $request->input('term');
        $studentId = $request->input('student_id');
        
        if (!$term && !$studentId) {
            return response()->json([
                'success' => false,
                'message' => 'Thiếu thông tin tìm kiếm'
            ], 400);
        }
        
        try {
            $result = $this->searchService->searchStudents($term, $studentId);
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tìm kiếm: ' . $e->getMessage()
            ], 500);
        }
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
                'message' => 'Không tìm thấy học viên hoặc có lỗi xảy ra: ' . $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Lấy lịch sử học viên (legacy method - giữ lại cho backward compatibility)
     */
    public function studentHistory($studentId)
    {
        return $this->getStudentHistory($studentId);
    }
} 