<?php

namespace Tests\Feature;

use App\Services\ChallongeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Event;
use App\Models\IntramuralGame;
use App\Models\User;
use Mockery;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected $mockChallonge;

    public function setUp(): void
    {
        parent::setUp();
        $this->mockChallonge = Mockery::mock(ChallongeService::class);
        $this->app->instance(ChallongeService::class, $this->mockChallonge);

    }

    public function test_index_events_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        
        $intrams_extra = IntramuralGame::factory()->create();

        Event::factory()->count(3)->create(['intrams_id' => $intrams_extra->id]);

        Event::factory()->count(3)->create(['intrams_id' => $intrams->id]);
        $this->mockChallonge->shouldReceive('getTournament')->andReturn([]);
        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/events");
        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    public function test_index_events_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        Event::factory()->count(3)->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/intramurals/{$intrams->id}/events");

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }

    public function test_store_event_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();

        $data = [
            'name' => 'Basketball Tournament',
            'category' => "Men's",
            'type' => 'Sports',
            'tournament_type' => 'single elimination',
            'hold_third_place_match' => true,
            'gold' => 3,
            'silver' => 2,
            'bronze' => 1,
            'intrams_id' => $intrams->id,
        ];
        // Mock expectations
        // Mock the ChallongeService correctly
        $this->mockChallonge->shouldReceive('createTournament')
            ->once()
            ->andReturn(['tournament' => ['id' => 12345]]); // Ensure correct structure

        $this->mockChallonge->shouldReceive('getTournament')->once()->andReturn(['tournament' => ['id' => 12345]]);
        $response = $this->actingAs($admin)->postJson("/api/v1/intramurals/{$intrams->id}/events/create", $data);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Basketball Tournament']);

        $this->assertDatabaseHas('events', ['name' => 'Basketball Tournament']);
    }

    public function test_store_event_invalid_intrams_id_if_admin() {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $invalid_id = $intrams->id +69;

        $data = [
            'name' => 'Basketball Tournament',
            'category' => "Men's",
            'type' => 'Sports',
            'gold' => 3,
            'silver' => 2,
            'bronze' => 1,
            'intrams_id' => $intrams->id,
        ];

        $response = $this->actingAs($admin)->postJson("/api/v1/intramurals/{$invalid_id}/events/create", $data);
        $response->assertStatus(400);
    }

    public function test_store_event_invalid_payload_if_admin() {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $invalid_id = $intrams-> id + 6969696;
        $data = [
            'name' => 'Basketball Tournament',
            'category' => "Men's",
            'type' => 'Sports',
            //'gold' => 3,
            'silver' => 2,
            'bronze' => 1,
        ];

        $response = $this->actingAs($admin)->postJson("/api/v1/intramurals/{$intrams->id}/events/create", $data);
        $response->assertStatus(400);
    }

    public function test_store_event_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();

        $data = [
            'name' => 'Basketball Tournament',
            'category' => "Men's",
            'type' => 'Sports',
            'gold' => 3,
            'silver' => 2,
            'bronze' => 1,
            'intrams_id' => $intrams->id,
        ];

        $response = $this->actingAs($user)->postJson("/api/v1/intramurals/{$intrams->id}/events/create", $data);

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }

    public function test_get_specific_event_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $event = Event::factory()->create(['intrams_id' => $intrams->id, 'challonge_event_id' => 12345]);

        // Mock Challonge service correctly
        $this->mockChallonge->shouldReceive('getTournament')
            ->once() // Expect it to be called once
            ->with(12345, ['include_participants' => false, 'include_matches' => false])
            ->andReturn(['tournament' => ['name' => 'Basketball Tournament']]);

        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/events/{$event->id}");

        $response->assertStatus(200)
                ->assertJsonFragment(['Basketball Tournament']); // Match expected name
    }


    public function test_get_specific_event_invalid_event_id_if_admin() {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        
        $event = Event::factory()->create(['intrams_id' => $intrams->id]);
        $invalid_id = $event->id + 69;
        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/events/{$invalid_id}");
        $response->assertStatus(400);
    }

    public function test_get_specific_event_invalid_intrams_id_if_admin() {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $invalid_id = $intrams->id+69;
        $event = Event::factory()->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$invalid_id}/events/{$event->id}");
        $response->assertStatus(400);
    }

    public function test_get_specific_event_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        $event = Event::factory()->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/intramurals/{$intrams->id}/events/{$event->id}");

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }

    public function test_update_event_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $event = Event::factory()->create(['intrams_id' => $intrams->id]);

        $updateData = ['name' => 'Updated Event Name'];

        // Mock Challonge API if needed
        $this->mockChallonge->shouldReceive('updateTournament')
            ->once()
            ->with($event->challonge_event_id, ['name' => 'Updated Event Name'])
            ->andReturn(['tournament' => ['name' => $event->category. " ". 'Updated Event Name']]);

        // Send the request
        $response = $this->actingAs($admin)->patchJson("/api/v1/intramurals/{$intrams->id}/events/{$event->id}/edit", $updateData);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Event updated successfully']);

        $this->assertDatabaseHas('events', ['id' => $event->id, 'name' => 'Updated Event Name']);
    }


    public function test_update_event_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        $event = Event::factory()->create(['intrams_id' => $intrams->id]);

        $updateData = ['name' => 'Updated Event Name'];

        $response = $this->actingAs($user)->patchJson("/api/v1/intramurals/{$intrams->id}/events/{$event->id}/edit", $updateData);

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }

    public function test_delete_event_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $event = Event::factory()->create(['intrams_id' => $intrams->id]);

        $this->mockChallonge->shouldReceive('deleteTournament')
            ->once()
            ->with($event->challonge_event_id)
            ->andReturn(true);

        $response = $this->actingAs($admin)->deleteJson("/api/v1/intramurals/{$intrams->id}/events/{$event->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }


    public function test_delete_event_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        $event = Event::factory()->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/intramurals/{$intrams->id}/events/{$event->id}");
        
        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);

        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }
}
