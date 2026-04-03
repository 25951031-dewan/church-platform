<?php

namespace Database\Factories;

use App\Models\User;
use App\Plugins\LiveMeeting\Models\Meeting;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 hour', '+7 days');
        $endsAt = (clone $startsAt)->modify('+1 hour');

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(10),
            'meeting_url' => 'https://zoom.us/j/' . fake()->numerify('##########'),
            'platform' => fake()->randomElement(['zoom', 'google_meet', 'youtube', 'other']),
            'church_id' => null,
            'host_id' => User::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'timezone' => 'UTC',
            'is_recurring' => false,
            'recurrence_rule' => null,
            'cover_image' => null,
            'is_active' => true,
        ];
    }

    public function live(): static
    {
        return $this->state(fn() => [
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(30),
        ]);
    }

    public function past(): static
    {
        return $this->state(fn() => [
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->subHour(),
        ]);
    }
}
