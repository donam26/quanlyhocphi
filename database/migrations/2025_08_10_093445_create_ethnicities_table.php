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
        Schema::create('ethnicities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Tên dân tộc
            $table->string('code', 10)->unique(); // Mã dân tộc
            $table->timestamps();
            
            // Index để tìm kiếm nhanh
            $table->index('name');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ethnicities');
    }
};
