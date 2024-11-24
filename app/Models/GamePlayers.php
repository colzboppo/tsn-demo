<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class GamePlayers extends Pivot
{
    public function game(): BelongsTo
    {
        return $this->belongsTo(Games::class, 'game_id');
    }
    
    public function player(): BelongsTo
    {
        return $this->belongsTo(Players::class, 'player_id');
    }
}
