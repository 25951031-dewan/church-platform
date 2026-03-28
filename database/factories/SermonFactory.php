<?php

namespace Database\Factories;

use App\Plugins\Sermons\Models\Sermon;
use Illuminate\Database\Eloquent\Factories\Factory;

class SermonFactory extends Factory
{
    protected $model = Sermon::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'speaker' => fake()->name(),
            'sermon_date' => fake()->date(),
            'is_active' => true,
            'is_published' => true,
            'author_id' => \App\Models\User::factory(),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function withAudio(): static
    {
        return $this->state(fn () => [
            'audio_url' => 'https://example.com/sermons/' . fake()->uuid() . '.mp3',
            'duration_minutes' => fake()->numberBetween(15, 60),
        ]);
    }

    public function withVideo(): static
    {
        return $this->state(fn () => [
            'video_url' => 'https://youtube.com/watch?v=' . fake()->lexify('???????????'),
        ]);
    }
}
