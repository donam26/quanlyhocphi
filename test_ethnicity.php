<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Student;
use App\Models\Ethnicity;

echo "=== Testing Ethnicity Relationship ===\n";

// Test 1: Get a student with ethnicity_id
$student = Student::first();
echo "Student ID: " . $student->id . "\n";
echo "Student Name: " . $student->full_name . "\n";
echo "ethnicity_id: " . $student->ethnicity_id . "\n";

// Test 2: Direct query for ethnicity
if ($student->ethnicity_id) {
    $ethnicity = Ethnicity::find($student->ethnicity_id);
    echo "Direct ethnicity query: " . ($ethnicity ? $ethnicity->name : 'null') . "\n";
}

// Test 3: Test relationship
echo "Testing relationship...\n";
try {
    $relationshipResult = $student->ethnicity;
    echo "Relationship result: " . ($relationshipResult ? $relationshipResult->name : 'null') . "\n";
} catch (Exception $e) {
    echo "Relationship error: " . $e->getMessage() . "\n";
}

// Test 4: Force load relationship
echo "Force loading relationship...\n";
$student->load('ethnicity');
echo "After load: " . ($student->ethnicity ? $student->ethnicity->name : 'null') . "\n";

// Test 5: Check all ethnicities
echo "\nAll ethnicities:\n";
$ethnicities = Ethnicity::all();
foreach ($ethnicities->take(5) as $eth) {
    echo "ID: {$eth->id} - Name: {$eth->name}\n";
}
