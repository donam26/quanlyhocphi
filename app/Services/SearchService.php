<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Student;
use App\Models\Enrollment;
use App\Enums\EnrollmentStatus;

class SearchService
{
    public function searchStudents($term = null, $studentId = null)
    {
        // Khởi tạo query
        $query = Student::query();
        
        if ($studentId) {
            // Tìm kiếm theo ID học viên
            $query->where('id', $studentId);
        } elseif ($term) {
            // Tìm kiếm theo tên hoặc số điện thoại
            $query->search($term);
        }
        
        // Lấy kết quả với các quan hệ
        $students = $query->with(['enrollments' => function($query) {
                $query->with(['courseItem', 'payments' => function($query) {
                    $query->where('payments.status', 'confirmed')->orderBy('payment_date', 'desc');
                }]);
            }])
            ->paginate(10);
            
        // Lấy thông tin chi tiết của mỗi học viên
        $studentsDetails = [];
        foreach ($students as $student) {
            $enrollmentsData = [];
            
            foreach ($student->enrollments as $enrollment) {
                // Bỏ qua các ghi danh đã hủy
                if ($enrollment->status === EnrollmentStatus::CANCELLED) {
                    continue;
                }
                
                $totalPaid = $enrollment->getTotalPaidAmount();
                $remainingAmount = $enrollment->getRemainingAmount();
                $paymentStatus = $enrollment->isFullyPaid() ? 'Đã thanh toán đủ' : 'Còn thiếu';
                
                // Lấy thông tin thanh toán gần nhất
                $latestPayment = $enrollment->payments->first();
                $paymentMethod = $latestPayment ? $this->getPaymentMethodText($latestPayment->payment_method) : 'Chưa thanh toán';
                
                $enrollmentsData[] = [
                    'id' => $enrollment->id,
                    'course_item' => $enrollment->courseItem,
                    'enrollment_date' => $enrollment->formatted_enrollment_date,
                    'status' => $this->getEnrollmentStatusText($enrollment->status),
                    'final_fee' => $enrollment->final_fee,
                    'total_paid' => $totalPaid,
                    'remaining_amount' => $remainingAmount,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $paymentMethod,
                    'payments' => $enrollment->payments
                ];
            }
            
            // Lấy danh sách chờ của học viên
            $waitingLists = Enrollment::where('student_id', $student->id)
                ->with('courseItem')
                ->where('enrollments.status', EnrollmentStatus::WAITING)
                ->get();
                
            $waitingListData = [];
            foreach ($waitingLists as $waitingList) {
                $waitingListData[] = [
                    'id' => $waitingList->id,
                    'course_item' => $waitingList->courseItem,
                    'registration_date' => $waitingList->formatted_request_date ?: $waitingList->formatted_enrollment_date,
                    'status' => $waitingList->status,
                    'notes' => $waitingList->notes
                ];
            }
            
            $studentsDetails[] = [
                'student' => $student,
                'enrollments' => $enrollmentsData,
                'waiting_lists' => $waitingListData,
                'total_courses' => count($enrollmentsData),
                'total_paid' => $student->getTotalPaidAmount(),
                'total_fee' => $student->getTotalFeeAmount(),
                'remaining' => $student->getRemainingAmount()
            ];
        }
        
        return [
            'searchTerm' => $term,
            'studentsDetails' => $studentsDetails,
            'students' => $students
        ];
    }

    public function getStudentHistory($studentId)
    {
        $student = Student::with(['enrollments.courseItem', 'enrollments.payments'])->findOrFail($studentId);
        
        // Lấy lịch sử thanh toán
        $payments = Payment::whereHas('enrollment', function($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->with(['enrollment.courseItem'])
            ->orderBy('payment_date', 'desc')
            ->get();
        
        // Lấy lịch sử điểm danh
        $attendances = $student->attendances()
            ->with(['courseItem'])
            ->orderBy('attendance_date', 'desc')
            ->get();
            
        return [
            'student' => $student,
            'payments' => $payments,
            'attendances' => $attendances
        ];
    }
    
    private function getEnrollmentStatusText($status)
    {
        if ($status instanceof EnrollmentStatus) {
            return $status->label();
        }
        
        // Fallback cho trường hợp status là string
        $enum = EnrollmentStatus::fromString($status);
        return $enum ? $enum->label() : $status;
    }
    
    private function getPaymentMethodText($method)
    {
        switch ($method) {
            case 'cash':
                return 'Tiền mặt';
            case 'bank_transfer':
                return 'Chuyển khoản';
            case 'sepay':
                return 'SEPAY';
            default:
                return $method;
        }
    }
} 