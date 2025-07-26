<?php

namespace App\Mail;

use App\Models\Payment;
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
     * The payment instance.
     *
     * @var \App\Models\Payment
     */
    public $payment;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($payment)
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
        $subject = 'Nhắc nhở học phí - ' . $this->payment->enrollment->student->full_name;
        
        // Kiểm tra xem payment có phải là object tạm không (trường hợp gửi trực tiếp từ enrollment)
        $isTemporaryPayment = is_object($this->payment) && (strpos($this->payment->id ?? '', 'temp_') === 0 || strpos($this->payment->id ?? '', 'direct_') === 0);
        
        // Tạo URL để học viên thanh toán
        if ($isTemporaryPayment) {
            // Trường hợp gửi trực tiếp từ enrollment
            $paymentUrl = route('payment.gateway.direct', $this->payment->enrollment->id);
        } else {
            // Trường hợp gửi từ payment thực
            $paymentUrl = route('payment.gateway.show', $this->payment->id);
        }
        
        return $this->subject($subject)
                    ->view('emails.payment.reminder')
                    ->with([
                        'payment' => $this->payment,
                        'student' => $this->payment->enrollment->student,
                        'courseItem' => $this->payment->enrollment->courseItem,
                        'amount' => $this->payment->amount,
                        'payment_url' => $paymentUrl
                    ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Thông báo nhắc nhở thanh toán học phí',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payment.reminder',
            with: [
                'payment' => $this->payment,
                'paymentUrl' => route('payment.gateway.show', $this->payment),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
