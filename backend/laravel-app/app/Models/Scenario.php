<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scenario extends Model
{
    protected $fillable = [
        'title',
        'description',
        'risk_zone',
        'sort_order',
        'replacement_for_scenario_id',
        'spawn_chance',
    ];

    protected function casts(): array
    {
        return ['spawn_chance' => 'float'];
    }

    public function choices(): HasMany
    {
        return $this->hasMany(ScenarioChoice::class)->orderBy('sort_order');
    }

    public function replacementForScenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class, 'replacement_for_scenario_id');
    }

    public function randomReplacements(): HasMany
    {
        return $this->hasMany(Scenario::class, 'replacement_for_scenario_id');
    }
}
