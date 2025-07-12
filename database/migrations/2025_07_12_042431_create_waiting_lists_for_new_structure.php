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
        // Tạo bảng waiting_lists mới
        Schema::create('waiting_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade'); // Liên kết với học viên
            $table->foreignId('course_item_id')->constrained()->onDelete('cascade'); // Liên kết với khóa học (course_items)
            $table->date('added_date'); // Ngày thêm vào danh sách chờ
            $table->enum('interest_level', ['low', 'medium', 'high'])->default('medium'); // Mức độ quan tâm
            $table->enum('status', ['waiting', 'contacted', 'enrolled', 'not_interested'])->default('waiting'); // Trạng thái
            $table->date('last_contact_date')->nullable(); // Ngày liên hệ cuối
            $table->text('contact_notes')->nullable(); // Ghi chú về việc liên hệ
            $table->timestamps();
            
            // Đảm bảo một học viên chỉ có một bản ghi chờ cho một khóa học
            $table->unique(['student_id', 'course_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiting_lists');
    }
};
