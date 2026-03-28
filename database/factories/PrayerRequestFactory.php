<?php

namespace Database\Factories;

use App\Plugins\Prayer\Models\PrayerRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrayerRequestFactory extends Factory
{
    protected $model = PrayerRequest::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'subject' => fake()->sentence(4),
            'request' => fake()->paragraph(),
            'status' => 'approved',
            'is_public' => true,
            'is_anonymous' => false,
            'is_urgent' => false,
            'prayer_count' => 0,
            'user_id' => \App\Models\User::factory(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function anonymous(): static
    {
        return $this->state(fn () => ['is_anonymous' => true]);
    }

    public function urgent(): static
    {
        return $this->state(fn () => ['is_urgent' => true]);
    }

    public function flagged(): static
    {
        return $this->state(fn () => ['pastoral_flag' => true]);
    }

    public function private(): static
    {
        return $this->state(fn () => ['is_public' => false]);
    }

    public function withCategory(string $category): static
    {
        return $this->state(fn () => ['category' => $category]);
    }

    public function guest(): static
    {
        return $this->state(fn () => ['user_id' => null, 'name' => 'Guest Visitor', 'email' => 'guest@example.com']);
    }
}
