<?php

// Bootstrap Laravel application
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CourseItem;
use App\Exports\CourseStudentsExport;
use Maatwebsite\Excel\Facades\Excel;

echo "=== SIMPLE EXPORT TEST ===\n\n";

try {
    // 1. Lấy khóa học có học viên
    $course = CourseItem::whereHas('enrollments')->first();
    
    if (!$course) {
        echo "❌ No course with students found!\n";
        exit;
    }
    
    echo "✅ Testing course: {$course->name} (ID: {$course->id})\n";
    echo "   - Direct enrollments: " . $course->enrollments()->count() . "\n";
    echo "   - Children count: " . $course->children()->count() . "\n";
    echo "   - Descendants count: " . $course->descendants()->count() . "\n";
    
    // 2. Test export với columns cơ bản
    echo "\n2. Testing basic export...\n";
    
    $columns = ['student_name', 'student_phone', 'student_email', 'course_name'];
    $export = new CourseStudentsExport($course, $columns, []);
    
    $collection = $export->collection();
    echo "✅ Collection count: " . $collection->count() . "\n";
    
    if ($collection->count() > 0) {
        // Test Excel generation
        $fileName = 'simple_test_' . date('Y_m_d_H_i_s') . '.xlsx';
        Excel::store($export, $fileName);
        
        $filePath = storage_path('app/' . $fileName);
        if (file_exists($filePath)) {
            $fileSize = filesize($filePath);
            echo "✅ Excel file created: {$fileName} ({$fileSize} bytes)\n";
            
            // Read and check content
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            
            echo "✅ Excel has {$highestRow} rows (including header)\n";
            
            // Show first few rows
            for ($row = 1; $row <= min(3, $highestRow); $row++) {
                $rowData = [];
                for ($col = 'A'; $col <= 'D'; $col++) {
                    $cellValue = $worksheet->getCell($col . $row)->getValue();
                    $rowData[] = $cellValue;
                }
                echo "   Row {$row}: " . implode(' | ', $rowData) . "\n";
            }
            
            unlink($filePath);
            echo "✅ Test file cleaned up\n";
        } else {
            echo "❌ Excel file not created\n";
        }
    } else {
        echo "❌ No data in collection\n";
    }
    
    // 3. Test với khóa học có children (nếu có)
    $parentCourse = CourseItem::whereHas('children')->whereHas('enrollments')->first();
    
    if ($parentCourse && $parentCourse->id != $course->id) {
        echo "\n3. Testing hierarchical export...\n";
        echo "✅ Parent course: {$parentCourse->name} (ID: {$parentCourse->id})\n";
        
        $hierarchicalExport = new CourseStudentsExport($parentCourse, $columns, []);
        $hierarchicalCollection = $hierarchicalExport->collection();
        
        echo "✅ Hierarchical collection count: " . $hierarchicalCollection->count() . "\n";
        echo "   - Direct enrollments: " . $parentCourse->enrollments()->count() . "\n";
        echo "   - Children: " . $parentCourse->children()->count() . "\n";
        
        // Show breakdown by course
        $courseBreakdown = [];
        foreach ($hierarchicalCollection as $enrollment) {
            $courseName = $enrollment->courseItem->name;
            if (!isset($courseBreakdown[$courseName])) {
                $courseBreakdown[$courseName] = 0;
            }
            $courseBreakdown[$courseName]++;
        }
        
        echo "   - Breakdown by course:\n";
        foreach ($courseBreakdown as $courseName => $count) {
            echo "     * {$courseName}: {$count} students\n";
        }
        
        if ($hierarchicalCollection->count() > $parentCourse->enrollments()->count()) {
            echo "🎉 SUCCESS: Hierarchical export includes children!\n";
        } else {
            echo "⚠️  Hierarchical export might not include children\n";
        }
    }
    
    echo "\n=== TEST COMPLETED ===\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
