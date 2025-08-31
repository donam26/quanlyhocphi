<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentHistoryExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    protected $payments;
    protected $columns;

    public function __construct($payments, $columns = [])
    {
        $this->payments = $payments;
        $this->columns = $columns ?: ['full_name', 'phone', 'email', 'date_of_birth', 'course_registered'];
    }

    public function collection()
    {
        return $this->payments;
    }

    public function headings(): array
    {
        $headings = [
            'Mã giao dịch',
            'Ngày thanh toán',
            'Số tiền',
            'Phương thức',
            'Trạng thái',
            'Ghi chú'
        ];

        // Thêm các cột tùy chọn
        $columnMap = [
            'full_name' => 'Họ và tên',
            'phone' => 'Số điện thoại',
            'email' => 'Email',
            'date_of_birth' => 'Ngày sinh',
            'gender' => 'Giới tính',
            'address' => 'Địa chỉ',
            'province' => 'Địa chỉ hiện tại',
            'workplace' => 'Nơi làm việc',
            'experience_years' => 'Kinh nghiệm',
            'course_registered' => 'Khóa học đã đăng ký'
        ];

        foreach ($this->columns as $column) {
            if (isset($columnMap[$column])) {
                $headings[] = $columnMap[$column];
            }
        }

        return $headings;
    }

    public function map($payment): array
    {
        $student = $payment->enrollment->student;
        $courseItem = $payment->enrollment->courseItem;

        $row = [
            $payment->transaction_reference ?: 'PT' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
            $payment->payment_date->format('d/m/Y H:i'),
            number_format($payment->amount),
            $this->getPaymentMethodText($payment->payment_method),
            $this->getPaymentStatusText($payment->status),
            $payment->notes ?: ''
        ];

        // Thêm các cột tùy chọn
        foreach ($this->columns as $column) {
            switch ($column) {
                case 'full_name':
                    $row[] = $student->full_name;
                    break;
                case 'phone':
                    $row[] = $student->phone ?: '';
                    break;
                case 'email':
                    $row[] = $student->email ?: '';
                    break;
                case 'date_of_birth':
                    $row[] = $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '';
                    break;
                case 'gender':
                    $row[] = $this->getGenderText($student->gender);
                    break;
                case 'address':
                    $row[] = $student->address ?: '';
                    break;
                case 'province':
                    $row[] = $student->province ? $student->province->name : '';
                    break;
                case 'workplace':
                    $row[] = $student->workplace ?: '';
                    break;
                case 'experience_years':
                    $row[] = $student->experience_years ?: '';
                    break;
                case 'course_registered':
                    $row[] = $courseItem->name;
                    break;
                default:
                    $row[] = '';
                    break;
            }
        }

        return $row;
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

    private function getGenderText($gender)
    {
        $genderMap = [
            'male' => 'Nam',
            'female' => 'Nữ',
            'other' => 'Khác'
        ];
        return $genderMap[$gender] ?? '';
    }
}