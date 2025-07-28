<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Added missing import for DB facade

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('course_item_id')->nullable()->after('enrollment_id')->constrained('course_items')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->after('course_item_id')->constrained('students')->nullOnDelete();
            
            // Thêm index cho các cột mới
            $table->index('course_item_id');
            $table->index('student_id');
            
            // Đổi tên để phù hợp với chuẩn Laravel
            $table->renameColumn('class_date', 'attendance_date');
        });
        
        // Cập nhật dữ liệu
        DB::statement('UPDATE attendances SET 
                      course_item_id = (SELECT course_item_id FROM enrollments WHERE enrollments.id = attendances.enrollment_id),
                      student_id = (SELECT student_id FROM enrollments WHERE enrollments.id = attendances.enrollment_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['course_item_id']);
            $table->dropForeign(['student_id']);
            $table->dropColumn(['course_item_id', 'student_id']);
            $table->renameColumn('attendance_date', 'class_date');
        });
    }
};
