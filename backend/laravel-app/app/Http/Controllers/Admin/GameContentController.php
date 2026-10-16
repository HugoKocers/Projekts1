<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use App\Models\Item;
use App\Models\Scenario;
use App\Models\ScenarioChoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GameContentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'scenarios' => $this->scenarios(),
            'items' => Item::query()->orderBy('name')->get(),
        ]);
    }

    public function storeScenario(Request $request): JsonResponse
    {
        $data = $this->validateScenario($request);
        $scenario = DB::transaction(function () use ($data) {
            $scenario = Scenario::create(collect($data)->except('choices')->all());
            $this->replaceChoices($scenario, $data['choices']);

            return $scenario;
        });

        return response()->json(['scenario' => $this->scenarios()->firstWhere('id', $scenario->id)], 201);
    }

    public function updateScenario(Request $request, Scenario $scenario): JsonResponse
    {
        $data = $this->validateScenario($request);
        DB::transaction(function () use ($scenario, $data) {
            $scenario->update(collect($data)->except('choices')->all());
            $scenario->choices()->delete();
            $this->replaceChoices($scenario, $data['choices']);
        });

        return response()->json(['scenario' => $this->scenarios()->firstWhere('id', $scenario->id)]);
    }

    public function destroyScenario(Scenario $scenario): JsonResponse
    {
        if (
            GameSession::where('current_scenario_id', $scenario->id)->exists()
            || ScenarioChoice::where('next_scenario_id', $scenario->id)->exists()
        ) {
            throw ValidationException::withMessages([
                'scenario' => ['This scenario is used by a saved game or another scenario and cannot be deleted.'],
            ]);
        }

        $scenario->delete();

        return response()->json(['message' => 'Scenario deleted.']);
    }

    public function storeItem(Request $request): JsonResponse
    {
        $item = Item::create($this->validateItem($request));

        return response()->json(['item' => $item], 201);
    }

    public function updateItem(Request $request, Item $item): JsonResponse
    {
        $item->update($this->validateItem($request));

        return response()->json(['item' => $item]);
    }

    public function destroyItem(Item $item): JsonResponse
    {
        if (
            ScenarioChoice::where('required_item_id', $item->id)->exists()
            || ScenarioChoice::where('reward_item_id', $item->id)->exists()
            || GameSession::whereJsonContains('inventory', $item->id)->exists()
        ) {
            throw ValidationException::withMessages([
                'item' => ['Remove this item from scenario choices and saved inventories before deleting it.'],
            ]);
        }

        $item->delete();

        return response()->json(['message' => 'Item deleted.']);
    }

    private function validateScenario(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'risk_zone' => ['required', 'in:safe,caution,danger'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'choices' => ['required', 'array', 'size:3'],
            'choices.*.label' => ['required', 'string', 'max:255'],
            'choices.*.aftermath' => ['required', 'string', 'max:10000'],
            'choices.*.next_scenario_id' => ['nullable', 'integer', 'exists:scenarios,id'],
            'choices.*.required_item_id' => ['nullable', 'integer', 'exists:items,id'],
            'choices.*.reward_item_id' => ['nullable', 'integer', 'exists:items,id'],
            'choices.*.reward_chance' => ['nullable', 'numeric', 'between:0,1'],
            'choices.*.ending_title' => ['nullable', 'string', 'max:255'],
            'choices.*.ending_text' => ['nullable', 'string', 'max:10000'],
            'choices.*.ending_kind' => ['nullable', 'in:good,dead'],
        ]);

        foreach ($data['choices'] as $index => $choice) {
            $hasNextScenario = ! empty($choice['next_scenario_id']);
            $hasEnding = ! empty($choice['ending_kind']);

            if ($hasNextScenario === $hasEnding) {
                throw ValidationException::withMessages([
                    "choices.{$index}.next_scenario_id" => ['Choose either a next scenario or an ending for each choice.'],
                ]);
            }

            if ($hasEnding && (empty($choice['ending_title']) || empty($choice['ending_text']))) {
                throw ValidationException::withMessages([
                    "choices.{$index}.ending_title" => ['Endings require both a title and ending text.'],
                ]);
            }

            if (isset($choice['reward_item_id']) !== isset($choice['reward_chance'])) {
                throw ValidationException::withMessages([
                    "choices.{$index}.reward_chance" => ['Set both a reward item and its chance, or leave both empty.'],
                ]);
            }
        }

        return $data;
    }

    private function validateItem(Request $request): array
    {
        $item = $request->route('item');
        $nameRule = Rule::unique('items', 'name');
        if ($item instanceof Item) {
            $nameRule->ignore($item->id);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255', $nameRule],
            'description' => ['required', 'string', 'max:5000'],
        ]);
    }

    private function replaceChoices(Scenario $scenario, array $choices): void
    {
        foreach ($choices as $index => $choice) {
            $scenario->choices()->create([...$choice, 'sort_order' => $index]);
        }
    }

    private function scenarios()
    {
        return Scenario::query()
            ->with(['choices.requiredItem', 'choices.rewardItem'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
