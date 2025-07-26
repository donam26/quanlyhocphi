<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Thêm ràng buộc duy nhất mới cho student_id và course_item_id
            // Điều này đảm bảo một học viên chỉ được ghi danh một lần vào mỗi khóa học
            $table->unique(['student_id', 'course_item_id'], 'enrollments_student_id_course_item_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Loại bỏ ràng buộc duy nhất khi rollback
            $table->dropUnique('enrollments_student_id_course_item_id_unique');
        });
    }
};
