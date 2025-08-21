<?php

// Bootstrap Laravel application
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CourseItem;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\CourseItemController;

echo "=== TESTING API EXPORT ===\n\n";

try {
    // 1. Tìm khóa học có học viên
    echo "1. Finding course with students...\n";
    
    $courseWithStudents = CourseItem::whereHas('enrollments')->first();
    
    if (!$courseWithStudents) {
        echo "❌ No course with students found!\n";
        exit;
    }
    
    echo "✅ Found course: {$courseWithStudents->name} (ID: {$courseWithStudents->id})\n";
    echo "   - Enrollments count: " . $courseWithStudents->enrollments()->count() . "\n";
    
    // 2. Tạo fake request
    echo "\n2. Creating test request...\n";
    
    $request = new Request();
    $request->merge([
        'columns' => [
            'student_name', 'student_phone', 'student_email', 
            'course_name', 'enrollment_date', 'enrollment_status'
        ]
    ]);
    
    echo "✅ Request created with columns: " . implode(', ', $request->input('columns')) . "\n";
    
    // 3. Test controller method
    echo "\n3. Testing controller export method...\n";
    
    $controller = new CourseItemController();
    
    try {
        $response = $controller->exportStudents($request, $courseWithStudents);
        
        // Check if it's a download response
        if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            echo "✅ Export successful - BinaryFileResponse returned\n";
            echo "   - File: " . $response->getFile()->getFilename() . "\n";
            echo "   - Size: " . number_format($response->getFile()->getSize()) . " bytes\n";
            
            // Check if file has content
            $content = file_get_contents($response->getFile()->getPathname());
            if (strlen($content) > 1000) { // Should be more than just headers
                echo "✅ File has content (" . number_format(strlen($content)) . " bytes)\n";
            } else {
                echo "⚠️  File seems small (" . strlen($content) . " bytes) - might be empty\n";
            }
            
        } else {
            echo "❌ Unexpected response type: " . get_class($response) . "\n";
            if (method_exists($response, 'getContent')) {
                echo "Response content: " . $response->getContent() . "\n";
            }
        }
        
    } catch (\Exception $e) {
        echo "❌ Controller error: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
    // 4. Test với filters
    echo "\n4. Testing with filters...\n";
    
    $requestWithFilters = new Request();
    $requestWithFilters->merge([
        'columns' => ['student_name', 'student_phone', 'course_name'],
        'status' => 'active'
    ]);
    
    try {
        $response = $controller->exportStudents($requestWithFilters, $courseWithStudents);
        
        if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            echo "✅ Export with filters successful\n";
            echo "   - File size: " . number_format($response->getFile()->getSize()) . " bytes\n";
        } else {
            echo "❌ Export with filters failed\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ Export with filters error: " . $e->getMessage() . "\n";
    }
    
    // 5. Test hierarchical export
    echo "\n5. Testing hierarchical export...\n";
    
    // Tìm khóa học có children
    $parentCourse = CourseItem::whereHas('children')->first();
    
    if ($parentCourse) {
        echo "✅ Found parent course: {$parentCourse->name} (ID: {$parentCourse->id})\n";
        echo "   - Children count: " . $parentCourse->children()->count() . "\n";
        echo "   - Descendants count: " . $parentCourse->descendants()->count() . "\n";
        
        $hierarchicalRequest = new Request();
        $hierarchicalRequest->merge([
            'columns' => ['student_name', 'student_phone', 'course_name', 'course_path']
        ]);
        
        try {
            $response = $controller->exportStudents($hierarchicalRequest, $parentCourse);
            
            if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
                echo "✅ Hierarchical export successful\n";
                echo "   - File size: " . number_format($response->getFile()->getSize()) . " bytes\n";
                
                // Check content
                $content = file_get_contents($response->getFile()->getPathname());
                $lines = explode("\n", $content);
                echo "   - Estimated rows: " . count($lines) . "\n";
                
            } else {
                echo "❌ Hierarchical export failed\n";
            }
            
        } catch (\Exception $e) {
            echo "❌ Hierarchical export error: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "⚠️  No parent course found for hierarchical test\n";
    }
    
    echo "\n=== API TEST COMPLETED ===\n";
    
} catch (\Exception $e) {
    echo "❌ General error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
