<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\Enrollment;
use App\Services\SePayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class PaymentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The enrollment or payment instance.
     */
    public $enrollment;
    public $remaining;
    public $qrData;
    public $paymentLink;

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

        // Generate QR code cho thanh toán
        $this->generatePaymentQR();

        // Tạo payment link public
        $this->generatePaymentLink();
    }

    /**
     * Generate QR code for payment
     */
    private function generatePaymentQR()
    {
        try {
            // Tạo payment record tạm thời để generate QR
            $tempPayment = new Payment([
                'enrollment_id' => $this->enrollment->id,
                'amount' => $this->remaining,
                'payment_method' => 'sepay',
                'payment_date' => now(),
                'status' => 'pending'
            ]);

            // Load relationships cho temp payment
            $tempPayment->setRelation('enrollment', $this->enrollment);

            // Generate QR code
            $sePayService = app(SePayService::class);
            $qrResult = $sePayService->generateQR($tempPayment);

            if ($qrResult['success']) {
                $this->qrData = $qrResult['data'];
            }
        } catch (\Exception $e) {
            // Log error nhưng không throw exception để email vẫn gửi được
            \Log::error('Failed to generate QR for payment reminder: ' . $e->getMessage());
            $this->qrData = null;
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

        $subject = 'Nhắc nhở thanh toán học phí - ' . $this->enrollment->courseItem->name;

        return $this->subject($subject)
                    ->view('emails.payment.reminder')
                    ->with([
                        'enrollment' => $this->enrollment,
                        'student' => $this->enrollment->student,
                        'course' => $this->enrollment->courseItem,
                        'remaining' => $this->remaining,
                        'qrData' => $this->qrData,
                        'paymentLink' => $this->paymentLink,
                        'formattedAmount' => number_format($this->remaining, 0, ',', '.') . ' VND',
                        'dueDate' => now()->addDays(7)->format('d/m/Y'), // 7 ngày để thanh toán
                    ]);
    }

    /**
     * Generate payment link
     */
    private function generatePaymentLink()
    {
        try {
            // Tạo token cho enrollment
            $token = \App\Http\Controllers\PublicPaymentController::generatePaymentToken($this->enrollment->id);
            $this->paymentLink = config('app.url') . '/pay/' . $token;
        } catch (\Exception $e) {
            Log::error('Error generating payment link', [
                'enrollment_id' => $this->enrollment->id,
                'error' => $e->getMessage()
            ]);
            $this->paymentLink = null;
        }
    }
}
