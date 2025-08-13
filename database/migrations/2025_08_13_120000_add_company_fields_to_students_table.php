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
            $table->string('company_name')->nullable()->after('address');
            $table->string('tax_code')->nullable()->after('company_name');
            $table->string('invoice_email')->nullable()->after('tax_code');
            $table->text('company_address')->nullable()->after('invoice_email');
            $table->decimal('tuition_fee', 15, 2)->nullable()->after('company_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'tax_code', 
                'invoice_email',
                'company_address',
                'tuition_fee'
            ]);
        });
    }
};
