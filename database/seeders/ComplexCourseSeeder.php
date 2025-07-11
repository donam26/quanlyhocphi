<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Major;
use App\Models\Course;
use App\Models\SubCourse;
use App\Models\CourseClass;
use App\Models\CoursePackage;

class ComplexCourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo ngành Kế toán nếu chưa có
        $major = Major::firstOrCreate(
            ['name' => 'Kế toán'],
            [
                'description' => 'Ngành đào tạo kế toán',
                'active' => true
            ]
        );

        // Tạo khóa học phức tạp "Đào tạo nghề kế toán"
        $course = Course::create([
            'major_id' => $major->id,
            'name' => 'Đào tạo nghề kế toán',
            'description' => 'Khóa học đào tạo kế toán chuyên nghiệp',
            'fee' => 15000000, // 15 triệu
            'duration' => 12, // 12 tháng
            'is_complex' => true,
            'course_type' => 'complex',
            'active' => true,
        ]);

        // Tạo các khóa học con
        $subCourses = [
            [
                'name' => 'ĐTN 2.5T',
                'description' => 'Đào tạo nghề 2.5 tháng',
                'fee' => 2500000,
                'order' => 1,
                'code' => 'DTN-2.5T',
                'has_online' => true,
                'has_offline' => true,
            ],
            [
                'name' => 'ĐTN 3T',
                'description' => 'Đào tạo nghề 3 tháng',
                'fee' => 3000000,
                'order' => 2,
                'code' => 'DTN-3T',
                'has_online' => true,
                'has_offline' => true,
            ],
            [
                'name' => 'ĐTN 4T',
                'description' => 'Đào tạo nghề 4 tháng',
                'fee' => 4000000,
                'order' => 3,
                'code' => 'DTN-4T',
                'has_online' => true,
                'has_offline' => true,
            ],
            [
                'name' => 'ĐTN 5T',
                'description' => 'Đào tạo nghề 5 tháng',
                'fee' => 5000000,
                'order' => 4,
                'code' => 'DTN-5T',
                'has_online' => true,
                'has_offline' => true,
            ],
            [
                'name' => 'KTTH',
                'description' => 'Kế toán tổng hợp',
                'fee' => 3500000,
                'order' => 5,
                'code' => 'KTTH',
                'has_online' => true,
                'has_offline' => true,
            ],
            [
                'name' => 'KT sổ',
                'description' => 'Kế toán sổ sách',
                'fee' => 2800000,
                'order' => 6,
                'code' => 'KTSO',
                'has_online' => true,
                'has_offline' => true,
            ],
            [
                'name' => 'KT máy',
                'description' => 'Kế toán máy',
                'fee' => 3200000,
                'order' => 7,
                'code' => 'KTMAY',
                'has_online' => true,
                'has_offline' => true,
            ],
            [
                'name' => 'KT Thuế',
                'description' => 'Kế toán thuế',
                'fee' => 3200000,
                'order' => 8,
                'code' => 'KTTHUE',
                'has_online' => true,
                'has_offline' => true,
            ],
            [
                'name' => 'BCTC',
                'description' => 'Báo cáo tài chính',
                'fee' => 4000000,
                'order' => 9,
                'code' => 'BCTC',
                'has_online' => true,
                'has_offline' => true,
            ],
        ];

        foreach ($subCourses as $subCourseData) {
            $course->subCourses()->create($subCourseData);
        }

        // Tạo gói khóa học Khóa 1 Online
        $onlinePackage = CoursePackage::create([
            'course_id' => $course->id,
            'name' => 'Đào tạo nghề kế toán khóa 1 ONLINE',
            'description' => 'Gói khóa học online cho Đào tạo nghề kế toán khóa 1',
            'type' => 'online',
            'batch_number' => 1,
            'package_fee' => 12000000, // Giảm giá khi mua gói
            'active' => true
        ]);

        // Tạo gói khóa học Khóa 1 Offline
        $offlinePackage = CoursePackage::create([
            'course_id' => $course->id,
            'name' => 'Đào tạo nghề kế toán khóa 1 OFFLINE',
            'description' => 'Gói khóa học offline cho Đào tạo nghề kế toán khóa 1',
            'type' => 'offline',
            'batch_number' => 1,
            'package_fee' => 13500000, // Giảm giá khi mua gói
            'active' => true
        ]);

        // Tạo các lớp học cho từng khóa con
        $subCourseList = $course->subCourses()->get();
        
        foreach ($subCourseList as $index => $subCourse) {
            // Tạo lớp online
            $onlineClass = CourseClass::create([
                'course_id' => $course->id,
                'sub_course_id' => $subCourse->id,
                'name' => $subCourse->name . ' khóa 1 Online',
                'type' => 'online',
                'batch_number' => 1,
                'max_students' => 50,
                'status' => 'open',
                'is_package' => false,
            ]);
            
            // Thêm lớp online vào gói online
            $onlinePackage->addClass($onlineClass, $subCourse->order);
            
            // Tạo lớp offline
            $offlineClass = CourseClass::create([
                'course_id' => $course->id,
                'sub_course_id' => $subCourse->id,
                'name' => $subCourse->name . ' khóa 1 Offline',
                'type' => 'offline',
                'batch_number' => 1,
                'max_students' => 30,
                'status' => 'open',
                'is_package' => false,
            ]);
            
            // Thêm lớp offline vào gói offline
            $offlinePackage->addClass($offlineClass, $subCourse->order);
        }

        // Tạo thêm một khóa học tiêu chuẩn "Kế toán trưởng DN"
        $standardCourse = Course::create([
            'major_id' => $major->id,
            'name' => 'Kế toán trưởng DN',
            'description' => 'Khóa học đào tạo kế toán trưởng doanh nghiệp',
            'fee' => 8000000,
            'duration' => 6,
            'is_complex' => false,
            'course_type' => 'standard',
            'active' => true,
        ]);

        // Tạo lớp online cho khóa học tiêu chuẩn
        CourseClass::create([
            'course_id' => $standardCourse->id,
            'name' => 'Kế toán trưởng DN khóa 1 Online',
            'type' => 'online',
            'batch_number' => 1,
            'max_students' => 50,
            'status' => 'open',
            'is_package' => false,
        ]);

        // Tạo lớp offline cho khóa học tiêu chuẩn
        CourseClass::create([
            'course_id' => $standardCourse->id,
            'name' => 'Kế toán trưởng DN khóa 1 Offline',
            'type' => 'offline',
            'batch_number' => 1,
            'max_students' => 30,
            'status' => 'open',
            'is_package' => false,
        ]);
    }
} 