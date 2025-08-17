<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Enums\EnrollmentStatus;

class EnrollmentApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $student;
    protected $course;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();
        
        // Create test student
        $this->student = Student::factory()->create();
        
        // Create test course
        $this->course = CourseItem::factory()->create([
            'name' => 'Test Course',
            'fee' => 1000000,
            'is_leaf' => true,
            'status' => 'active'
        ]);
    }

    public function test_can_get_enrollments_list()
    {
        // Create test enrollments
        Enrollment::factory()->count(3)->create([
            'student_id' => $this->student->id,
            'course_item_id' => $this->course->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/enrollments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'student_id',
                            'course_item_id',
                            'enrollment_date',
                            'status',
                            'final_fee'
                        ]
                    ]
                ]
            ]);
    }

    public function test_can_create_enrollment()
    {
        $enrollmentData = [
            'student_id' => $this->student->id,
            'course_item_id' => $this->course->id,
            'enrollment_date' => now()->format('Y-m-d'),
            'status' => 'waiting',
            'discount_percentage' => 10,
            'discount_amount' => 0,
            'final_fee' => 900000,
            'notes' => 'Test enrollment'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/enrollments', $enrollmentData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Tạo ghi danh thành công'
            ]);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->student->id,
            'course_item_id' => $this->course->id,
            'status' => 'waiting'
        ]);
    }

    public function test_can_update_enrollment()
    {
        $enrollment = Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_item_id' => $this->course->id,
            'status' => 'waiting'
        ]);

        $updateData = [
            'status' => 'active',
            'notes' => 'Updated enrollment'
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/enrollments/{$enrollment->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cập nhật ghi danh thành công'
            ]);

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'status' => 'active',
            'notes' => 'Updated enrollment'
        ]);
    }

    public function test_can_confirm_from_waiting()
    {
        $enrollment = Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_item_id' => $this->course->id,
            'status' => EnrollmentStatus::WAITING->value
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/enrollments/{$enrollment->id}/confirm-waiting");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Xác nhận ghi danh thành công'
            ]);

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'status' => EnrollmentStatus::ACTIVE->value
        ]);
    }

    public function test_can_cancel_enrollment()
    {
        $enrollment = Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_item_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/enrollments/{$enrollment->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Hủy ghi danh thành công'
            ]);

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'status' => EnrollmentStatus::CANCELLED->value
        ]);
    }

    public function test_can_get_enrollment_stats()
    {
        // Create enrollments with different statuses
        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_item_id' => $this->course->id,
            'status' => EnrollmentStatus::WAITING->value
        ]);

        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_item_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/enrollments/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'waiting',
                    'active',
                    'completed',
                    'cancelled'
                ]
            ]);
    }

    public function test_validation_errors_on_create()
    {
        $invalidData = [
            'student_id' => 999999, // Non-existent student
            'course_item_id' => 999999, // Non-existent course
            'enrollment_date' => 'invalid-date',
            'status' => 'invalid-status'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/enrollments', $invalidData);

        $response->assertStatus(422);
    }
}
