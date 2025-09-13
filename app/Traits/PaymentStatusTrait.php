<?php

namespace App\Traits;

trait PaymentStatusTrait
{
    /**
     * Tính toán trạng thái thanh toán chuẩn hóa
     * 
     * @param float $totalFee Tổng học phí
     * @param float $paidAmount Số tiền đã thanh toán
     * @return string Trạng thái thanh toán
     */
    protected function getPaymentStatus($totalFee, $paidAmount)
    {
        // Nếu không có học phí
        if ($totalFee == 0) {
            return 'no_fee';
        }
        
        // Nếu đã thanh toán đủ hoặc thừa
        if ($paidAmount >= $totalFee) {
            return 'paid';
        }
        
        // Nếu đã thanh toán một phần
        if ($paidAmount > 0) {
            return 'partial';
        }
        
        // Chưa thanh toán gì
        return 'unpaid';
    }

    /**
     * Lấy badge HTML cho trạng thái thanh toán
     * 
     * @param string $status Trạng thái thanh toán
     * @return string HTML badge
     */
    protected function getPaymentStatusBadge($status)
    {
        switch ($status) {
            case 'paid':
                return '<span class="badge bg-success">Đã đóng đủ</span>';
            case 'partial':
                return '<span class="badge bg-warning">Đóng một phần</span>';
            case 'unpaid':
                return '<span class="badge bg-danger">Chưa đóng</span>';
            case 'no_fee':
                return '<span class="badge bg-secondary">Miễn phí</span>';
            default:
                return '<span class="badge bg-secondary">Không xác định</span>';
        }
    }

    /**
     * Lấy màu cho trạng thái thanh toán (dùng cho frontend)
     * 
     * @param string $status Trạng thái thanh toán
     * @return string Màu CSS
     */
    protected function getPaymentStatusColor($status)
    {
        switch ($status) {
            case 'paid':
                return 'success';
            case 'partial':
                return 'warning';
            case 'unpaid':
                return 'danger';
            case 'no_fee':
                return 'secondary';
            default:
                return 'secondary';
        }
    }

    /**
     * Lấy text hiển thị cho trạng thái thanh toán
     * 
     * @param string $status Trạng thái thanh toán
     * @return string Text hiển thị
     */
    protected function getPaymentStatusText($status)
    {
        switch ($status) {
            case 'paid':
                return 'Đã đóng đủ';
            case 'partial':
                return 'Đóng một phần';
            case 'unpaid':
                return 'Chưa đóng';
            case 'no_fee':
                return 'Miễn phí';
            default:
                return 'Không xác định';
        }
    }

    /**
     * Tính phần trăm thanh toán
     * 
     * @param float $totalFee Tổng học phí
     * @param float $paidAmount Số tiền đã thanh toán
     * @return float Phần trăm đã thanh toán
     */
    protected function getPaymentPercentage($totalFee, $paidAmount)
    {
        if ($totalFee <= 0) {
            return 0;
        }
        
        return round(($paidAmount / $totalFee) * 100, 2);
    }

    /**
     * Tính số tiền còn thiếu
     * 
     * @param float $totalFee Tổng học phí
     * @param float $paidAmount Số tiền đã thanh toán
     * @return float Số tiền còn thiếu
     */
    protected function getRemainingAmount($totalFee, $paidAmount)
    {
        return max(0, $totalFee - $paidAmount);
    }
}
