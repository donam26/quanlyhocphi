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
            $table->enum('learning_method', ['online', 'offline'])
                  ->nullable()
                  ->after('is_special')
                  ->comment('Phương thức học: online (trực tuyến) hoặc offline (trực tiếp). Chỉ áp dụng cho khóa học cuối cùng (is_leaf = true)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_items', function (Blueprint $table) {
            $table->dropColumn('learning_method');
        });
    }
};
