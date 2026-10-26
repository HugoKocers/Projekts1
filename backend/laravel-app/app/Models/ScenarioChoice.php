<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScenarioChoice extends Model
{
    protected $fillable = [
        'scenario_id',
        'label',
        'label_en',
        'aftermath',
        'aftermath_en',
        'next_scenario_id',
        'required_item_id',
        'reward_item_id',
        'reward_chance',
        'ending_title',
        'ending_title_en',
        'ending_text',
        'ending_text_en',
        'ending_kind',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['reward_chance' => 'float'];
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function nextScenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class, 'next_scenario_id');
    }

    public function requiredItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'required_item_id');
    }

    public function rewardItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'reward_item_id');
    }
}
