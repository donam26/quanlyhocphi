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
        Schema::create('sub_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade'); // Liên kết với khóa học chính
            $table->string('name'); // Tên khóa nhỏ (ĐTN 2.5T, ĐTN 3T, KTTH, v.v.)
            $table->text('description')->nullable(); // Mô tả khóa nhỏ
            $table->decimal('fee', 15, 2)->default(0); // Học phí riêng
            $table->integer('order')->default(1); // Thứ tự trong khóa học
            $table->string('code')->nullable(); // Mã khóa con (ĐTN 2.5T, ĐTN 3T, KTTH, v.v.)
            $table->boolean('has_online')->default(true); // Có lớp online không
            $table->boolean('has_offline')->default(true); // Có lớp offline không
            $table->boolean('active')->default(true); // Trạng thái hoạt động
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_courses');
    }
};
