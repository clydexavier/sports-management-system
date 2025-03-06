<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\IntramuralGame;
use App\Models\OverallTeam;
use App\Models\Player;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Player>
 */
class PlayerFactory extends Factory
{
    protected $model = Player::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'id_number' => $this->faker->unique()->numerify('##-#-#####'),
            'is_varsity' => $this->faker->boolean(), 
            'sport' => $this->faker->randomElement(['Basketball', 'Volleyball', 'Soccer', 'Swimming']),
            'intrams_id' => IntramuralGame::factory(),
            'team_id' => OverallTeam::factory(), // Default assigns a team
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the player is a varsity player.
     */
    public function varsity(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_varsity' => true,
            'team_id' => null, // Varsity players do not belong to a team
        ]);
    }

    /**
     * Indicate that the player is a non-varsity player.
     */
    public function nonVarsity(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_varsity' => false,
            'team_id' => OverallTeam::factory(),
        ]);
    }
}
