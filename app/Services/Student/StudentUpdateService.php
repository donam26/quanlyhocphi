<?php

namespace App\Services\Student;

use App\Contracts\StudentRepositoryInterface;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

/**
 * StudentUpdateService - Chuyên biệt cho việc cập nhật thông tin học viên
 * Tuân thủ Single Responsibility Principle
 */
class StudentUpdateService
{
    protected StudentRepositoryInterface $studentRepository;

    public function __construct(StudentRepositoryInterface $studentRepository)
    {
        $this->studentRepository = $studentRepository;
    }

    /**
     * Cập nhật thông tin học viên
     * 
     * @param Student $student
     * @param array $data
     * @return bool
     * @throws ValidationException
     */
    public function updateStudent(Student $student, array $data): bool
    {
        // Validate dữ liệu
        $validatedData = $this->validateUpdateData($student, $data);

        DB::beginTransaction();
        
        try {
            // Chuẩn bị dữ liệu
            $updateData = $this->prepareUpdateData($validatedData);
            
            // Cập nhật học viên
            $result = $this->studentRepository->update($student, $updateData);
            
            // Xử lý các thông tin bổ sung nếu có
            $this->handleAdditionalUpdates($student, $validatedData);
            
            DB::commit();
            
            return $result;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cập nhật trạng thái học viên
     * 
     * @param Student $student
     * @param string $status
     * @param string|null $reason
     * @return bool
     */
    public function updateStatus(Student $student, string $status, ?string $reason = null): bool
    {
        $data = [
            'status' => $status,
            'status_updated_at' => now()
        ];

        if ($reason) {
            $data['status_reason'] = $reason;
        }

        return $this->studentRepository->update($student, $data);
    }

    /**
     * Cập nhật thông tin liên hệ
     * 
     * @param Student $student
     * @param array $contactData
     * @return bool
     * @throws ValidationException
     */
    public function updateContactInfo(Student $student, array $contactData): bool
    {
        $rules = [
            'email' => [
                'nullable',
                'email',
                Rule::unique('students', 'email')->ignore($student->id)
            ],
            'phone' => [
                'required',
                'string',
                Rule::unique('students', 'phone')->ignore($student->id)
            ],
            'address' => 'nullable|string',
            'province_id' => 'nullable|exists:provinces,id'
        ];

        $validator = Validator::make($contactData, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validatedData = $validator->validated();
        
        // Chuẩn hóa số điện thoại
        if (isset($validatedData['phone'])) {
            $validatedData['phone'] = $this->normalizePhoneNumber($validatedData['phone']);
        }

        // Chuẩn hóa email
        if (isset($validatedData['email'])) {
            $validatedData['email'] = strtolower(trim($validatedData['email']));
        }

        return $this->studentRepository->update($student, $validatedData);
    }

    /**
     * Cập nhật thông tin học vấn
     * 
     * @param Student $student
     * @param array $educationData
     * @return bool
     * @throws ValidationException
     */
    public function updateEducationInfo(Student $student, array $educationData): bool
    {
        $rules = [
            'education_level' => 'nullable|string|max:255',
            'training_specialization' => 'nullable|string|max:255',
            'current_workplace' => 'nullable|string|max:255',
            'accounting_experience_years' => 'nullable|integer|min:0|max:50'
        ];

        $validator = Validator::make($educationData, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $this->studentRepository->update($student, $validator->validated());
    }

    /**
     * Cập nhật ghi chú
     * 
     * @param Student $student
     * @param string $notes
     * @return bool
     */
    public function updateNotes(Student $student, string $notes): bool
    {
        return $this->studentRepository->update($student, [
            'notes' => $notes,
            'notes_updated_at' => now()
        ]);
    }

    /**
     * Validate dữ liệu cập nhật
     * 
     * @param Student $student
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    protected function validateUpdateData(Student $student, array $data): array
    {
        $rules = [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'date_of_birth' => 'sometimes|required|date|before:today',
            'gender' => 'sometimes|nullable|in:male,female,other',
            'email' => [
                'sometimes',
                'nullable',
                'email',
                Rule::unique('students', 'email')->ignore($student->id)
            ],
            'phone' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('students', 'phone')->ignore($student->id)
            ],
            'address' => 'sometimes|nullable|string',
            'province_id' => 'sometimes|nullable|exists:provinces,id',
            'place_of_birth' => 'sometimes|nullable|string|max:255',
            'nation' => 'sometimes|nullable|string|max:255',
            'current_workplace' => 'sometimes|nullable|string|max:255',
            'accounting_experience_years' => 'sometimes|nullable|integer|min:0|max:50',
            'education_level' => 'sometimes|nullable|string|max:255',
            'training_specialization' => 'sometimes|nullable|string|max:255',
            'status' => 'sometimes|nullable|string',
            'notes' => 'sometimes|nullable|string'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Chuẩn bị dữ liệu cập nhật
     * 
     * @param array $data
     * @return array
     */
    protected function prepareUpdateData(array $data): array
    {
        // Chuẩn hóa số điện thoại
        if (isset($data['phone'])) {
            $data['phone'] = $this->normalizePhoneNumber($data['phone']);
        }

        // Chuẩn hóa email
        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        // Thêm timestamp cập nhật
        $data['updated_at'] = now();

        return $data;
    }

    /**
     * Xử lý các cập nhật bổ sung
     * 
     * @param Student $student
     * @param array $data
     */
    protected function handleAdditionalUpdates(Student $student, array $data): void
    {
        // Có thể thêm logic xử lý cập nhật bổ sung ở đây
        // Ví dụ: log changes, send notifications, etc.
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
     * Cập nhật hàng loạt học viên
     * 
     * @param array $studentIds
     * @param array $data
     * @return array
     */
    public function bulkUpdate(array $studentIds, array $data): array
    {
        $results = [
            'success' => [],
            'errors' => []
        ];

        foreach ($studentIds as $studentId) {
            try {
                $student = $this->studentRepository->findByIdOrFail($studentId);
                $this->updateStudent($student, $data);
                $results['success'][] = $studentId;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'student_id' => $studentId,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}
