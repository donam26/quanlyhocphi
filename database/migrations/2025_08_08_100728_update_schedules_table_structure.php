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
            // Thêm các cột mới nếu chưa có
            if (!Schema::hasColumn('schedules', 'name')) {
                $table->string('name')->after('course_item_id')->comment('Tên lịch học');
            }
            
            if (!Schema::hasColumn('schedules', 'description')) {
                $table->text('description')->nullable()->after('name')->comment('Mô tả lịch học');
            }
            
            if (!Schema::hasColumn('schedules', 'days_of_week')) {
                $table->json('days_of_week')->after('description')->comment('Các ngày trong tuần (1-7: Thứ 2 - Chủ nhật)');
            }
            
            if (!Schema::hasColumn('schedules', 'start_time')) {
                $table->time('start_time')->after('days_of_week')->comment('Giờ bắt đầu');
            }
            
            if (!Schema::hasColumn('schedules', 'end_time')) {
                $table->time('end_time')->after('start_time')->comment('Giờ kết thúc');
            }
            
            if (!Schema::hasColumn('schedules', 'duration_weeks')) {
                $table->integer('duration_weeks')->default(12)->after('end_time')->comment('Số tuần học');
            }
            
            if (!Schema::hasColumn('schedules', 'location')) {
                $table->string('location')->nullable()->after('duration_weeks')->comment('Địa điểm học');
            }
            
            if (!Schema::hasColumn('schedules', 'room')) {
                $table->string('room')->nullable()->after('location')->comment('Phòng học');
            }
            
            if (!Schema::hasColumn('schedules', 'instructor_name')) {
                $table->string('instructor_name')->nullable()->after('room')->comment('Tên giảng viên');
            }
            
            if (!Schema::hasColumn('schedules', 'instructor_phone')) {
                $table->string('instructor_phone')->nullable()->after('instructor_name')->comment('SĐT giảng viên');
            }
            
            if (!Schema::hasColumn('schedules', 'instructor_email')) {
                $table->string('instructor_email')->nullable()->after('instructor_phone')->comment('Email giảng viên');
            }
            
            if (!Schema::hasColumn('schedules', 'is_inherited')) {
                $table->boolean('is_inherited')->default(false)->after('active')->comment('Có phải lịch được kế thừa từ khóa cha không');
            }
            
            if (!Schema::hasColumn('schedules', 'parent_schedule_id')) {
                $table->foreignId('parent_schedule_id')->nullable()->after('is_inherited')->constrained('schedules')->onDelete('cascade')->comment('ID lịch cha (nếu kế thừa)');
            }
            
            if (!Schema::hasColumn('schedules', 'metadata')) {
                $table->json('metadata')->nullable()->after('parent_schedule_id')->comment('Thông tin bổ sung (JSON)');
            }
            
            // Thêm indexes
            try {
                $table->index(['course_item_id', 'active']);
            } catch (\Exception $e) {
                // Index đã tồn tại
            }
            
            try {
                $table->index(['start_date', 'end_date']);
            } catch (\Exception $e) {
                // Index đã tồn tại
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Xóa các cột đã thêm (nếu cần rollback)
            $columnsToRemove = [
                'name', 'description', 'days_of_week', 'start_time', 'end_time', 
                'duration_weeks', 'location', 'room', 'instructor_name', 
                'instructor_phone', 'instructor_email', 'is_inherited', 
                'parent_schedule_id', 'metadata'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('schedules', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
    

};
