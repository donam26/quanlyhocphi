<?php

namespace App\Contracts;

use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * StudentRepositoryInterface - Interface cho Student Repository
 * Tuân thủ Dependency Inversion Principle
 */
interface StudentRepositoryInterface
{
    /**
     * Lấy tất cả học viên với phân trang
     * 
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Tìm học viên theo ID
     * 
     * @param int $id
     * @return Student|null
     */
    public function findById(int $id): ?Student;

    /**
     * Tìm học viên theo ID với exception nếu không tìm thấy
     * 
     * @param int $id
     * @return Student
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findByIdOrFail(int $id): Student;

    /**
     * Tìm kiếm học viên
     * 
     * @param string $term
     * @param int $limit
     * @return Collection
     */
    public function search(string $term, int $limit = 10): Collection;

    /**
     * Tạo học viên mới
     * 
     * @param array $data
     * @return Student
     */
    public function create(array $data): Student;

    /**
     * Cập nhật học viên
     * 
     * @param Student $student
     * @param array $data
     * @return bool
     */
    public function update(Student $student, array $data): bool;

    /**
     * Xóa học viên
     * 
     * @param Student $student
     * @return bool
     */
    public function delete(Student $student): bool;

    /**
     * Lấy học viên với các quan hệ
     * 
     * @param int $id
     * @param array $relations
     * @return Student|null
     */
    public function findWithRelations(int $id, array $relations = []): ?Student;

    /**
     * Lấy học viên theo số điện thoại
     * 
     * @param string $phone
     * @return Student|null
     */
    public function findByPhone(string $phone): ?Student;

    /**
     * Lấy học viên theo email
     * 
     * @param string $email
     * @return Student|null
     */
    public function findByEmail(string $email): ?Student;

    /**
     * Lấy học viên theo trạng thái
     * 
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection;

    /**
     * Đếm tổng số học viên
     * 
     * @return int
     */
    public function count(): int;

    /**
     * Lấy học viên mới trong khoảng thời gian
     * 
     * @param \Carbon\Carbon $from
     * @param \Carbon\Carbon $to
     * @return Collection
     */
    public function getNewStudentsInPeriod(\Carbon\Carbon $from, \Carbon\Carbon $to): Collection;

    /**
     * Lấy học viên có sinh nhật trong tháng
     * 
     * @param int $month
     * @return Collection
     */
    public function getBirthdaysInMonth(int $month): Collection;
}
