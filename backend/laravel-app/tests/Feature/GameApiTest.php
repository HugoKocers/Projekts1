<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\Item;
use App\Models\Scenario;
use App\Models\User;
use Database\Seeders\GameAdminSeeder;
use Database\Seeders\StorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_regular_user_and_login_accepts_username(): void
    {
        $this->postJson('/game-api/register', [
            'username' => 'story-player',
            'email' => 'player@example.com',
            'password' => 'safe-password',
            'password_confirmation' => 'safe-password',
        ])->assertCreated()
            ->assertJsonPath('user.username', 'story-player')
            ->assertJsonPath('user.is_admin', false);

        $this->postJson('/game-api/logout')->assertOk();

        $this->postJson('/game-api/login', [
            'username' => 'story-player',
            'password' => 'safe-password',
        ])->assertOk()
            ->assertJsonPath('user.email', 'player@example.com');

        $this->assertAuthenticated();
    }

    public function test_continue_lists_owned_incomplete_sessions_with_scenario_description_and_capped_time(): void
    {
        $user = User::factory()->create();
        $scenario = Scenario::create([
            'title' => 'First tunnel',
            'description' => 'The old tunnel shakes in the dark.',
            'risk_zone' => 'danger',
            'sort_order' => 1,
        ]);
        $item = Item::create(['name' => 'Torch', 'description' => 'A small light.']);
        $session = GameSession::create([
            'user_id' => $user->id,
            'current_scenario_id' => $scenario->id,
            'inventory' => [$item->id],
            'elapsed_seconds' => 5999,
        ]);

        $this->actingAs($user)->getJson('/game-api/sessions')
            ->assertOk()
            ->assertJsonPath('sessions.0.scenario_description', 'The old tunnel shakes in the dark.')
            ->assertJsonPath('sessions.0.playtime', '99:59')
            ->assertJsonPath('sessions.0.inventory.0', $item->id);

        $this->assertSame($session->id, $session->fresh()->id);
    }

    public function test_bootstrap_exposes_seeded_game_content_and_csrf_token(): void
    {
        $this->seed(StorySeeder::class);

        $this->getJson('/game-api/bootstrap')
            ->assertOk()
            ->assertJsonStructure([
                'csrf_token',
                'scenarios' => [['id', 'title', 'description', 'choices']],
                'items',
                'user',
            ])
            ->assertJsonCount(5, 'scenarios');
    }

    public function test_users_cannot_read_another_users_game_session(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $scenario = Scenario::create([
            'title' => 'Tunnel',
            'description' => 'A dark tunnel.',
            'risk_zone' => 'danger',
            'sort_order' => 1,
        ]);
        $session = GameSession::create([
            'user_id' => $owner->id,
            'current_scenario_id' => $scenario->id,
            'inventory' => [],
            'elapsed_seconds' => 30,
        ]);

        $this->actingAs($otherUser)->getJson("/game-api/sessions/{$session->id}")
            ->assertNotFound();
    }

    public function test_non_admin_users_are_denied_admin_content_management(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/game-api/admin')
            ->assertForbidden();
    }

    public function test_admin_can_create_scenarios_and_items(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $nextScenario = Scenario::create([
            'title' => 'Next',
            'description' => 'The next room.',
            'risk_zone' => 'safe',
            'sort_order' => 3,
        ]);

        $item = $this->actingAs($admin)->postJson('/game-api/admin/items', [
            'name' => 'Iron bar',
            'description' => 'A sturdy piece of iron.',
        ])->assertCreated()
            ->assertJsonPath('item.name', 'Iron bar')
            ->json('item');

        $this->putJson("/game-api/admin/items/{$item['id']}", [
            'name' => 'Iron tool',
            'description' => 'An updated sturdy iron tool.',
        ])->assertOk()->assertJsonPath('item.name', 'Iron tool');

        GameSession::create([
            'user_id' => $admin->id,
            'current_scenario_id' => $nextScenario->id,
            'inventory' => [$item['id']],
            'elapsed_seconds' => 0,
        ]);
        $this->deleteJson("/game-api/admin/items/{$item['id']}")
            ->assertUnprocessable();

        $choices = [
            [
                'label' => 'Left',
                'aftermath' => 'A narrow passage.',
                'ending_kind' => 'good',
                'ending_title' => 'Escaped',
                'ending_text' => 'You made it out.',
            ],
            ['label' => 'Middle', 'aftermath' => 'A blocked passage.', 'next_scenario_id' => $nextScenario->id],
            ['label' => 'Right', 'aftermath' => 'Fresh air.', 'next_scenario_id' => $nextScenario->id],
        ];
        $scenario = $this->postJson('/game-api/admin/scenarios', [
            'title' => 'Escape',
            'description' => 'Find a way out.',
            'risk_zone' => 'caution',
            'sort_order' => 2,
            'choices' => $choices,
        ])->assertCreated()
            ->assertJsonPath('scenario.title', 'Escape')
            ->assertJsonCount(3, 'scenario.choices')
            ->json('scenario');

        $this->putJson("/game-api/admin/scenarios/{$scenario['id']}", [
            'title' => 'Escape route',
            'description' => 'An updated way out.',
            'risk_zone' => 'safe',
            'sort_order' => 2,
            'choices' => $choices,
        ])->assertOk()->assertJsonPath('scenario.title', 'Escape route');
    }

    public function test_configured_admin_is_seeded_only_from_private_configuration(): void
    {
        config([
            'game.admin_email' => 'admin@example.com',
            'game.admin_password' => 'private-admin-password',
            'game.admin_username' => 'game-admin',
        ]);

        $this->seed(GameAdminSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'username' => 'game-admin',
            'is_admin' => true,
        ]);
    }

    public function test_story_seeder_creates_the_authored_scenarios_and_choices(): void
    {
        $this->seed(StorySeeder::class);

        $this->assertDatabaseCount('scenarios', 5);
        $this->assertDatabaseCount('scenario_choices', 15);
        $this->assertDatabaseHas('items', ['name' => 'Ticība pasakām']);
        $this->assertDatabaseHas('scenario_choices', [
            'label' => 'Paslēpies zem arkas otrajā ejā.',
            'ending_kind' => null,
        ]);
    }

    public function test_elapsed_time_outside_the_clock_range_is_rejected(): void
    {
        $user = User::factory()->create();
        $scenario = Scenario::create([
            'title' => 'Tunnel',
            'description' => 'A dark tunnel.',
            'risk_zone' => 'danger',
            'sort_order' => 1,
        ]);

        $this->actingAs($user)->postJson('/game-api/sessions', [
            'current_scenario_id' => $scenario->id,
            'inventory' => [],
            'elapsed_seconds' => 6000,
            'is_completed' => false,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('elapsed_seconds');
    }
}
