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
            $table->json('recurring_days')->nullable()->after('day_of_week');
            $table->date('end_date')->nullable()->after('date');
            $table->renameColumn('date', 'start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['recurring_days', 'end_date']);
            $table->renameColumn('start_date', 'date');
        });
    }
};
