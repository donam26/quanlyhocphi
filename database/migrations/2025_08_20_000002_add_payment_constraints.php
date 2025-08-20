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
        // Add check constraints for data integrity (only if they don't exist)
        try {
            DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_positive_amount CHECK (amount > 0)');
        } catch (\Exception $e) {
            // Constraint already exists, skip
        }

        try {
            DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_reasonable_amount CHECK (amount <= 100000000)'); // Max 100M VND
        } catch (\Exception $e) {
            // Constraint already exists, skip
        }

        try {
            DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_valid_status CHECK (status IN ("pending", "confirmed", "cancelled", "refunded"))');
        } catch (\Exception $e) {
            // Constraint already exists, skip
        }

        try {
            DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_valid_payment_method CHECK (payment_method IN ("cash", "bank_transfer", "card", "qr_code", "sepay"))');
        } catch (\Exception $e) {
            // Constraint already exists, skip
        }
        
        // Add constraint for enrollments
        DB::statement('ALTER TABLE enrollments ADD CONSTRAINT chk_positive_final_fee CHECK (final_fee >= 0)');
        DB::statement('ALTER TABLE enrollments ADD CONSTRAINT chk_valid_discount_percentage CHECK (discount_percentage >= 0 AND discount_percentage <= 100)');
        DB::statement('ALTER TABLE enrollments ADD CONSTRAINT chk_valid_discount_amount CHECK (discount_amount >= 0)');
        
        // Ensure discount amount doesn't exceed final fee
        DB::statement('ALTER TABLE enrollments ADD CONSTRAINT chk_discount_not_exceed_fee CHECK (discount_amount <= final_fee)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop constraints
        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS chk_positive_amount');
        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS chk_reasonable_amount');
        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS chk_valid_status');
        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS chk_valid_payment_method');
        
        DB::statement('ALTER TABLE enrollments DROP CONSTRAINT IF EXISTS chk_positive_final_fee');
        DB::statement('ALTER TABLE enrollments DROP CONSTRAINT IF EXISTS chk_valid_discount_percentage');
        DB::statement('ALTER TABLE enrollments DROP CONSTRAINT IF EXISTS chk_valid_discount_amount');
        DB::statement('ALTER TABLE enrollments DROP CONSTRAINT IF EXISTS chk_discount_not_exceed_fee');
    }
};
