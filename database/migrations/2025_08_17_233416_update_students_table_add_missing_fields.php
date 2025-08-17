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
            // Thêm trường ethnicity_id để liên kết với bảng ethnicities
            $table->foreignId('ethnicity_id')->nullable()->after('nation')->constrained('ethnicities')->nullOnDelete();

            // Thêm trường place_of_birth_province_id để chọn tỉnh nơi sinh
            $table->foreignId('place_of_birth_province_id')->nullable()->after('place_of_birth')->constrained('provinces')->nullOnDelete();

            // Sửa lại trường address để chỉ lưu địa chỉ chi tiết (không bao gồm tỉnh/thành phố)
            // province_id đã có sẵn để lưu tỉnh/thành phố
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['ethnicity_id']);
            $table->dropForeign(['place_of_birth_province_id']);
            $table->dropColumn(['ethnicity_id', 'place_of_birth_province_id']);
        });
    }
};
