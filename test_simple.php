<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Student;
use App\Models\Ethnicity;

// Test với fresh instance
$student = new Student();
$student = $student->find(195);

echo "Fresh instance test:\n";
echo "ethnicity_id: " . $student->ethnicity_id . "\n";

// Test relationship với fresh query
$studentWithEthnicity = Student::with('ethnicity')->find(195);
echo "With eager loading: " . ($studentWithEthnicity->ethnicity ? $studentWithEthnicity->ethnicity->name : 'null') . "\n";

// Test bằng cách tạo relationship thủ công trong code
$ethnicity = Ethnicity::find($student->ethnicity_id);
echo "Manual relationship: " . ($ethnicity ? $ethnicity->name : 'null') . "\n";
