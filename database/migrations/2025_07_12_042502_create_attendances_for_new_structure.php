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
        // Tạo bảng attendances mới
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->onDelete('cascade');
            $table->date('class_date'); // Ngày học
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('present'); // Trạng thái điểm danh
            $table->time('start_time')->nullable(); // Thời gian bắt đầu (tùy chọn)
            $table->time('end_time')->nullable(); // Thời gian kết thúc (tùy chọn)
            $table->text('notes')->nullable(); // Ghi chú
            $table->timestamps();
            
            // Index để tối ưu truy vấn
            $table->index('class_date');
            $table->index('status');
            
            // Đảm bảo mỗi học viên chỉ được điểm danh một lần trong ngày cho mỗi ghi danh
            $table->unique(['enrollment_id', 'class_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
