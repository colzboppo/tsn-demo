<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Moves extends Model
{
    protected $casts = [
        'move' => 'array',
    ];

    protected $fillable = [
        'player_id', 'game_id', 'move'
    ];

    protected function game()
    {
        return $this->belongsTo(Games::class, 'game_id');
    }

    protected function player()
    {
        return $this->belongsTo(Players::class, 'player_id');
    }

    public static function makeMove(Games $game, Players $player, array $move)
    {
        return self::create(["player_id" => $player->getKey(), "game_id" => $game->getKey(), "move" => $move]);
    }
}
