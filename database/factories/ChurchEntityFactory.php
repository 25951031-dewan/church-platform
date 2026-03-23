<?php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Plugins\Entity\Models\ChurchEntity;

class ChurchEntityFactory extends Factory
{
    protected $model = ChurchEntity::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true);
        return [
            'type'        => 'page',
            'owner_id'    => User::factory(),
            'name'        => ucwords($name),
            'slug'        => Str::slug($name) . '-' . $this->faker->randomNumber(4),
            'description' => $this->faker->sentence(),
            'is_active'   => true,
        ];
    }

    public function community(): static
    {
        return $this->state(['type' => 'community', 'privacy' => 'public']);
    }
}
