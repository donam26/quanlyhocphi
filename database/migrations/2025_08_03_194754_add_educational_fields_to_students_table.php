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
            // Xóa trường custom_fields nếu tồn tại
            if (Schema::hasColumn('students', 'custom_fields')) {
                $table->dropColumn('custom_fields');
            }
            
            // Thêm các trường mới
            $table->enum('hard_copy_documents', ['submitted', 'not_submitted'])->nullable()->after('notes');
            $table->enum('education_level', ['vocational', 'associate', 'bachelor', 'master', 'secondary'])->nullable()->after('hard_copy_documents');
            $table->string('workplace')->nullable()->after('education_level');
            $table->integer('experience_years')->nullable()->after('workplace');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Xóa các trường mới nếu tồn tại
            $columnsToRemove = [
                'hard_copy_documents',
                'education_level', 
                'workplace',
                'experience_years'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Thêm lại trường custom_fields nếu chưa tồn tại
            if (!Schema::hasColumn('students', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('notes');
            }
        });
    }
};
