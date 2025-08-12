<?php

namespace App\Contracts;

use App\Models\Payment;
use App\Models\Enrollment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * PaymentRepositoryInterface - Interface cho Payment Repository
 */
interface PaymentRepositoryInterface
{
    /**
     * Lấy tất cả thanh toán với phân trang
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Tìm thanh toán theo ID
     */
    public function findById(int $id): ?Payment;

    /**
     * Tạo thanh toán mới
     */
    public function create(array $data): Payment;

    /**
     * Cập nhật thanh toán
     */
    public function update(Payment $payment, array $data): bool;

    /**
     * Xóa thanh toán
     */
    public function delete(Payment $payment): bool;

    /**
     * Lấy thanh toán theo enrollment
     */
    public function getByEnrollment(Enrollment $enrollment): Collection;

    /**
     * Lấy thanh toán theo trạng thái
     */
    public function getByStatus(string $status): Collection;

    /**
     * Lấy thanh toán theo phương thức
     */
    public function getByMethod(string $method): Collection;

    /**
     * Lấy thanh toán trong khoảng thời gian
     */
    public function getInPeriod(Carbon $from, Carbon $to): Collection;

    /**
     * Tính tổng doanh thu
     */
    public function getTotalRevenue(): float;

    /**
     * Tính doanh thu theo tháng
     */
    public function getMonthlyRevenue(int $year, int $month): float;

    /**
     * Lấy thanh toán chờ xác nhận
     */
    public function getPendingPayments(): Collection;

    /**
     * Xác nhận thanh toán
     */
    public function confirm(Payment $payment): bool;

    /**
     * Hủy thanh toán
     */
    public function cancel(Payment $payment): bool;
}
