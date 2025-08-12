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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('first_name'); // Họ và tên
            $table->string('last_name'); // Họ và tên
            $table->date('date_of_birth'); // Ngày tháng năm sinh
            $table->enum('gender', ['male', 'female', 'other'])->nullable(); // Giới tính
            $table->string('email')->nullable(); // Email
            $table->string('phone')->unique(); // Số điện thoại
            $table->text('address')->nullable(); // Địa chỉ
            
            // Thông tin bổ sung cho lớp Kế toán trưởng
            $table->string('current_workplace')->nullable(); // Nơi công tác hiện tại
            $table->integer('accounting_experience_years')->nullable(); // Số năm kinh nghiệm làm kế toán
            $table->string('training_specialization')->nullable(); // Chuyên môn đào tạo
            $table->enum('hard_copy_documents', ['submitted', 'not_submitted'])->nullable();
            $table->enum('education_level', ['vocational', 'associate', 'bachelor', 'master', 'secondary'])->nullable();

            $table->text('notes')->nullable(); // Ghi chú
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
