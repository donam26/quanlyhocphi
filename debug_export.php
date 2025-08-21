<?php

// Bootstrap Laravel application
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Exports\CourseStudentsExport;

echo "=== DEBUG EXPORT ISSUE ===\n\n";

try {
    // 1. Tìm một khóa học có học viên
    echo "1. Finding course with students...\n";
    
    $courseWithStudents = CourseItem::whereHas('enrollments')->first();
    
    if (!$courseWithStudents) {
        echo "❌ No course with students found. Creating test data...\n";
        
        // Tạo khóa học test
        $courseWithStudents = CourseItem::create([
            'name' => 'Test Course for Export',
            'fee' => 1000000,
            'level' => 1,
            'is_leaf' => true,
            'status' => 'active'
        ]);
        
        // Tạo học viên test
        $student = \App\Models\Student::create([
            'first_name' => 'Test',
            'last_name' => 'Student',
            'phone' => '0901234567',
            'email' => 'test@example.com',
            'gender' => 'male'
        ]);
        
        // Tạo enrollment
        Enrollment::create([
            'student_id' => $student->id,
            'course_item_id' => $courseWithStudents->id,
            'enrollment_date' => now(),
            'final_fee' => $courseWithStudents->fee,
            'status' => 'active'
        ]);
        
        echo "✅ Created test data\n";
    }
    
    echo "✅ Found course: {$courseWithStudents->name} (ID: {$courseWithStudents->id})\n";
    
    // 2. Kiểm tra enrollments trực tiếp
    echo "\n2. Checking enrollments directly...\n";
    
    $enrollments = $courseWithStudents->enrollments()->with(['student', 'courseItem'])->get();
    echo "✅ Direct enrollments count: " . $enrollments->count() . "\n";
    
    foreach ($enrollments as $enrollment) {
        $student = $enrollment->student;
        echo "   - {$student->full_name} (ID: {$student->id})\n";
    }
    
    // 3. Test CourseStudentsExport
    echo "\n3. Testing CourseStudentsExport...\n";
    
    $columns = ['student_name', 'student_phone', 'student_email', 'course_name'];
    $filters = []; // No filters
    
    $export = new CourseStudentsExport($courseWithStudents, $columns, $filters);
    
    // Test collection method
    $collection = $export->collection();
    echo "✅ Export collection count: " . $collection->count() . "\n";
    
    if ($collection->count() > 0) {
        echo "✅ Collection data:\n";
        foreach ($collection as $enrollment) {
            $student = $enrollment->student;
            $course = $enrollment->courseItem;
            echo "   - {$student->full_name} in {$course->name}\n";
        }
        
        // Test headings
        $headings = $export->headings();
        echo "\n✅ Export headings: " . implode(', ', $headings) . "\n";
        
        // Test mapping
        $firstEnrollment = $collection->first();
        $mappedData = $export->map($firstEnrollment);
        echo "✅ Mapped data: " . implode(', ', $mappedData) . "\n";
        
    } else {
        echo "❌ No data in export collection!\n";
        
        // Debug: Check what's in the course
        echo "\nDEBUG INFO:\n";
        echo "Course ID: {$courseWithStudents->id}\n";
        echo "Course descendants: " . $courseWithStudents->descendants()->count() . "\n";
        echo "Direct enrollments: " . $courseWithStudents->enrollments()->count() . "\n";
        
        // Check if there's an issue with the query
        $courseItemIds = [$courseWithStudents->id];
        foreach ($courseWithStudents->descendants() as $descendant) {
            $courseItemIds[] = $descendant->id;
        }
        echo "Course IDs being queried: " . implode(', ', $courseItemIds) . "\n";
        
        $testQuery = Enrollment::whereIn('course_item_id', $courseItemIds)
            ->with(['student', 'courseItem']);
        echo "Test query count: " . $testQuery->count() . "\n";
    }
    
    // 4. Test Excel generation if we have data
    if ($collection->count() > 0) {
        echo "\n4. Testing Excel generation...\n";
        
        $fileName = 'debug_export_' . date('Y_m_d_H_i_s') . '.xlsx';
        $filePath = storage_path('app/' . $fileName);
        
        \Maatwebsite\Excel\Facades\Excel::store($export, $fileName);
        
        if (file_exists($filePath)) {
            $fileSize = filesize($filePath);
            echo "✅ Excel file created successfully!\n";
            echo "   - File: {$fileName}\n";
            echo "   - Size: " . number_format($fileSize) . " bytes\n";
            echo "   - Path: {$filePath}\n";
            
            // Read the file to check content
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            
            echo "   - Rows: {$highestRow}\n";
            echo "   - Columns: {$highestColumn}\n";
            
            // Show first few rows
            echo "   - Content preview:\n";
            for ($row = 1; $row <= min(5, $highestRow); $row++) {
                $rowData = [];
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $cellValue = $worksheet->getCell($col . $row)->getValue();
                    $rowData[] = $cellValue;
                }
                echo "     Row {$row}: " . implode(' | ', $rowData) . "\n";
            }
            
            // Clean up
            unlink($filePath);
            echo "   - Test file cleaned up\n";
        } else {
            echo "❌ Failed to create Excel file\n";
        }
    }
    
    echo "\n=== DEBUG COMPLETED ===\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
