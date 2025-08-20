<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentsExport;

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

    public function exportStudents(array $filters = [])
    {
        $query = Student::with(['enrollments.courseItem', 'province']);

        // Áp dụng các filter
        if (!empty($filters['course_item_id'])) {
            $query->whereHas('enrollments', function($q) use ($filters) {
                $q->where('course_item_id', $filters['course_item_id']);
            });
        }

        if (!empty($filters['status'])) {
            $statusMap = [
                'active' => [\App\Enums\EnrollmentStatus::ACTIVE->value],
                'completed' => ['completed'],
                'waiting' => ['waiting'],
                'inactive' => ['cancelled', 'dropped']
            ];
            
            if (isset($statusMap[$filters['status']])) {
                $query->whereHas('enrollments', function($q) use ($statusMap, $filters) {
                    $q->whereIn('status', $statusMap[$filters['status']]);
                });
            }
        }

        if (!empty($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }

        if (!empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (!empty($filters['date_of_birth_from'])) {
            $query->where('date_of_birth', '>=', $filters['date_of_birth_from']);
        }

        if (!empty($filters['date_of_birth_to'])) {
            $query->where('date_of_birth', '<=', $filters['date_of_birth_to']);
        }

        $students = $query->get();
        
        // Xác định các cột cần xuất (chuẩn hóa theo yêu cầu mới với đầy đủ fields)
        $columns = $filters['columns'] ?? [
            'first_name', 'last_name', 'phone', 'email', 'date_of_birth', 
            'place_of_birth', 'nation', 'gender', 'province', 'address', 
            'current_workplace', 'accounting_experience_years', 'notes', 
            'hard_copy_documents', 'education_level', 'workplace', 'experience_years', 
            'enrollments'
        ];

        $fileName = 'danh_sach_hoc_vien_' . date('Y_m_d_H_i_s') . '.xlsx';
        
        return Excel::download(new StudentsExport($students, $columns), $fileName);
    }

    /**
     * Xuất hóa đơn điện tử cho học viên
     */
    public function exportInvoice($studentId, $enrollmentId, $invoiceDate, $notes = null)
    {
        $student = Student::with(['province'])->findOrFail($studentId);
        $enrollment = \App\Models\Enrollment::with(['courseItem'])->findOrFail($enrollmentId);

        // Kiểm tra enrollment thuộc về student
        if ($enrollment->student_id != $studentId) {
            throw new \Exception('Ghi danh không thuộc về học viên này');
        }

        $invoiceData = [
            'student' => $student,
            'enrollment' => $enrollment,
            'invoice_date' => $invoiceDate,
            'notes' => $notes,
            'company_info' => [
                'name' => 'TRƯỜNG ĐẠI HỌC KINH TẾ QUỐC DÂN',
                'department' => 'Đơn vị: Trung tâm Đào tạo Liên tục',
                'address' => 'Địa chỉ: Trung tâm Đào tạo Liên tục - ĐH Kinh tế Quốc dân',
                'purpose' => 'Đề nghị phòng TC-KT xuất cho chúng tôi hóa đơn GTGT với nội dung như sau:'
            ]
        ];

        $fileName = 'hoa_don_' . $student->id . '_' . date('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(new \App\Exports\InvoiceExport($invoiceData), $fileName);
    }

    /**
     * Import học viên từ file Excel
     */
    public function importStudents($file, $importMode = 'update_only')
    {
        Log::info('StudentService: Starting import', [
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'import_mode' => $importMode
        ]);

        try {
            $import = new \App\Imports\StudentsImport($importMode);
            Excel::import($import, $file);

            $result = [
                'success' => true,
                'message' => 'Import thành công!',
                'created_count' => $import->getCreatedCount(),
                'updated_count' => $import->getUpdatedCount(),
                'skipped_count' => $import->getSkippedCount(),
                'errors' => $import->getErrors()
            ];

            Log::info('StudentService: Import completed', $result);

            return $result;

        } catch (\Exception $e) {
            Log::error('StudentService: Import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Lỗi khi import file: ' . $e->getMessage());
        }
    }

    /**
     * Tải file mẫu Excel cho import học viên
     */
    public function downloadImportTemplate()
    {
        // Sử dụng template mới với cấu trúc database hiện tại
        $templateData = [];

        $fileName = 'mau_import_hoc_vien_' . date('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(new \App\Exports\StudentImportTemplateExport($templateData), $fileName);
    }
}
