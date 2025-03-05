<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\IntramuralGame;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3), 
            'intrams_id' => IntramuralGame::factory(),
            'category' => $this->faker->randomElement(["Men's", "Women's"]),
            'type' => $this->faker->randomElement(['Sports', 'Dance', 'Music']),
            'gold' => $this->faker->numberBetween(0, 10),
            'silver' => $this->faker->numberBetween(0, 10),
            'bronze' => $this->faker->numberBetween(0, 10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
