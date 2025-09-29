<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Student;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'date_of_birth' => $this->faker->date('Y-m-d', '2000-01-01'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->unique()->numerify('09########'),
            'address' => $this->faker->address(),
            'place_of_birth' => $this->faker->city(),
            'nation' => 'Kinh',
            'current_workplace' => $this->faker->optional()->company(),
            'accounting_experience_years' => $this->faker->optional()->numberBetween(0, 20),
            'education_level' => $this->faker->optional()->randomElement(['secondary', 'vocational', 'associate', 'bachelor', 'second_degree', 'master', 'phd']),
            'training_specialization' => $this->faker->optional()->jobTitle(),
            'hard_copy_documents' => $this->faker->randomElement(['submitted', 'not_submitted']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the student is for accounting courses.
     */
    public function forAccounting(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_workplace' => $this->faker->company(),
            'accounting_experience_years' => $this->faker->numberBetween(1, 15),
            'education_level' => $this->faker->randomElement(['associate', 'bachelor', 'second_degree', 'master', 'phd']),
            'training_specialization' => 'Kế toán',
        ]);
    }
}
