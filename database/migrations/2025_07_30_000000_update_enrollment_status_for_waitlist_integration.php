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
        Schema::table('enrollments', function (Blueprint $table) {
            // Thêm các trường mới
            $table->timestamp('request_date')->nullable()->after('status');
            $table->timestamp('confirmation_date')->nullable()->after('request_date');
            $table->timestamp('last_status_change')->nullable()->after('confirmation_date');
            $table->string('previous_status')->nullable()->after('last_status_change');
            
            // Đảm bảo status có thể chứa các trạng thái mới
            $table->string('status')->default('pending')->change();
            
            // Kiểm tra xem cột notes đã tồn tại chưa
            if (!Schema::hasColumn('enrollments', 'notes')) {
                $table->text('notes')->nullable()->after('custom_fields');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'request_date',
                'confirmation_date',
                'last_status_change',
                'previous_status'
            ]);
            
            // Chỉ drop cột notes nếu nó được tạo trong migration này
            if (Schema::hasColumn('enrollments', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
}; 