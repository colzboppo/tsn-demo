<?php

namespace Tests\Feature;

use App\Exceptions\GameLobbyException;
use App\Models\Games;
use App\Services\GameLobby;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

class GamesTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected GameLobby $lobby;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lobby = app(GameLobby::class);
        
        // Flush the Redis database before each test
        Redis::connection()->flushdb();
    }

    protected function addPlayers(int $count = 1, $session=null)
    {
        return collect(range(1, $count))->map(function () use ($session) {

            return $this->lobby->addPlayer($this->faker->name, $session);
        });
    }

    protected function getValidMove(Games $game): array
    {
        do {
            $move = [$this->faker->randomElement([1,2,3]), $this->faker->randomElement([1,2,3])];

            if ($game->isValidMove($move)) return $move;

        } while ($game->isInProgress());
    }

    public function test_can_create_player(): void
    {
        session()->setId($this->faker->uuid());

        $response = $this->post('/player', ['name' => $this->faker->name]);

        $response->assertRedirect('/join/1');
    }
    
    public function test_can_join_lobby(): void
    {
        $session = session()->getId();
        $player = $this->addPlayers(1, $session)->first();

        /** @var Response $response */
        $response = $this->withCookie(config('session.cookie'), $session)->put('/join/'.$player->getKey());

        $response->assertStatus(200);
    }

    public function test_can_create_game(): void
    {
        $player = $this->addPlayers()->first();

        $response = $this->post('/game/'.$player->getKey());

        $response->assertStatus(201);
    }

    public function test_can_join_game(): void
    {
        $players = $this->addPlayers(2);

        $game = $this->lobby->playerStartGame($players->first());

        $response = $this->put("/game/{$game->getKey()}/{$players->last()->getKey()}");

        $response->assertStatus(200);
    }

    public function test_can_start_game(): void
    {
        $players = $this->addPlayers(2);
        $game = $this->lobby->playerStartGame($players->first());
        $this->lobby->playerJoinGame($game, $players->last());

        $this->assertTrue($game->isInProgress());
    }

    /**
     * play a quickly won game
     */
    #[Group("win_game")]
    #[Group("play_game")]
    public function test_can_win_game(): void
    {
        $players = $this->addPlayers(2);
        $game = $this->lobby->playerStartGame($players->first());
        $this->lobby->playerJoinGame($game, $players->last());

        $this->assertTrue($game->isInProgress());
        
        collect(range(1,3))->each(function ($i) use ($game) {
            $this->lobby->playerMoveGame($game, $game->players->first(), [$i, $i]); // winning moves
            if ($game->isInProgress()) $this->lobby->playerMoveGame($game, $game->players->last(), [$i===3?1:2, $i===2?3:$i]); // losing moves
        });

        $this->assertTrue($game->finished);

        $this->assertTrue($game->winner->getKey() === $players->first()->getKey());
    }

    /**
     * play a series of random moves and test for win/draw
     */
    #[Group("random_game")]
    #[Group("play_game")]
    public function test_random_game(): void
    {
        $players = $this->addPlayers(2);
        $game = $this->lobby->playerStartGame($players->first());
        $this->lobby->playerJoinGame($game, $players->last());

        $this->assertTrue($game->isInProgress());
        
        collect(range(1,5))->each(function ($i) use ($game) {
            if ($game->isInProgress()) $this->lobby->playerMoveGame($game, $game->players->first(), $this->getValidMove($game));
            if ($game->isInProgress()) $this->lobby->playerMoveGame($game, $game->players->last(), $this->getValidMove($game));
        });

        $this->assertTrue($game->finished);

        if (!$game->winner) {
            $this->assertTrue($game->isDraw);   
        }
    }

    /**
     * play one game, invalid move order
     */
    #[Group("invalid_game")]
    #[Group("play_game")]
    public function test_game_invalid_moves_order(): void
    {
        $players = $this->addPlayers(2);

        $game = $this->lobby->playerStartGame($players->first());
        $this->lobby->playerJoinGame($game, $players->last());

        $this->assertTrue($game->isInProgress());

        // first move
        $this->lobby->playerMoveGame($game, $game->players->first(), $this->getValidMove($game));

        // test wrong player (first player again) moves
        $this->expectException(GameLobbyException::class);
        $this->lobby->playerMoveGame($game, $game->players->first(), $this->getValidMove($game));
    }

    /**
     * play one game, invalid player move
     */
    #[Group("invalid_game")]
    #[Group("play_game")]
    public function test_game_invalid_player_move(): void
    {
        $players = $this->addPlayers(2);

        $game = $this->lobby->playerStartGame($players->first());
        $this->lobby->playerJoinGame($game, $players->last());

        $this->assertTrue($game->isInProgress());

        // first move
        $this->lobby->playerMoveGame($game, $game->players->first(), $this->getValidMove($game));

        // test player invalid move (first move again) 
        $this->expectException(GameLobbyException::class);
        $this->lobby->playerMoveGame($game, $game->players->last(), $game->moves->first()->move);
    }
    
    /**
     * play one game, invalid move player
     */
    #[Group("invalid_game")]
    #[Group("play_game")]
    public function test_game_invalid_moves_player(): void
    {
        $players = $this->addPlayers(2);

        $game = $this->lobby->playerStartGame($players->first());
        $this->lobby->playerJoinGame($game, $players->last());

        $this->assertTrue($game->isInProgress());

        // first move
        $this->lobby->playerMoveGame($game, $game->players->first(), $this->getValidMove($game));

        // test random player invalid move 
        $this->expectException(GameLobbyException::class);
        $this->lobby->playerMoveGame($game, $this->addPlayers()->first(), $this->getValidMove($game));
    }

    /**
     * add many players, play many games, until one wins...
     */
    #[Group("many_game")]
    #[Group("play_game")]
    public function test_many_games_consecutively(): void
    {
        $players = $this->addPlayers(2);

        $game = $this->lobby->playerStartGame($players->first());
        $this->lobby->playerJoinGame($game, $players->last());

        $this->assertTrue($game->isInProgress());

        $games_played=0;
        while (!$game->winner && $game->isInProgress()) {
            
            collect(range(1,5))->each(function ($i) use ($game) {
                if ($game->isInProgress()) $this->lobby->playerMoveGame($game, $game->players->first(), $this->getValidMove($game));
                if ($game->isInProgress()) $this->lobby->playerMoveGame($game, $game->players->last(), $this->getValidMove($game));
            });

            if ($game->finished) {
                // create new game
                $games_played++;
                if (!$game->winner || $games_played < 10) {
                    $game = $this->lobby->playerStartGame($players->first());
                    $this->lobby->playerJoinGame($game, $players->last());
                }
            }
        }

        $this->assertGreaterThan(1, $games_played);
        $this->assertNotNull($game->winner);
    }
    
    #[Group("many_players")]
    #[Group("play_game")]
    public function test_many_player_games_consecutively(): void
    {
        $players = $this->addPlayers($this->faker->randomElement([2,5,8,15]));

        $game = $this->lobby->playerStartGame($players->random());
        $this->lobby->playerJoinGame($game, $players->reject(fn ($p) => $p->getKey() === $game->players->first()->getKey())->random());

        $this->assertTrue($game->isInProgress());

        $games_played=0;
        while (!$game->winner && $game->isInProgress()) {
            
            collect(range(1,5))->each(function ($i) use ($game) {
                if ($game->isInProgress()) $this->lobby->playerMoveGame($game, $game->players->first(), $this->getValidMove($game));
                if ($game->isInProgress()) $this->lobby->playerMoveGame($game, $game->players->last(), $this->getValidMove($game));
            });

            if ($game->finished) {
                // create new game (new players)
                $games_played++;
                // dump('game over', $game->winner ? 'WIN: '.$game->winner->name:'DRAW', $games_played, $game->players->pluck('name'), $game->moves()->where(fn ($q) => $q->when($game->winner, fn ($q) => $q->where('player_id', $game->winner->getKey())))->get()->map(fn ($move) => [$move->player->name => $move->move])->toArray());
                if (!$game->winner) {
                    $game = $this->lobby->playerStartGame($players->random()->first());
                    $this->lobby->playerJoinGame($game, $players->reject(fn ($p) => $p->getKey() === $game->players->first()->getKey())->random());
                }
            }
        }

        $this->assertGreaterThan(0, $games_played);
        $this->assertNotNull($game->winner);
    }

}
