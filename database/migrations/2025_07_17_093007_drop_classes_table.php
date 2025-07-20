<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropClassesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Xóa bảng classes
        Schema::dropIfExists('classes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tạo lại bảng classes nếu cần khôi phục
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_item_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['online', 'offline']);
            $table->integer('batch_number')->default(1);
            $table->integer('max_students')->default(50);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('registration_deadline')->nullable();
            $table->enum('status', ['planned', 'open', 'in_progress', 'completed', 'cancelled'])->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Index để tối ưu truy vấn
            $table->index('course_item_id');
            $table->index('status');
            $table->index('start_date');
        });
    }
}
