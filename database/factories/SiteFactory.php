<?php

namespace Database\Factories;

use App\Enums\SiteStatus;
use App\Models\Category;
use App\Models\ITStaff;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'category_id' => Category::factory(),
            'base_url' => $this->faker->unique()->url(),
            'description' => $this->faker->optional()->sentence(),
            'responsible_person_id' => ITStaff::factory(),
            'status' => SiteStatus::Up,
            'consecutive_down_count' => 0,
            'first_down_at' => null,
            'notification_sent' => false,
            'notification_cycle_counter' => 0,
            'avg_response_time' => $this->faker->randomFloat(2, 50, 2000),
        ];
    }

    public function up(): static
    {
        return $this->state(fn () => ['status' => SiteStatus::Up]);
    }

    public function partiallyDown(): static
    {
        return $this->state(fn () => [
            'status' => SiteStatus::PartiallyDown,
            'first_down_at' => now()->subMinutes(30),
        ]);
    }

    public function totallyDown(): static
    {
        return $this->state(fn () => [
            'status' => SiteStatus::TotallyDown,
            'first_down_at' => now()->subMinutes(30),
        ]);
    }
}
