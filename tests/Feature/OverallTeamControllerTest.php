<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\OverallTeam;
use App\Models\IntramuralGame;
use App\Models\User;

class OverallTeamControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_overall_teams_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        
        $intrams_extra = IntramuralGame::factory()->create();


        OverallTeam::factory()->count(3)->create(['intrams_id' => $intrams->id]);
        OverallTeam::factory()->count(3)->create(['intrams_id' => $intrams_extra->id]);

        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/overall_teams");

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    public function test_index_overall_teams_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        OverallTeam::factory()->count(3)->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/intramurals/{$intrams->id}/overall_teams");

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }

    public function test_store_overall_team_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();

        $data = [
            'name' => 'Team Alpha',
            'team_logo_path' => 'https://example.com/team_logo.png', 
            'intrams_id' => $intrams->id,
        ];

        $response = $this->actingAs($admin)->postJson("/api/v1/intramurals/{$intrams->id}/overall_teams/create", $data);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Team Alpha']);

        $this->assertDatabaseHas('overall_teams', ['name' => 'Team Alpha']);
    }

    public function test_store_overall_team_invalid_intrams_id_admin() {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $invalid_id = $intrams->id + 69;

        $data = [
            'name' => 'Team Alpha',
            'team_logo_path' => 'https://example.com/team_logo.png', 
            'intrams_id' => $intrams->id,
        ];

        $response = $this->actingAs($admin)->postJson("/api/v1/intramurals/{$invalid_id}/overall_teams/create", $data);

        $response->assertStatus(400);
    }

    public function test_store_overall_team_invalid_payload_admin() {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();

        $data = [
            'name' => 123,
            'team_logo_path' => 'https://example.com/team_logo.png', 
            'intrams_id' => "wtf",
        ];

        $response = $this->actingAs($admin)->postJson("/api/v1/intramurals/{$intrams->id}/overall_teams/create", $data);

        $response->assertStatus(400);
    }

    public function test_store_overall_team_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();

        $data = [
            'name' => 'Team Alpha',
            'team_logo_path' => 'https://example.com/team_logo.png', 
            'intrams_id' => $intrams->id,
        ];

        $response = $this->actingAs($user)->postJson("/api/v1/intramurals/{$intrams->id}/overall_teams/create", $data);
        
        $response->assertStatus(403)
            ->assertJson(['error' => 'unauthorized']);
    }

    public function test_get_specific_overall_team_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $team = OverallTeam::factory()->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/overall_teams/{$team->id}");

        $response->assertStatus(200)
                 ->assertJson(['id' => $team->id, 'name' => $team->name]);
    }

    public function test_get_specific_overall_team_invalid_intrams_id_if_admin() {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $team = OverallTeam::factory()->create(['intrams_id' => $intrams->id]);
        $invalid_intrams_id = $intrams->id + 69;


        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/overall_teams/{$invalid_intrams_id}");
        
        $response->assertStatus(400);
    } 

    public function test_get_specific_overall_team_invalid_team_id_if_admin() {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $team = OverallTeam::factory()->create(['intrams_id' => $intrams->id]);
        $invalid_team_id = $team->id + 69;


        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/overall_teams/{$invalid_team_id}");
        
        $response->assertStatus(400);

    }

    public function test_get_specific_overall_team_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        $team = OverallTeam::factory()->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/intramurals/{$intrams->id}/overall_teams/{$team->id}");

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }

    public function test_update_overall_team_info_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $team = OverallTeam::factory()->create(['intrams_id' => $intrams->id]);

        $updateData = ['name' => 'Updated Team Name'];

        $response = $this->actingAs($admin)->patchJson("/api/v1/intramurals/{$intrams->id}/overall_teams/{$team->id}/edit", $updateData);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Team info updated successfully']);

        $this->assertDatabaseHas('overall_teams', ['id' => $team->id, 'name' => 'Updated Team Name']);
    }

    public function test_update_overall_team_info_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        $team = OverallTeam::factory()->create(['intrams_id' => $intrams->id]);

        $updateData = ['name' => 'Updated Team Name'];

        $response = $this->actingAs($user)->patchJson("/api/v1/intramurals/{$intrams->id}/overall_teams/{$team->id}/edit", $updateData);

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }

    public function test_update_overall_team_medals_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $team = OverallTeam::factory()->create(['intrams_id' => $intrams->id, 'total_gold' => 1, 'total_silver' => 1, 'total_bronze' => 1]);

        $medalData = [
            'total_gold' => 1,
            'total_silver' => 2,
            'total_bronze' => 3
        ];

        $response = $this->actingAs($admin)->patchJson("/api/v1/intramurals/{$intrams->id}/overall_teams/{$team->id}/update_medal", $medalData);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Medals updated successfully']);

        $this->assertDatabaseHas('overall_teams', [
            'id' => $team->id,
            'total_gold' => 2,
            'total_silver' => 3,
            'total_bronze' => 4
        ]);
    }

    public function test_update_overall_team_medals_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        $team = OverallTeam::factory()->create(['intrams_id' => $intrams->id, 'total_gold' => 1, 'total_silver' => 1, 'total_bronze' => 1]);

        $medalData = [
            'total_gold' => 1,
            'total_silver' => 2,
            'total_bronze' => 3
        ];

        $response = $this->actingAs($user)->patchJson("/api/v1/intramurals/{$intrams->id}/overall_teams/{$team->id}/update_medal", $medalData);

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }

    public function test_delete_overall_team_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $team = OverallTeam::factory()->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($admin)->deleteJson("/api/v1/intramurals/{$intrams->id}/overall_teams/{$team->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('overall_teams', ['id' => $team->id]);
    }

    public function test_delete_overall_team_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        $team = OverallTeam::factory()->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/intramurals/{$intrams->id}/overall_teams/{$team->id}");

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);

        $this->assertDatabaseHas('overall_teams', ['id' => $team->id]);
    }
}
