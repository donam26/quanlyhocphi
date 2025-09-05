<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\User;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo Khóa học cha và con
        $courseRoot = CourseItem::create([
            'name' => 'Lộ trình tổng hợp',
            'parent_id' => null,
            'level' => 1,
            'is_leaf' => false,
            'fee' => 0,
            'order_index' => 1,
            'status' => 'active',
        ]);

        $courseChild1 = CourseItem::create([
            'name' => 'Khóa học Nhập môn Lập trình',
            'parent_id' => $courseRoot->id,
            'level' => 2,
            'is_leaf' => true,
            'fee' => 5000000,
            'order_index' => 1,
            'status' => 'active',
        ]);

        $courseChild2 = CourseItem::create([
            'name' => 'Khóa học Cấu trúc dữ liệu',
            'parent_id' => $courseRoot->id,
            'level' => 2,
            'is_leaf' => true,
            'fee' => 6000000,
            'order_index' => 2,
            'status' => 'active',
        ]);

        // 2. Tạo Lộ trình học tập cho các khóa học con
        LearningPath::create(['course_item_id' => $courseChild1->id, 'title' => 'Bài 1: Giới thiệu', 'order' => 1]);
        LearningPath::create(['course_item_id' => $courseChild1->id, 'title' => 'Bài 2: Biến và Kiểu dữ liệu', 'order' => 2]);
        LearningPath::create(['course_item_id' => $courseChild2->id, 'title' => 'Bài 1: Mảng', 'order' => 1]);
        LearningPath::create(['course_item_id' => $courseChild2->id, 'title' => 'Bài 2: Danh sách liên kết', 'order' => 2]);

        // 3. Tạo một học viên mẫu và liên kết với User
        $studentUser = User::create([
            'name' => 'Học viên Mẫu',
            'email' => 'student@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $student = Student::create([
            'user_id' => $studentUser->id,
            'first_name' => 'Mẫu',
            'last_name' => 'Học viên',
            'date_of_birth' => '2000-01-01',
            'phone' => '09' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT),
            'email' => $studentUser->email,
        ]);

        // 4. Ghi danh học viên vào các khóa học
        Enrollment::create([
            'student_id' => $student->id,
            'course_item_id' => $courseChild1->id,
            'enrollment_date' => now(),
            'status' => 'active',
            'final_fee' => $courseChild1->fee,
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'course_item_id' => $courseChild2->id,
            'enrollment_date' => now(),
            'status' => 'active',
            'final_fee' => $courseChild2->fee,
        ]);
    }
}

