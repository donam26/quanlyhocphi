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
            $table->enum('source', ['facebook', 'zalo', 'website', 'linkedin', 'tiktok', 'friend_referral'])
                  ->nullable()
                  ->after('company_address')
                  ->comment('Nguồn biết đến: Facebook, Zalo, Website, LinkedIn, TikTok, Bạn bè giới thiệu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
