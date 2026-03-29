<?php

namespace Database\Factories;

use App\Plugins\Library\Models\BookCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookCategoryFactory extends Factory
{
    protected $model = BookCategory::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);
        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
