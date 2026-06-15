<?php

namespace Database\Factories;

use App\Models\ITStaff;
use Illuminate\Database\Eloquent\Factories\Factory;

class ITStaffFactory extends Factory
{
    protected $model = ITStaff::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'position' => $this->faker->jobTitle(),
        ];
    }
}
