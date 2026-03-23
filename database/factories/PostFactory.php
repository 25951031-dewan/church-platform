<?php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Plugins\Post\Models\Post;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'type'         => 'post',
            'body'         => $this->faker->paragraph(),
            'status'       => 'published',
            'published_at' => now(),
            'meta'         => null,
            'is_anonymous' => false,
        ];
    }
}
