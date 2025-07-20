<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Added this import for DB facade

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('course_item_id')->nullable()->after('class_id')->constrained('course_items')->onDelete('cascade');
            
            // Cập nhật course_item_id từ các bản ghi class_id hiện có
            DB::statement('UPDATE enrollments e JOIN classes c ON e.class_id = c.id SET e.course_item_id = c.course_item_id');
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
