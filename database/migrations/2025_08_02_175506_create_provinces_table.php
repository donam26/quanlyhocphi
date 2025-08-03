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
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên tỉnh thành
            $table->string('code', 10)->unique(); // Mã tỉnh (VD: HN, HCM)
            $table->enum('region', ['north', 'central', 'south']); // Phân vùng: Bắc, Trung, Nam
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
