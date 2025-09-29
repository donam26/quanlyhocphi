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
        // Thêm giá trị 'other' vào enum source và đặt default là 'other'
        DB::statement("ALTER TABLE students MODIFY COLUMN source ENUM('facebook', 'zalo', 'website', 'linkedin', 'tiktok', 'friend_referral', 'other') DEFAULT 'other' COMMENT 'Nguồn biết đến: Facebook, Zalo, Website, LinkedIn, TikTok, Bạn bè giới thiệu, Khác'");
        
        // Thêm giá trị 'other' vào enum education_level và đặt default là 'other'
        DB::statement("ALTER TABLE students MODIFY COLUMN education_level ENUM('vocational', 'associate', 'bachelor', 'master', 'secondary', 'other') DEFAULT 'other' COMMENT 'Trình độ học vấn: Trung cấp, Cao đẳng, Đại học, Thạc sĩ, Trung học, Khác'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa giá trị 'other' khỏi enum source và bỏ default
        DB::statement("ALTER TABLE students MODIFY COLUMN source ENUM('facebook', 'zalo', 'website', 'linkedin', 'tiktok', 'friend_referral') NULL COMMENT 'Nguồn biết đến: Facebook, Zalo, Website, LinkedIn, TikTok, Bạn bè giới thiệu'");
        
        // Xóa giá trị 'other' khỏi enum education_level và bỏ default
        DB::statement("ALTER TABLE students MODIFY COLUMN education_level ENUM('vocational', 'associate', 'bachelor', 'master', 'secondary') NULL COMMENT 'Trình độ học vấn: Trung cấp, Cao đẳng, Đại học, Thạc sĩ, Trung học'");
    }
};
