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
        Schema::table('payments', function (Blueprint $table) {
            // Add idempotency key for preventing duplicate payments
            $table->string('idempotency_key')->nullable()->unique()->after('transaction_reference');
            
            // Add audit fields
            $table->timestamp('confirmed_at')->nullable()->after('status');
            $table->unsignedBigInteger('confirmed_by')->nullable()->after('confirmed_at');
            $table->timestamp('cancelled_at')->nullable()->after('confirmed_by');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
            
            // Add webhook tracking
            $table->string('webhook_id')->nullable()->after('cancelled_by');
            $table->json('webhook_data')->nullable()->after('webhook_id');
            
            // Add foreign key for user tracking
            $table->foreign('confirmed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
            
            // Add indexes for performance
            $table->index(['status', 'payment_date']);
            $table->index(['payment_method', 'status']);
            $table->index('confirmed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['confirmed_by']);
            $table->dropForeign(['cancelled_by']);
            
            // Drop indexes
            $table->dropIndex(['status', 'payment_date']);
            $table->dropIndex(['payment_method', 'status']);
            $table->dropIndex(['confirmed_at']);
            
            // Drop columns
            $table->dropColumn([
                'idempotency_key',
                'confirmed_at',
                'confirmed_by',
                'cancelled_at',
                'cancelled_by',
                'webhook_id',
                'webhook_data'
            ]);
        });
    }
};
