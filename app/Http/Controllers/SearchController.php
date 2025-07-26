<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\WaitingList;
use App\Models\Payment;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Hiển thị trang tìm kiếm
     */
    public function index()
    {
        return view('search.index');
    }
    
    /**
     * Xử lý tìm kiếm và hiển thị kết quả
     */
    public function search(Request $request)
    {
        // Validate dữ liệu
        $request->validate([
            'term' => 'required|min:2',
        ]);
        
        $term = $request->input('term');
        
        // Tìm kiếm học viên theo tên hoặc số điện thoại
        $students = Student::search($term)
            ->with(['enrollments' => function($query) {
                $query->with(['courseItem', 'payments' => function($query) {
                    $query->where('status', 'confirmed')->orderBy('payment_date', 'desc');
                }]);
            }])
            ->paginate(10);
            
        // Lấy thông tin chi tiết của mỗi học viên
        $studentsDetails = [];
        foreach ($students as $student) {
            $enrollmentsData = [];
            
            foreach ($student->enrollments as $enrollment) {
                // Bỏ qua các ghi danh đã hủy
                if ($enrollment->status === 'cancelled') {
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
                    'enrollment_date' => $enrollment->enrollment_date->format('d/m/Y'),
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
            $waitingLists = WaitingList::where('student_id', $student->id)
                ->with('courseItem')
                ->where('status', 'waiting')
                ->get();
                
            $waitingListData = [];
            foreach ($waitingLists as $waitingList) {
                $waitingListData[] = [
                    'id' => $waitingList->id,
                    'course_item' => $waitingList->courseItem,
                    'registration_date' => $waitingList->registration_date->format('d/m/Y'),
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
        
        return view('search.index', [
            'searchTerm' => $term,
            'studentsDetails' => $studentsDetails,
            'students' => $students
        ]);
    }
    
    /**
     * Hiển thị lịch sử của học viên
     */
    public function studentHistory($studentId)
    {
        $student = Student::with(['enrollments.courseItem', 'enrollments.payments', 'waitingLists.courseItem'])->findOrFail($studentId);
        
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
            
        return view('search.student-history', [
            'student' => $student,
            'payments' => $payments,
            'attendances' => $attendances
        ]);
    }
    
    /**
     * Lấy tên trạng thái ghi danh
     */
    private function getEnrollmentStatusText($status)
    {
        switch ($status) {
            case 'enrolled':
                return 'Đang học';
            case 'completed':
                return 'Đã hoàn thành';
            case 'on_hold':
                return 'Tạm dừng';
            case 'cancelled':
                return 'Đã hủy';
            default:
                return $status;
        }
    }
    
    /**
     * Lấy tên phương thức thanh toán
     */
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
