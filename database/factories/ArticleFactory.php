<?php

namespace Database\Factories;

use App\Models\User;
use App\Plugins\Blog\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->unique()->numberBetween(1, 99999),
            'content' => '<p>' . implode('</p><p>', fake()->paragraphs(3)) . '</p>',
            'excerpt' => fake()->sentence(10),
            'cover_image' => null,
            'author_id' => User::factory(),
            'category_id' => null,
            'church_id' => null,
            'status' => 'draft',
            'published_at' => null,
            'view_count' => 0,
            'is_featured' => false,
            'is_active' => true,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn() => [
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn() => [
            'status' => 'scheduled',
            'published_at' => now()->addDays(7),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn() => [
            'is_featured' => true,
        ]);
    }
}
