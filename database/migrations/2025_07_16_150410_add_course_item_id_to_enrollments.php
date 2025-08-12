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
        // Thêm cột course_item_id vào bảng enrollments
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('course_item_id')->nullable()->after('student_id')->constrained('course_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['course_item_id']);
            $table->dropColumn('course_item_id');
        });
    }
};
