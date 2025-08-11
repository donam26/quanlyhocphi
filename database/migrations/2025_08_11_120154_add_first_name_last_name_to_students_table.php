<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Thêm các trường họ và tên riêng biệt
            $table->string('first_name')->after('full_name')->comment('Họ và tên đệm');
            $table->string('last_name')->after('first_name')->comment('Tên');
        });
        
        // Di chuyển dữ liệu từ full_name sang first_name, last_name
        DB::statement('
            UPDATE students 
            SET 
                last_name = TRIM(SUBSTRING_INDEX(full_name, " ", -1)),
                first_name = TRIM(SUBSTRING(full_name, 1, LENGTH(full_name) - LENGTH(SUBSTRING_INDEX(full_name, " ", -1)) - 1))
            WHERE full_name IS NOT NULL AND full_name != ""
        ');
        
        Schema::table('students', function (Blueprint $table) {
            // Xóa trường full_name cũ
            $table->dropColumn('full_name');
            
            // Thêm index để tìm kiếm nhanh
            $table->index(['first_name', 'last_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Thêm lại trường full_name
            $table->string('full_name')->after('id');
        });
        
        // Ghép lại full_name từ first_name + last_name
        DB::statement('
            UPDATE students 
            SET full_name = TRIM(CONCAT(IFNULL(first_name, ""), " ", IFNULL(last_name, "")))
            WHERE (first_name IS NOT NULL OR last_name IS NOT NULL)
        ');
        
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['first_name', 'last_name']);
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};