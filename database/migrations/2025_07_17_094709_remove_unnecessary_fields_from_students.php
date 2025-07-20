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
        Schema::table('students', function (Blueprint $table) {
            // Xóa các trường không cần thiết
            $table->dropColumn('place_of_birth');
            $table->dropUnique(['citizen_id']); // Xóa ràng buộc unique trước khi xóa trường
            $table->dropColumn('citizen_id');
            $table->dropColumn('ethnicity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Thêm lại các trường nếu cần
            $table->string('place_of_birth')->nullable()->after('date_of_birth');
            $table->string('citizen_id')->unique()->after('place_of_birth');
            $table->string('ethnicity')->nullable()->after('citizen_id');
        });
    }
};
