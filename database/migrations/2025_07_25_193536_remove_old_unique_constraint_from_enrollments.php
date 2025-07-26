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
        // Xóa ràng buộc duy nhất cũ nếu còn tồn tại
        // Kiểm tra xem ràng buộc có tồn tại không bằng cách truy vấn thông tin bảng
        $schemaName = config('database.connections.mysql.database');
        $constraintExists = DB::select("
            SELECT * 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = ? 
            AND TABLE_NAME = 'enrollments' 
            AND CONSTRAINT_NAME = 'enrollments_student_id_class_id_unique'",
            [$schemaName]
        );

        if (!empty($constraintExists)) {
        Schema::table('enrollments', function (Blueprint $table) {
                $table->dropIndex('enrollments_student_id_class_id_unique');
        });
        }
    }

    /**
     * Reverse the migrations.
     * Chúng ta không cần thêm lại ràng buộc cũ khi rollback vì nó đã không còn phù hợp 
     * với cấu trúc bảng hiện tại (không còn cột class_id).
     */
    public function down(): void
    {
        // Không làm gì khi rollback
    }
};
