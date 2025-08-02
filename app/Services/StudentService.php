<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentService
{
    public function getStudents(array $filters = [])
    {
        $query = Student::with(['enrollments']);
        
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['student_id'])) {
            $query->where('id', $filters['student_id']);
        }
        
        if (isset($filters['course_item_id'])) {
            $query->whereHas('enrollments', function($q) use ($filters) {
                $q->where('course_item_id', $filters['course_item_id']);
            });
        }

        return $query->latest()->paginate(isset($filters['per_page']) ? $filters['per_page'] : 20)
                    ->appends(request()->except('page'));
    }

    public function getStudent($id)
    {
        return Student::findOrFail($id);
    }

    public function getStudentWithRelations($id, array $relations = [])
    {
        return Student::with($relations)->findOrFail($id);
    }

    public function createStudent(array $data)
    {
        return Student::create($data);
    }

    public function updateStudent(Student $student, array $data)
    {
        // Xử lý custom fields
        $customFields = [];
        if (isset($data['custom_field_keys'])) {
            $keys = $data['custom_field_keys'];
            $values = $data['custom_field_values'] ?? [];
            
            foreach ($keys as $index => $key) {
                if (!empty($key) && isset($values[$index])) {
                    $customFields[$key] = $values[$index];
                }
            }
        }

        // Lọc dữ liệu cập nhật
        $dataToUpdate = array_filter($data, function($key) {
            return !in_array($key, ['custom_field_keys', 'custom_field_values']);
        }, ARRAY_FILTER_USE_KEY);

        // Thêm trường custom_fields
        if (!empty($customFields)) {
            $dataToUpdate['custom_fields'] = $customFields;
        }

        $student->update($dataToUpdate);
        return $student;
    }

    public function deleteStudent(Student $student)
    {
        return $student->delete();
    }

    public function getStudentStatistics()
    {
        return [
            'total_students' => Student::count(),
            'recent_registrations' => Student::where('created_at', '>=', now()->subDays(30))->count()
        ];
    }

    public function searchStudents($term)
    {
        return Student::search($term)
                    ->with(['enrollments.courseItem', 'waitingLists.courseItem'])
                    ->limit(10)
                    ->get();
    }
} 