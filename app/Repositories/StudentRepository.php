<?php

namespace App\Repositories;

use App\Contracts\StudentRepositoryInterface;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * StudentRepository - Concrete implementation của StudentRepositoryInterface
 * Tuân thủ Single Responsibility và Dependency Inversion Principle
 */
class StudentRepository implements StudentRepositoryInterface
{
    protected Student $model;

    public function __construct(Student $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['province', 'enrollments.courseItem'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?Student
    {
        return $this->model->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByIdOrFail(int $id): Student
    {
        return $this->model->findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return $this->model->search($term)
            ->with(['enrollments.courseItem', 'province'])
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Student
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(Student $student, array $data): bool
    {
        return $student->update($data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Student $student): bool
    {
        return $student->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function findWithRelations(int $id, array $relations = []): ?Student
    {
        $defaultRelations = ['province', 'enrollments.courseItem', 'payments'];
        $relations = array_merge($defaultRelations, $relations);
        
        return $this->model->with($relations)->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByPhone(string $phone): ?Student
    {
        return $this->model->where('phone', $phone)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function findByEmail(string $email): ?Student
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * {@inheritDoc}
     */
    public function getNewStudentsInPeriod(Carbon $from, Carbon $to): Collection
    {
        return $this->model->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getBirthdaysInMonth(int $month): Collection
    {
        return $this->model->whereMonth('date_of_birth', $month)
            ->orderBy('date_of_birth')
            ->get();
    }

    /**
     * Lấy học viên có học phí chưa thanh toán đủ
     * 
     * @return Collection
     */
    public function getStudentsWithUnpaidFees(): Collection
    {
        return $this->model->whereHas('enrollments', function ($query) {
            $query->where('status', 'active')
                ->whereRaw('final_fee > (
                    SELECT COALESCE(SUM(amount), 0) 
                    FROM payments 
                    WHERE enrollment_id = enrollments.id 
                    AND status = "confirmed"
                )');
        })->with(['enrollments.payments'])->get();
    }

    /**
     * Lấy học viên theo khu vực
     * 
     * @param string $region
     * @return Collection
     */
    public function getByRegion(string $region): Collection
    {
        return $this->model->whereHas('province', function ($query) use ($region) {
            $query->where('region', $region);
        })->with('province')->get();
    }

    /**
     * Thống kê học viên theo giới tính
     * 
     * @return array
     */
    public function getGenderStatistics(): array
    {
        return $this->model->selectRaw('gender, COUNT(*) as count')
            ->groupBy('gender')
            ->pluck('count', 'gender')
            ->toArray();
    }

    /**
     * Thống kê học viên theo độ tuổi
     * 
     * @return array
     */
    public function getAgeStatistics(): array
    {
        return $this->model->selectRaw('
            CASE 
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 20 THEN "Dưới 20"
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 20 AND 25 THEN "20-25"
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 26 AND 30 THEN "26-30"
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 31 AND 35 THEN "31-35"
                ELSE "Trên 35"
            END as age_group,
            COUNT(*) as count
        ')
        ->groupBy('age_group')
        ->pluck('count', 'age_group')
        ->toArray();
    }

    /**
     * Lấy top học viên theo số khóa học đã đăng ký
     * 
     * @param int $limit
     * @return Collection
     */
    public function getTopStudentsByEnrollments(int $limit = 10): Collection
    {
        return $this->model->withCount('enrollments')
            ->orderBy('enrollments_count', 'desc')
            ->limit($limit)
            ->get();
    }
}
