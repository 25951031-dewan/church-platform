<?php

namespace Database\Factories;

use App\Plugins\ChurchBuilder\Models\ChurchPage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChurchPageFactory extends Factory
{
    protected $model = ChurchPage::class;

    public function definition(): array
    {
        return [
            'church_id' => \App\Plugins\ChurchBuilder\Models\Church::factory(),
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'body' => fake()->paragraphs(3, true),
            'sort_order' => fake()->numberBetween(0, 10),
            'is_published' => true,
            'created_by' => \App\Models\User::factory(),
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
