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
        Schema::table('schedules', function (Blueprint $table) {
            // Xóa cột day_of_week cũ nếu tồn tại
            if (Schema::hasColumn('schedules', 'day_of_week')) {
                $table->dropColumn('day_of_week');
            }
            
            // Xóa cột recurring_days cũ nếu tồn tại
            if (Schema::hasColumn('schedules', 'recurring_days')) {
                $table->dropColumn('recurring_days');
            }
            
            // Xóa cột is_recurring cũ nếu tồn tại
            if (Schema::hasColumn('schedules', 'is_recurring')) {
                $table->dropColumn('is_recurring');
            }
            
            // Xóa cột notes cũ nếu tồn tại
            if (Schema::hasColumn('schedules', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Khôi phục các cột cũ nếu cần
            $table->string('day_of_week')->nullable();
            $table->json('recurring_days')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->text('notes')->nullable();
        });
    }
};
