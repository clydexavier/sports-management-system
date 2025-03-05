<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\IntramuralGame;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OverallTeam>
 */
class OverallTeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'name' => $this->faker->word(),
            'team_logo_path' => $this->faker->imageUrl(200, 200, 'sports', true),
            'total_gold' => $this->faker->numberBetween(0, 50),
            'total_silver' => $this->faker->numberBetween(0, 50),
            'total_bronze' => $this->faker->numberBetween(0, 50),
            'intrams_id' => IntramuralGame::factory(), // Assuming you have an IntramuralGameFactory
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
