<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Di chuyển dữ liệu từ waiting_lists sang enrollments
        $waitlists = DB::table('waiting_lists')->get();
        
        foreach ($waitlists as $waitlist) {
            // Kiểm tra xem đã có enrollment nào cho student và course này chưa
            $existingEnrollment = DB::table('enrollments')
                ->where('student_id', $waitlist->student_id)
                ->where('course_item_id', $waitlist->course_item_id)
                ->first();
            
            if (!$existingEnrollment) {
                // Nếu chưa có, thêm mới enrollment với trạng thái waiting
                DB::table('enrollments')->insert([
                    'student_id' => $waitlist->student_id,
                    'course_item_id' => $waitlist->course_item_id,
                    'status' => 'waiting',
                    'request_date' => $waitlist->request_date,
                    'last_status_change' => now(),
                    'notes' => $waitlist->notes,
                    'created_at' => $waitlist->created_at,
                    'updated_at' => now()
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không cần rollback vì dữ liệu gốc vẫn còn trong bảng waiting_lists
    }
}; 