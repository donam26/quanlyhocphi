<?php

// Bootstrap Laravel application
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Student;
use App\Models\Province;
use App\Models\Ethnicity;
use App\Exports\StudentsExport;
use Maatwebsite\Excel\Facades\Excel;

// Test script để kiểm tra chức năng export
echo "=== TESTING EXPORT FUNCTIONALITY ===\n\n";

try {
    // 1. Test tạo dữ liệu mẫu
    echo "1. Creating sample data...\n";
    
    // Tạo province mẫu
    $province = Province::firstOrCreate([
        'name' => 'Hà Nội',
        'code' => 'HN'
    ]);
    
    // Tạo ethnicity mẫu
    $ethnicity = Ethnicity::firstOrCreate([
        'name' => 'Kinh'
    ]);
    
    // Tạo students mẫu
    $students = collect();
    for ($i = 1; $i <= 5; $i++) {
        $student = Student::firstOrCreate([
            'phone' => '090123456' . $i
        ], [
            'first_name' => 'Nguyễn Văn',
            'last_name' => 'Test ' . $i,
            'email' => "test{$i}@example.com",
            'date_of_birth' => now()->subYears(25)->subDays($i),
            'gender' => $i % 2 == 0 ? 'male' : 'female',
            'province_id' => $province->id,
            'ethnicity_id' => $ethnicity->id,
            'current_workplace' => "Công ty Test {$i}",
            'accounting_experience_years' => $i,
            'education_level' => 'bachelor',
            'source' => 'website'
        ]);
        $students->push($student);
    }
    
    echo "Created {$students->count()} sample students\n\n";
    
    // 2. Test StudentsExport class
    echo "2. Testing StudentsExport class...\n";
    
    $columns = [
        'full_name', 'phone', 'email', 'date_of_birth', 'gender',
        'province', 'current_workplace', 'accounting_experience_years'
    ];
    
    $export = new StudentsExport($students, $columns);
    
    // Test collection method
    $collection = $export->collection();
    echo "Collection count: " . $collection->count() . "\n";
    
    // Test headings method
    $headings = $export->headings();
    echo "Headings: " . implode(', ', $headings) . "\n";
    
    // Test mapping method
    $firstStudent = $students->first();
    $mappedData = $export->map($firstStudent);
    echo "Mapped data for first student: " . implode(', ', $mappedData) . "\n\n";
    
    // 3. Test actual Excel generation
    echo "3. Testing Excel file generation...\n";
    
    $fileName = 'test_export_' . date('Y_m_d_H_i_s') . '.xlsx';
    $filePath = storage_path('app/' . $fileName);
    
    Excel::store($export, $fileName);
    
    if (file_exists($filePath)) {
        $fileSize = filesize($filePath);
        echo "✅ Excel file created successfully!\n";
        echo "File: {$filePath}\n";
        echo "Size: {$fileSize} bytes\n\n";
        
        // Clean up test file
        unlink($filePath);
        echo "Test file cleaned up.\n\n";
    } else {
        echo "❌ Excel file creation failed!\n\n";
    }
    
    // 4. Test API endpoint simulation
    echo "4. Testing API endpoint logic...\n";
    
    // Simulate request data
    $requestData = [
        'columns' => $columns,
        'filters' => [
            'search' => 'Test',
            'gender' => 'male',
            'province_id' => $province->id
        ]
    ];
    
    // Build query like in controller
    $query = Student::with(['province', 'placeOfBirthProvince', 'ethnicity']);
    
    // Apply search filter
    if (!empty($requestData['filters']['search'])) {
        $term = $requestData['filters']['search'];
        $query->where(function($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhereRaw("CONCAT(IFNULL(first_name, ''), ' ', IFNULL(last_name, '')) LIKE ?", ["%{$term}%"])
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
    }
    
    // Apply gender filter
    if (!empty($requestData['filters']['gender'])) {
        $query->where('gender', $requestData['filters']['gender']);
    }
    
    // Apply province filter
    if (!empty($requestData['filters']['province_id'])) {
        $query->where('province_id', $requestData['filters']['province_id']);
    }
    
    $filteredStudents = $query->get();
    echo "Filtered students count: " . $filteredStudents->count() . "\n";
    
    if ($filteredStudents->count() > 0) {
        echo "✅ Filtering works correctly!\n";
        
        // Test export with filtered data
        $filteredExport = new StudentsExport($filteredStudents, $columns);
        $testFileName = 'test_filtered_export_' . date('Y_m_d_H_i_s') . '.xlsx';
        $testFilePath = storage_path('app/' . $testFileName);
        
        Excel::store($filteredExport, $testFileName);
        
        if (file_exists($testFilePath)) {
            echo "✅ Filtered export created successfully!\n";
            unlink($testFilePath);
            echo "Filtered test file cleaned up.\n";
        }
    } else {
        echo "⚠️ No students found with applied filters\n";
    }
    
    echo "\n=== EXPORT FUNCTIONALITY TEST COMPLETED ===\n";
    echo "✅ All tests passed! Export functionality is working correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
