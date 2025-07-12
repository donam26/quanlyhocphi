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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_item_id')->constrained()->onDelete('cascade'); // Liên kết với khóa học (nút lá)
            $table->string('name'); // Tên lớp (VD: Đào tạo nghề kế toán khóa 1 Online)
            $table->enum('type', ['online', 'offline']); // Loại lớp
            $table->integer('batch_number')->default(1); // Số khóa (khóa 1, khóa 2,...)
            $table->integer('max_students')->default(50); // Số lượng học viên tối đa
            $table->date('start_date')->nullable(); // Ngày khai giảng
            $table->date('end_date')->nullable(); // Ngày kết thúc
            $table->date('registration_deadline')->nullable(); // Hạn đăng ký
            $table->enum('status', ['planned', 'open', 'in_progress', 'completed', 'cancelled'])->default('planned'); // Trạng thái lớp
            $table->text('notes')->nullable(); // Ghi chú
            $table->timestamps();
            
            // Index để tối ưu truy vấn
            $table->index('course_item_id');
            $table->index('status');
            $table->index('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
