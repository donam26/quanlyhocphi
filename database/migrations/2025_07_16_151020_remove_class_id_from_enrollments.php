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
            // Trước khi xóa cột class_id, cần đảm bảo course_item_id đã có dữ liệu
            if (!Schema::hasColumn('enrollments', 'course_item_id')) {
                return;
            }
            
            // Xóa foreign key constraint
            $table->dropForeign(['class_id']);
            
            // Xóa cột class_id
            $table->dropColumn('class_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Thêm lại cột class_id
            $table->foreignId('class_id')->nullable()->after('student_id')->constrained('classes')->onDelete('cascade');
            
            // Cập nhật lại class_id từ course_item_id (lấy class đầu tiên có course_item_id tương ứng)
            DB::statement('
                UPDATE enrollments e
                LEFT JOIN classes c ON e.course_item_id = c.course_item_id
                SET e.class_id = c.id
                WHERE e.class_id IS NULL
            ');
        });
    }
};
