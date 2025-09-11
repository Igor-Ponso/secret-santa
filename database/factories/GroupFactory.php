<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Group> */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->boolean(60) ? $this->faker->sentence() : null,
            'min_value' => $this->faker->optional()->numberBetween(10, 50),
            'max_value' => $this->faker->optional()->numberBetween(60, 200),
            'draw_at' => $this->faker->optional()->dateTimeBetween('+3 days', '+2 months'),
        ];
    }
}
