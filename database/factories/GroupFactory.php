<?php

namespace Database\Factories;

use App\Plugins\Groups\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'type' => 'public',
            'created_by' => \App\Models\User::factory(),
        ];
    }

    public function private(): static
    {
        return $this->state(fn () => ['type' => 'private']);
    }

    public function churchOnly(): static
    {
        return $this->state(fn () => ['type' => 'church_only']);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }
}
