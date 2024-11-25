<?php

namespace App\Services;

use App\Enums\PlayerStatus;
use App\Events\GameUpdate;
use App\Events\LobbyUpdate;
use App\Models\Games;
use App\Models\Moves;
use App\Models\Players;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Exceptions\GameLobbyException;

class GameLobby
{
    protected $lobbyKey = 'game_lobby';

    /**
     * Add a player to the lobby.
     */
    public function addPlayer(string $playerName, string $session = null): Players
    {
        $player = Players::firstOrCreate(['name' => $playerName]);

        Redis::hset($this->lobbyKey, $session ?? session()->getId(), json_encode([
            'logged_in_at' => now(),
            'name' => $playerName,
            'status' => PlayerStatus::ONLINE->value,
            'player_id' => $player->getKey(),
        ]));

        $this->broadcastLobbyUpdate();

        return $player;
    }

    /**
     * Remove a player from the lobby.
     */
    public function removePlayer(string $sessionId): void
    {
        Redis::hdel($this->lobbyKey, $sessionId ?? session()->getId());

        $this->broadcastLobbyUpdate();
    }

    /**
     * Get the list of players in the lobby.
     */
    public function getPlayers(): array
    {
        return Redis::hgetall($this->lobbyKey);
    }

    /**
     * gets player from record in lobby
     */
    public function getPlayer(): ?Players
    {
        $player_session_id = optional(Redis::hget($this->lobbyKey, session()->getId()), function ($lobby_session) {
            return json_decode($lobby_session, true)["player_id"];
        });

        return Players::find($player_session_id);
    }

    public function playerStartGame(Players $player): Games
    {
        if (!Players::available()->where($player->getKeyName(), $player->getKey())->exists())
            throw new GameLobbyException("Cannot start new game - Player is already in a game.", 422);

        $game = Games::create();

        $game->players()->save($player);

        return $game;
    }

    /**
     * checks for max joining players / joins them to game / starts game
     */
    public function playerJoinGame(Games $game, Players $player): void
    {
        if ($game->isStarting() && $player->isAvailable() && !$game->isGameFull()) {
            $game->players()->save($player);
            if (Games::MIN_PLAYERS === $game->players()->count()) {
                $game = $game->startGame();
            }
            $this->broadcastGameUpdate($game);

        } else {
            $err = []; // handle / concatenate errors...
            if (!$game->isStarting()) $err[] = "game not ready";
            if (!$player->isAvailable()) $err[] = "player already in-game";
            if ($game->isGameFull()) $err[] = "max players already in-game";

            throw new GameLobbyException(sprintf("Unable to join player {$player->name} to game: %s", implode(' & ',$err)), 422);
        }
    }

    /**
     * plays a move and checks if game outcome is a win
     */
    public function playerMoveGame(Games $game, Players $player, array $move): void
    {
        $game_started = Games::inProgress()->where($game->getKeyName(), $game->getKey())->exists();

        $next_move_player = $game->nextMovePlayer();

        $valid_move = $game->isValidMove($move);

        if (!$game_started) throw new GameLobbyException("Game has not started (or has finished already).", 422);

        if (!$valid_move) throw new GameLobbyException("Invalid move: ".implode(',', $move), 422);

        if ($next_move_player->getKey() !== $player->getKey()) throw new GameLobbyException("Not players turn yet (still {$next_move_player->name}'s turn.)", 403);

        $move = Moves::makeMove($game, $player, $move);

        $game = $game->checkGameFinished();

        $this->broadcastGameUpdate($game, $move);
    }

    public function getPlayerGames(Players $player)
    {
        return Games::where(fn ($q) => $q->inProgress()->whereHas('players', fn ($q) => $q->where('id', $player->getKey())))->orWhere(fn ($q) => $q->starting())->get();
    }

    /**
     * Broadcast the updated lobby to all connected clients.
     */
    protected function broadcastLobbyUpdate(): void
    {
        try {
            broadcast(new LobbyUpdate());
        } catch (\Exception $e) {
            Log::error('Failed to broadcast lobby update: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast the updated game to all connected clients.
     */
    protected function broadcastGameUpdate(Games $game, Moves $move = null): void
    {
        try {
            broadcast(new GameUpdate($game, $move));
        } catch (\Exception $e) {
            Log::error('Failed to broadcast game update: ' . $e->getMessage(), ['game' => $game]);
        }
    }
}
