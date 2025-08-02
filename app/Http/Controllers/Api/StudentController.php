<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Enrollment;
use App\Services\StudentService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    protected $studentService;
    
    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * Tìm kiếm học viên
     */
    public function search(Request $request)
    {
        $term = $request->get('term');
        
        $students = $this->studentService->searchStudents($term);

        return response()->json($students->map(function($student) {
            return [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'phone' => $student->phone,
                'current_classes' => $student->enrollments->map(function($enrollment) {
                    return $enrollment->courseItem->name;
                })->toArray(),
            ];
        }));
    }

    /**
     * Lấy thông tin chi tiết của học viên
     */
    public function getInfo($id)
    {
        $student = $this->studentService->getStudentWithRelations($id, ['enrollments.courseItem']);
            
        $enrollments = $student->enrollments->map(function($enrollment) {
            return [
                'id' => $enrollment->id,
                'course_name' => $enrollment->courseItem->name,
                'final_fee' => $enrollment->final_fee,
                'remaining_fee' => $enrollment->getRemainingAmount(),
            ];
        })->toArray();
        
        return response()->json([
            'id' => $student->id,
            'full_name' => $student->full_name,
            'phone' => $student->phone,
            'email' => $student->email,
            'enrollments' => $enrollments
        ]);
    }
    
    /**
     * Lấy thông tin chi tiết của ghi danh
     */
    public function getEnrollmentInfo($id)
    {
        $enrollment = Enrollment::with(['student', 'courseItem'])
                    ->findOrFail($id);
                    
        return response()->json([
            'id' => $enrollment->id,
            'student' => [
                'id' => $enrollment->student->id,
                'full_name' => $enrollment->student->full_name,
                'phone' => $enrollment->student->phone
            ],
            'course' => [
                'id' => $enrollment->courseItem->id,
                'name' => $enrollment->courseItem->name
            ],
            'enrollment_date' => $enrollment->enrollment_date->format('Y-m-d'),
            'final_fee' => $enrollment->final_fee,
            'total_paid' => $enrollment->getTotalPaidAmount(),
            'remaining_fee' => $enrollment->getRemainingAmount()
        ]);
    }
} 