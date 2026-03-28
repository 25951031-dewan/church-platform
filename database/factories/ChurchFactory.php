<?php

namespace Database\Factories;

use App\Plugins\ChurchBuilder\Models\Church;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChurchFactory extends Factory
{
    protected $model = Church::class;

    public function definition(): array
    {
        $name = fake()->company() . ' Church';
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->randomNumber(4),
            'status' => 'approved',
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'zip_code' => fake()->postcode(),
            'country' => 'US',
            'latitude' => fake()->latitude(25, 48),
            'longitude' => fake()->longitude(-125, -70),
            'denomination' => fake()->randomElement(['Baptist', 'Methodist', 'Non-denominational', 'Catholic', 'Pentecostal']),
            'short_description' => fake()->sentence(),
            'primary_color' => '#4F46E5',
            'is_featured' => false,
            'is_verified' => false,
            'admin_user_id' => \App\Models\User::factory(),
            'created_by' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function verified(): static
    {
        return $this->state(fn () => ['is_verified' => true, 'verified_at' => now()]);
    }

    public function withLocation(float $lat, float $lng): static
    {
        return $this->state(fn () => ['latitude' => $lat, 'longitude' => $lng]);
    }
}
