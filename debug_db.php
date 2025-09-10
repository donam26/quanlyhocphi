<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\Ethnicity;

echo "=== Database Structure Debug ===\n";

// Check if ethnicity_id column exists in students table
echo "Checking students table structure:\n";
$columns = DB::select("SHOW COLUMNS FROM students WHERE Field = 'ethnicity_id'");
if (empty($columns)) {
    echo "ERROR: ethnicity_id column does not exist in students table!\n";
} else {
    echo "ethnicity_id column exists: " . json_encode($columns[0]) . "\n";
}

// Check foreign key constraints
echo "\nChecking foreign key constraints:\n";
$constraints = DB::select("
    SELECT 
        CONSTRAINT_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_NAME = 'students' 
    AND COLUMN_NAME = 'ethnicity_id'
    AND TABLE_SCHEMA = DATABASE()
");

if (empty($constraints)) {
    echo "No foreign key constraint found for ethnicity_id\n";
} else {
    echo "Foreign key constraint: " . json_encode($constraints[0]) . "\n";
}

// Check actual data
echo "\nChecking actual data:\n";
$result = DB::select("
    SELECT s.id, CONCAT(s.first_name, ' ', s.last_name) as full_name, s.ethnicity_id, e.name as ethnicity_name
    FROM students s
    LEFT JOIN ethnicities e ON s.ethnicity_id = e.id
    WHERE s.ethnicity_id IS NOT NULL
    LIMIT 5
");

foreach ($result as $row) {
    echo "Student: {$row->full_name}, ethnicity_id: {$row->ethnicity_id}, ethnicity_name: " . ($row->ethnicity_name ?? 'NULL') . "\n";
}
