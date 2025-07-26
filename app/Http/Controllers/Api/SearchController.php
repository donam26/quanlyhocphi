<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Tìm kiếm học viên theo tên hoặc số điện thoại để autocomplete
     */
    public function autocomplete(Request $request)
    {
        $search = $request->input('q');
        
        if (strlen($search) < 2) {
            return response()->json([]);
        }
        
        $students = Student::where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->with(['enrollments' => function($query) {
                        $query->where('status', 'enrolled')
                            ->with(['courseItem']);
                    }])
                    ->limit(10)
                    ->get();
                    
        $results = $students->map(function($student) {
            $enrollments = $student->enrollments->map(function($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'course_name' => $enrollment->courseItem->name,
                    'course_item_id' => $enrollment->courseItem->id,
                    'final_fee' => $enrollment->final_fee,
                    'remaining_fee' => $enrollment->getRemainingAmount(),
                ];
            })->toArray();
            
            return [
                'id' => $student->id,
                'text' => $student->full_name . ' - ' . $student->phone,
                'enrollments' => $enrollments
            ];
        });
        
        return response()->json($results);
    }
} 