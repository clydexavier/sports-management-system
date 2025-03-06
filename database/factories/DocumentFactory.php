<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Document;
use App\Models\IntramuralGame;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . '.pdf',
            'file_path' => 'documents/' . $this->faker->uuid . '.pdf', // Simulating a file path
            'intrams_id' => IntramuralGame::factory(), // Generates an associated intramural game
        ];
    }
}
