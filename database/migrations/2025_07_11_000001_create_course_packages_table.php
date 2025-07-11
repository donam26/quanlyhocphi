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
        Schema::create('course_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade'); // Liên kết với khóa học chính
            $table->string('name'); // Tên gói (VD: Đào tạo nghề kế toán khóa 1 ONLINE)
            $table->text('description')->nullable(); // Mô tả gói khóa học
            $table->enum('type', ['online', 'offline']); // Loại gói
            $table->integer('batch_number')->default(1); // Số khóa (khóa 1, khóa 2,...)
            $table->decimal('package_fee', 15, 2)->default(0); // Học phí gói (có thể khác tổng học phí các khóa con)
            $table->date('start_date')->nullable(); // Ngày bắt đầu gói
            $table->date('end_date')->nullable(); // Ngày kết thúc gói
            $table->boolean('active')->default(true); // Trạng thái hoạt động
            $table->timestamps();
        });

        // Bảng liên kết giữa gói khóa học và lớp học
        Schema::create('course_package_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('course_packages')->onDelete('cascade'); // Liên kết với gói khóa học
            $table->foreignId('course_class_id')->constrained()->onDelete('cascade'); // Liên kết với lớp học
            $table->integer('order')->default(0); // Thứ tự trong gói
            $table->timestamps();
            
            // Đảm bảo mỗi lớp chỉ thuộc về một gói một lần
            $table->unique(['package_id', 'course_class_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_package_classes');
        Schema::dropIfExists('course_packages');
    }
}; 