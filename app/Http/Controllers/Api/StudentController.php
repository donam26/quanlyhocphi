<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\CourseItem;
use App\Enums\EnrollmentStatus;
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
        $students = $query->with(['province', 'ethnicity', 'enrollments.courseItem'])
            ->withCount(['enrollments', 'payments'])
            ->paginate($perPage);

        // Add computed fields
        $students->through(function ($student) {
            $student->full_name = $student->first_name . ' ' . $student->last_name;
            $student->formatted_date_of_birth = $student->date_of_birth ?
                \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') : null;

            // Get active enrollments and waiting list
            $activeEnrollments = $student->enrollments->filter(function ($enrollment) {
                return in_array($enrollment->status->value, ['active', 'enrolled']);
            });
            $waitingEnrollments = $student->enrollments->filter(function ($enrollment) {
                return $enrollment->status->value === 'waiting';
            });

            // Prepare enrolled courses info
            $enrolledCourses = [];
            foreach ($activeEnrollments as $enrollment) {
                if ($enrollment->courseItem) {
                    $courseName = $this->getCourseName($enrollment->courseItem);
                    $enrolledCourses[] = [
                        'id' => $enrollment->courseItem->id,
                        'name' => $courseName,
                        'status' => $enrollment->status->value,
                        'learning_method' => $enrollment->courseItem->learning_method ?? null
                    ];
                }
            }

            // Prepare waiting courses info
            $waitingCourses = [];
            foreach ($waitingEnrollments as $enrollment) {
                if ($enrollment->courseItem) {
                    $courseName = $this->getCourseName($enrollment->courseItem);
                    $waitingCourses[] = [
                        'id' => $enrollment->courseItem->id,
                        'name' => $courseName,
                        'status' => $enrollment->status->value,
                        'learning_method' => $enrollment->courseItem->learning_method ?? null
                    ];
                }
            }

            $student->enrolled_courses = $enrolledCourses;
            $student->waiting_courses = $waitingCourses;

            // Calculate payment status only if has active enrollments
            if ($activeEnrollments->count() > 0) {
                $totalFee = $activeEnrollments->sum('final_fee');
                $paidAmount = Payment::whereHas('enrollment', function ($q) use ($student) {
                    $q->where('student_id', $student->id)
                      ->whereIn('status', [
                          EnrollmentStatus::ACTIVE->value,
                          'enrolled'  // fallback for old data
                      ]);
                })->where('status', 'confirmed')->sum('amount');

                $student->total_fee = $totalFee;
                $student->paid_amount = $paidAmount;
                $student->payment_status = $this->getPaymentStatus($totalFee, $paidAmount);
            } else {
                // No active enrollments, no payment status
                $student->total_fee = 0;
                $student->paid_amount = 0;
                $student->payment_status = null;
            }

            return $student;
        });

        return response()->json($students);
    }

    /**
     * Get formatted course name
     */
    private function getCourseName($courseItem)
    {
        // Use the actual course name
        $name = $courseItem->name;

        // Add learning method info if available and not already in name
        if ($courseItem->learning_method) {
            $methodText = $courseItem->learning_method->value === 'online' ? 'Online' : 'Offline';
            if (!str_contains(strtolower($name), strtolower($methodText))) {
                $name .= ' (' . $methodText . ')';
            }
        }

        return $name;
    }



    /**
     * Advanced search for students
     */
    public function advancedSearch(Request $request)
    {
        $query = Student::with(['province', 'placeOfBirthProvince', 'ethnicity', 'enrollments.courseItem']);

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

        // Filter by place of birth province
        if ($request->has('place_of_birth_province_id') && $request->place_of_birth_province_id) {
            $query->where('place_of_birth_province_id', $request->place_of_birth_province_id);
        }

        // Filter by ethnicity
        if ($request->has('ethnicity_id') && $request->ethnicity_id) {
            $query->where('ethnicity_id', $request->ethnicity_id);
        }

        // Filter by accounting experience years
        if ($request->has('accounting_experience_years') && $request->accounting_experience_years !== '') {
            if ($request->accounting_experience_years == '5') {
                // 5 năm trở lên
                $query->where('accounting_experience_years', '>=', 5);
            } else {
                $query->where('accounting_experience_years', $request->accounting_experience_years);
            }
        }

        // Filter by current workplace
        if ($request->has('current_workplace') && $request->current_workplace) {
            $query->where('current_workplace', 'like', '%' . $request->current_workplace . '%');
        }

        // Filter by course (both parent and child courses)
        if ($request->has('course_id') && $request->course_id) {
            $courseId = $request->course_id;
            $query->whereHas('enrollments', function ($q) use ($courseId) {
                $q->whereHas('courseItem', function ($courseQuery) use ($courseId) {
                    $courseQuery->where('id', $courseId)
                               ->orWhere('parent_id', $courseId)
                               ->orWhereHas('parent', function ($parentQuery) use ($courseId) {
                                   $parentQuery->where('id', $courseId)
                                              ->orWhere('parent_id', $courseId);
                               });
                });
            });
        }

        // Sorting logic
        $sortBy = $request->get('sort_by', 'relevance');
        $sortOrder = $request->get('sort_order', 'asc');

        switch ($sortBy) {
            case 'course':
                // Sort by course name (get the first enrolled course)
                $query->leftJoin('enrollments', 'students.id', '=', 'enrollments.student_id')
                      ->leftJoin('course_items', 'enrollments.course_item_id', '=', 'course_items.id')
                      ->orderBy('course_items.name', $sortOrder)
                      ->select('students.*')
                      ->distinct();
                break;

            case 'name':
                $query->orderByRaw("CONCAT(first_name, ' ', last_name) {$sortOrder}");
                break;

            case 'enrollment_date':
                $query->leftJoin('enrollments as e_sort', 'students.id', '=', 'e_sort.student_id')
                      ->orderBy('e_sort.enrollment_date', $sortOrder)
                      ->select('students.*')
                      ->distinct();
                break;

            case 'relevance':
            default:
                // Order by relevance (name match first, then phone)
                if ($request->has('q') && $request->q) {
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
                } else {
                    $query->orderBy('created_at', 'desc');
                }
                break;
        }

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

            // Validation cơ bản - chỉ bắt buộc họ và tên
            $rules = [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other',
                'phone' => 'nullable|string|max:20|unique:students',
                'email' => 'nullable|unique:students',
                'citizen_id' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'province_id' => 'nullable|exists:provinces,id',
                'place_of_birth_province_id' => 'nullable|exists:provinces,id',
                'nation' => 'nullable|string|max:255',
                'ethnicity_id' => 'nullable|exists:ethnicities,id',
                'current_workplace' => 'nullable|string|max:255',
                'accounting_experience_years' => 'nullable|integer|min:0',
                'training_specialization' => 'nullable|string|max:255',
                'hard_copy_documents' => 'nullable|in:submitted,not_submitted',
                'education_level' => 'nullable|in:vocational,associate,bachelor,master,secondary',
                'source' => 'nullable|in:facebook,zalo,website,linkedin,tiktok,friend_referral',
                'notes' => 'nullable|string',
                // Thông tin công ty
                'company_name' => 'nullable|string|max:255',
                'tax_code' => 'nullable|string|max:20',
                'invoice_email' => 'nullable|email',
                'company_address' => 'nullable|string|max:500',
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


            return response()->json([
                'success' => true,
                'message' => 'Tạo học viên thành công',
                'data' => $student
            ], 201);

        } catch (\Exception $e) {
        

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

            $rules = [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other',
                'phone' => "nullable|string|max:20|unique:students,phone,{$student->id}",
                'email' => "nullable|unique:students,email,{$student->id}",
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
              
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $student->update($request->all());
            $student->load('province');
            $student->full_name = "{$student->first_name} {$student->last_name}";

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật học viên thành công',
                'data' => $student
            ]);

        } catch (\Exception $e) {
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

            // Tải số lượng bản ghi liên quan
            $student->loadCount(['enrollments', 'payments']);

            // Kiểm tra ràng buộc
            // Nếu có ghi danh và không chọn xóa ghi danh -> lỗi
            if ($student->enrollments_count > 0 && !$deleteEnrollments) {
                return response()->json([
                    'success' => false,
                    'message' => "Không thể xóa học viên \"{$student->full_name}\" vì có {$student->enrollments_count} ghi danh khóa học. Vui lòng chọn xóa cả ghi danh hoặc xóa ghi danh trước.",
                    'error_code' => 'HAS_ENROLLMENTS',
                    'data' => ['enrollments_count' => $student->enrollments_count]
                ], 422);
            }

            // Xóa dữ liệu liên quan
            // Nếu chọn xóa ghi danh, các thanh toán liên quan cũng sẽ bị xóa (do cascade on delete trong DB)
            if ($deleteEnrollments && $student->enrollments_count > 0) {
                $student->enrollments()->delete();
            }

            // Xóa học viên
            $student->delete();

            $student->delete();

            return response()->json([
                'success' => true,
                'message' => "Đã xóa học viên \"{$student->full_name}\" thành công",
                'data' => [
                    'deleted_student' => [
                        'id' => $student->id,
                        'full_name' => $student->full_name
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Có lỗi xảy ra khi xóa học viên \"{$student->full_name}\": " . $e->getMessage(),
                'error_code' => 'DELETE_FAILED'
            ], 500);
        }
    }

    /**
     * Bulk delete students
     */
    public function bulkDestroy(Request $request)
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

            // Get students with their related data counts
            $students = Student::whereIn('id', $studentIds)
                ->withCount(['enrollments', 'payments'])
                ->get();

            if ($students->count() !== count($studentIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Một số học viên không tồn tại',
                    'error_code' => 'STUDENTS_NOT_FOUND'
                ], 422);
            }

            $errors = [];
            $deletedStudents = [];
            $skippedStudents = [];

            foreach ($students as $student) {
                try {
                    // Check constraints
                    if (!$deleteEnrollments && $student->enrollments_count > 0) {
                        $skippedStudents[] = [
                            'id' => $student->id,
                            'full_name' => $student->full_name,
                            'reason' => "Có {$student->enrollments_count} ghi danh khóa học"
                        ];
                        continue;
                    }

                    // Nếu có enrollments và được phép xóa, thì payments cũng sẽ bị xóa theo
                    // Chỉ check payments riêng lẻ nếu không có enrollments
                    if ($student->enrollments_count == 0 && !$deletePayments && $student->payments_count > 0) {
                        $skippedStudents[] = [
                            'id' => $student->id,
                            'full_name' => $student->full_name,
                            'reason' => "Có {$student->payments_count} thanh toán"
                        ];
                        continue;
                    }

                    // Delete related data
                    if ($deleteEnrollments && $student->enrollments_count > 0) {
                        // Xóa enrollments sẽ tự động xóa payments liên quan (cascade)
                        $student->enrollments()->delete();
                    } elseif ($deletePayments && $student->payments_count > 0) {
                        // Chỉ xóa payments riêng lẻ nếu không xóa enrollments
                        $student->payments()->delete();
                    }

                    $student->delete();

                    $deletedStudents[] = [
                        'id' => $student->id,
                        'full_name' => $student->full_name
                    ];

                } catch (\Exception $e) {
                    $errors[] = [
                        'student_id' => $student->id,
                        'student_name' => $student->full_name,
                        'error' => $e->getMessage()
                    ];
                }
            }

            $deletedCount = count($deletedStudents);
            $skippedCount = count($skippedStudents);
            $errorCount = count($errors);

            $message = "Đã xóa {$deletedCount} học viên thành công";
            if ($skippedCount > 0) {
                $message .= ", bỏ qua {$skippedCount} học viên";
            }
            if ($errorCount > 0) {
                $message .= ", {$errorCount} học viên lỗi";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'deleted_count' => $deletedCount,
                    'skipped_count' => $skippedCount,
                    'error_count' => $errorCount,
                    'deleted_students' => $deletedStudents,
                    'skipped_students' => $skippedStudents,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Có lỗi xảy ra khi xóa hàng loạt học viên: " . $e->getMessage(),
                'error_code' => 'BULK_DELETE_FAILED'
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
        $student->load([
            'province',
            'placeOfBirthProvince',
            'ethnicity',
            'enrollments.courseItem',
            'enrollments.payments'
        ]);

        $student->full_name = $student->first_name . ' ' . $student->last_name;
        $student->formatted_date_of_birth = $student->date_of_birth ?
            \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') : null;

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
     * Get bulk student details for delete modal (optimized)
     */
    public function bulkDetails(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:students,id'
        ]);

        $studentIds = $request->input('student_ids');

        // Optimized query with counts
        $students = Student::whereIn('id', $studentIds)
            ->withCount(['enrollments', 'payments'])
            ->with(['enrollments.courseItem', 'payments'])
            ->get()
            ->map(function ($student) {
                $student->full_name = $student->first_name . ' ' . $student->last_name;
                return $student;
            });

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    /**
     * Get available courses for student enrollment
     */
    public function availableCourses(Student $student)
    {
        try {
            // Lấy danh sách ID các khóa học mà học viên đã ghi danh (chỉ tính active và waiting)
            $enrolledCourseIds = $student->enrollments()
                ->whereIn('status', ['active', 'waiting'])
                ->pluck('course_item_id')
                ->toArray();

            // Lấy danh sách khóa học chưa ghi danh (chỉ lấy leaf courses - khóa học có thể ghi danh)
            $availableCourses = \App\Models\CourseItem::whereNotIn('id', $enrolledCourseIds)
                ->where('is_leaf', true)
                ->where('status', 'active')
                ->with(['parent'])
                ->orderBy('name')
                ->get()
                ->map(function ($courseItem) {
                    return [
                        'id' => $courseItem->id,
                        'name' => $courseItem->name,
                        'parent_name' => $courseItem->parent->name ?? null,
                        'path' => $courseItem->path ?? $courseItem->name,
                        'fee' => $courseItem->fee,
                        'status' => $courseItem->status->value ?? $courseItem->status
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $availableCourses
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách khóa học'
            ], 500);
        }
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
            $import = new \App\Imports\UnifiedStudentImport($request->import_mode ?? 'create_and_update');
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            $result = [
                'success' => true,
                'message' => 'Import thành công!',
                'data' => [
                    'created_count' => $import->getCreatedCount(),
                    'updated_count' => $import->getUpdatedCount(),
                    'skipped_count' => $import->getSkippedCount(),
                    'total_rows_processed' => $import->getTotalRowsProcessed(),
                    'errors' => $import->getErrors()
                ]
            ];

            return response()->json($result);

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
                'filters' => 'array',
                'search' => 'nullable|string',
                'province_id' => 'nullable|integer',
                'place_of_birth_province_id' => 'nullable|integer',
                'ethnicity_id' => 'nullable|integer',
                'gender' => 'nullable|in:male,female,other',
                'education_level' => 'nullable|in:vocational,associate,bachelor,master,secondary',
                'accounting_experience_years' => 'nullable|string',
                'current_workplace' => 'nullable|string',
                'source' => 'nullable|string',
                'hard_copy_documents' => 'nullable|in:submitted,not_submitted',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'course_item_id' => 'nullable|integer',
                'status' => 'nullable|string'
            ]);

            // Get filters from both direct params and filters array
            $filters = array_merge(
                $request->input('filters', []),
                $request->only([
                    'search', 'province_id', 'place_of_birth_province_id', 'ethnicity_id',
                    'gender', 'education_level', 'accounting_experience_years', 'current_workplace',
                    'source', 'hard_copy_documents', 'start_date', 'end_date', 'course_item_id', 'status'
                ])
            );

            // Build query with relationships
            $query = Student::with(['province', 'placeOfBirthProvince', 'ethnicity', 'enrollments.courseItem', 'enrollments.payments']);

            // Apply search filter
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

            // Apply province filter
            if (!empty($filters['province_id'])) {
                $query->where('province_id', $filters['province_id']);
            }

            // Apply gender filter
            if (!empty($filters['gender'])) {
                $query->where('gender', $filters['gender']);
            }

            // Apply education level filter
            if (!empty($filters['education_level'])) {
                $query->where('education_level', $filters['education_level']);
            }

            // Apply date range filter
            if (!empty($filters['start_date'])) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            }

            if (!empty($filters['end_date'])) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            }

            // Apply place of birth province filter
            if (!empty($filters['place_of_birth_province_id'])) {
                $query->where('place_of_birth_province_id', $filters['place_of_birth_province_id']);
            }

            // Apply ethnicity filter
            if (!empty($filters['ethnicity_id'])) {
                $query->where('ethnicity_id', $filters['ethnicity_id']);
            }

            // Apply accounting experience years filter
            if (!empty($filters['accounting_experience_years']) && $filters['accounting_experience_years'] !== '') {
                if ($filters['accounting_experience_years'] == '5') {
                    // 5 năm trở lên
                    $query->where('accounting_experience_years', '>=', 5);
                } else {
                    $query->where('accounting_experience_years', $filters['accounting_experience_years']);
                }
            }

            // Apply current workplace filter
            if (!empty($filters['current_workplace'])) {
                $query->where('current_workplace', 'like', '%' . $filters['current_workplace'] . '%');
            }

            // Apply source filter
            if (!empty($filters['source'])) {
                $query->where('source', $filters['source']);
            }

            // Apply hard copy documents filter
            if (!empty($filters['hard_copy_documents'])) {
                $query->where('hard_copy_documents', $filters['hard_copy_documents']);
            }

            // Apply course filter (bao gồm cả khóa con)
            if (!empty($filters['course_item_id'])) {
                $courseItem = \App\Models\CourseItem::find($filters['course_item_id']);
                if ($courseItem) {
                    // Lấy tất cả ID của khóa học này và các khóa học con
                    $courseItemIds = [$courseItem->id];
                    foreach ($courseItem->descendants() as $descendant) {
                        $courseItemIds[] = $descendant->id;
                    }

                    $query->whereHas('enrollments', function($q) use ($courseItemIds) {
                        $q->whereIn('course_item_id', $courseItemIds);
                    });
                }
            }

            // Apply enrollment status filter
            if (!empty($filters['status'])) {
                $statusMap = [
                    'active' => ['active'],
                    'completed' => ['completed'],
                    'waiting' => ['waiting'],
                    'cancelled' => ['cancelled', 'dropped']
                ];

                if (isset($statusMap[$filters['status']])) {
                    $query->whereHas('enrollments', function($q) use ($statusMap, $filters) {
                        $q->whereIn('status', $statusMap[$filters['status']]);
                    });
                }
            }

            $students = $query->orderBy('created_at', 'desc')->get();

            // Default columns if none provided
            $columns = $request->input('columns', [
                'full_name', 'phone', 'email', 'date_of_birth', 'gender',
                'province', 'current_workplace', 'accounting_experience_years',
                'education_level', 'source', 'created_at'
            ]);

            $fileName = 'danh_sach_hoc_vien_' . date('Y_m_d_H_i_s') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\StudentsExport($students, $columns),
                $fileName
            );

        } catch (\Exception $e) {
            \Log::error('Student export error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

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

    /**
     * Đếm tổng số dòng trong file Excel
     */
    private function countExcelRows($file)
    {
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();

            // Đếm số dòng có dữ liệu thực tế
            $dataRowCount = 0;
            for ($row = 2; $row <= $highestRow; $row++) { // Bắt đầu từ row 2 (sau header)
                $hasData = false;
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $cellValue = $worksheet->getCell($col . $row)->getValue();
                    if (!empty($cellValue)) {
                        $hasData = true;
                        break;
                    }
                }
                if ($hasData) {
                    $dataRowCount++;
                }
            }

            return $dataRowCount;

        } catch (\Exception $e) {
            \Log::error('Error counting Excel rows', ['error' => $e->getMessage()]);
            return 0;
        }
    }
}
