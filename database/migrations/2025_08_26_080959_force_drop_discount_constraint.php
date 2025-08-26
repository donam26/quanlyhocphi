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
        // Force drop the problematic constraint using raw SQL
        $constraints = [
            'chk_discount_not_exceed_fee',
            'enrollments_chk_1',
            'enrollments_chk_2',
            'enrollments_chk_3',
            'enrollments_chk_4'
        ];

        foreach ($constraints as $constraint) {
            try {
                DB::statement("ALTER TABLE enrollments DROP CONSTRAINT {$constraint}");
                echo "Dropped constraint: {$constraint}\n";
            } catch (\Exception $e) {
                echo "Constraint {$constraint} not found or already dropped\n";
            }
        }

        // Also try with CHECK prefix
        try {
            DB::statement("ALTER TABLE enrollments DROP CHECK chk_discount_not_exceed_fee");
        } catch (\Exception $e) {
            // Ignore if not found
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do nothing - we don't want to restore the problematic constraint
    }
};
