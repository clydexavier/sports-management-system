<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\IntramuralGame;
use App\Models\User;



class IntramuralGameControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;
    
    public function test_index_intramurals_if_admin() {
        $admin = User::factory()->admin()->create();

        IntramuralGame::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson('/api/v1/intramurals');

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }
    public function test_index_intramurals_if_user() {
        $user = User::factory()->create();

        IntramuralGame::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson('/api/v1/intramurals');
        
        $response->assertStatus(403)
             ->assertJson(['error' => 'unauthorized']); 
    }

    public function test_store_intramurals_if_admin() {

        $admin = User::factory()->admin()->create();

        $data = [
            'name' => 'Basketball',
            'year' => 2025,
        ];

        $response = $this->actingAs($admin)->postJson('/api/v1/intramurals/create', $data);
        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Basketball']);

        $this->assertDatabaseHas('intramural_games', ['name' => 'Basketball']);
    }

    public function test_store_intramurals_invalid_year_if_admin() {

        $admin = User::factory()->admin()->create();

        $data = [
            'name' => 'Basketball',
            'year' => 42322,
        ];

        $response = $this->actingAs($admin)->postJson('/api/v1/intramurals/create', $data);
        $response->assertStatus(400)
                 ->assertJsonFragment(['message' => 'The year field must be 4 digits.']);
    }

    public function test_store_intramurals_no_name_if_admin() {

        $admin = User::factory()->admin()->create();

        $data = [
            'year' => 4232,
        ];

        $response = $this->actingAs($admin)->postJson('/api/v1/intramurals/create', $data);
        $response->dump();
        $response->assertStatus(400)
                 ->assertJsonFragment(['message' => 'The name field is required.']);
    }

    public function test_store_intramurals_no_year_if_admin() {

        $admin = User::factory()->admin()->create();

        $data = [
            'name' => "Salingkusog",
        ];

        $response = $this->actingAs($admin)->postJson('/api/v1/intramurals/create', $data);
        $response->dump();
        $response->assertStatus(400)
                 ->assertJsonFragment(['message' => 'The year field is required.']);
    }
    
    public function test_store_intramurals_if_user() {

        $user = User::factory()->create();

        $data = [
            'name' => 'Basketball',
            'year' => 2025,
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/intramurals/create', $data);
        $response->assertStatus(403) // Expect Forbidden
             ->assertJson(['error' => 'unauthorized']); //
    }

    public function test_get_specific_intramural_game_if_admin() {
        $admin = User::factory()->admin()->create();
        $game = IntramuralGame::factory()->create();

        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$game->id}");

        $response->assertStatus(200)
                 ->assertJson(['id' => $game->id, 'name' => $game->name]);
    }

    public function test_get_specific_intramural_game_invalid_id_if_admin() {
        $admin = User::factory()->admin()->create();
        $game = IntramuralGame::factory()->create();

        $invalid_id = $game->id + 1;

        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$invalid_id}");

        $response->assertStatus(400);
    }

    public function test_get_specific_intramural_game_if_user() {
        $user = User::factory()->create();
        $game = IntramuralGame::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/v1/intramurals/{$game->id}");

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }

    public function test_update_intramural_game_if_admin() {
        $admin = User::factory()->admin()->create();
        $game = IntramuralGame::factory()->create();

        $updateData = ['id' => $game->id, 'name' => 'Updated Basketball', 'year' => 2026];

        $response = $this->actingAs($admin)->patchJson("/api/v1/intramurals/{$game->id}/edit", $updateData);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Game updated successfully', 'game' => ['name' => 'Updated Basketball']]);

        $this->assertDatabaseHas('intramural_games', ['id' => $game->id, 'name' => 'Updated Basketball']);
    }
    
    public function test_update_intramural_game_if_user() {
        $user = User::factory()->create();
        $game = IntramuralGame::factory()->create();

        $updateData = ['id' => $game->id, 'name' => 'Updated Basketball', 'year' => 2026];

        $response = $this->actingAs($user)->patchJson("/api/v1/intramurals/{$game->id}/edit", $updateData);

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }

    public function test_delete_intramural_game_if_admin() {
        $admin = User::factory()->admin()->create();
        $game = IntramuralGame::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/v1/intramurals/{$game->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('intramural_games', ['id' => $game->id]);
    }

    public function test_delete_intramural_game_if_user() {
        $user = User::factory()->create();
        $game = IntramuralGame::factory()->create();

        $response = $this->actingAs($user)->deleteJson("/api/v1/intramurals/{$game->id}");

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);

        $this->assertDatabaseHas('intramural_games', ['id' => $game->id]);
    }
}
