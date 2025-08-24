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
            // Xóa unique constraint trên student_id để cho phép học viên có nhiều enrollment
            $table->dropUnique('enrollments_student_id_unique');
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
