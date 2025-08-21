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
            // Thay đổi trường nation thành enum với tùy chọn "other"
            $table->enum('ethnicity', [
                'kinh', 'tay', 'thai', 'muong', 'khmer', 'hoa', 'nung', 'hmong', 'dao', 'gia_rai',
                'ngai', 'ede', 'ba_na', 'xo_dang', 'san_chay', 'co_ho', 'cham', 'san_diu', 'hre',
                'mnong', 'ra_glai', 'xtieng', 'bru_van_kieu', 'tho', 'giay', 'co_tu', 'gie_trieng',
                'ma', 'kho_mu', 'co', 'ta_oi', 'cho_ro', 'khanh', 'xinh_mun', 'ha_nhi', 'chu_ru',
                'lao', 'la_chi', 'la_ha', 'pu_peo', 'bo_y', 'la_hu', 'cong', 'si_la', 'mang',
                'pa_then', 'co_lao', 'cong_cong', 'lo_lo', 'chut', 'mieu', 'brau', 'o_du', 'roma', 'other'
            ])->nullable()->after('source')->comment('Dân tộc');

            // Trường nhập tự do khi chọn "Khác" cho dân tộc
            $table->string('ethnicity_other')->nullable()->after('ethnicity')->comment('Dân tộc khác (khi chọn Khác)');

            // Trường nhập tự do khi chọn "Khác" cho nơi sinh
            $table->string('place_of_birth_other')->nullable()->after('ethnicity_other')->comment('Nơi sinh khác (khi chọn Khác)');

            // Trường nhập tự do khi chọn "Khác" cho tỉnh thành
            $table->string('province_other')->nullable()->after('place_of_birth_other')->comment('Tỉnh/thành khác (khi chọn Khác)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'ethnicity',
                'ethnicity_other',
                'place_of_birth_other',
                'province_other'
            ]);
        });
    }
};
