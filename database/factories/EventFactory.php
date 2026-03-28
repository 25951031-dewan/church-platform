<?php

namespace Database\Factories;

use App\Plugins\Events\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'start_date' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'is_active' => true,
            'created_by' => \App\Models\User::factory(),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function past(): static
    {
        return $this->state(fn () => ['start_date' => fake()->dateTimeBetween('-30 days', '-1 day')]);
    }

    public function withMeetingUrl(): static
    {
        return $this->state(fn () => ['meeting_url' => 'https://zoom.us/j/' . fake()->randomNumber(9)]);
    }
}
