<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\WaitingList;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Trang tìm kiếm chính
     */
    public function index()
    {
        $majors = \App\Models\Major::all();
        return view('search.index', compact('majors'));
    }

    /**
     * Tìm kiếm học viên theo SĐT hoặc họ tên
     */
    public function searchStudents(Request $request)
    {
        $request->validate([
            'term' => 'required|string|min:2'
        ]);

        $term = $request->term;
        
        $students = Student::search($term)
                          ->with([
                              'enrollments.courseClass.course',
                              'enrollments.payments',
                              'waitingLists.course'
                          ])
                          ->get();

        return view('search.results', compact('students', 'term'));
    }

    /**
     * API tìm kiếm học viên cho autocomplete
     */
    public function apiSearchStudents(Request $request)
    {
        $term = $request->get('q', '');
        
        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $students = Student::search($term)
                          ->with(['enrollments.courseClass.course', 'waitingLists.course'])
                          ->limit(10)
                          ->get();

        return response()->json($students->map(function($student) {
            return [
                'id' => $student->id,
                'text' => $student->full_name . ' - ' . $student->phone,
                'full_name' => $student->full_name,
                'phone' => $student->phone,
                'email' => $student->email,
                'status' => $student->status,
                'current_classes' => $student->enrollments->where('status', 'enrolled')->map(function($enrollment) {
                    return [
                        'id' => $enrollment->courseClass->id,
                        'name' => $enrollment->courseClass->name,
                        'course' => $enrollment->courseClass->course->name,
                        'payment_status' => $enrollment->hasFullyPaid() ? 'paid' : 'unpaid',
                        'remaining_amount' => $enrollment->getRemainingAmount()
                    ];
                })->values(),
                'waiting_courses' => $student->waitingLists->where('status', 'waiting')->map(function($waiting) {
                    return [
                        'id' => $waiting->course->id,
                        'name' => $waiting->course->name,
                        'interest_level' => $waiting->interest_level,
                        'added_date' => $waiting->added_date->format('d/m/Y')
                    ];
                })->values(),
                'history' => $this->getStudentHistory($student)
            ];
        }));
    }

    /**
     * Lấy lịch sử học viên (đã hoàn thành hoặc đã bỏ học)
     */
    private function getStudentHistory($student)
    {
        $completedEnrollments = $student->enrollments()
                                       ->whereIn('status', ['completed', 'dropped'])
                                       ->with('courseClass.course')
                                       ->get();

        $previousWaitingLists = $student->waitingLists()
                                       ->whereIn('status', ['enrolled', 'not_interested'])
                                       ->with('course')
                                       ->get();

        return [
            'completed_courses' => $completedEnrollments->map(function($enrollment) {
                return [
                    'course_name' => $enrollment->courseClass->course->name,
                    'class_name' => $enrollment->courseClass->name,
                    'status' => $enrollment->status,
                    'enrollment_date' => $enrollment->enrollment_date->format('d/m/Y')
                ];
            }),
            'previous_interests' => $previousWaitingLists->map(function($waiting) {
                return [
                    'course_name' => $waiting->course->name,
                    'status' => $waiting->status,
                    'added_date' => $waiting->added_date->format('d/m/Y')
                ];
            })
        ];
    }

    /**
     * Tìm kiếm nâng cao
     */
    public function advancedSearch(Request $request)
    {
        $query = Student::query();

        // Tìm theo tên
        if ($request->filled('name')) {
            $query->where('full_name', 'like', '%' . $request->name . '%');
        }

        // Tìm theo SĐT
        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        // Tìm theo CCCD
        if ($request->filled('citizen_id')) {
            $query->where('citizen_id', 'like', '%' . $request->citizen_id . '%');
        }

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Lọc theo khóa học đang theo học
        if ($request->filled('current_course')) {
            $query->whereHas('enrollments', function($q) use ($request) {
                $q->where('status', 'enrolled')
                  ->whereHas('courseClass', function($q2) use ($request) {
                      $q2->where('course_id', $request->current_course);
                  });
            });
        }

        // Lọc theo khóa học quan tâm
        if ($request->filled('interested_course')) {
            $query->whereHas('waitingLists', function($q) use ($request) {
                $q->where('status', 'waiting')
                  ->where('course_id', $request->interested_course);
            });
        }

        // Lọc theo trạng thái thanh toán
        if ($request->filled('payment_status')) {
            if ($request->payment_status === 'unpaid') {
                $query->whereHas('enrollments', function($q) {
                    $q->where('status', 'enrolled')
                      ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE enrollment_id = enrollments.id AND status = "confirmed") < final_fee');
                });
            } elseif ($request->payment_status === 'paid') {
                $query->whereHas('enrollments', function($q) {
                    $q->where('status', 'enrolled')
                      ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE enrollment_id = enrollments.id AND status = "confirmed") >= final_fee');
                });
            }
        }

        $students = $query->with([
                            'enrollments.courseClass.course',
                            'enrollments.payments',
                            'waitingLists.course'
                         ])
                         ->paginate(20);

        $courses = Course::all();

        return view('search.advanced', compact('students', 'courses'));
    }

    /**
     * Xuất kết quả tìm kiếm ra Excel
     */
    public function exportSearch(Request $request)
    {
        // Logic xuất Excel sẽ được implement sau
        return response()->json(['message' => 'Chức năng xuất Excel đang được phát triển']);
    }
}
