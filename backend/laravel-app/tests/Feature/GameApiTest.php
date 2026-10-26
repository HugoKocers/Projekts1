<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\Item;
use App\Models\Scenario;
use App\Models\ScenarioChoice;
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
            'title_en' => 'First tunnel',
            'description' => 'The old tunnel shakes in the dark.',
            'description_en' => 'The old tunnel shakes in the dark.',
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
            ->assertJsonPath('sessions.0.scenario_title_en', 'First tunnel')
            ->assertJsonPath('sessions.0.scenario_description', 'The old tunnel shakes in the dark.')
            ->assertJsonPath('sessions.0.scenario_description_en', 'The old tunnel shakes in the dark.')
            ->assertJsonPath('sessions.0.playtime', '99:59')
            ->assertJsonPath('sessions.0.inventory.0', $item->id);

        $this->assertSame($session->id, $session->fresh()->id);
    }

    public function test_continue_keeps_only_the_three_most_recent_saves(): void
    {
        $user = User::factory()->create();
        $scenario = Scenario::create([
            'title' => 'Tunnel',
            'description' => 'A dark tunnel.',
            'risk_zone' => 'danger',
            'sort_order' => 1,
        ]);
        $sessionIds = [];

        for ($index = 0; $index < 4; $index++) {
            $sessionIds[] = $this->actingAs($user)->postJson('/game-api/sessions', [
                'current_scenario_id' => $scenario->id,
                'inventory' => [],
                'elapsed_seconds' => $index,
                'is_completed' => false,
            ])->assertCreated()->json('session.id');
        }

        $this->getJson('/game-api/sessions')
            ->assertOk()
            ->assertJsonCount(3, 'sessions');

        $this->assertDatabaseMissing('game_sessions', ['id' => $sessionIds[0]]);
        $this->assertDatabaseHas('game_sessions', ['id' => $sessionIds[3]]);
        $this->assertDatabaseCount('game_sessions', 3);
    }

    public function test_bootstrap_exposes_seeded_game_content_and_csrf_token(): void
    {
        $this->seed(StorySeeder::class);

        $this->getJson('/game-api/bootstrap')
            ->assertOk()
            ->assertJsonStructure([
                'csrf_token',
                'scenarios' => [['id', 'title', 'title_en', 'description', 'description_en', 'choices' => [['label_en', 'aftermath_en']]]],
                'items' => [['id', 'name', 'name_en', 'description', 'description_en']],
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
            'name_en' => 'Iron bar',
            'description' => 'A sturdy piece of iron.',
            'description_en' => 'A sturdy piece of iron.',
        ])->assertCreated()
            ->assertJsonPath('item.name', 'Iron bar')
            ->assertJsonPath('item.name_en', 'Iron bar')
            ->json('item');

        $this->putJson("/game-api/admin/items/{$item['id']}", [
            'name' => 'Iron tool',
            'name_en' => 'Iron tool',
            'description' => 'An updated sturdy iron tool.',
            'description_en' => 'An updated sturdy iron tool.',
        ])->assertOk()
            ->assertJsonPath('item.name', 'Iron tool')
            ->assertJsonPath('item.name_en', 'Iron tool');

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
                'label_en' => 'Left',
                'aftermath' => 'A narrow passage.',
                'aftermath_en' => 'A narrow passage.',
                'ending_kind' => 'good',
                'ending_title' => 'Escaped',
                'ending_title_en' => 'Escaped',
                'ending_text' => 'You made it out.',
                'ending_text_en' => 'You made it out.',
            ],
            ['label' => 'Middle', 'label_en' => 'Middle', 'aftermath' => 'A blocked passage.', 'aftermath_en' => 'A blocked passage.', 'next_scenario_id' => $nextScenario->id],
            ['label' => 'Right', 'label_en' => 'Right', 'aftermath' => 'Fresh air.', 'aftermath_en' => 'Fresh air.', 'next_scenario_id' => $nextScenario->id],
        ];
        $scenario = $this->postJson('/game-api/admin/scenarios', [
            'title' => 'Escape',
            'title_en' => 'Escape',
            'description' => 'Find a way out.',
            'description_en' => 'Find a way out.',
            'risk_zone' => 'caution',
            'sort_order' => 2,
            'choices' => $choices,
        ])->assertCreated()
            ->assertJsonPath('scenario.title', 'Escape')
            ->assertJsonPath('scenario.title_en', 'Escape')
            ->assertJsonPath('scenario.choices.0.label_en', 'Left')
            ->assertJsonCount(3, 'scenario.choices')
            ->json('scenario');

        $this->putJson("/game-api/admin/scenarios/{$scenario['id']}", [
            'title' => 'Escape route',
            'title_en' => 'Escape route',
            'description' => 'An updated way out.',
            'description_en' => 'An updated way out.',
            'risk_zone' => 'safe',
            'sort_order' => 2,
            'choices' => $choices,
        ])->assertOk()->assertJsonPath('scenario.title', 'Escape route');
    }

    public function test_admin_content_requires_english_translations(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $nextScenario = Scenario::create([
            'title' => 'Next scene',
            'description' => 'The next scene.',
            'risk_zone' => 'safe',
            'sort_order' => 1,
        ]);
        $choices = [
            ['label' => 'Left', 'aftermath' => 'A passage.', 'next_scenario_id' => $nextScenario->id],
            ['label' => 'Middle', 'aftermath' => 'A passage.', 'next_scenario_id' => $nextScenario->id],
            ['label' => 'Right', 'aftermath' => 'A passage.', 'next_scenario_id' => $nextScenario->id],
        ];

        $this->actingAs($admin)->postJson('/game-api/admin/items', [
            'name' => 'Lantern',
            'description' => 'A small light.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name_en', 'description_en']);

        $this->postJson('/game-api/admin/scenarios', [
            'title' => 'A new scene',
            'description' => 'A scene in the tunnels.',
            'risk_zone' => 'caution',
            'sort_order' => 2,
            'choices' => $choices,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title_en',
                'description_en',
                'choices.0.label_en',
                'choices.0.aftermath_en',
            ]);
    }

    public function test_admin_can_delete_a_scenario_while_preserving_incoming_endings(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $scenario = Scenario::create([
            'title' => 'Temporary replacement',
            'description' => 'A scenario that should be removed.',
            'risk_zone' => 'danger',
            'sort_order' => 2,
        ]);
        $source = Scenario::create([
            'title' => 'Final scene',
            'description' => 'The normal ending scene.',
            'risk_zone' => 'safe',
            'sort_order' => 1,
        ]);
        $choice = ScenarioChoice::create([
            'scenario_id' => $source->id,
            'label' => 'Keep the original ending',
            'aftermath' => 'The story reaches its ending.',
            'next_scenario_id' => $scenario->id,
            'ending_kind' => 'good',
            'ending_title' => 'A good ending',
            'ending_title_en' => 'A good ending',
            'ending_text' => 'The original ending is preserved.',
            'ending_text_en' => 'The original ending is preserved.',
            'sort_order' => 1,
        ]);
        $completedSession = GameSession::create([
            'user_id' => $admin->id,
            'current_scenario_id' => $scenario->id,
            'inventory' => [],
            'elapsed_seconds' => 10,
            'is_completed' => true,
        ]);

        $this->actingAs($admin)->deleteJson("/game-api/admin/scenarios/{$scenario->id}")
            ->assertOk();

        $this->assertDatabaseMissing('scenarios', ['id' => $scenario->id]);
        $this->assertDatabaseMissing('game_sessions', ['id' => $completedSession->id]);
        $this->assertDatabaseHas('scenario_choices', [
            'id' => $choice->id,
            'next_scenario_id' => null,
            'ending_kind' => 'good',
            'ending_title' => 'A good ending',
            'ending_title_en' => 'A good ending',
            'ending_text' => 'The original ending is preserved.',
            'ending_text_en' => 'The original ending is preserved.',
        ]);
    }

    public function test_admin_cannot_delete_a_scenario_used_by_an_unfinished_save(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $scenario = Scenario::create([
            'title' => 'In progress',
            'description' => 'A saved scene.',
            'risk_zone' => 'danger',
            'sort_order' => 1,
        ]);
        $session = GameSession::create([
            'user_id' => $admin->id,
            'current_scenario_id' => $scenario->id,
            'inventory' => [],
            'elapsed_seconds' => 10,
            'is_completed' => false,
        ]);

        $this->actingAs($admin)->deleteJson("/game-api/admin/scenarios/{$scenario->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scenario');

        $this->assertDatabaseHas('scenarios', ['id' => $scenario->id]);
        $this->assertDatabaseHas('game_sessions', ['id' => $session->id]);
    }

    public function test_admin_cannot_delete_a_scenario_used_by_a_choice_without_an_ending(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $scenario = Scenario::create([
            'title' => 'Next scene',
            'description' => 'A linked scene.',
            'risk_zone' => 'danger',
            'sort_order' => 2,
        ]);
        $source = Scenario::create([
            'title' => 'Source scene',
            'description' => 'A scene with a route.',
            'risk_zone' => 'safe',
            'sort_order' => 1,
        ]);
        $choice = ScenarioChoice::create([
            'scenario_id' => $source->id,
            'label' => 'Continue',
            'aftermath' => 'The story continues.',
            'next_scenario_id' => $scenario->id,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)->deleteJson("/game-api/admin/scenarios/{$scenario->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scenario');

        $this->assertDatabaseHas('scenarios', ['id' => $scenario->id]);
        $this->assertDatabaseHas('scenario_choices', [
            'id' => $choice->id,
            'next_scenario_id' => $scenario->id,
        ]);
    }

    public function test_admin_can_create_random_replacement_scenarios_with_a_chance_pool_capped_at_one(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $reward = Item::create(['name' => 'Small reward', 'description' => 'A chance-based reward.']);
        $normalScenario = Scenario::create([
            'title' => 'Normal tunnel',
            'description' => 'The regular event.',
            'risk_zone' => 'caution',
            'sort_order' => 1,
        ]);
        $choices = [
            ['label' => 'First', 'label_en' => 'First', 'aftermath' => 'It ends.', 'aftermath_en' => 'It ends.', 'ending_kind' => 'good', 'ending_title' => 'End', 'ending_title_en' => 'End', 'ending_text' => 'Safe.', 'ending_text_en' => 'Safe.'],
            ['label' => 'Second', 'label_en' => 'Second', 'aftermath' => 'It ends.', 'aftermath_en' => 'It ends.', 'ending_kind' => 'dead', 'ending_title' => 'End', 'ending_title_en' => 'End', 'ending_text' => 'Dead.', 'ending_text_en' => 'Dead.'],
            ['label' => 'Third', 'label_en' => 'Third', 'aftermath' => 'It ends.', 'aftermath_en' => 'It ends.', 'ending_kind' => 'good', 'ending_title' => 'End', 'ending_title_en' => 'End', 'ending_text' => 'Safe.', 'ending_text_en' => 'Safe.'],
        ];

        $payload = [
            'title' => 'Unexpected tunnel',
            'title_en' => 'Unexpected tunnel',
            'description' => 'A random event replaces the normal tunnel.',
            'description_en' => 'A random event replaces the normal tunnel.',
            'risk_zone' => 'danger',
            'sort_order' => 2,
            'replacement_for_scenario_id' => $normalScenario->id,
            'spawn_chance' => 0.27,
            'choices' => $choices,
        ];
        $payload['choices'][0]['reward_item_id'] = $reward->id;
        $payload['choices'][0]['reward_chance'] = 0.27;

        $this->actingAs($admin)->postJson('/game-api/admin/scenarios', $payload)
            ->assertCreated()
            ->assertJsonPath('scenario.replacement_for_scenario_id', $normalScenario->id)
            ->assertJsonPath('scenario.spawn_chance', 0.27)
            ->assertJsonPath('scenario.choices.0.reward_chance', 0.27);

        $payload['title'] = 'Over-precision replacement';
        $payload['spawn_chance'] = 0.275;
        $this->postJson('/game-api/admin/scenarios', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('spawn_chance');

        $payload['title'] = 'Over-precision reward';
        $payload['spawn_chance'] = 0.27;
        $payload['choices'][0]['reward_chance'] = 0.275;
        $this->postJson('/game-api/admin/scenarios', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('choices.0.reward_chance');

        $payload['title'] = 'Another replacement';
        $payload['choices'][0]['reward_chance'] = 0.27;
        $payload['spawn_chance'] = 0.8;
        $this->postJson('/game-api/admin/scenarios', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('spawn_chance');
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
        $this->assertDatabaseHas('items', ['name' => 'Ticība pasakām', 'name_en' => 'Belief in fairytales']);
        $this->assertDatabaseHas('scenarios', ['title' => '1. SCENĀRIJS — BRUKUMS', 'title_en' => 'SCENARIO 1 — COLLAPSE']);
        $this->assertDatabaseHas('scenario_choices', [
            'label' => 'Paslēpies zem arkas otrajā ejā.',
            'label_en' => 'Hide beneath the arch in the second passage.',
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
