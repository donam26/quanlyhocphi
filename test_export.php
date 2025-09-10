<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Attendance;
use App\Models\CourseItem;

echo "=== Testing Export Ethnicity Data ===\n";

// Get first course item for testing
$courseItem = CourseItem::first();
if (!$courseItem) {
    echo "No course items found\n";
    exit;
}

echo "Testing with course: " . $courseItem->name . "\n";

// Test the export logic similar to AttendanceExport
$query = Attendance::with([
    'enrollment.student.province',
    'enrollment.student.ethnicity',
    'enrollment.student.placeOfBirthProvince',
    'enrollment.courseItem',
    'enrollment.payments'
])->where('course_item_id', $courseItem->id);

$attendances = $query->limit(3)->get();

foreach ($attendances as $attendance) {
    $student = $attendance->enrollment->student;
    
    echo "\n--- Student: " . $student->full_name . " ---\n";
    echo "ethnicity_id: " . $student->ethnicity_id . "\n";
    
    // Test relationship method
    echo "Relationship result: " . ($student->ethnicity ? $student->ethnicity->name : 'null') . "\n";
    
    // Test workaround method
    $ethnicity = $student->ethnicity_id ? \App\Models\Ethnicity::find($student->ethnicity_id) : null;
    echo "Workaround result: " . ($ethnicity ? $ethnicity->name : 'null') . "\n";
}
