<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\CourseItem;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class ExportInvoicesTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $courseItem;
    protected $students;
    protected $enrollments;

    protected function setUp(): void
    {
        parent::setUp();

        // Tạo user để authenticate
        $this->user = User::factory()->create();

        // Tạo khóa học
        $this->courseItem = CourseItem::factory()->create([
            'name' => 'Khóa học Test',
            'fee' => 1000000,
            'active' => true
        ]);

        // Tạo học viên và đăng ký
        $this->students = Student::factory()->count(3)->create([
            'company_name' => 'Công ty Test',
            'tax_code' => '0123456789',
            'invoice_email' => 'test@company.com',
            'company_address' => '123 Test Street'
        ]);
        $this->enrollments = collect();

        foreach ($this->students as $student) {
            $enrollment = Enrollment::factory()->create([
                'student_id' => $student->id,
                'course_item_id' => $this->courseItem->id,
                'final_fee' => 1000000,
                'status' => 'enrolled'
            ]);

            // Tạo thanh toán cho một số học viên
            Payment::factory()->create([
                'enrollment_id' => $enrollment->id,
                'amount' => 500000,
                'status' => 'completed'
            ]);

            $this->enrollments->push($enrollment);
        }
    }

    /** @test */
    public function it_can_export_invoices_with_student_info()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/course-items/export-invoices', [
                'course_id' => $this->courseItem->id,
                'invoice_date' => now()->format('Y-m-d'),
                'notes' => 'Test invoice'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'file_count' => 3
            ]);

        $this->assertArrayHasKey('download_urls', $response->json());
        $this->assertCount(3, $response->json('download_urls'));
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/course-items/export-invoices', [
                'course_id' => $this->courseItem->id
                // Missing invoice_date
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['invoice_date']);
    }

    /** @test */
    public function it_returns_error_for_course_with_no_students()
    {
        $emptyCourse = CourseItem::factory()->create([
            'name' => 'Empty Course',
            'fee' => 500000
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/course-items/export-invoices', [
                'course_id' => $emptyCourse->id,
                'invoice_date' => now()->format('Y-m-d')
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'message' => 'Không có học viên nào trong khóa học này!'
            ]);
    }

    /** @test */
    public function it_returns_error_for_invalid_course_id()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/course-items/export-invoices', [
                'course_id' => 99999,
                'invoice_date' => now()->format('Y-m-d')
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['course_id']);
    }
}
