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
        Schema::table('course_items', function (Blueprint $table) {
            // Trước khi xóa column active, đảm bảo tất cả course items có active = false 
            // sẽ được chuyển status thành 'completed' nếu chưa có
            \DB::statement("
                UPDATE course_items 
                SET status = 'completed' 
                WHERE active = 0 AND status = 'active'
            ");
            
            // Drop column active
            $table->dropColumn('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_items', function (Blueprint $table) {
            // Thêm lại column active
            $table->boolean('active')->default(true)->after('order_index');
            
            // Khôi phục dữ liệu: active = true nếu status = 'active', false nếu status = 'completed'
            \DB::statement("
                UPDATE course_items 
                SET active = CASE 
                    WHEN status = 'active' THEN 1 
                    ELSE 0 
                END
            ");
        });
    }
};
