<?php

// Bootstrap Laravel application
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CourseItem;
use App\Models\Student;
use App\Models\Enrollment;
use App\Services\CourseHierarchyService;
use App\Exports\CourseStudentsExport;
use Maatwebsite\Excel\Facades\Excel;

echo "=== TESTING HIERARCHICAL EXPORT FUNCTIONALITY ===\n\n";

try {
    // 1. Tìm hoặc tạo cấu trúc khóa học phân cấp
    echo "1. Setting up course hierarchy...\n";
    
    // Tạo khóa cha
    $parentCourse = CourseItem::firstOrCreate([
        'name' => 'Kế toán Tổng hợp',
        'parent_id' => null
    ], [
        'fee' => 2000000,
        'level' => 1,
        'is_leaf' => false,
        'status' => 'active'
    ]);
    
    // Tạo khóa con 1
    $childCourse1 = CourseItem::firstOrCreate([
        'name' => 'Kế toán Cơ bản',
        'parent_id' => $parentCourse->id
    ], [
        'fee' => 1000000,
        'level' => 2,
        'is_leaf' => true,
        'status' => 'active'
    ]);
    
    // Tạo khóa con 2
    $childCourse2 = CourseItem::firstOrCreate([
        'name' => 'Kế toán Nâng cao',
        'parent_id' => $parentCourse->id
    ], [
        'fee' => 1200000,
        'level' => 2,
        'is_leaf' => true,
        'status' => 'active'
    ]);
    
    echo "✅ Created course hierarchy:\n";
    echo "   - Parent: {$parentCourse->name} (ID: {$parentCourse->id})\n";
    echo "   - Child 1: {$childCourse1->name} (ID: {$childCourse1->id})\n";
    echo "   - Child 2: {$childCourse2->name} (ID: {$childCourse2->id})\n\n";
    
    // 2. Tạo học viên mẫu
    echo "2. Creating sample students...\n";
    
    $students = [];
    for ($i = 1; $i <= 6; $i++) {
        $student = Student::firstOrCreate([
            'phone' => '090123456' . $i
        ], [
            'first_name' => 'Nguyễn Văn',
            'last_name' => 'Test' . $i,
            'email' => 'test' . $i . '@example.com',
            'gender' => $i % 2 == 0 ? 'female' : 'male'
        ]);
        $students[] = $student;
    }
    
    echo "✅ Created " . count($students) . " students\n\n";
    
    // 3. Tạo enrollments
    echo "3. Creating enrollments...\n";
    
    // Ghi danh học viên 1,2 vào khóa con 1
    for ($i = 0; $i < 2; $i++) {
        Enrollment::firstOrCreate([
            'student_id' => $students[$i]->id,
            'course_item_id' => $childCourse1->id
        ], [
            'enrollment_date' => now(),
            'final_fee' => $childCourse1->fee,
            'status' => 'active'
        ]);
    }
    
    // Ghi danh học viên 3,4 vào khóa con 2
    for ($i = 2; $i < 4; $i++) {
        Enrollment::firstOrCreate([
            'student_id' => $students[$i]->id,
            'course_item_id' => $childCourse2->id
        ], [
            'enrollment_date' => now(),
            'final_fee' => $childCourse2->fee,
            'status' => 'active'
        ]);
    }
    
    // Ghi danh học viên 5,6 trực tiếp vào khóa cha
    for ($i = 4; $i < 6; $i++) {
        Enrollment::firstOrCreate([
            'student_id' => $students[$i]->id,
            'course_item_id' => $parentCourse->id
        ], [
            'enrollment_date' => now(),
            'final_fee' => $parentCourse->fee,
            'status' => 'active'
        ]);
    }
    
    echo "✅ Created enrollments:\n";
    echo "   - Child Course 1: 2 students\n";
    echo "   - Child Course 2: 2 students\n";
    echo "   - Parent Course: 2 students\n\n";
    
    // 4. Test CourseHierarchyService
    echo "4. Testing CourseHierarchyService...\n";
    
    $hierarchyInfo = CourseHierarchyService::getCourseHierarchyInfo($parentCourse->id);
    echo "✅ Hierarchy info for parent course:\n";
    echo "   - Descendants count: " . $hierarchyInfo['descendants_count'] . "\n";
    echo "   - Total course IDs: " . implode(', ', $hierarchyInfo['total_descendants_ids']) . "\n";
    echo "   - Is root: " . ($hierarchyInfo['is_root'] ? 'Yes' : 'No') . "\n";
    echo "   - Has children: " . ($hierarchyInfo['has_children'] ? 'Yes' : 'No') . "\n\n";
    
    $stats = CourseHierarchyService::getCourseHierarchyStats($parentCourse->id);
    echo "✅ Hierarchy stats:\n";
    echo "   - Total courses: " . $stats['total_courses'] . "\n";
    echo "   - Total students: " . $stats['total_students'] . "\n";
    echo "   - Total enrollments: " . $stats['total_enrollments'] . "\n";
    echo "   - Total revenue: " . number_format($stats['total_revenue']) . " VNĐ\n\n";
    
    // 5. Test export khóa cha (bao gồm học viên từ khóa con)
    echo "5. Testing hierarchical export...\n";
    
    $columns = [
        'student_name', 'student_phone', 'student_email', 
        'course_name', 'course_path', 'enrollment_date', 
        'enrollment_status', 'final_fee'
    ];
    
    $export = new CourseStudentsExport($parentCourse, $columns);
    $collection = $export->collection();
    
    echo "✅ Export from parent course includes:\n";
    echo "   - Total enrollments: " . $collection->count() . "\n";
    
    foreach ($collection as $enrollment) {
        $student = $enrollment->student;
        $course = $enrollment->courseItem;
        echo "   - {$student->full_name} in {$course->name} (Path: {$course->path})\n";
    }
    
    // 6. Test actual Excel generation
    echo "\n6. Testing Excel file generation...\n";
    
    $fileName = 'test_hierarchical_export_' . date('Y_m_d_H_i_s') . '.xlsx';
    $filePath = storage_path('app/' . $fileName);
    
    Excel::store($export, $fileName);
    
    if (file_exists($filePath)) {
        $fileSize = filesize($filePath);
        echo "✅ Excel file created successfully!\n";
        echo "   - File: {$fileName}\n";
        echo "   - Size: " . number_format($fileSize) . " bytes\n";
        echo "   - Path: {$filePath}\n";
        
        // Clean up test file
        unlink($filePath);
        echo "   - Test file cleaned up\n";
    } else {
        echo "❌ Failed to create Excel file\n";
    }
    
    // 7. Test export khóa con (chỉ học viên của khóa đó)
    echo "\n7. Testing child course export...\n";
    
    $childExport = new CourseStudentsExport($childCourse1, $columns);
    $childCollection = $childExport->collection();
    
    echo "✅ Export from child course 1:\n";
    echo "   - Total enrollments: " . $childCollection->count() . "\n";
    
    foreach ($childCollection as $enrollment) {
        $student = $enrollment->student;
        $course = $enrollment->courseItem;
        echo "   - {$student->full_name} in {$course->name}\n";
    }
    
    // 8. So sánh kết quả
    echo "\n8. Comparison results:\n";
    echo "✅ Parent course export: " . $collection->count() . " enrollments (includes children)\n";
    echo "✅ Child course export: " . $childCollection->count() . " enrollments (only direct)\n";
    echo "✅ Expected behavior: Parent should include all children's students\n\n";
    
    if ($collection->count() > $childCollection->count()) {
        echo "🎉 SUCCESS: Hierarchical export is working correctly!\n";
        echo "   Parent course export includes students from child courses.\n";
    } else {
        echo "⚠️  WARNING: Hierarchical export might not be working as expected.\n";
    }
    
    echo "\n=== TEST COMPLETED ===\n";
    
} catch (\Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
