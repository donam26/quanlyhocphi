<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    /**
     * Display a listing of students
     */
    public function index(Request $request)
    {
        $query = Student::query();

        // Search - xử lý cả 'search' và 'q' parameter
        $searchTerm = $request->get('search') ?: $request->get('q');
        if ($searchTerm) {
            $query->search($searchTerm); // Sử dụng scope search đã có trong model
        }

        // Filter by province
        if ($request->has('province_id') && $request->province_id) {
            $query->where('province_id', $request->province_id);
        }

        // Filter by gender
        if ($request->has('gender') && $request->gender) {
            $query->where('gender', $request->gender);
        }

        // Filter by source
        if ($request->has('source') && $request->source) {
            $query->where('source', $request->source);
        }

        // Sort - xử lý sắp xếp từ frontend
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Validate sort fields để tránh SQL injection
        $allowedSortFields = [
            'id', 'first_name', 'last_name', 'email', 'phone',
            'date_of_birth', 'created_at', 'updated_at'
        ];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Paginate
        $perPage = $request->get('per_page', 15);
        $students = $query->with(['province', 'ethnicity'])->paginate($perPage);

        // Add computed fields
        $students->getCollection()->transform(function ($student) {
            $student->full_name = $student->first_name . ' ' . $student->last_name;
            $student->formatted_date_of_birth = $student->date_of_birth ? 
                \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') : null;
            
            // Calculate payment status
            $totalFee = $student->enrollments()->sum('final_fee');
            $paidAmount = Payment::whereHas('enrollment', function ($q) use ($student) {
                $q->where('student_id', $student->id);
            })->where('status', 'confirmed')->sum('amount');
            
            $student->total_fee = $totalFee;
            $student->paid_amount = $paidAmount;
            $student->payment_status = $this->getPaymentStatus($totalFee, $paidAmount);
            
            return $student;
        });

        return response()->json($students);
    }

    /**
     * Advanced search for students
     */
    public function advancedSearch(Request $request)
    {
        $query = Student::with(['province', 'enrollments.courseItem']);

        // Search by name or phone
        if ($request->has('q') && $request->q) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', "%{$searchTerm}%")
                  ->orWhere('last_name', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('current_workplace', 'like', "%{$searchTerm}%")
                  ->orWhere('training_specialization', 'like', "%{$searchTerm}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"]);
            });
        }

        // Filter by gender
        if ($request->has('gender') && $request->gender) {
            $query->where('gender', $request->gender);
        }

        // Filter by education level
        if ($request->has('education_level') && $request->education_level) {
            $query->where('education_level', $request->education_level);
        }

        // Filter by hard copy documents
        if ($request->has('hard_copy_documents') && $request->hard_copy_documents) {
            $query->where('hard_copy_documents', $request->hard_copy_documents);
        }

        // Filter by province
        if ($request->has('province_id') && $request->province_id) {
            $query->where('province_id', $request->province_id);
        }

        // Filter by source
        if ($request->has('source') && $request->source) {
            $query->where('source', $request->source);
        }

        // Order by relevance (name match first, then phone)
        $query->orderByRaw("
            CASE
                WHEN CONCAT(first_name, ' ', last_name) LIKE ? THEN 1
                WHEN first_name LIKE ? OR last_name LIKE ? THEN 2
                WHEN phone LIKE ? THEN 3
                ELSE 4
            END
        ", [
            "%{$request->q}%",
            "%{$request->q}%",
            "%{$request->q}%",
            "%{$request->q}%"
        ]);

        $students = $query->limit(50)->get();

        // Add computed fields
        $students->transform(function ($student) {
            $student->full_name = $student->first_name . ' ' . $student->last_name;
            return $student;
        });

        return response()->json([
            'success' => true,
            'data' => $students,
            'total' => $students->count()
        ]);
    }

    /**
     * Store a newly created student
     */
    public function store(Request $request)
    {
        try {
            \Log::info('Creating new student', ['request_data' => $request->all()]);

            // Validation cơ bản
            $rules = [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'date_of_birth' => 'required|date',
                'gender' => 'required|in:male,female,other',
                'phone' => 'required|string|max:20|unique:students',
                'email' => 'nullable|email|unique:students',
                'province_id' => 'nullable|exists:provinces,id',
                'place_of_birth_province_id' => 'nullable|exists:provinces,id',
                'nation' => 'nullable|string|max:255',
                'ethnicity_id' => 'nullable|exists:ethnicities,id',
                'source' => 'nullable|in:facebook,zalo,website,linkedin,tiktok,friend_referral',
                'notes' => 'nullable|string',
                // Thông tin công ty
                'company_name' => 'nullable|string|max:255',
                'tax_code' => 'nullable|string|max:20',
                'invoice_email' => 'nullable|email',
                'company_address' => 'nullable|string',
                // Thông tin bổ sung cho khóa học đặc biệt
                'current_workplace' => 'nullable|string|max:255',
                'accounting_experience_years' => 'nullable|integer|min:0',
                'training_specialization' => 'nullable|string|max:255',
                'education_level' => 'nullable|in:secondary,vocational,associate,bachelor,master',
                'hard_copy_documents' => 'nullable|in:submitted,not_submitted',
            ];

            // Kiểm tra nếu đang tạo cho khóa học đặc biệt
            $courseId = $request->input('course_id');
            if ($courseId) {
                $course = \App\Models\CourseItem::find($courseId);
                if ($course && $course->is_special) {
                    \Log::info('Creating student for special course', ['course_id' => $courseId, 'course_name' => $course->name]);
                    // Bắt buộc các trường cho khóa học đặc biệt
                    $rules['current_workplace'] = 'required|string|max:255';
                    $rules['accounting_experience_years'] = 'required|integer|min:0';
                    $rules['training_specialization'] = 'required|string|max:255';
                    $rules['education_level'] = 'required|in:secondary,vocational,associate,bachelor,master';
                    $rules['hard_copy_documents'] = 'required|in:submitted,not_submitted';
                }
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                \Log::warning('Student validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->all()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $student = Student::create($request->all());
            $student->load('province');
            $student->full_name = $student->first_name . ' ' . $student->last_name;

            \Log::info('Student created successfully', ['student_id' => $student->id, 'student_name' => $student->full_name]);

            return response()->json([
                'success' => true,
                'message' => 'Tạo học viên thành công',
                'data' => $student
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error creating student', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo học viên: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified student
     */
    public function show(Student $student)
    {
        $student->load(['province', 'enrollments.courseItem', 'enrollments.payments']);
        $student->full_name = $student->first_name . ' ' . $student->last_name;
        $student->formatted_date_of_birth = $student->date_of_birth ? 
            \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') : null;

        return response()->json($student);
    }

    /**
     * Update the specified student
     */
    public function update(Request $request, Student $student)
    {
        try {
            \Log::info('Updating student', ['student_id' => $student->id, 'request_data' => $request->all()]);

            $rules = [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'date_of_birth' => 'required|date',
                'gender' => 'required|in:male,female,other',
                'phone' => "required|string|max:20|unique:students,phone,{$student->id}",
                'email' => "nullable|email|unique:students,email,{$student->id}",
                'province_id' => 'nullable|exists:provinces,id',
                'place_of_birth_province_id' => 'nullable|exists:provinces,id',
                'nation' => 'nullable|string|max:255',
                'ethnicity_id' => 'nullable|exists:ethnicities,id',
                'source' => 'nullable|in:facebook,zalo,website,linkedin,tiktok,friend_referral',
                'notes' => 'nullable|string',
                // Thông tin công ty
                'company_name' => 'nullable|string|max:255',
                'tax_code' => 'nullable|string|max:20',
                'invoice_email' => 'nullable|email',
                'company_address' => 'nullable|string',
                // Thông tin bổ sung cho khóa học đặc biệt
                'current_workplace' => 'nullable|string|max:255',
                'accounting_experience_years' => 'nullable|integer|min:0',
                'training_specialization' => 'nullable|string|max:255',
                'education_level' => 'nullable|in:secondary,vocational,associate,bachelor,master',
                'hard_copy_documents' => 'nullable|in:submitted,not_submitted',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                \Log::warning('Student update validation failed', [
                    'student_id' => $student->id,
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->all()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $student->update($request->all());
            $student->load('province');
            $student->full_name = "{$student->first_name} {$student->last_name}";

            \Log::info('Student updated successfully', ['student_id' => $student->id, 'student_name' => $student->full_name]);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật học viên thành công',
                'data' => $student
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating student', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật học viên: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified student
     */
    public function destroy(Student $student, Request $request)
    {
        try {
            $deleteEnrollments = $request->boolean('delete_enrollments', false);
            $deletePayments = $request->boolean('delete_payments', false);

            // Check if student has enrollments and user doesn't want to delete them
            if (!$deleteEnrollments && $student->enrollments()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa học viên có ghi danh. Vui lòng chọn xóa cả ghi danh hoặc xóa ghi danh trước.'
                ], 422);
            }

            // Check if student has payments and user doesn't want to delete them
            if (!$deletePayments && $student->payments()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa học viên có thanh toán. Vui lòng chọn xóa cả thanh toán hoặc xóa thanh toán trước.'
                ], 422);
            }

            // Delete related data if requested
            if ($deleteEnrollments) {
                $student->enrollments()->delete();
            }

            if ($deletePayments) {
                $student->payments()->delete();
            }

            $student->delete();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa học viên thành công'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting student', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa học viên: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete students
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:students,id',
            'delete_enrollments' => 'boolean',
            'delete_payments' => 'boolean'
        ]);

        try {
            $studentIds = $request->input('student_ids');
            $deleteEnrollments = $request->boolean('delete_enrollments', false);
            $deletePayments = $request->boolean('delete_payments', false);

            $students = Student::whereIn('id', $studentIds)->get();
            $deletedCount = 0;
            $errors = [];

            foreach ($students as $student) {
                try {
                    // Check constraints
                    if (!$deleteEnrollments && $student->enrollments()->count() > 0) {
                        $errors[] = "Học viên {$student->full_name} có ghi danh, không thể xóa";
                        continue;
                    }

                    if (!$deletePayments && $student->payments()->count() > 0) {
                        $errors[] = "Học viên {$student->full_name} có thanh toán, không thể xóa";
                        continue;
                    }

                    // Delete related data if requested
                    if ($deleteEnrollments) {
                        $student->enrollments()->delete();
                    }

                    if ($deletePayments) {
                        $student->payments()->delete();
                    }

                    $student->delete();
                    $deletedCount++;

                } catch (\Exception $e) {
                    $errors[] = "Lỗi khi xóa học viên {$student->full_name}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Đã xóa {$deletedCount} học viên thành công",
                'deleted_count' => $deletedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            \Log::error('Error bulk deleting students', [
                'student_ids' => $request->input('student_ids'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa học viên: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student enrollments
     */
    public function enrollments(Student $student)
    {
        try {
            $enrollments = $student->enrollments()
                ->with(['courseItem', 'payments'])
                ->orderBy('enrollment_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $enrollments,
                'message' => 'Student enrollments loaded successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading student enrollments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to load student enrollments'
            ], 500);
        }
    }

    /**
     * Get student payments
     */
    public function payments(Student $student)
    {
        $payments = Payment::whereHas('enrollment', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })->with(['enrollment.courseItem'])->orderBy('payment_date', 'desc')->get();

        return response()->json($payments);
    }

    /**
     * Get student details for modal
     */
    public function details(Student $student)
    {
        $student->load(['province', 'enrollments.courseItem', 'enrollments.payments']);
        $student->full_name = $student->first_name . ' ' . $student->last_name;
        
        // Calculate totals
        $totalFee = $student->enrollments()->sum('final_fee');
        $paidAmount = Payment::whereHas('enrollment', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })->where('status', 'confirmed')->sum('amount');
        
        $student->total_fee = $totalFee;
        $student->paid_amount = $paidAmount;
        $student->remaining_amount = $totalFee - $paidAmount;

        return response()->json($student);
    }

    /**
     * Get student basic info
     */
    public function info(Student $student)
    {
        $student->full_name = $student->first_name . ' ' . $student->last_name;
        
        return response()->json([
            'id' => $student->id,
            'full_name' => $student->full_name,
            'phone' => $student->phone,
            'email' => $student->email,
        ]);
    }

    /**
     * Get students by province
     */
    public function byProvince($provinceId)
    {
        $students = Student::where('province_id', $provinceId)
            ->select('id', 'first_name', 'last_name', 'phone', 'email')
            ->get()
            ->map(function ($student) {
                $student->full_name = $student->first_name . ' ' . $student->last_name;
                return $student;
            });

        return response()->json($students);
    }

    /**
     * Get students by region
     */
    public function byRegion($region)
    {
        $students = Student::whereHas('province', function ($q) use ($region) {
            $q->where('region', $region);
        })->with('province')->get()->map(function ($student) {
            $student->full_name = $student->first_name . ' ' . $student->last_name;
            return $student;
        });

        return response()->json($students);
    }

    /**
     * Import students from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'import_mode' => 'in:create_only,update_only,create_and_update'
        ]);

        try {
            $import = new \App\Imports\StudentsImport($request->import_mode ?? 'create_and_update');
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Import thành công!',
                'data' => [
                    'created_count' => $import->getCreatedCount(),
                    'updated_count' => $import->getUpdatedCount(),
                    'skipped_count' => $import->getSkippedCount(),
                    'errors' => $import->getErrors()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi import file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export students to Excel
     */
    public function export(Request $request)
    {
        try {
            $request->validate([
                'columns' => 'array',
                'columns.*' => 'string',
                'filters' => 'array'
            ]);

            // Lấy danh sách học viên với filters
            $query = Student::with(['province', 'placeOfBirthProvince', 'ethnicity', 'enrollments.courseItem', 'payments']);

            // Apply filters
            $filters = $request->input('filters', []);

            if (!empty($filters['search'])) {
                $query->where(function($q) use ($filters) {
                    $term = $filters['search'];
                    $q->where('first_name', 'like', "%{$term}%")
                      ->orWhere('last_name', 'like', "%{$term}%")
                      ->orWhereRaw("CONCAT(IFNULL(first_name, ''), ' ', IFNULL(last_name, '')) LIKE ?", ["%{$term}%"])
                      ->orWhere('phone', 'like', "%{$term}%")
                      ->orWhere('email', 'like', "%{$term}%");
                });
            }

            if (!empty($filters['province_id'])) {
                $query->where('province_id', $filters['province_id']);
            }

            if (!empty($filters['gender'])) {
                $query->where('gender', $filters['gender']);
            }

            if (!empty($filters['education_level'])) {
                $query->where('education_level', $filters['education_level']);
            }

            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            $students = $query->get();

            // Columns to export
            $columns = $request->input('columns', [
                'full_name', 'phone', 'email', 'date_of_birth', 'gender',
                'province', 'place_of_birth_province', 'ethnicity', 'current_workplace',
                'accounting_experience_years', 'education_level', 'training_specialization',
                'hard_copy_documents', 'company_name', 'tax_code', 'source'
            ]);

            $fileName = 'danh_sach_hoc_vien_' . date('Y_m_d_H_i_s') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\StudentsExport($students, $columns),
                $fileName
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xuất file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download import template
     */
    public function downloadImportTemplate()
    {
        try {
            // Template data theo cấu trúc database hiện tại
            $templateData = [];

            $fileName = 'mau_import_hoc_vien_' . date('Y_m_d_H_i_s') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\StudentImportTemplateExport($templateData),
                $fileName
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tải template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment status
     */
    private function getPaymentStatus($totalFee, $paidAmount)
    {
        if ($totalFee == 0) return 'no_fee';
        if ($paidAmount >= $totalFee) return 'paid';
        if ($paidAmount > 0) return 'partial';
        return 'unpaid';
    }
}
