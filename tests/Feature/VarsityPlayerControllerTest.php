<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Player;
use App\Models\IntramuralGame;

class VarsityPlayerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_varsity_players_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $intrams_extra = IntramuralGame::factory()->create();

        Player::factory()->varsity()->count(3)->create(['intrams_id' => $intrams->id]);
        Player::factory()->varsity()->count(3)->create(['intrams_id' => $intrams_extra->id]);


        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/varsity_players");

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    public function test_index_no_varsity_players_if_admin() 
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $intrams_extra = IntramuralGame::factory()->create();

        Player::factory()->nonVarsity()->count(3)->create(['intrams_id' => $intrams->id]);
        Player::factory()->varsity()->count(3)->create(['intrams_id' => $intrams_extra->id]);


        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/varsity_players");

        $response->assertStatus(200)
                 ->assertJsonCount(0);
    }

    public function test_index_varsity_players_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        $intrams_extra = IntramuralGame::factory()->create();

        Player::factory()->varsity()->count(3)->create(['intrams_id' => $intrams->id]);
        Player::factory()->varsity()->count(3)->create(['intrams_id' => $intrams_extra->id]);


        $response = $this->actingAs($user)->getJson("/api/v1/intramurals/{$intrams->id}/varsity_players");

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }


    public function test_store_varsity_player_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();

        $data = [
            'name' => 'John Doe',
            'id_number' => '21-1-01025',
            'sport' => 'Basketball',
        ];

        $response = $this->actingAs($admin)->postJson("/api/v1/intramurals/{$intrams->id}/varsity_players/create", $data);

        $response->assertStatus(201)
                ->assertJsonFragment(['name' => 'John Doe']);

        $this->assertDatabaseHas('players', ['name' => 'John Doe', 'id_number' => '21-1-01025', 'is_varsity' => true]);
    }

    public function test_store_varsity_invalid_payload_as_admin() 
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();

        $data = [
            'name' => 'John Doe',
            //'id_number' => '21-1-01025',
            'sport' => 'Basketball',
        ];

        $response = $this->actingAs($admin)->postJson("/api/v1/intramurals/{$intrams->id}/varsity_players/create", $data);

        $response->assertStatus(400);
    }

    public function test_store_varsity_player_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();

        $data = [
            'name' => 'John Doe',
            'id_number' => '21-1-01025',
            'sport' => 'Basketball',
        ];

        $response = $this->actingAs($user)->postJson("/api/v1/intramurals/{$intrams->id}/varsity_players/create", $data);

        $response->assertStatus(403)
                ->assertJson(['error' => 'unauthorized']);
    }

    public function test_update_varsity_player_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $player = Player::factory()->varsity()->create(['intrams_id' => $intrams->id]);

        $updateData = [
            'name' => 'Updated Player Name',
        ];

        $response = $this->actingAs($admin)->patchJson("/api/v1/intramurals/{$intrams->id}/varsity_players/{$player->id}/edit", $updateData);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Varsity updated successfully']);

        $this->assertDatabaseHas('players', ['id' => $player->id, 'name' => 'Updated Player Name']);
    }

    public function test_update_varsity_player_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        $player = Player::factory()->varsity()->create(['intrams_id' => $intrams->id]);


        $updateData = [
            'name' => 'Updated Player Name',
        ];

        $response = $this->actingAs($user)->patchJson("/api/v1/intramurals/{$intrams->id}/varsity_players/{$player->id}/edit", $updateData);

        $response->assertStatus(403)
                ->assertJson(['error' => 'unauthorized']);
    }

    public function test_get_specific_varsity_player_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $player = Player::factory()->varsity()->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/varsity_players/{$player->id}");

        $response->assertStatus(200)
                 ->assertJson(['id' => $player->id, 'name' => $player->name]);
    }

    public function test_get_specific_varsity_player_invalid_intrams_id_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $player = Player::factory()->varsity()->create(['intrams_id' => $intrams->id]);
        $invalid_intrams_id = $intrams->id + 69;

        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$invalid_intrams_id}/varsity_players/{$player->id}");

        $response->assertStatus(400);
    }

    public function test_get_specific_varsity_player_invalid_player_id_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $player = Player::factory()->varsity()->create(['intrams_id' => $intrams->id]);
        $invalid_player_id = $player->id + 69;

        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/varsity_players/{$invalid_player_id}");

        $response->assertStatus(400);
    }

    public function test_get_specific_varsity_player_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        $player = Player::factory()->varsity()->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/intramurals/{$intrams->id}/varsity_players/{$player->id}");

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }
    public function test_delete_varsity_player_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $player = Player::factory()->varsity()->create([
            'intrams_id' => $intrams->id]);

        $response = $this->actingAs($admin)->deleteJson("/api/v1/intramurals/{$intrams->id}/varsity_players/{$player->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('players', ['id' => $player->id]);
    }

    public function test_delete_varsity_player_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        $player = Player::factory()->varsity()->create([
            'intrams_id' => $intrams->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/intramurals/{$intrams->id}/varsity_players/{$player->id}");

        $response = $this->actingAs($user)->deleteJson("/api/v1/intramurals/{$intrams->id}/varsity_players/{$player->id}");

        $response->assertStatus(403)
                ->assertJson(['error' => 'unauthorized']);

        $this->assertDatabaseHas('players', ['id' => $player->id]);
    }

}
