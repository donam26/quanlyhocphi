<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\CourseItem;
use App\Enums\CourseStatus;
use App\Enums\LearningMethod;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseItem>
 */
class CourseItemFactory extends Factory
{
    protected $model = CourseItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'parent_id' => null,
            'level' => 1,
            'is_leaf' => true,
            'fee' => $this->faker->numberBetween(500000, 5000000),
            'order_index' => $this->faker->numberBetween(1, 100),
            'active' => true,
            'status' => CourseStatus::ACTIVE,
            'is_special' => false,
            'learning_method' => $this->faker->randomElement([LearningMethod::ONLINE, LearningMethod::OFFLINE]),
            'custom_fields' => null,
        ];
    }

    /**
     * Indicate that the course is a parent course.
     */
    public function parent(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_leaf' => false,
            'fee' => 0,
            'learning_method' => null,
        ]);
    }

    /**
     * Indicate that the course is a child course.
     */
    public function child(CourseItem $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'level' => $parent->level + 1,
            'is_leaf' => true,
        ]);
    }

    /**
     * Indicate that the course is online.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'learning_method' => LearningMethod::ONLINE,
        ]);
    }

    /**
     * Indicate that the course is offline.
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'learning_method' => LearningMethod::OFFLINE,
        ]);
    }

    /**
     * Indicate that the course is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::COMPLETED,
        ]);
    }

    /**
     * Indicate that the course is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Indicate that the course is special.
     */
    public function special(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_special' => true,
        ]);
    }
}
