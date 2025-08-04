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
            $table->string('nation')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->foreignId('user_note_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_of_birth')->nullable()->change();
            $table->string('phone')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['user_note_id']);
            $table->dropColumn(['nation', 'place_of_birth', 'user_note_id']);
            $table->date('date_of_birth');
            $table->string('phone');
        });
    }
};
