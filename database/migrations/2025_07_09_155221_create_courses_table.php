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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('major_id')->constrained()->onDelete('cascade'); // Liên kết với ngành học
            $table->string('name'); // Tên khóa học
            $table->text('description')->nullable(); // Mô tả khóa học
            $table->decimal('fee', 15, 2)->default(0); // Học phí
            $table->integer('duration')->nullable(); // Thời lượng (tháng)
            $table->boolean('is_complex')->default(false); // Đánh dấu khóa phức tạp (có khóa nhỏ)
            $table->enum('course_type', ['standard', 'complex'])->default('standard'); // Loại khóa học (tiêu chuẩn hoặc phức tạp)
            $table->boolean('active')->default(true); // Trạng thái hoạt động
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
