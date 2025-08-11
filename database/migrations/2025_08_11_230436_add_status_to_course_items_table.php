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
        Schema::table('course_items', function (Blueprint $table) {
            // Thêm trường status với enum cho trạng thái khóa học
            $table->enum('status', ['active', 'completed'])->default('active')->after('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_items', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};