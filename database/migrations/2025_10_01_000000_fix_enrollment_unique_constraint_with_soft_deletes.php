<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Xóa unique constraint cũ nếu tồn tại
            try {
                $table->dropUnique(['student_id', 'course_item_id']);
            } catch (Exception $e) {
                // Ignore if constraint doesn't exist
            }
        });

        // Tạo unique index mới bao gồm deleted_at
        // Điều này cho phép tạo lại enrollment sau khi đã bị soft delete
        DB::statement('
            CREATE UNIQUE INDEX enrollments_student_course_unique 
            ON enrollments (student_id, course_item_id, deleted_at)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa unique index mới
        DB::statement('DROP INDEX IF EXISTS enrollments_student_course_unique');
        
        // Khôi phục unique constraint cũ
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unique(['student_id', 'course_item_id'], 'enrollments_student_id_course_item_id_unique');
        });
    }
};
