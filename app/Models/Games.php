<?php

namespace App\Models;

use App\Enums\GameStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Games extends Model
{
    protected $casts = [
        'finished_at' => 'datetime',
        'started_at' => 'datetime',
    ];

    protected $appends = [
        'started',
        'finished',
    ];

    public const MAX_PLAYERS = 2, MIN_PLAYERS = 2;

    public function getStartedAttribute(): bool
    {
        return !empty($this->attributes['started_at']);
    }

    public function getFinishedAttribute(): bool
    {
        return !empty($this->attributes['finished_at']);
    }

    public function getStatusAttribute(): string
    {
        return $this->finished ? GameStatus::FINISHED->value : ($this->started ? GameStatus::IN_PROGRESS->value : GameStatus::STARTING->value);
    }

    public function getIsDrawAttribute(): bool
    {
        return $this->finished_at && !$this->winner_id;
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Players::class, 'winner_id',);
    }

    public function game_players(): HasMany
    {
        return $this->hasMany(GamePlayers::class, 'game_id');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Players::class, 'game_players', 'game_id', 'player_id')->using(GamePlayers::class);
    }

    public function moves(): HasMany
    {
        return $this->hasMany(Moves::class, 'game_id', 'id');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->whereNull('finished_at')->whereNotNull('started_at');
    }

    public function scopeStarting(Builder $query): Builder
    {
        return $query->whereNull('started_at')->whereNull('finished_at');
    }

    public function isStarting(): bool
    {
        return self::starting()->where($this->getKeyName(), $this->getKey())->exists();
    }

    public function isInProgress(): bool
    {
        return self::inProgress()->where($this->getKeyName(), $this->getKey())->exists();
    }

    public function startGame()
    {
        $this->started_at = now();
        $this->save();

        Log::info("Game started..");

        return $this;
    }

    public function winGame(Moves $move)
    {
        $this->finished_at = now();
        $this->winner_id = $move->player_id;
        $this->save();

        Log::info("Game Won!! winning player {$move->player->name}");

        return $this;
    }

    public function drawGame()
    {
        $this->finished_at = now();
        $this->save();

        Log::info("Game drawn!!");

        return $this;
    }

    public function isGameStarting(): bool
    {
        return self::starting()->where($this->getKeyName(), $this->getKey())->exists();
    }

    public function isGameFull(): bool
    {
        return $this->players()->count() >= self::MAX_PLAYERS;
    }

    public function nextMovePlayer(): Players
    {
        $last_move = $this->moves->last();

        return $last_move ? $this->players()->where('players.id', '!=', $last_move->player_id)->first() : $this->players->first();
    }

    public function isValidMove(array $move): bool
    {        
        return !$this->moves->contains(fn ($existingMove) => $existingMove->move[0] === $move[0] && $existingMove->move[1] === $move[1]);
    }

    /**
     * checks game moves to see if win condition met
     * eg. $board => new Board($size=3,$ratio=1)
     */
    public function checkGameFinished()
    {
        $board = $this->getGameBoard();

        $moves = $this->moves()->orderBy('id','ASC')->get();

        $moves->each(function(Moves $move) use (&$board, &$winning_move) {
            // assuming asc orderand  each move is [1-3,1-3]
            // from first move check for first win condition 
            $board[$move->move[0]][$move->move[1]] = $move->player_id;

            for ($i=1; $i < 3; $i++) {
                if (
                    (!is_null($board[$i][1]) && $board[$i][1] === $board[$i][2] && $board[$i][2] === $board[$i][3])     // HORIZONTAL
                    || (!is_null($board[1][$i]) && $board[1][$i] === $board[2][$i] && $board[2][$i] === $board[3][$i])  // VERTICAL
                    || (!is_null($board[1][1]) && $board[1][1] === $board[2][2] && $board[2][2] === $board[3][3])       // DIAGONAL
                    || (!is_null($board[3][1]) && $board[3][1] === $board[2][2] && $board[3][1] === $board[1][3])       // DIAGONAL
                ) {
                    // assume current move is winning move
                    $winning_move = $move;
                }
            }
        });


        if ($winning_move) {
            Log::debug('WINNING Board:', $board);
            $this->winGame($winning_move);
        } else if (!$winning_move && $moves->count() >= count($board[1]) * count($board)) {
            Log::debug('DRAWN Board:', $board);
            $this->drawGame();
        }

        return $this->refresh();
    }

    /**
     * makes tic-tac-toe board
     * TODO: refac board/rules into playable interface / abstract class parent to allow other game types
     */
    protected function getGameBoard()
    {
        return array_fill(1, 3, array_fill(1, 3, null));
    }
}
