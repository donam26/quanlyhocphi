<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentService
{
    public function getPayments($filters = [])
    {
        $query = Payment::with(['enrollment.student', 'enrollment.courseItem']);
        
        if (isset($filters['student_id'])) {
            $query->whereHas('enrollment', function($q) use ($filters) {
                $q->where('student_id', $filters['student_id']);
            });
        }
        
        if (isset($filters['course_item_id'])) {
            $query->whereHas('enrollment', function($q) use ($filters) {
                $q->where('course_item_id', $filters['course_item_id']);
            });
        }
        
        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['date_from'])) {
            $query->where('payment_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('payment_date', '<=', $filters['date_to']);
        }
        
        return $query->orderBy('payment_date', 'desc')
            ->paginate(isset($filters['per_page']) ? $filters['per_page'] : 15);
    }

    public function getPayment($id)
    {
        return Payment::with(['enrollment.student', 'enrollment.courseItem'])->findOrFail($id);
    }

    public function createPayment(array $data)
    {
        DB::beginTransaction();
        
        try {
            $payment = Payment::create([
                'enrollment_id' => $data['enrollment_id'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_date' => $data['payment_date'] ?? now(),
                'status' => $data['status'] ?? 'confirmed',
                'transaction_id' => $data['transaction_id'] ?? null,
                'notes' => $data['notes'] ?? null
            ]);
            
            // Nếu payment là từ online gateway, cập nhật trạng thái
            if (isset($data['payment_method']) && $data['payment_method'] === 'sepay') {
                $payment->update([
                    'transaction_id' => $data['transaction_id'] ?? null
                ]);
            }
            
            DB::commit();
            return $payment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updatePayment(Payment $payment, array $data)
    {
        $payment->update($data);
        return $payment;
    }

    public function deletePayment(Payment $payment)
    {
        return $payment->delete();
    }

    public function getPaymentsByEnrollment(Enrollment $enrollment)
    {
        return $enrollment->payments()->orderBy('payment_date', 'desc')->get();
    }

    public function getTotalPaidForEnrollment(Enrollment $enrollment)
    {
        return $enrollment->payments()
            ->where('status', 'confirmed')
            ->sum('amount');
    }

    /**
     * Xử lý bulk actions trên thanh toán
     */
    public function processBulkAction(array $paymentIds, string $action): array
    {
        $payments = Payment::whereIn('id', $paymentIds)->get();
        $count = 0;
        $failedIds = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($payments as $payment) {
                switch ($action) {
                    case 'confirm':
                        $this->updatePayment($payment, ['status' => 'confirmed']);
                        $count++;
                        break;
                        
                    case 'cancel':
                        $this->updatePayment($payment, ['status' => 'cancelled']);
                        $count++;
                        break;
                        
                    case 'refund':
                        $this->updatePayment($payment, ['status' => 'refunded']);
                        $count++;
                        break;
                    
                    default:
                        $failedIds[] = $payment->id;
                }
            }
            
            DB::commit();
            
            return [
                'total' => count($paymentIds),
                'processed' => $count,
                'failed' => count($failedIds),
                'failed_ids' => $failedIds,
                'message' => "Đã xử lý $count thanh toán thành công!"
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            throw $e;
        }
    }

    public function getPaymentStats($startDate = null, $endDate = null)
    {
        $query = Payment::where('status', 'confirmed');
        
        if ($startDate) {
            $query->where('payment_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('payment_date', '<=', $endDate);
        }
        
        $payments = $query->get();
        
        return [
            'total_amount' => $payments->sum('amount'),
            'count' => $payments->count(),
            'by_method' => $payments->groupBy('payment_method')->map->sum('amount'),
            'by_date' => $payments->groupBy(function($item) {
                return Carbon::parse($item->payment_date)->format('Y-m-d');
            })->map->sum('amount')
        ];
    }

    public function generateReceipt(Payment $payment)
    {
        $receiptNumber = 'REC-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
        
        $receipt = [
            'number' => $receiptNumber,
            'date' => $payment->formatted_payment_date,
            'amount' => $payment->amount,
            'payment_method' => $this->getPaymentMethodText($payment->payment_method),
            'student' => $payment->enrollment->student->full_name,
            'course' => $payment->enrollment->courseItem->name,
            'notes' => $payment->notes
        ];
        
        return $receipt;
    }

    public function generateBulkReceipt($paymentIds)
    {
        $payments = Payment::whereIn('id', $paymentIds)
            ->with(['enrollment.student', 'enrollment.courseItem'])
            ->get();
            
        if ($payments->isEmpty()) {
            return null;
        }
        
        $receiptNumber = 'BULK-' . date('Ymd') . '-' . str_pad(count($payments), 3, '0', STR_PAD_LEFT);
        
        $receipt = [
            'number' => $receiptNumber,
            'date' => now()->format(config('app.date_format', 'd/m/Y')),
            'total_amount' => $payments->sum('amount'),
            'payment_count' => $payments->count(),
            'payments' => $payments->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'date' => $payment->formatted_payment_date,
                    'amount' => $payment->amount,
                    'payment_method' => $this->getPaymentMethodText($payment->payment_method),
                    'student' => $payment->enrollment->student->full_name,
                    'course' => $payment->enrollment->courseItem->name
                ];
            })
        ];
        
        return $receipt;
    }

    public function sendPaymentConfirmation(Payment $payment)
    {
        $student = $payment->enrollment->student;
        
        if ($student->email) {
            // Gửi email xác nhận thanh toán
            \Illuminate\Support\Facades\Mail::to($student->email)->send(new \App\Mail\PaymentConfirmationMail($payment));
            return true;
        }
        
        return false;
    }

    public function sendPaymentReminder(Enrollment $enrollment)
    {
        $student = $enrollment->student;
        
        if ($student && $student->email) {
            // Tính toán số tiền còn thiếu
            $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
            $remaining = $enrollment->final_fee - $totalPaid;
            
            if ($remaining <= 0) {
                return false; // Không cần gửi nếu đã thanh toán đủ
            }
            
            // Gửi email nhắc thanh toán
            \Illuminate\Support\Facades\Mail::to($student->email)->send(
                new \App\Mail\PaymentReminderMail($enrollment, $remaining)
            );
            return true;
        }
        
        return false;
    }

    private function getPaymentMethodText($method)
    {
        switch ($method) {
            case 'cash':
                return 'Tiền mặt';
            case 'bank_transfer':
                return 'Chuyển khoản';
            case 'card':
                return 'Thẻ tín dụng';
            case 'qr_code':
                return 'Quét QR';
            case 'sepay':
                return 'SEPAY';
            default:
                return 'Không xác định';
        }
    }
} 