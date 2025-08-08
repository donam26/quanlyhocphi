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
        Schema::table('schedules', function (Blueprint $table) {
            // Xóa các cột không cần thiết
            $columnsToRemove = [
                'name',
                'description', 
                'start_time',
                'end_time',
                'duration_weeks',
                'location',
                'room',
                'instructor_name',
                'instructor_phone',
                'instructor_email',
                'metadata'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('schedules', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Khôi phục các cột nếu cần rollback
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('duration_weeks')->default(12);
            $table->string('location')->nullable();
            $table->string('room')->nullable();
            $table->string('instructor_name')->nullable();
            $table->string('instructor_phone')->nullable();
            $table->string('instructor_email')->nullable();
            $table->json('metadata')->nullable();
        });
    }
};
