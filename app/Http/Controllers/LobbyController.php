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

        $lobby = $this->lobby->getPlayers();

        $currentGame = $player->currentGame;

        $startingGames = Games::starting()->get();

        return view('lobby', compact('player', 'lobby'));
    }

    public function create_player(Request $request)
    {
        $player = $this->lobby->addPlayer($request->input('name'));

        return redirect('/join/' . $player->getKey());
    }

    public function get_players()
    {
        return response()->json(['players' => $this->lobby->getPlayers()]);
    }
}
