<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->boolean(60) ? $this->faker->sentence() : null,
            'min_gift_cents' => $this->faker->optional()->numberBetween(1000, 5000),
            'max_gift_cents' => $this->faker->optional()->numberBetween(6000, 20000),
            'draw_at' => $this->faker->optional()->dateTimeBetween('+3 days', '+2 months'),
            'join_code' => strtoupper(Str::random(12)),
        ];
    }
}
