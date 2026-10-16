<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scenario extends Model
{
    protected $fillable = ['title', 'description', 'risk_zone', 'sort_order'];

    public function choices(): HasMany
    {
        return $this->hasMany(ScenarioChoice::class)->orderBy('sort_order');
    }
}
