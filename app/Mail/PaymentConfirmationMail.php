<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PaymentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Thanh toán
     *
     * @var \App\Models\Payment
     */
    public $payment;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = 'Xác nhận thanh toán - ' . $this->payment->enrollment->student->full_name;
        
        return $this->subject($subject)
                    ->view('emails.payment.confirmation')
                    ->with([
                        'payment' => $this->payment,
                        'student' => $this->payment->enrollment->student,
                        'courseItem' => $this->payment->enrollment->courseItem,
                        'amount' => $this->payment->amount,
                    ]);
    }
} 