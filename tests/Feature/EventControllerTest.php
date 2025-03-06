<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Event;
use App\Models\IntramuralGame;
use App\Models\User;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_events_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        Event::factory()->count(3)->create(['intrams_id' => $intrams->id]);

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
            'gold' => 3,
            'silver' => 2,
            'bronze' => 1,
            'intrams_id' => $intrams->id,
        ];

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
        $response->dump();
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
        $event = Event::factory()->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/events/{$event->id}");

        $response->assertStatus(200)
                 ->assertJson(['id' => $event->id, 'name' => $event->name]);
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
