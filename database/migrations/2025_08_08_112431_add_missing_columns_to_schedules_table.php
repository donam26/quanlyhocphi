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
        Schema::table('schedules', function (Blueprint $table) {
            // Thêm các cột còn thiếu nếu chưa có
            if (!Schema::hasColumn('schedules', 'days_of_week')) {
                $table->json('days_of_week')->nullable()->after('course_item_id')->comment('Các ngày trong tuần (1-7: Thứ 2 - Chủ nhật)');
            }
            
            if (!Schema::hasColumn('schedules', 'start_date')) {
                $table->date('start_date')->nullable()->after('days_of_week')->comment('Ngày bắt đầu khóa học');
            }
            
            if (!Schema::hasColumn('schedules', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date')->comment('Ngày kết thúc khóa học');
            }
            
            if (!Schema::hasColumn('schedules', 'is_inherited')) {
                $table->boolean('is_inherited')->default(false)->after('active')->comment('Có phải lịch được kế thừa từ khóa cha không');
            }
            
            if (!Schema::hasColumn('schedules', 'parent_schedule_id')) {
                $table->foreignId('parent_schedule_id')->nullable()->after('is_inherited')->constrained('schedules')->onDelete('cascade')->comment('ID lịch cha (nếu kế thừa)');
            }
            
            // Indexes đã được tạo trong migration trước đó, bỏ qua
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Xóa foreign key trước
            if (Schema::hasColumn('schedules', 'parent_schedule_id')) {
                $table->dropForeign(['parent_schedule_id']);
            }
            
            // Xóa các cột đã thêm
            $columnsToRemove = [
                'days_of_week',
                'start_date', 
                'end_date',
                'is_inherited',
                'parent_schedule_id'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('schedules', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
