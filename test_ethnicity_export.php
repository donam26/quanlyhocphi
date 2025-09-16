<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== KIỂM TRA DỮ LIỆU ETHNICITY ===\n\n";

// 1. Kiểm tra bảng ethnicities
echo "1. Kiểm tra bảng ethnicities:\n";
$ethnicities = DB::table('ethnicities')->get();
echo "Tổng số dân tộc: " . $ethnicities->count() . "\n";
if ($ethnicities->count() > 0) {
    echo "Một số dân tộc:\n";
    foreach ($ethnicities->take(5) as $ethnicity) {
        echo "  - ID: {$ethnicity->id}, Name: {$ethnicity->name}, Code: {$ethnicity->code}\n";
    }
} else {
    echo "⚠️ CẢNH BÁO: Bảng ethnicities trống!\n";
}
echo "\n";

// 2. Kiểm tra students có ethnicity_id
echo "2. Kiểm tra students có ethnicity_id:\n";
$studentStats = DB::table('students')
    ->selectRaw('
        COUNT(*) as total,
        COUNT(ethnicity_id) as has_ethnicity,
        COUNT(*) - COUNT(ethnicity_id) as null_ethnicity
    ')
    ->first();

echo "Tổng số students: {$studentStats->total}\n";
echo "Có ethnicity_id: {$studentStats->has_ethnicity}\n";
echo "Không có ethnicity_id: {$studentStats->null_ethnicity}\n";
echo "\n";

// 3. Kiểm tra relationship
echo "3. Kiểm tra relationship:\n";
$studentsWithEthnicity = DB::table('students')
    ->join('ethnicities', 'students.ethnicity_id', '=', 'ethnicities.id')
    ->select('students.id', 'students.first_name', 'students.last_name', 'ethnicities.name as ethnicity_name')
    ->limit(5)
    ->get();

if ($studentsWithEthnicity->count() > 0) {
    echo "Một số students có dân tộc:\n";
    foreach ($studentsWithEthnicity as $student) {
        echo "  - Student ID: {$student->id}, Name: {$student->first_name} {$student->last_name}, Ethnicity: {$student->ethnicity_name}\n";
    }
} else {
    echo "⚠️ CẢNH BÁO: Không có student nào có dân tộc hợp lệ!\n";
}
echo "\n";

// 4. Kiểm tra các ethnicity_id không tồn tại
echo "4. Kiểm tra ethnicity_id không hợp lệ:\n";
$invalidEthnicityIds = DB::table('students')
    ->whereNotNull('ethnicity_id')
    ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
            ->from('ethnicities')
            ->whereRaw('ethnicities.id = students.ethnicity_id');
    })
    ->pluck('ethnicity_id')
    ->unique();

if ($invalidEthnicityIds->count() > 0) {
    echo "⚠️ CẢNH BÁO: Có " . $invalidEthnicityIds->count() . " ethnicity_id không hợp lệ:\n";
    foreach ($invalidEthnicityIds as $id) {
        echo "  - Ethnicity ID: {$id}\n";
    }
} else {
    echo "✅ Tất cả ethnicity_id đều hợp lệ\n";
}
echo "\n";

// 5. Test với Eloquent Model
echo "5. Test với Eloquent Model:\n";
try {
    $students = \App\Models\Student::with('ethnicity')->limit(3)->get();
    foreach ($students as $student) {
        echo "Student ID: {$student->id}\n";
        echo "  - ethnicity_id: " . ($student->ethnicity_id ?? 'NULL') . "\n";
        echo "  - ethnicity loaded: " . ($student->relationLoaded('ethnicity') ? 'YES' : 'NO') . "\n";
        echo "  - ethnicity name: " . ($student->ethnicity ? $student->ethnicity->name : 'NULL') . "\n";
        echo "\n";
    }
} catch (Exception $e) {
    echo "❌ Lỗi khi test Eloquent: " . $e->getMessage() . "\n";
}

echo "=== KẾT THÚC KIỂM TRA ===\n";
