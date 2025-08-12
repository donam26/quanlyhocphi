<?php

namespace App\Services\Student;

use App\Contracts\StudentRepositoryInterface;
use App\Models\Student;
use App\Enums\StudentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * StudentCreationService - Chuyên biệt cho việc tạo học viên mới
 * Tuân thủ Single Responsibility Principle
 */
class StudentCreationService
{
    protected StudentRepositoryInterface $studentRepository;

    public function __construct(StudentRepositoryInterface $studentRepository)
    {
        $this->studentRepository = $studentRepository;
    }

    /**
     * Tạo học viên mới với validation
     * 
     * @param array $data
     * @return Student
     * @throws ValidationException
     */
    public function createStudent(array $data): Student
    {
        // Validate dữ liệu
        $validatedData = $this->validateStudentData($data);

        DB::beginTransaction();
        
        try {
            // Chuẩn bị dữ liệu
            $studentData = $this->prepareStudentData($validatedData);
            
            // Tạo học viên
            $student = $this->studentRepository->create($studentData);
            
            // Xử lý các thông tin bổ sung nếu có
            $this->handleAdditionalData($student, $validatedData);
            
            DB::commit();
            
            return $student;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Tạo nhiều học viên từ import
     * 
     * @param array $studentsData
     * @return array
     */
    public function createMultipleStudents(array $studentsData): array
    {
        $results = [
            'success' => [],
            'errors' => []
        ];

        DB::beginTransaction();
        
        try {
            foreach ($studentsData as $index => $studentData) {
                try {
                    $student = $this->createStudent($studentData);
                    $results['success'][] = [
                        'index' => $index,
                        'student' => $student,
                        'data' => $studentData
                    ];
                } catch (ValidationException $e) {
                    $results['errors'][] = [
                        'index' => $index,
                        'errors' => $e->errors(),
                        'data' => $studentData
                    ];
                }
            }
            
            // Chỉ commit nếu không có lỗi
            if (empty($results['errors'])) {
                DB::commit();
            } else {
                DB::rollBack();
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * Validate dữ liệu học viên
     * 
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    protected function validateStudentData(array $data): array
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'email' => 'nullable|email|unique:students,email',
            'phone' => 'required|string|unique:students,phone',
            'address' => 'nullable|string',
            'province_id' => 'nullable|exists:provinces,id',
            'place_of_birth' => 'nullable|string|max:255',
            'nation' => 'nullable|string|max:255',
            'current_workplace' => 'nullable|string|max:255',
            'accounting_experience_years' => 'nullable|integer|min:0|max:50',
            'education_level' => 'nullable|string|max:255',
            'training_specialization' => 'nullable|string|max:255',
            'status' => 'nullable|string',
            'notes' => 'nullable|string'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Chuẩn bị dữ liệu học viên
     * 
     * @param array $data
     * @return array
     */
    protected function prepareStudentData(array $data): array
    {
        // Set default status nếu không có
        if (!isset($data['status'])) {
            $data['status'] = StudentStatus::ACTIVE;
        }

        // Chuẩn hóa số điện thoại
        if (isset($data['phone'])) {
            $data['phone'] = $this->normalizePhoneNumber($data['phone']);
        }

        // Chuẩn hóa email
        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        return $data;
    }

    /**
     * Xử lý các thông tin bổ sung
     * 
     * @param Student $student
     * @param array $data
     */
    protected function handleAdditionalData(Student $student, array $data): void
    {
        // Có thể thêm logic xử lý thông tin bổ sung ở đây
        // Ví dụ: upload avatar, gửi email chào mừng, etc.
    }

    /**
     * Chuẩn hóa số điện thoại
     * 
     * @param string $phone
     * @return string
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Loại bỏ các ký tự không phải số
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Chuẩn hóa số điện thoại Việt Nam
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return $phone;
        }
        
        if (strlen($phone) === 9) {
            return '0' . $phone;
        }
        
        if (strlen($phone) === 11 && substr($phone, 0, 2) === '84') {
            return '0' . substr($phone, 2);
        }
        
        return $phone;
    }

    /**
     * Kiểm tra học viên đã tồn tại chưa
     * 
     * @param array $data
     * @return Student|null
     */
    public function findExistingStudent(array $data): ?Student
    {
        if (isset($data['phone'])) {
            $normalizedPhone = $this->normalizePhoneNumber($data['phone']);
            $student = $this->studentRepository->findByPhone($normalizedPhone);
            if ($student) {
                return $student;
            }
        }

        if (isset($data['email'])) {
            $normalizedEmail = strtolower(trim($data['email']));
            return $this->studentRepository->findByEmail($normalizedEmail);
        }

        return null;
    }

    /**
     * Tạo học viên với kiểm tra trùng lặp
     * 
     * @param array $data
     * @param bool $allowDuplicate
     * @return Student
     * @throws \Exception
     */
    public function createStudentWithDuplicateCheck(array $data, bool $allowDuplicate = false): Student
    {
        if (!$allowDuplicate) {
            $existingStudent = $this->findExistingStudent($data);
            if ($existingStudent) {
                throw new \Exception('Học viên đã tồn tại với số điện thoại hoặc email này.');
            }
        }

        return $this->createStudent($data);
    }
}
