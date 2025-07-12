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
        // Tạo bảng payments mới
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->onDelete('cascade'); 
            $table->decimal('amount', 15, 2); // Số tiền thanh toán
            $table->date('payment_date'); // Ngày nộp tiền
            $table->enum('payment_method', ['cash', 'bank_transfer', 'card', 'qr_code']); // Hình thức nộp
            $table->string('transaction_reference')->nullable(); // Mã giao dịch (cho chuyển khoản)
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('confirmed'); // Trạng thái thanh toán
            $table->text('notes')->nullable(); // Ghi chú
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_payments');
    }
};
