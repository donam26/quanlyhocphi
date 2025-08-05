<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The enrollment or payment instance.
     */
    public $enrollment;
    public $remaining;

    /**
     * Create a new message instance.
     *
     * @param mixed $data Enrollment hoặc Payment object
     * @param float|null $remaining Số tiền còn thiếu
     * @return void
     */
    public function __construct($data, $remaining = null)
    {
        // Trường hợp nhận vào là enrollment
        if ($data instanceof Enrollment) {
            $this->enrollment = $data;
            $this->remaining = $remaining;
        } 
        // Trường hợp nhận vào là payment
        else if ($data instanceof Payment) {
            $this->enrollment = $data->enrollment;
            
            // Tính toán số tiền còn thiếu nếu không được cung cấp
            if ($remaining === null) {
                $totalPaid = $this->enrollment->payments->where('status', 'confirmed')->sum('amount');
                $this->remaining = $this->enrollment->final_fee - $totalPaid;
            } else {
                $this->remaining = $remaining;
            }
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if (!$this->enrollment || !$this->enrollment->student) {
            throw new \Exception('Enrollment hoặc Student không hợp lệ');
        }

        $subject = 'Nhắc nhở học phí - ' . $this->enrollment->student->full_name;
        
        // Tạo URL để học viên thanh toán
        $paymentUrl = route('payment.gateway.direct', $this->enrollment->id);
        
        return $this->subject($subject)
                    ->view('emails.payment.reminder')
                    ->with([
                        'enrollment' => $this->enrollment,
                        'student' => $this->enrollment->student,
                        'course' => $this->enrollment->courseItem,
                        'remaining' => $this->remaining,
                        'paymentUrl' => $paymentUrl
                    ]);
    }
}
