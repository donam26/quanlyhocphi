<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\CourseItem;
use App\Enums\EnrollmentStatus;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Enrollment>
 */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $student = Student::factory()->create();
        $course = CourseItem::factory()->create();
        $discountPercentage = $this->faker->numberBetween(0, 20);
        $discountAmount = ($course->fee * $discountPercentage) / 100;
        $finalFee = $course->fee - $discountAmount;

        return [
            'student_id' => $student->id,
            'course_item_id' => $course->id,
            'enrollment_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'status' => $this->faker->randomElement([
                EnrollmentStatus::WAITING,
                EnrollmentStatus::ACTIVE,
                EnrollmentStatus::COMPLETED,
                EnrollmentStatus::CANCELLED
            ]),
            'request_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'confirmation_date' => $this->faker->optional()->dateTimeBetween('-6 months', 'now'),
            'last_status_change' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'previous_status' => $this->faker->optional()->randomElement(['waiting', 'active']),
            'cancelled_at' => null,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'final_fee' => $finalFee,
            'notes' => $this->faker->optional()->sentence(),
            'custom_fields' => null,
        ];
    }

    /**
     * Indicate that the enrollment is waiting.
     */
    public function waiting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatus::WAITING,
            'confirmation_date' => null,
            'request_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the enrollment is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatus::ACTIVE,
            'confirmation_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ]);
    }

    /**
     * Indicate that the enrollment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatus::COMPLETED,
            'confirmation_date' => $this->faker->dateTimeBetween('-6 months', '-1 month'),
        ]);
    }

    /**
     * Indicate that the enrollment is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatus::CANCELLED,
            'cancelled_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'previous_status' => $this->faker->randomElement(['waiting', 'active']),
        ]);
    }

    /**
     * Indicate that the enrollment has no discount.
     */
    public function noDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $course = CourseItem::find($attributes['course_item_id']);
            return [
                'discount_percentage' => 0,
                'discount_amount' => 0,
                'final_fee' => $course->fee,
            ];
        });
    }

    /**
     * Indicate that the enrollment has a specific discount.
     */
    public function withDiscount(float $percentage): static
    {
        return $this->state(function (array $attributes) use ($percentage) {
            $course = CourseItem::find($attributes['course_item_id']);
            $discountAmount = ($course->fee * $percentage) / 100;
            return [
                'discount_percentage' => $percentage,
                'discount_amount' => $discountAmount,
                'final_fee' => $course->fee - $discountAmount,
            ];
        });
    }

    /**
     * Create enrollment for specific student and course.
     */
    public function forStudentAndCourse(Student $student, CourseItem $course): static
    {
        return $this->state(function (array $attributes) use ($student, $course) {
            $discountPercentage = $attributes['discount_percentage'] ?? 0;
            $discountAmount = ($course->fee * $discountPercentage) / 100;
            return [
                'student_id' => $student->id,
                'course_item_id' => $course->id,
                'discount_amount' => $discountAmount,
                'final_fee' => $course->fee - $discountAmount,
            ];
        });
    }
}
