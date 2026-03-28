<?php

namespace Database\Factories;

use App\Plugins\Timeline\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'content' => fake()->paragraph(),
            'type' => 'text',
            'visibility' => 'public',
            'is_pinned' => false,
        ];
    }

    public function announcement(): static
    {
        return $this->state(fn () => ['type' => 'announcement']);
    }

    public function pinned(): static
    {
        return $this->state(fn () => ['is_pinned' => true]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['scheduled_at' => now()->addDay()]);
    }
}
