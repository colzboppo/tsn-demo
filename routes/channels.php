<?php

use App\Models\Games;
use App\Services\GameLobby;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
 
Broadcast::channel('game.{id}', function ($playerid, $gameid) {
    return Games::where('id', $gameid)->whereHas('players', fn ($q) => $q->where('player_id', $playerid))->exists();
});
 
Broadcast::channel('lobby', function ($playerid) {
    return app(GameLobby::class)->getPlayer()->getKey() === $playerid;
});
