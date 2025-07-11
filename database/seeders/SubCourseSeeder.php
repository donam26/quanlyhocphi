<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\SubCourse;

class SubCourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy khóa "Đào tạo nghề kế toán"
        $accountingTrainingCourse = Course::where('name', 'Đào tạo nghề kế toán')->first();
        
        if (!$accountingTrainingCourse) {
            return;
        }

        // 10 khóa nhỏ trong khóa Đào tạo nghề kế toán
        $subCourses = [
            [
                'course_id' => $accountingTrainingCourse->id,
                'name' => 'ĐTN 2.5T',
                'description' => 'Đào tạo nghề kế toán 2.5 tháng',
                'fee' => 1500000,
                'order' => 1,
                'active' => true
            ],
            [
                'course_id' => $accountingTrainingCourse->id,
                'name' => 'ĐTN 3T',
                'description' => 'Đào tạo nghề kế toán 3 tháng',
                'fee' => 1800000,
                'order' => 2,
                'active' => true
            ],
            [
                'course_id' => $accountingTrainingCourse->id,
                'name' => 'ĐTN 4T',
                'description' => 'Đào tạo nghề kế toán 4 tháng',
                'fee' => 2000000,
                'order' => 3,
                'active' => true
            ],
            [
                'course_id' => $accountingTrainingCourse->id,
                'name' => 'ĐTN 5T',
                'description' => 'Đào tạo nghề kế toán 5 tháng',
                'fee' => 2200000,
                'order' => 4,
                'active' => true
            ],
            [
                'course_id' => $accountingTrainingCourse->id,
                'name' => 'KTTH',
                'description' => 'Kế toán tổng hợp',
                'fee' => 1200000,
                'order' => 5,
                'active' => true
            ],
            [
                'course_id' => $accountingTrainingCourse->id,
                'name' => 'KT sổ',
                'description' => 'Kế toán sổ sách',
                'fee' => 1000000,
                'order' => 6,
                'active' => true
            ],
            [
                'course_id' => $accountingTrainingCourse->id,
                'name' => 'KT máy',
                'description' => 'Kế toán máy tính',
                'fee' => 1500000,
                'order' => 7,
                'active' => true
            ],
            [
                'course_id' => $accountingTrainingCourse->id,
                'name' => 'KT Thuế',
                'description' => 'Kế toán thuế',
                'fee' => 1300000,
                'order' => 8,
                'active' => true
            ],
            [
                'course_id' => $accountingTrainingCourse->id,
                'name' => 'BCTC',
                'description' => 'Báo cáo tài chính',
                'fee' => 1500000,
                'order' => 9,
                'active' => true
            ]
        ];

        foreach ($subCourses as $subCourse) {
            SubCourse::create($subCourse);
        }
    }
}
