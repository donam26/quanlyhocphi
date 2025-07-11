<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Major;

class MajorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $majors = [
            [
                'name' => 'Kế toán',
                'description' => 'Ngành Kế toán bao gồm các khóa học về đào tạo nghề kế toán, kế toán trưởng doanh nghiệp, kế toán HCSN và các khóa học liên quan',
                'active' => true
            ],
            [
                'name' => 'Marketing',
                'description' => 'Ngành Marketing gồm các khóa học về marketing bán hàng, marketing truyền thông và bán hàng truyền thông',
                'active' => true
            ],
            [
                'name' => 'Quản trị - Tài chính',
                'description' => 'Ngành Quản trị - Tài chính bao gồm các khóa học về quản trị kinh doanh, quản trị doanh nghiệp cao cấp và quản lý kinh tế tài chính',
                'active' => true
            ]
        ];

        foreach ($majors as $major) {
            Major::create($major);
        }
    }
}
