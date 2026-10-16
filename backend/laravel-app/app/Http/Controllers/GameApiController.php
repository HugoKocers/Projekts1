<?php

namespace App\Http\Controllers;

use App\Models\GameSession;
use App\Models\Item;
use App\Models\Scenario;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class GameApiController extends Controller
{
    public function bootstrap(): JsonResponse
    {
        return response()->json([
            'csrf_token' => csrf_token(),
            'user' => $this->userData(Auth::user()),
            'scenarios' => $this->scenarios(),
            'items' => Item::query()->orderBy('name')->get(['id', 'name', 'description']),
        ])->header('Cache-Control', 'no-store');
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:2', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['username'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $this->userData($user)], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('username', $data['username'])
            ->orWhere('email', strtolower($data['username']))
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided login details are incorrect.'],
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $this->userData($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    public function indexSessions(Request $request): JsonResponse
    {
        $sessions = $request->user()->gameSessions()
            ->where('is_completed', false)
            ->with('currentScenario:id,title,description')
            ->latest('updated_at')
            ->get()
            ->map(fn (GameSession $session) => $this->sessionData($session));

        return response()->json(['sessions' => $sessions]);
    }

    public function storeSession(Request $request): JsonResponse
    {
        $data = $this->validatedSession($request);
        $session = $request->user()->gameSessions()->create($data);
        $session->load('currentScenario:id,title,description');

        return response()->json(['session' => $this->sessionData($session)], 201);
    }

    public function showSession(Request $request, GameSession $gameSession): JsonResponse
    {
        abort_unless($gameSession->user_id === $request->user()->id, 404);
        $gameSession->load('currentScenario:id,title,description');

        return response()->json(['session' => $this->sessionData($gameSession)]);
    }

    public function updateSession(Request $request, GameSession $gameSession): JsonResponse
    {
        abort_unless($gameSession->user_id === $request->user()->id, 404);
        $gameSession->update($this->validatedSession($request));
        $gameSession->load('currentScenario:id,title,description');

        return response()->json(['session' => $this->sessionData($gameSession)]);
    }

    private function validatedSession(Request $request): array
    {
        return $request->validate([
            'current_scenario_id' => ['required', 'integer', 'exists:scenarios,id'],
            'inventory' => ['present', 'array'],
            'inventory.*' => ['integer', 'distinct', 'exists:items,id'],
            'elapsed_seconds' => ['required', 'integer', 'between:0,5999'],
            'is_completed' => ['required', 'boolean'],
        ]);
    }

    private function sessionData(GameSession $session): array
    {
        $seconds = min($session->elapsed_seconds, 5999);

        return [
            'id' => $session->id,
            'scenario_id' => $session->current_scenario_id,
            'scenario_title' => $session->currentScenario->title,
            'scenario_description' => $session->currentScenario->description,
            'inventory' => $session->inventory ?? [],
            'elapsed_seconds' => $seconds,
            'playtime' => sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60),
            'is_completed' => $session->is_completed,
            'updated_at' => $session->updated_at,
        ];
    }

    private function scenarios()
    {
        return Scenario::query()
            ->with(['choices.requiredItem:id,name,description', 'choices.rewardItem:id,name,description'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function userData(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'username' => $user->username ?? $user->name,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
        ];
    }
}
