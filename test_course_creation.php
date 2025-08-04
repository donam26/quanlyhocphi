<?php

require_once 'vendor/autoload.php';

use App\Services\CourseItemService;

// Test data cho khóa học chính (không có parent_id)
$testDataRoot = [
    'name' => 'Test Ngành Học',
    'is_leaf' => false,
    'active' => true
    // Không có parent_id - đây là nguyên nhân gây lỗi trước đây
];

// Test data cho khóa học con (có parent_id)
$testDataChild = [
    'name' => 'Test Khóa Học Con',
    'parent_id' => 1,
    'is_leaf' => true,
    'fee' => 1000000,
    'active' => true
];

echo "Testing CourseItemService::createCourseItem()\n";
echo "=========================================\n\n";

try {
    $service = new CourseItemService();
    
    echo "Test 1: Tạo khóa học chính (không có parent_id)\n";
    echo "Data: " . json_encode($testDataRoot) . "\n";
    
    // Trước khi sửa: sẽ báo lỗi "Undefined array key 'parent_id'"
    // Sau khi sửa: sẽ hoạt động bình thường
    $result1 = $service->createCourseItem($testDataRoot);
    echo "✅ SUCCESS: Tạo thành công khóa học chính\n";
    echo "ID: " . $result1->id . ", Name: " . $result1->name . "\n\n";
    
    echo "Test 2: Tạo khóa học con (có parent_id)\n";
    echo "Data: " . json_encode($testDataChild) . "\n";
    
    $result2 = $service->createCourseItem($testDataChild);
    echo "✅ SUCCESS: Tạo thành công khóa học con\n";
    echo "ID: " . $result2->id . ", Name: " . $result2->name . "\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "Test completed!\n"; 