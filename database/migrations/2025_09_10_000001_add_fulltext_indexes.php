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
        // Add fulltext indexes for better text searching
        // Note: MySQL supports fulltext indexes on InnoDB tables from version 5.6+
        
        // Fulltext index for student names and contact info
        DB::statement('ALTER TABLE students ADD FULLTEXT idx_students_fulltext_search (first_name, last_name, email, phone)');
        
        // Fulltext index for workplace and specialization
        DB::statement('ALTER TABLE students ADD FULLTEXT idx_students_fulltext_work (current_workplace, training_specialization)');
        
        // Fulltext index for course names
        DB::statement('ALTER TABLE course_items ADD FULLTEXT idx_course_items_fulltext_name (name)');
        
        // Add some additional composite indexes for complex queries
        Schema::table('students', function (Blueprint $table) {
            // Composite index for date range + status queries
            $table->index(['created_at', 'source'], 'idx_students_created_source');
            
            // Composite index for province + gender filtering
            $table->index(['province_id', 'gender'], 'idx_students_province_gender');
        });
        
        Schema::table('enrollments', function (Blueprint $table) {
            // Composite index for payment status calculations
            $table->index(['student_id', 'status', 'final_fee'], 'idx_enrollments_payment_calc');
            
            // Index for date range queries
            $table->index(['enrollment_date', 'status'], 'idx_enrollments_date_status');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            // Composite index for revenue calculations
            $table->index(['payment_date', 'status', 'amount'], 'idx_payments_revenue_calc');
            
            // Index for payment method analysis
            $table->index(['payment_method', 'status'], 'idx_payments_method_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop fulltext indexes
        DB::statement('ALTER TABLE students DROP INDEX idx_students_fulltext_search');
        DB::statement('ALTER TABLE students DROP INDEX idx_students_fulltext_work');
        DB::statement('ALTER TABLE course_items DROP INDEX idx_course_items_fulltext_name');
        
        // Drop composite indexes
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_created_source');
            $table->dropIndex('idx_students_province_gender');
        });
        
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex('idx_enrollments_payment_calc');
            $table->dropIndex('idx_enrollments_date_status');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_revenue_calc');
            $table->dropIndex('idx_payments_method_status');
        });
    }
};
