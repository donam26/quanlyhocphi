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
        // Đầu tiên tạo bảng enrollments mới
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade'); // Liên kết với học viên
            $table->date('enrollment_date'); // Ngày ghi danh
            $table->enum('status', ['enrolled', 'completed', 'dropped', 'transferred'])->default('enrolled'); // Trạng thái ghi danh
            $table->decimal('discount_percentage', 5, 2)->default(0); // Phần trăm chiết khấu
            $table->decimal('discount_amount', 15, 2)->default(0); // Số tiền chiết khấu
            $table->decimal('final_fee', 15, 2); // Học phí cuối cùng sau chiết khấu
            $table->text('notes')->nullable(); // Ghi chú
            $table->json('custom_fields')->nullable()->after('notes');
            $table->timestamps();
            
            // Đảm bảo một học viên chỉ ghi danh một lần cho một lớp
            $table->unique(['student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
