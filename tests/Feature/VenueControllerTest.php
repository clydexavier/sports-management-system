<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Venue;
use App\Models\IntramuralGame;
use App\Models\User;

class VenueControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_venues_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $intrams_extra = IntramuralGame::factory()->create();
        Venue::factory()->count(3)->create(['intrams_id' => $intrams->id]);
        Venue::factory()->count(3)->create(['intrams_id' => $intrams_extra->id]);

        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/venues");

        $response->assertStatus(200)
                 ->assertJsonCount(3);
                
        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams_extra->id}/venues");

        $response->assertStatus(200)
                ->assertJsonCount(3);
    }

    public function test_index_venues_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        Venue::factory()->count(3)->create(['intrams_id' => $intrams->id]);
       
        $response = $this->actingAs($user)->getJson("/api/v1/intramurals/{$intrams->id}/venues");

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }

    public function test_store_venue_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();

        $data = [
            'name' => 'Main Court',
            'location' => 'Sports Complex',
            'type' => 'Indoor',
            'intrams_id' => $intrams->id,
        ];

        $response = $this->actingAs($admin)->postJson("/api/v1/intramurals/{$intrams->id}/venues/create", $data);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Main Court']);

        $this->assertDatabaseHas('venues', ['name' => 'Main Court']);
    }

    public function test_store_venue_invalid_intrams_id_if_admin() {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $invalid_id = $intrams->id + 69;

        $data = [
            'name' => 'Main Court',
            'location' => 'Sports Complex',
            'type' => 'Indoor',
            'intrams_id' => $intrams->id,
        ];

        $response = $this->actingAs($admin)->postJson("/api/v1/intramurals/{$invalid_id}/venues/create", $data);

        $response->assertStatus(400);

    }

    public function test_store_venue_invalid_payload_if_admin() {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();

        $data = [
            //'name' => 'Main Court',
            'location' => 'Sports Complex',
            'type' => 'Indoor',
            'intrams_id' => $intrams->id,
        ];

        $response = $this->actingAs($admin)->postJson("/api/v1/intramurals/{$intrams->id}/venues/create", $data);
        
        $response->assertStatus(400);

        $data = [
            'name' => 'Main Court',
            //'location' => 'Sports Complex',
            'type' => 'Indoor',
            'intrams_id' => $intrams->id,
        ];

        $response = $this->actingAs($admin)->postJson("/api/v1/intramurals/{$intrams->id}/venues/create", $data);
        
        $response->assertStatus(400);

        $data = [
            'name' => 'Main Court',
            'location' => 'Sports Complex',
            //'type' => 'Indoor',
            'intrams_id' => $intrams->id,
        ];

        $response = $this->actingAs($admin)->postJson("/api/v1/intramurals/{$intrams->id}/venues/create", $data);
        
        $response->assertStatus(400);
    }

    public function test_store_venue_if_user() {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();

        $data = [
            'name' => 'Main Court',
            'location' => 'Sports Complex',
            'type' => 'Indoor',
            'intrams_id' => $intrams->id,
        ];

        $response = $this->actingAs($user)->postJson("/api/v1/intramurals/{$intrams->id}/venues/create", $data);

        $response->assertStatus(403)
            ->assertJson(['error' => 'unauthorized']);
    }

    public function test_get_specific_venue_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $venue = Venue::factory()->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/venues/{$venue->id}");

        $response->assertStatus(200)
                 ->assertJson(['id' => $venue->id, 'name' => $venue->name]);
    }

    public function test_get_specific_venue_invalid_intrams_id_if_admin() {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $venue = Venue::factory()->create(['intrams_id' => $intrams->id]);
        $invalid_intrams_id = $intrams->id + 69;

        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$invalid_intrams_id}/venues/{$venue->id}");

        $response->assertStatus(400);
    }

    public function test_get_specific_venue_invalid_venue_id_if_admin() {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $venue = Venue::factory()->create(['intrams_id' => $intrams->id]);
        $invalid_venue_id = $venue->id + 69;

        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/venues/{$invalid_venue_id}");

        $response->assertStatus(400);
    }

    public function test_get_specific_venue_if_user() {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        $venue = Venue::factory()->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/intramurals/{$intrams->id}/venues/{$venue->id}");

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }

    public function test_update_venue_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $venue = Venue::factory()->create(['intrams_id' => $intrams->id]);

        $updateData = ['id' => $venue->id, 'name' => 'Updated Venue Name'];

        $response = $this->actingAs($admin)->patchJson("/api/v1/intramurals/{$intrams->id}/venues/{$venue->id}/edit", $updateData);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Venue updated successfully']);

        $this->assertDatabaseHas('venues', ['id' => $venue->id, 'name' => 'Updated Venue Name']);
    }

    public function test_update_venue_if_user() {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        $venue = Venue::factory()->create(['intrams_id' => $intrams->id]);

        $updateData = ['id' => $venue->id, 'name' => 'Updated Venue Name'];

        $response = $this->actingAs($user)->patchJson("/api/v1/intramurals/{$intrams->id}/venues/{$venue->id}/edit", $updateData);

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }

    public function test_delete_venue_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $venue = Venue::factory()->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($admin)->deleteJson("/api/v1/intramurals/{$intrams->id}/venues/{$venue->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('venues', ['id' => $venue->id]);
    }

    public function test_delete_venue_if_user() {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        $venue = Venue::factory()->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/intramurals/{$intrams->id}/venues/{$venue->id}");
        
        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);

        $this->assertDatabaseHas('venues', ['id' => $venue->id]);
    }
}
