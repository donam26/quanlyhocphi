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
        // Indexes for students table
        Schema::table('students', function (Blueprint $table) {
            // Composite index for name searches (first_name + last_name)
            $table->index(['first_name', 'last_name'], 'idx_students_name');
            
            // Individual indexes for commonly filtered fields
            $table->index('email', 'idx_students_email');
            $table->index('gender', 'idx_students_gender');
            $table->index('source', 'idx_students_source');
            $table->index('education_level', 'idx_students_education_level');
            $table->index('created_at', 'idx_students_created_at');
            
            // Foreign key indexes (if not already exist)
            $table->index('province_id', 'idx_students_province_id');
            $table->index('place_of_birth_province_id', 'idx_students_place_of_birth_province_id');
            $table->index('ethnicity_id', 'idx_students_ethnicity_id');
            
            // Composite index for workplace searches
            $table->index('current_workplace', 'idx_students_current_workplace');
            $table->index('accounting_experience_years', 'idx_students_accounting_experience_years');
        });

        // Indexes for enrollments table
        Schema::table('enrollments', function (Blueprint $table) {
            // Composite index for student-course relationship (most important)
            $table->index(['student_id', 'course_item_id'], 'idx_enrollments_student_course');
            
            // Status index (very frequently used for filtering)
            $table->index('status', 'idx_enrollments_status');
            
            // Date index for sorting and filtering
            $table->index('enrollment_date', 'idx_enrollments_enrollment_date');
            
            // Composite index for status + date queries
            $table->index(['status', 'enrollment_date'], 'idx_enrollments_status_date');
            
            // Course-specific queries
            $table->index(['course_item_id', 'status'], 'idx_enrollments_course_status');
        });

        // Indexes for payments table
        Schema::table('payments', function (Blueprint $table) {
            // Foreign key index
            $table->index('enrollment_id', 'idx_payments_enrollment_id');
            
            // Status index (for confirmed payments)
            $table->index('status', 'idx_payments_status');
            
            // Date index for sorting
            $table->index('payment_date', 'idx_payments_payment_date');
            
            // Composite index for enrollment + status (for payment calculations)
            $table->index(['enrollment_id', 'status'], 'idx_payments_enrollment_status');
            
            // Payment method index
            $table->index('payment_method', 'idx_payments_payment_method');
        });

        // Indexes for course_items table
        Schema::table('course_items', function (Blueprint $table) {
            // Tree structure index
            $table->index('parent_id', 'idx_course_items_parent_id');

            // Status and leaf indexes
            $table->index('status', 'idx_course_items_status');
            $table->index('is_leaf', 'idx_course_items_is_leaf');

            // Composite index for active leaf courses
            $table->index(['status', 'is_leaf'], 'idx_course_items_status_leaf');

            // Name index for searching
            $table->index('name', 'idx_course_items_name');

            // Order index for sorting
            $table->index('order_index', 'idx_course_items_order_index');

            // Learning method index
            $table->index('learning_method', 'idx_course_items_learning_method');
        });

        // Indexes for attendances table
        Schema::table('attendances', function (Blueprint $table) {
            // Foreign key indexes
            $table->index('enrollment_id', 'idx_attendances_enrollment_id');
            $table->index('course_item_id', 'idx_attendances_course_item_id');

            // Date index
            $table->index('attendance_date', 'idx_attendances_attendance_date');

            // Status index
            $table->index('status', 'idx_attendances_status');

            // Composite indexes for common queries
            $table->index(['course_item_id', 'attendance_date'], 'idx_attendances_course_date');
            $table->index(['enrollment_id', 'attendance_date'], 'idx_attendances_enrollment_date');
        });

        // Indexes for learning_paths table
        Schema::table('learning_paths', function (Blueprint $table) {
            // Foreign key index
            $table->index('course_item_id', 'idx_learning_paths_course_item_id');

            // Order index for sorting
            $table->index('order', 'idx_learning_paths_order');

            // Completion status index
            $table->index('is_completed', 'idx_learning_paths_is_completed');

            // Composite index for course + order
            $table->index(['course_item_id', 'order'], 'idx_learning_paths_course_order');
        });

        // Indexes for provinces table
        Schema::table('provinces', function (Blueprint $table) {
            // Region index for filtering by region
            $table->index('region', 'idx_provinces_region');

            // Name index for searching
            $table->index('name', 'idx_provinces_name');
        });

        // Indexes for ethnicities table
        Schema::table('ethnicities', function (Blueprint $table) {
            // Name index for searching (if not already exists)
            if (!Schema::hasIndex('ethnicities', 'idx_ethnicities_name')) {
                $table->index('name', 'idx_ethnicities_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes for students table
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_name');
            $table->dropIndex('idx_students_email');
            $table->dropIndex('idx_students_gender');
            $table->dropIndex('idx_students_source');
            $table->dropIndex('idx_students_education_level');
            $table->dropIndex('idx_students_created_at');
            $table->dropIndex('idx_students_province_id');
            $table->dropIndex('idx_students_place_of_birth_province_id');
            $table->dropIndex('idx_students_ethnicity_id');
            $table->dropIndex('idx_students_current_workplace');
            $table->dropIndex('idx_students_accounting_experience_years');
        });

        // Drop indexes for enrollments table
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex('idx_enrollments_student_course');
            $table->dropIndex('idx_enrollments_status');
            $table->dropIndex('idx_enrollments_enrollment_date');
            $table->dropIndex('idx_enrollments_status_date');
            $table->dropIndex('idx_enrollments_course_status');
        });

        // Drop indexes for payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_enrollment_id');
            $table->dropIndex('idx_payments_status');
            $table->dropIndex('idx_payments_payment_date');
            $table->dropIndex('idx_payments_enrollment_status');
            $table->dropIndex('idx_payments_payment_method');
        });

        // Drop indexes for course_items table
        Schema::table('course_items', function (Blueprint $table) {
            $table->dropIndex('idx_course_items_parent_id');
            $table->dropIndex('idx_course_items_status');
            $table->dropIndex('idx_course_items_is_leaf');
            $table->dropIndex('idx_course_items_status_leaf');
            $table->dropIndex('idx_course_items_name');
            $table->dropIndex('idx_course_items_order_index');
            $table->dropIndex('idx_course_items_learning_method');
        });

        // Drop indexes for attendances table
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_attendances_enrollment_id');
            $table->dropIndex('idx_attendances_course_item_id');
            $table->dropIndex('idx_attendances_attendance_date');
            $table->dropIndex('idx_attendances_status');
            $table->dropIndex('idx_attendances_course_date');
            $table->dropIndex('idx_attendances_enrollment_date');
        });

        // Drop indexes for learning_paths table
        Schema::table('learning_paths', function (Blueprint $table) {
            $table->dropIndex('idx_learning_paths_course_item_id');
            $table->dropIndex('idx_learning_paths_order');
            $table->dropIndex('idx_learning_paths_is_completed');
            $table->dropIndex('idx_learning_paths_course_order');
        });

        // Drop indexes for provinces table
        Schema::table('provinces', function (Blueprint $table) {
            $table->dropIndex('idx_provinces_region');
            $table->dropIndex('idx_provinces_name');
        });

        // Drop indexes for ethnicities table
        Schema::table('ethnicities', function (Blueprint $table) {
            if (Schema::hasIndex('ethnicities', 'idx_ethnicities_name')) {
                $table->dropIndex('idx_ethnicities_name');
            }
        });
    }
};
