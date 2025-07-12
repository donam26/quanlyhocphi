<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Major;
use App\Models\Course;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy các ngành học
        $accounting = Major::where('name', 'Kế toán')->first();
        $marketing = Major::where('name', 'Marketing')->first();
        $management = Major::where('name', 'Quản trị - Tài chính')->first();

        // Khóa học ngành Kế toán
        $accountingCourses = [
            [
                'major_id' => $accounting->id,
                'name' => 'Đào tạo nghề kế toán',
                'description' => 'Khóa học phức tạp nhất của trung tâm, bao gồm 10 khóa nhỏ',
                'fee' => 15000000,
                'duration' => 12,
                'is_complex' => true,
                'active' => true
            ],
            [
                'major_id' => $accounting->id,
                'name' => 'Kế toán trưởng Doanh nghiệp',
                'description' => 'Khóa học đào tạo kế toán trưởng cho doanh nghiệp',
                'fee' => 8000000,
                'duration' => 6,
                'is_complex' => false,
                'active' => true
            ],
            [
                'major_id' => $accounting->id,
                'name' => 'Kế toán trưởng HCSN',
                'description' => 'Khóa học đào tạo kế toán trưởng hành chính sự nghiệp',
                'fee' => 8000000,
                'duration' => 6,
                'is_complex' => false,
                'active' => true
            ],
            [
                'major_id' => $accounting->id,
                'name' => 'Kế toán HCSN',
                'description' => 'Khóa học kế toán hành chính sự nghiệp',
                'fee' => 6000000,
                'duration' => 4,
                'is_complex' => false,
                'active' => true
            ]
        ];

        // Khóa học ngành Marketing
        $marketingCourses = [
            [
                'major_id' => $marketing->id,
                'name' => 'Marketing bán hàng',
                'description' => 'Khóa học chuyên sâu về marketing bán hàng',
                'fee' => 5000000,
                'duration' => 3,
                'is_complex' => false,
                'active' => true
            ],
            [
                'major_id' => $marketing->id,
                'name' => 'Marketing Truyền thông',
                'description' => 'Khóa học về marketing và truyền thông',
                'fee' => 5000000,
                'duration' => 3,
                'is_complex' => false,
                'active' => true
            ],
            [
                'major_id' => $marketing->id,
                'name' => 'Bán hàng và truyền thông',
                'description' => 'Khóa học kết hợp bán hàng và truyền thông',
                'fee' => 6000000,
                'duration' => 4,
                'is_complex' => false,
                'active' => true
            ]
        ];

        // Khóa học ngành Quản trị - Tài chính
        $managementCourses = [
            [
                'major_id' => $management->id,
                'name' => 'Quản trị Kinh doanh',
                'description' => 'Khóa học cơ bản về quản trị kinh doanh',
                'fee' => 7000000,
                'duration' => 5,
                'is_complex' => false,
                'active' => true
            ],
            [
                'major_id' => $management->id,
                'name' => 'Quản trị Doanh nghiệp cao cấp',
                'description' => 'Khóa học nâng cao về quản trị doanh nghiệp',
                'fee' => 10000000,
                'duration' => 8,
                'is_complex' => false,
                'active' => true
            ],
            [
                'major_id' => $management->id,
                'name' => 'Quản trị điều hành doanh nghiệp',
                'description' => 'Khóa học về quản trị điều hành doanh nghiệp',
                'fee' => 9000000,
                'duration' => 6,
                'is_complex' => false,
                'active' => true
            ],
            [
                'major_id' => $management->id,
                'name' => 'Quản lý Kinh tế Tài chính',
                'description' => 'Khóa học về quản lý kinh tế và tài chính',
                'fee' => 8000000,
                'duration' => 6,
                'is_complex' => false,
                'active' => true
            ]
        ];

        // Tạo tất cả các khóa học
        foreach ($accountingCourses as $course) {
            Course::firstOrCreate([
                'major_id' => $course['major_id'],
                'name' => $course['name']
            ], $course);
        }
        
        foreach ($marketingCourses as $course) {
            Course::firstOrCreate([
                'major_id' => $course['major_id'],
                'name' => $course['name']
            ], $course);
        }
        
        foreach ($managementCourses as $course) {
            Course::firstOrCreate([
                'major_id' => $course['major_id'],
                'name' => $course['name']
            ], $course);
        }
    }
}
