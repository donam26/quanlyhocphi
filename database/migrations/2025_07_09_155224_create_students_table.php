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
            $table->string('full_name'); // Họ và tên
            $table->date('date_of_birth'); // Ngày tháng năm sinh
            $table->string('place_of_birth')->nullable(); // Nơi sinh
            $table->string('citizen_id')->unique(); // CCCD
            $table->string('ethnicity')->nullable(); // Dân tộc
            $table->enum('gender', ['male', 'female', 'other'])->nullable(); // Giới tính
            $table->string('email')->nullable(); // Email
            $table->string('phone')->unique(); // Số điện thoại
            $table->text('address')->nullable(); // Địa chỉ
            
            // Thông tin bổ sung cho lớp Kế toán trưởng
            $table->string('current_workplace')->nullable(); // Nơi công tác hiện tại
            $table->integer('accounting_experience_years')->nullable(); // Số năm kinh nghiệm làm kế toán
            $table->enum('education_level', ['trung_cap', 'cao_dang', 'dai_hoc', 'thac_si', 'vb2'])->nullable(); // Bằng cấp
            $table->string('major_studied')->nullable(); // Ngành học
            
            // Trạng thái và ghi chú
            $table->enum('status', ['active', 'inactive', 'potential'])->default('active'); // Trạng thái học viên
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
