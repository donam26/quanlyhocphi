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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_item_id')->constrained('course_items')->onDelete('cascade');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('day_of_week'); // Thứ trong tuần (T2, T3, T4, T5, T6, T7, CN)
            $table->string('location')->nullable(); // Địa điểm học
            $table->string('teacher_name')->nullable(); // Tên giáo viên
            $table->text('notes')->nullable(); // Ghi chú
            $table->boolean('is_recurring')->default(false); // Lịch học định kỳ hay không
            $table->boolean('active')->default(true); // Trạng thái hoạt động
            $table->timestamps();
            
            // Tạo index để tối ưu truy vấn
            $table->index('date');
            $table->index('day_of_week');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
