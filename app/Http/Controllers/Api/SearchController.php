<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\Payment;
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
     * API: Tìm kiếm tự động hoàn thành
     */
    public function autocomplete(Request $request)
    {
        try {
            $term = $request->input('term', '');
            $type = $request->input('type', 'all'); // all, students, courses
            
            $results = [];
            
            if ($type === 'all' || $type === 'students') {
                $students = Student::search($term)->limit(10)->get();
                $results['students'] = $students->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'type' => 'student',
                        'label' => $student->full_name,
                        'phone' => $student->phone,
                        'email' => $student->email
                    ];
                });
            }
            
            if ($type === 'all' || $type === 'courses') {
                $courses = CourseItem::where('name', 'like', "%{$term}%")
                    ->where('status', 'active')
                    ->limit(10)
                    ->get();
                $results['courses'] = $courses->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'type' => 'course',
                        'label' => $course->name,
                        'path' => $course->path
                    ];
                });
            }
            
            return response()->json([
                'success' => true,
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tìm kiếm: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Tìm kiếm chi tiết
     */
    public function details(Request $request)
    {
        try {
            $term = $request->input('term', '');
            $filters = $request->input('filters', []);
            
            $results = $this->searchService->search($term, $filters);
            
            return response()->json([
                'success' => true,
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tìm kiếm chi tiết: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lịch sử học viên
     */
    public function studentHistory(Student $student)
    {
        try {
            $enrollments = $student->enrollments()
                ->with(['courseItem', 'payments'])
                ->orderBy('enrollment_date', 'desc')
                ->get();
            
            $payments = $student->payments()
                ->with(['enrollment.courseItem'])
                ->orderBy('payment_date', 'desc')
                ->get();
            
            $attendances = $student->attendances()
                ->with(['enrollment.courseItem'])
                ->orderBy('attendance_date', 'desc')
                ->limit(50)
                ->get();
            
            $summary = [
                'total_enrollments' => $enrollments->count(),
                'active_enrollments' => $enrollments->where('status', 'active')->count(),
                'completed_enrollments' => $enrollments->where('status', 'completed')->count(),
                'total_paid' => $payments->where('status', 'confirmed')->sum('amount'),
                'total_fee' => $enrollments->sum('final_fee'),
                'attendance_rate' => $attendances->count() > 0 ? 
                    round(($attendances->where('status', 'present')->count() / $attendances->count()) * 100, 2) : 0
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'student' => $student,
                    'enrollments' => $enrollments,
                    'payments' => $payments,
                    'recent_attendances' => $attendances,
                    'summary' => $summary
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy lịch sử học viên: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Tìm kiếm nâng cao
     */
    public function advanced(Request $request)
    {
        try {
            $filters = $request->validate([
                'student_name' => 'nullable|string',
                'student_phone' => 'nullable|string',
                'course_name' => 'nullable|string',
                'enrollment_status' => 'nullable|string',
                'payment_status' => 'nullable|string',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'province_id' => 'nullable|exists:provinces,id',
                'education_level' => 'nullable|string'
            ]);
            
            $query = Student::with(['enrollments.courseItem', 'enrollments.payments']);
            
            // Filter by student name
            if (!empty($filters['student_name'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('first_name', 'like', "%{$filters['student_name']}%")
                      ->orWhere('last_name', 'like', "%{$filters['student_name']}%")
                      ->orWhereRaw("CONCAT(IFNULL(first_name, ''), ' ', IFNULL(last_name, '')) LIKE ?", ["%{$filters['student_name']}%"]);
                });
            }
            
            // Filter by phone
            if (!empty($filters['student_phone'])) {
                $query->where('phone', 'like', "%{$filters['student_phone']}%");
            }
            
            // Filter by province
            if (!empty($filters['province_id'])) {
                $query->where('province_id', $filters['province_id']);
            }
            
            // Filter by education level
            if (!empty($filters['education_level'])) {
                $query->where('education_level', $filters['education_level']);
            }
            
            // Filter by course
            if (!empty($filters['course_name'])) {
                $query->whereHas('enrollments.courseItem', function ($q) use ($filters) {
                    $q->where('name', 'like', "%{$filters['course_name']}%");
                });
            }
            
            // Filter by enrollment status
            if (!empty($filters['enrollment_status'])) {
                $query->whereHas('enrollments', function ($q) use ($filters) {
                    $q->where('status', $filters['enrollment_status']);
                });
            }
            
            // Filter by date range
            if (!empty($filters['start_date'])) {
                $query->whereHas('enrollments', function ($q) use ($filters) {
                    $q->whereDate('enrollment_date', '>=', $filters['start_date']);
                });
            }
            
            if (!empty($filters['end_date'])) {
                $query->whereHas('enrollments', function ($q) use ($filters) {
                    $q->whereDate('enrollment_date', '<=', $filters['end_date']);
                });
            }
            
            $students = $query->paginate($request->input('per_page', 15));
            
            return response()->json([
                'success' => true,
                'data' => $students,
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tìm kiếm nâng cao: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Tìm kiếm nhanh
     */
    public function quick(Request $request)
    {
        try {
            $term = $request->input('term', '');
            
            if (empty($term)) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }
            
            // Tìm kiếm học viên
            $students = Student::search($term)->limit(5)->get();
            
            // Tìm kiếm khóa học
            $courses = CourseItem::where('name', 'like', "%{$term}%")
                ->where('status', 'active')
                ->limit(5)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'students' => $students,
                    'courses' => $courses,
                    'total_found' => $students->count() + $courses->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tìm kiếm nhanh: ' . $e->getMessage()
            ], 500);
        }
    }
}
