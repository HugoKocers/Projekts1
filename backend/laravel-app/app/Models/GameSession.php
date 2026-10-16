<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSession extends Model
{
    protected $fillable = [
        'user_id',
        'current_scenario_id',
        'inventory',
        'elapsed_seconds',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'inventory' => 'array',
            'elapsed_seconds' => 'integer',
            'is_completed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentScenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class, 'current_scenario_id');
    }
}
