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
        // Check and drop existing unique constraints if they exist
        $this->dropUniqueConstraintIfExists('students', 'students_phone_unique');
        $this->dropUniqueConstraintIfExists('students', 'phone');
        $this->dropUniqueConstraintIfExists('students', 'students_email_unique');
        $this->dropUniqueConstraintIfExists('students', 'email');
        $this->dropUniqueConstraintIfExists('students', 'students_citizen_id_unique');
        $this->dropUniqueConstraintIfExists('students', 'citizen_id');

        // Add partial unique indexes that exclude soft deleted records
        // Note: MySQL doesn't support partial indexes with WHERE clause
        // So we rely on application-level validation for MySQL
        
        if (config('database.default') === 'pgsql') {
            // PostgreSQL supports partial indexes natively
            DB::statement('
                CREATE UNIQUE INDEX idx_students_phone_not_deleted 
                ON students (phone) 
                WHERE deleted_at IS NULL AND phone IS NOT NULL
            ');
            
            DB::statement('
                CREATE UNIQUE INDEX idx_students_email_not_deleted 
                ON students (email) 
                WHERE deleted_at IS NULL AND email IS NOT NULL
            ');
            
            DB::statement('
                CREATE UNIQUE INDEX idx_students_citizen_id_not_deleted 
                ON students (citizen_id) 
                WHERE deleted_at IS NULL AND citizen_id IS NOT NULL
            ');
        } 
        
        // For MySQL, we handle uniqueness at application level
        // through validation rules with Rule::unique()->whereNull('deleted_at')
    }

    /**
     * Helper method to drop unique constraint only if it exists
     */
    private function dropUniqueConstraintIfExists($table, $indexName)
    {
        try {
            // Check if index exists first
            $indexExists = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            
            if (!empty($indexExists)) {
                DB::statement("ALTER TABLE {$table} DROP INDEX {$indexName}");
            }
        } catch (\Exception $e) {
            // Index doesn't exist or other error, safely ignore
            // Log::info("Index {$indexName} on table {$table} doesn't exist or couldn't be dropped: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the partial unique indexes
        $this->dropUniqueConstraintIfExists('students', 'idx_students_phone_not_deleted');
        $this->dropUniqueConstraintIfExists('students', 'idx_students_email_not_deleted');
        $this->dropUniqueConstraintIfExists('students', 'idx_students_citizen_id_not_deleted');

        // Note: We don't restore original unique constraints because
        // they would conflict with soft deleted records
        // Application-level validation is sufficient
    }
};
