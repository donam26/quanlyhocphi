<?php

require_once 'vendor/autoload.php';

use App\Models\CourseItem;
use App\Models\Student;
use App\Models\Enrollment;
use App\Http\Controllers\AttendanceController;
use Illuminate\Http\Request;

echo "Testing Attendance Hierarchy Logic\n";
echo "==================================\n\n";

try {
    // Tạo một instance của AttendanceController
    $controller = new AttendanceController();
    
    // Test với một khóa học có ID = 1 (giả sử đây là khóa cha)
    $courseId = 1;
    $courseItem = CourseItem::with('children.children.children')->find($courseId);
    
    if (!$courseItem) {
        echo "❌ Không tìm thấy khóa học với ID: $courseId\n";
        exit;
    }
    
    echo "📚 Khóa học được test: {$courseItem->name} (ID: {$courseItem->id})\n";
    echo "🌳 Loại: " . ($courseItem->is_leaf ? 'Khóa học cuối' : 'Khóa học cha') . "\n";
    
    // Đếm số khóa con
    $childrenCount = $courseItem->children->count();
    echo "👶 Số khóa con trực tiếp: $childrenCount\n";
    
    if ($childrenCount > 0) {
        echo "📋 Danh sách khóa con:\n";
        foreach ($courseItem->children as $child) {
            echo "   - {$child->name} (ID: {$child->id})\n";
            if ($child->children->count() > 0) {
                foreach ($child->children as $grandChild) {
                    echo "     └─ {$grandChild->name} (ID: {$grandChild->id})\n";
                }
            }
        }
    }
    
    // Test method getAllChildrenIds
    $courseItemIds = [$courseItem->id];
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('getAllChildrenIds');
    $method->setAccessible(true);
    $method->invoke($controller, $courseItem, $courseItemIds);
    
    echo "\n🔍 Tất cả ID khóa học (bao gồm khóa cha và con):\n";
    echo "   " . implode(', ', $courseItemIds) . "\n";
    
    // Đếm số học viên trong tất cả khóa học
    $totalEnrollments = \App\Models\Enrollment::whereIn('course_item_id', $courseItemIds)
        ->where('status', \App\Enums\EnrollmentStatus::ACTIVE->value)
        ->count();
    
    echo "\n👥 Tổng số học viên đang học trong tất cả khóa: $totalEnrollments\n";
    
    // Test API call
    echo "\n🧪 Test API call getStudentsForAttendance...\n";
    $request = new Request(['date' => now()->format('Y-m-d')]);
    $response = $controller->getStudentsForAttendance($courseItem, $request);
    $responseData = $response->getData(true);
    
    if ($responseData['success']) {
        $students = $responseData['students'];
        echo "✅ API call thành công!\n";
        echo "📊 Số học viên trả về: " . count($students) . "\n";
        
        if (count($students) > 0) {
            echo "\n📝 Danh sách học viên (5 đầu tiên):\n";
            foreach (array_slice($students, 0, 5) as $index => $student) {
                echo "   " . ($index + 1) . ". {$student['student_name']} - {$student['course_name']}\n";
            }
            
            if (count($students) > 5) {
                echo "   ... và " . (count($students) - 5) . " học viên khác\n";
            }
        }
        
        // Kiểm tra xem có học viên từ nhiều khóa học khác nhau không
        $uniqueCourses = array_unique(array_column($students, 'course_name'));
        echo "\n🎯 Số khóa học khác nhau có học viên: " . count($uniqueCourses) . "\n";
        if (count($uniqueCourses) > 1) {
            echo "✅ THÀNH CÔNG: Hệ thống đã lấy được học viên từ nhiều khóa con!\n";
            echo "📚 Các khóa học:\n";
            foreach ($uniqueCourses as $courseName) {
                $courseStudentCount = count(array_filter($students, function($s) use ($courseName) {
                    return $s['course_name'] === $courseName;
                }));
                echo "   - $courseName: $courseStudentCount học viên\n";
            }
        } else {
            echo "ℹ️  Chỉ có học viên từ 1 khóa học hoặc không có học viên\n";
        }
        
    } else {
        echo "❌ API call thất bại: " . ($responseData['message'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Test completed!\n";
