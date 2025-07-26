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
        // Lấy ra danh sách tất cả các ràng buộc duy nhất trên bảng enrollments
        $schemaName = config('database.connections.mysql.database');
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = ? 
            AND TABLE_NAME = 'enrollments' 
            AND CONSTRAINT_TYPE = 'UNIQUE'",
            [$schemaName]
        );

        // Xóa tất cả các ràng buộc duy nhất hiện tại
        Schema::table('enrollments', function (Blueprint $table) use ($constraints) {
            foreach ($constraints as $constraint) {
                try {
                    DB::statement("ALTER TABLE enrollments DROP INDEX {$constraint->CONSTRAINT_NAME}");
                } catch (\Exception $e) {
                    // Bỏ qua lỗi nếu ràng buộc không tồn tại
                }
            }
        });

        // Thêm lại ràng buộc duy nhất đúng
        Schema::table('enrollments', function (Blueprint $table) {
            // Đảm bảo một học viên chỉ được ghi danh một lần cho mỗi khóa học
            try {
                $table->unique(['student_id', 'course_item_id'], 'enrollments_student_course_unique');
            } catch (\Exception $e) {
                // Bỏ qua lỗi nếu ràng buộc đã tồn tại
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa ràng buộc duy nhất nếu rollback
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique('enrollments_student_course_unique');
        });
    }
};
