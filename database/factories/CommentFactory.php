<?php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Plugins\Comment\Models\Comment;
use Plugins\Post\Models\Post;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'commentable_type' => Post::class,
            'commentable_id'   => Post::factory(),
            'user_id'          => User::factory(),
            'parent_id'        => null,
            'body'             => $this->faker->sentence(),
        ];
    }
}
