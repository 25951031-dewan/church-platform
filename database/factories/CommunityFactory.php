<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Plugins\Community\Models\Community;

class CommunityFactory extends Factory
{
    protected $model = Community::class;

    public function definition(): array
    {
        return [
            'name'              => $this->faker->words(3, true),
            'slug'              => $this->faker->unique()->slug(),
            'description'       => $this->faker->sentence(),
            'privacy'           => 'public',
            'privacy_closed'    => '0',
            'status'            => 'active',
            'is_counsel_group'  => false,
            'requires_approval' => false,
            'members_count'     => 0,
            'posts_count'       => 0,
        ];
    }

    public function closed(): static
    {
        return $this->state([
            'privacy'           => 'private',
            'privacy_closed'    => '1',
            'requires_approval' => true,
        ]);
    }
}
