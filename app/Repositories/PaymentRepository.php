<?php

namespace App\Repositories;

use App\Contracts\PaymentRepositoryInterface;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Enums\PaymentStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * PaymentRepository - Concrete implementation của PaymentRepositoryInterface
 */
class PaymentRepository implements PaymentRepositoryInterface
{
    protected Payment $model;

    public function __construct(Payment $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['enrollment.student', 'enrollment.courseItem'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?Payment
    {
        return $this->model->with(['enrollment.student', 'enrollment.courseItem'])->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Payment
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(Payment $payment, array $data): bool
    {
        return $payment->update($data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Payment $payment): bool
    {
        return $payment->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function getByEnrollment(Enrollment $enrollment): Collection
    {
        return $this->model->where('enrollment_id', $enrollment->id)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)
            ->with(['enrollment.student', 'enrollment.courseItem'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByMethod(string $method): Collection
    {
        return $this->model->where('payment_method', $method)
            ->with(['enrollment.student', 'enrollment.courseItem'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getInPeriod(Carbon $from, Carbon $to): Collection
    {
        return $this->model->whereBetween('payment_date', [$from, $to])
            ->with(['enrollment.student', 'enrollment.courseItem'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalRevenue(): float
    {
        return $this->model->where('status', PaymentStatus::CONFIRMED)
            ->sum('amount');
    }

    /**
     * {@inheritDoc}
     */
    public function getMonthlyRevenue(int $year, int $month): float
    {
        return $this->model->where('status', PaymentStatus::CONFIRMED)
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->sum('amount');
    }

    /**
     * {@inheritDoc}
     */
    public function getPendingPayments(): Collection
    {
        return $this->model->where('status', PaymentStatus::PENDING)
            ->with(['enrollment.student', 'enrollment.courseItem'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function confirm(Payment $payment): bool
    {
        return $payment->update([
            'status' => PaymentStatus::CONFIRMED,
            'confirmed_at' => now()
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function cancel(Payment $payment): bool
    {
        return $payment->update([
            'status' => PaymentStatus::CANCELLED,
            'cancelled_at' => now()
        ]);
    }

    /**
     * Lấy doanh thu theo khóa học
     */
    public function getRevenueByCourse(): Collection
    {
        return $this->model->join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
            ->join('course_items', 'enrollments.course_item_id', '=', 'course_items.id')
            ->where('payments.status', PaymentStatus::CONFIRMED)
            ->selectRaw('course_items.name, SUM(payments.amount) as total_revenue, COUNT(payments.id) as payment_count')
            ->groupBy('course_items.id', 'course_items.name')
            ->orderBy('total_revenue', 'desc')
            ->get();
    }

    /**
     * Lấy doanh thu theo phương thức thanh toán
     */
    public function getRevenueByMethod(): Collection
    {
        return $this->model->where('status', PaymentStatus::CONFIRMED)
            ->selectRaw('payment_method, SUM(amount) as total_revenue, COUNT(*) as payment_count')
            ->groupBy('payment_method')
            ->orderBy('total_revenue', 'desc')
            ->get();
    }

    /**
     * Lấy doanh thu theo ngày trong tháng
     */
    public function getDailyRevenueInMonth(int $year, int $month): Collection
    {
        return $this->model->where('status', PaymentStatus::CONFIRMED)
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->selectRaw('DAY(payment_date) as day, SUM(amount) as total_revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    /**
     * Lấy top học viên theo số tiền đã thanh toán
     */
    public function getTopPayingStudents(int $limit = 10): Collection
    {
        return $this->model->join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->where('payments.status', PaymentStatus::CONFIRMED)
            ->selectRaw('students.id, students.first_name, students.last_name, SUM(payments.amount) as total_paid')
            ->groupBy('students.id', 'students.first_name', 'students.last_name')
            ->orderBy('total_paid', 'desc')
            ->limit($limit)
            ->get();
    }
}
