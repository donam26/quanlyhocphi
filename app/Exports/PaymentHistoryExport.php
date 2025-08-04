<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentHistoryExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    protected $payments;

    public function __construct($payments)
    {
        $this->payments = $payments;
    }

    public function collection()
    {
        return $this->payments;
    }

    public function headings(): array
    {
        return [
            'Mã giao dịch',
            'Ngày thanh toán',
            'Học viên',
            'Số điện thoại',
            'Email',
            'Khóa học',
            'Số tiền',
            'Phương thức',
            'Trạng thái',
            'Ghi chú',
            'Ngày tạo'
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->transaction_reference ?: 'PT' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
            $payment->payment_date->format('d/m/Y H:i'),
            $payment->enrollment->student->full_name,
            $payment->enrollment->student->phone,
            $payment->enrollment->student->email,
            $payment->enrollment->courseItem->name,
            number_format($payment->amount),
            $this->getPaymentMethodText($payment->payment_method),
            $this->getPaymentStatusText($payment->status),
            $payment->notes,
            $payment->created_at->format('d/m/Y H:i')
        ];
    }

    private function getPaymentMethodText($method)
    {
        $methodMap = [
            'cash' => 'Tiền mặt',
            'bank_transfer' => 'Chuyển khoản',
            'card' => 'Thẻ',
            'qr_code' => 'Mã QR',
            'sepay' => 'SePay',
            'other' => 'Khác'
        ];
        return $methodMap[$method] ?? $method;
    }

    private function getPaymentStatusText($status)
    {
        $statusMap = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'cancelled' => 'Đã hủy',
            'refunded' => 'Đã hoàn tiền'
        ];
        return $statusMap[$status] ?? $status;
    }
} 