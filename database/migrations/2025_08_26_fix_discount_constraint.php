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
        // Drop the problematic constraint that prevents 100% discount
        try {
            DB::statement('ALTER TABLE enrollments DROP CONSTRAINT IF EXISTS chk_discount_not_exceed_fee');
        } catch (\Exception $e) {
            // Constraint might not exist, continue
        }

        // Add simpler, more logical constraints
        try {
            DB::statement('ALTER TABLE enrollments ADD CONSTRAINT chk_discount_percentage_range CHECK (discount_percentage >= 0 AND discount_percentage <= 100)');
        } catch (\Exception $e) {
            // Constraint might already exist, continue
        }

        try {
            DB::statement('ALTER TABLE enrollments ADD CONSTRAINT chk_discount_amount_positive CHECK (discount_amount >= 0)');
        } catch (\Exception $e) {
            // Constraint might already exist, continue
        }

        try {
            DB::statement('ALTER TABLE enrollments ADD CONSTRAINT chk_final_fee_non_negative CHECK (final_fee >= 0)');
        } catch (\Exception $e) {
            // Constraint might already exist, continue
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new constraints
        try {
            DB::statement('ALTER TABLE enrollments DROP CONSTRAINT IF EXISTS chk_discount_percentage_range');
        } catch (\Exception $e) {
            // Constraint might not exist, continue
        }

        try {
            DB::statement('ALTER TABLE enrollments DROP CONSTRAINT IF EXISTS chk_discount_amount_positive');
        } catch (\Exception $e) {
            // Constraint might not exist, continue
        }

        try {
            DB::statement('ALTER TABLE enrollments DROP CONSTRAINT IF EXISTS chk_final_fee_non_negative');
        } catch (\Exception $e) {
            // Constraint might not exist, continue
        }

        // Restore the old constraint (if needed)
        try {
            DB::statement('ALTER TABLE enrollments ADD CONSTRAINT chk_discount_not_exceed_fee CHECK (discount_amount <= final_fee)');
        } catch (\Exception $e) {
            // Constraint might already exist, continue
        }
    }
};
