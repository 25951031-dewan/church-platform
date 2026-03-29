<?php

namespace Database\Factories;

use App\Plugins\Library\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'author' => fake()->name(),
            'description' => fake()->paragraphs(2, true),
            'isbn' => fake()->isbn13(),
            'publisher' => fake()->company(),
            'pages_count' => fake()->numberBetween(50, 500),
            'published_year' => fake()->year(),
            'is_featured' => false,
            'is_active' => true,
            'view_count' => 0,
            'download_count' => 0,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withPdf(): static
    {
        return $this->state(fn () => ['pdf_path' => 'books/sample-' . Str::random(8) . '.pdf']);
    }
}
