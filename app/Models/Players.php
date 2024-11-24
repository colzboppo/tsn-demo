<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Players extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
    ];

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->doesntHave('currentGame');
    }

    public function currentGame(): BelongsToMany
    {
        return $this->games()->inProgress();
    }

    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Games::class, 'game_players', 'player_id', 'game_id')->using(GamePlayers::class);
    }

    public function isAvailable(): bool
    {
        return self::available()->where($this->getKeyName(), $this->getKey())->exists();
    }
}
