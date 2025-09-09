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
            // Kiểm tra xem constraint có tồn tại không trước khi xóa
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('enrollments');

            if (array_key_exists('enrollments_student_id_unique', $indexes)) {
                $table->dropUnique('enrollments_student_id_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Khôi phục unique constraint nếu cần rollback
            $table->unique('student_id', 'enrollments_student_id_unique');
        });
    }
};
