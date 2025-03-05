<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\IntramuralGame;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Venue>
 */
class VenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'location' => $this->faker->city(),
            'type' => $this->faker->randomElement(['Indoor', 'Outdoor']), // Adjust based on actual venue types
            'intrams_id' => IntramuralGame::factory(), // Assuming you have an IntramuralGameFactory
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
