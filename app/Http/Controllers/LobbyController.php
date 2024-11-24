<?php

namespace App\Http\Controllers;

use App\Models\Games;
use App\Models\Players;
use App\Services\GameLobby;
use Illuminate\Http\Request;

class LobbyController extends Controller
{
    protected GameLobby $lobby;

    public function __construct()
    {
        $this->lobby = app(GameLobby::class);
    }

    public function join_lobby(Players $player)
    {        
        $player = $this->lobby->getPlayer();

        if (!$player) {
            abort(403, "Player does not exist, create one first..");
        }

        $players = $this->lobby->getPlayers();

        $currentGame = $player->currentGame;

        $startingGames = Games::starting()->get();

        return view('lobby', compact('players', 'player', 'currentGame'));
    }

    public function create_player(Request $request)
    {
        $this->lobby->addPlayer($request->input('name'));
    }
}
