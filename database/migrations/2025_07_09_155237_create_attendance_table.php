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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->onDelete('cascade'); // Liên kết với bản ghi ghi danh
            $table->date('class_date'); // Ngày học
            $table->time('start_time')->nullable(); // Giờ bắt đầu
            $table->time('end_time')->nullable(); // Giờ kết thúc
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('present'); // Trạng thái điểm danh
            $table->text('notes')->nullable(); // Ghi chú
            $table->timestamps();
            
            // Đảm bảo một học viên chỉ có một bản ghi điểm danh cho một ngày học
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
