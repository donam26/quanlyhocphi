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
        Schema::table('attendances', function (Blueprint $table) {
            // Kiểm tra xem cột class_date có tồn tại không
            if (Schema::hasColumn('attendances', 'class_date')) {
                // Đổi tên cột class_date thành attendance_date nếu nó tồn tại
                $table->renameColumn('class_date', 'attendance_date');
            }
            
            // Đảm bảo có index cho attendance_date
            if (!Schema::hasIndex('attendances', 'attendances_attendance_date_index')) {
                $table->index('attendance_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Đổi tên lại từ attendance_date thành class_date
            if (Schema::hasColumn('attendances', 'attendance_date')) {
                $table->renameColumn('attendance_date', 'class_date');
            }
            
            // Đảm bảo có index cho class_date
            if (!Schema::hasIndex('attendances', 'attendances_class_date_index')) {
                $table->index('class_date');
            }
        });
    }
};
