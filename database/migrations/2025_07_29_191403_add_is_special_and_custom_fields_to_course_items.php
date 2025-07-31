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
            $table->boolean('is_special')->default(false)->after('active');
            $table->json('custom_fields')->nullable()->after('is_special');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_items', function (Blueprint $table) {
            $table->dropColumn(['is_special', 'custom_fields']);
        });
    }
};
