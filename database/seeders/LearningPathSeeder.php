<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CourseItem;
use App\Models\LearningPath;

class LearningPathSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy tất cả khóa học lá (is_leaf = true)
        $leafCourses = CourseItem::where('is_leaf', true)->get();

        foreach ($leafCourses as $course) {
            $this->createLearningPathsForCourse($course);
        }
    }

    /**
     * Tạo lộ trình học tập cho một khóa học
     */
    private function createLearningPathsForCourse($course)
    {
        $courseName = strtolower($course->name);
        
        // Xác định loại khóa học và tạo lộ trình phù hợp
        if (str_contains($courseName, 'kế toán')) {
            $this->createAccountingPaths($course);
        } elseif (str_contains($courseName, 'marketing')) {
            $this->createMarketingPaths($course);
        } elseif (str_contains($courseName, 'quản trị')) {
            $this->createManagementPaths($course);
        } else {
            $this->createGeneralPaths($course);
        }
    }

    /**
     * Tạo lộ trình cho khóa học Kế toán
     */
    private function createAccountingPaths($course)
    {
        $paths = [
            [
                'title' => 'Làm quen với môi trường học tập',
                'description' => 'Tìm hiểu về hệ thống học tập và các công cụ cần thiết',
                'order' => 1
            ],
            [
                'title' => 'Học lý thuyết cơ bản',
                'description' => 'Nắm vững các khái niệm và nguyên tắc kế toán cơ bản',
                'order' => 2
            ],
            [
                'title' => 'Thực hành với phần mềm kế toán',
                'description' => 'Làm quen và thực hành với các phần mềm kế toán phổ biến',
                'order' => 3
            ],
            [
                'title' => 'Làm bài tập thực tế',
                'description' => 'Áp dụng kiến thức vào các bài tập và case study thực tế',
                'order' => 4
            ],
            [
                'title' => 'Thi thử và đánh giá',
                'description' => 'Kiểm tra kiến thức thông qua các bài thi thử',
                'order' => 5
            ],
            [
                'title' => 'Hoàn thành dự án cuối khóa',
                'description' => 'Thực hiện dự án tổng hợp để chứng minh năng lực',
                'order' => 6
            ]
        ];

        $this->createPaths($course, $paths);
    }

    /**
     * Tạo lộ trình cho khóa học Marketing
     */
    private function createMarketingPaths($course)
    {
        $paths = [
            [
                'title' => 'Tìm hiểu cơ bản về Marketing',
                'description' => 'Nắm vững các khái niệm và chiến lược marketing cơ bản',
                'order' => 1
            ],
            [
                'title' => 'Nghiên cứu thị trường',
                'description' => 'Học cách phân tích thị trường và đối tượng khách hàng',
                'order' => 2
            ],
            [
                'title' => 'Xây dựng chiến lược Marketing',
                'description' => 'Thiết kế và triển khai các chiến lược marketing hiệu quả',
                'order' => 3
            ],
            [
                'title' => 'Digital Marketing',
                'description' => 'Thực hành marketing trên các nền tảng số',
                'order' => 4
            ],
            [
                'title' => 'Đo lường và tối ưu hóa',
                'description' => 'Học cách đo lường hiệu quả và tối ưu hóa chiến dịch',
                'order' => 5
            ]
        ];

        $this->createPaths($course, $paths);
    }

    /**
     * Tạo lộ trình cho khóa học Quản trị
     */
    private function createManagementPaths($course)
    {
        $paths = [
            [
                'title' => 'Nguyên lý quản trị cơ bản',
                'description' => 'Tìm hiểu các nguyên lý và lý thuyết quản trị cơ bản',
                'order' => 1
            ],
            [
                'title' => 'Quản lý nhân sự',
                'description' => 'Học cách quản lý và phát triển đội ngũ nhân viên',
                'order' => 2
            ],
            [
                'title' => 'Quản lý tài chính doanh nghiệp',
                'description' => 'Nắm vững các kỹ năng quản lý tài chính và ngân sách',
                'order' => 3
            ],
            [
                'title' => 'Lãnh đạo và ra quyết định',
                'description' => 'Phát triển kỹ năng lãnh đạo và ra quyết định chiến lược',
                'order' => 4
            ],
            [
                'title' => 'Case study thực tế',
                'description' => 'Phân tích và giải quyết các tình huống thực tế',
                'order' => 5
            ]
        ];

        $this->createPaths($course, $paths);
    }

    /**
     * Tạo lộ trình chung cho các khóa học khác
     */
    private function createGeneralPaths($course)
    {
        $paths = [
            [
                'title' => 'Làm quen với khóa học',
                'description' => 'Tìm hiểu về nội dung và mục tiêu của khóa học',
                'order' => 1
            ],
            [
                'title' => 'Học lý thuyết cơ bản',
                'description' => 'Nắm vững các kiến thức nền tảng',
                'order' => 2
            ],
            [
                'title' => 'Thực hành và ứng dụng',
                'description' => 'Áp dụng kiến thức vào thực tế',
                'order' => 3
            ],
            [
                'title' => 'Đánh giá và hoàn thành',
                'description' => 'Kiểm tra kiến thức và hoàn thành khóa học',
                'order' => 4
            ]
        ];

        $this->createPaths($course, $paths);
    }

    /**
     * Tạo các bước học tập cho khóa học
     */
    private function createPaths($course, $paths)
    {
        foreach ($paths as $pathData) {
            LearningPath::create([
                'course_item_id' => $course->id,
                'title' => $pathData['title'],
                'description' => $pathData['description'],
                'order' => $pathData['order'],
                'is_completed' => false
            ]);
        }
    }
}
