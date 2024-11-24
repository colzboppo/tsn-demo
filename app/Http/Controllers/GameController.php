<?php

namespace App\Http\Controllers;

use App\Models\Games;
use App\Models\Players;
use App\Services\GameLobby;
use Illuminate\Http\Request;

class GameController extends Controller
{
    protected GameLobby $lobby;

    public function __construct(GameLobby $lobby)
    {
        $this->lobby = $lobby;
    }
    public function player_move(Games $game, Players $player, Request $request)
    {
        $this->lobby->playerMoveGame($game, $player, $request->input('move'));
    }

    public function create_game(Players $player)
    {
        return $this->lobby->playerStartGame($player);
    }

    public function join_game(Games $game, Players $player)
    {
        return $this->lobby->playerJoinGame($game, $player);
    }
}
