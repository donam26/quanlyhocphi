<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Student;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Enums\EnrollmentStatus;
use App\Enums\CourseStatus;

class ValidEnrollmentRule implements ValidationRule
{
    private $studentId;
    private $courseItemId;
    private $excludeEnrollmentId;

    /**
     * Create a new rule instance.
     *
     * @param int|null $studentId
     * @param int|null $courseItemId
     * @param int|null $excludeEnrollmentId For update operations
     */
    public function __construct($studentId = null, $courseItemId = null, $excludeEnrollmentId = null)
    {
        $this->studentId = $studentId;
        $this->courseItemId = $courseItemId;
        $this->excludeEnrollmentId = $excludeEnrollmentId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Get student and course IDs from request if not provided
        $studentId = $this->studentId ?? request('student_id');
        $courseItemId = $this->courseItemId ?? request('course_item_id');

        if (!$studentId || !$courseItemId) {
            return; // Let other validation rules handle missing required fields
        }

        // Check if student exists
        $student = Student::find($studentId);
        if (!$student) {
            $fail('Học viên không tồn tại.');
            return;
        }

        // Check if course exists
        $courseItem = CourseItem::find($courseItemId);
        if (!$courseItem) {
            $fail('Khóa học không tồn tại.');
            return;
        }

        // Check if course is active
        if (!$courseItem->active) {
            $fail('Khóa học này hiện không hoạt động.');
            return;
        }

        // Check if course is not completed
        if ($courseItem->status === CourseStatus::COMPLETED) {
            $fail('Khóa học này đã kết thúc, không thể ghi danh mới.');
            return;
        }

        // Check if course is a leaf node (can be enrolled)
        if (!$courseItem->is_leaf) {
            $fail('Chỉ có thể ghi danh vào các khóa học cụ thể, không thể ghi danh vào nhóm khóa học.');
            return;
        }

        // Check for duplicate enrollment
        $existingEnrollment = Enrollment::where('student_id', $studentId)
            ->where('course_item_id', $courseItemId)
            ->whereIn('status', [
                EnrollmentStatus::WAITING->value,
                EnrollmentStatus::ACTIVE->value
            ]);

        // Exclude current enrollment for update operations
        if ($this->excludeEnrollmentId) {
            $existingEnrollment->where('id', '!=', $this->excludeEnrollmentId);
        }

        if ($existingEnrollment->exists()) {
            $fail('Học viên đã được ghi danh vào khóa học này.');
            return;
        }

        // Check for enrollment in sibling courses (same parent)
        if ($courseItem->parent_id) {
            $siblingCourseIds = CourseItem::where('parent_id', $courseItem->parent_id)
                ->where('id', '!=', $courseItemId)
                ->pluck('id')
                ->toArray();

            if (!empty($siblingCourseIds)) {
                $siblingEnrollment = Enrollment::where('student_id', $studentId)
                    ->whereIn('course_item_id', $siblingCourseIds)
                    ->whereIn('status', [
                        EnrollmentStatus::WAITING->value,
                        EnrollmentStatus::ACTIVE->value
                    ]);

                // Exclude current enrollment for update operations
                if ($this->excludeEnrollmentId) {
                    $siblingEnrollment->where('id', '!=', $this->excludeEnrollmentId);
                }

                if ($siblingEnrollment->exists()) {
                    $existingSiblingEnrollment = $siblingEnrollment->with('courseItem')->first();
                    $siblingCourseName = $existingSiblingEnrollment->courseItem->name ?? 'khóa học khác';
                    $fail("Học viên đã được ghi danh vào khóa học '{$siblingCourseName}' trong cùng nhóm khóa học. Một học viên chỉ có thể ghi danh vào một khóa con trong cùng nhóm.");
                    return;
                }
            }
        }

        // Check course capacity if defined
        if (isset($courseItem->custom_fields['max_students'])) {
            $maxStudents = (int) $courseItem->custom_fields['max_students'];
            $currentEnrollments = Enrollment::where('course_item_id', $courseItemId)
                ->where('status', EnrollmentStatus::ACTIVE->value)
                ->count();

            if ($currentEnrollments >= $maxStudents) {
                $fail('Khóa học đã đạt số lượng học viên tối đa.');
                return;
            }
        }

        // Check prerequisites if defined
        if (isset($courseItem->custom_fields['prerequisites'])) {
            $prerequisites = $courseItem->custom_fields['prerequisites'];
            if (is_array($prerequisites)) {
                foreach ($prerequisites as $prerequisiteId) {
                    $hasPrerequisite = Enrollment::where('student_id', $studentId)
                        ->where('course_item_id', $prerequisiteId)
                        ->where('status', EnrollmentStatus::COMPLETED->value)
                        ->exists();

                    if (!$hasPrerequisite) {
                        $prerequisiteCourse = CourseItem::find($prerequisiteId);
                        $courseName = $prerequisiteCourse ? $prerequisiteCourse->name : "ID: $prerequisiteId";
                        $fail("Học viên chưa hoàn thành khóa học tiên quyết: $courseName");
                        return;
                    }
                }
            }
        }

        // Check if student has required documents for special courses
        if ($courseItem->is_special) {
            if ($student->hard_copy_documents !== 'submitted') {
                $fail('Khóa học này yêu cầu học viên phải nộp đầy đủ hồ sơ giấy tờ.');
                return;
            }

            // Additional checks for accounting courses
            if (str_contains(strtolower($courseItem->name), 'kế toán trưởng')) {
                if (empty($student->current_workplace)) {
                    $fail('Khóa học Kế toán trưởng yêu cầu thông tin nơi công tác hiện tại.');
                    return;
                }

                if (empty($student->accounting_experience_years) || $student->accounting_experience_years < 1) {
                    $fail('Khóa học Kế toán trưởng yêu cầu ít nhất 1 năm kinh nghiệm làm kế toán.');
                    return;
                }

                if (empty($student->education_level)) {
                    $fail('Khóa học Kế toán trưởng yêu cầu thông tin trình độ học vấn.');
                    return;
                }
            }
        }
    }
}
