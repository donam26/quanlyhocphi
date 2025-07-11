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
        Schema::create('course_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade'); // Liên kết với khóa học
            $table->foreignId('sub_course_id')->nullable()->constrained()->onDelete('cascade'); // Liên kết với khóa nhỏ (nếu có)
            $table->string('name'); // Tên lớp (VD: Đào tạo nghề kế toán khóa 1 Online)
            $table->enum('type', ['online', 'offline']); // Loại lớp
            $table->integer('batch_number')->default(1); // Số khóa (khóa 1, khóa 2,...)
            $table->integer('max_students')->default(50); // Số lượng học viên tối đa
            $table->date('start_date')->nullable(); // Ngày khai giảng
            $table->date('end_date')->nullable(); // Ngày kết thúc
            $table->date('registration_deadline')->nullable(); // Hạn đăng ký
            $table->enum('status', ['planned', 'open', 'in_progress', 'completed', 'cancelled'])->default('planned'); // Trạng thái lớp
            $table->boolean('is_package')->default(false); // Đánh dấu lớp là một gói (package) hay một lớp đơn lẻ
            $table->foreignId('parent_class_id')->nullable()->references('id')->on('course_classes')->onDelete('cascade'); // Liên kết với lớp cha (nếu là lớp con trong gói)
            $table->text('notes')->nullable(); // Ghi chú
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_classes');
    }
};
