<?php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Plugins\Event\Models\Event;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'title'      => $this->faker->sentence(3),
            'start_at'   => now()->addDays(7),
            'end_at'     => now()->addDays(7)->addHours(2),
            'category'   => 'worship',
            'status'     => 'published',
        ];
    }
}
