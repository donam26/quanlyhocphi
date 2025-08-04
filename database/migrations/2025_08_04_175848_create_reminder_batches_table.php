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
        Schema::create('reminder_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên batch (VD: Nhắc nhở khóa ABC)');
            $table->enum('type', ['single_course', 'multiple_courses', 'individual'])->comment('Loại batch');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            
            // Counters
            $table->integer('total_count')->default(0)->comment('Tổng số email cần gửi');
            $table->integer('processed_count')->default(0)->comment('Số email đã xử lý');
            $table->integer('sent_count')->default(0)->comment('Số email gửi thành công');
            $table->integer('failed_count')->default(0)->comment('Số email gửi thất bại');
            $table->integer('skipped_count')->default(0)->comment('Số email bỏ qua');
            
            // Metadata
            $table->json('course_ids')->nullable()->comment('Danh sách ID khóa học');
            $table->json('enrollment_ids')->nullable()->comment('Danh sách ID enrollment');
            $table->json('errors')->nullable()->comment('Chi tiết lỗi');
            
            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            
            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'created_at']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminder_batches');
    }
};
