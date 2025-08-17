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
        // Thêm 'sepay' vào enum payment_method
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'bank_transfer', 'card', 'qr_code', 'sepay')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa 'sepay' khỏi enum payment_method
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'bank_transfer', 'card', 'qr_code')");
    }
};
