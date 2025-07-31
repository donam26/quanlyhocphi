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
        // Xóa bảng waiting_lists sau khi đã di chuyển dữ liệu sang bảng enrollments
        Schema::dropIfExists('waiting_lists');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tạo lại bảng waiting_lists nếu cần rollback
        Schema::create('waiting_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_item_id')->constrained()->onDelete('cascade');
            $table->timestamp('request_date')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->text('contact_notes')->nullable();
            $table->string('priority')->default('normal');
            $table->timestamps();
            
            // Thêm các cột khác nếu cần
        });
    }
}; 