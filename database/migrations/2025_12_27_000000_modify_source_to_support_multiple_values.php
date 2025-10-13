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
        // Backup dữ liệu hiện tại
        $students = DB::table('students')->select('id', 'source')->get();
        
        // Thêm cột mới để lưu trữ nhiều nguồn
        Schema::table('students', function (Blueprint $table) {
            $table->json('sources')->nullable()->after('source')->comment('Các nguồn biết đến (có thể nhiều nguồn)');
        });
        
        // Migrate dữ liệu từ cột cũ sang cột mới
        foreach ($students as $student) {
            if ($student->source) {
                DB::table('students')
                    ->where('id', $student->id)
                    ->update(['sources' => json_encode([$student->source])]);
            }
        }
        
        // Xóa cột cũ
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Thêm lại cột enum cũ
        Schema::table('students', function (Blueprint $table) {
            $table->enum('source', ['facebook', 'zalo', 'website', 'linkedin', 'tiktok', 'friend_referral', 'other'])
                  ->nullable()
                  ->after('company_address')
                  ->comment('Nguồn biết đến: Facebook, Zalo, Website, LinkedIn, TikTok, Bạn bè giới thiệu, Khác');
        });
        
        // Migrate dữ liệu từ JSON về enum (chỉ lấy nguồn đầu tiên)
        $students = DB::table('students')->select('id', 'sources')->get();
        
        foreach ($students as $student) {
            if ($student->sources) {
                $sourcesArray = json_decode($student->sources, true);
                if (is_array($sourcesArray) && count($sourcesArray) > 0) {
                    DB::table('students')
                        ->where('id', $student->id)
                        ->update(['source' => $sourcesArray[0]]);
                }
            }
        }
        
        // Xóa cột JSON
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('sources');
        });
    }
};
