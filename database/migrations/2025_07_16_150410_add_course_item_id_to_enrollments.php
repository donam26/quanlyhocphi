<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Added this import for DB facade
use Illuminate\Support\Facades\Log; // Added this import for Log facade

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Thêm cột course_item_id vào bảng enrollments
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('course_item_id')->nullable()->after('class_id')->constrained('course_items')->onDelete('cascade');
        });
        
        // Sau khi đã thêm cột, thực hiện cập nhật dữ liệu
        try {
            DB::statement('UPDATE enrollments e JOIN classes c ON e.class_id = c.id SET e.course_item_id = c.course_item_id');
        } catch (\Exception $e) {
            // Ghi log lỗi nếu có
            Log::error('Error updating course_item_id: ' . $e->getMessage());
        }
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
