<?php

namespace Tests\Feature;

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

    protected function getValidMove(Games $game): ?array
    {
        do {
            $move = [$this->faker->randomElement([1,2,3]), $this->faker->randomElement([1,2,3])];

            if ($game->isValidMove($move)) return $move;

        } while ($game->isInProgress());

        return null;
    }

    public function test_can_create_player(): void
    {
        session()->setId($this->faker->uuid());

        $response = $this->post('/player', ['name' => $this->faker->name]);

        $response->assertStatus(200);
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

    /**
     * play a quickly won game
     */
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
    public function test_can_win_game(): void
    {
        $players = $this->addPlayers(2);
        $game = $this->lobby->playerStartGame($players->first());
        $this->lobby->playerJoinGame($game, $players->last());

        $this->assertTrue($game->isInProgress());
        
        collect(range(1,3))->each(function ($i) use ($game, $players) {
            $this->lobby->playerMoveGame($game, $players->first(), [$i, $i]);
            if ($game->isInProgress()) $this->lobby->playerMoveGame($game, $players->last(), [$i===3?1:2, $i===2?3:$i]);
        });

        $this->assertTrue($game->finished);

        $this->assertTrue($game->winner->getKey() === $players->first()->getKey());
    }

    /**
     * play a series of random moves and test for win/draw
     */
    public function test_random_game(): void
    {
        $players = $this->addPlayers(2);
        $game = $this->lobby->playerStartGame($players->first());
        $this->lobby->playerJoinGame($game, $players->last());

        $this->assertTrue($game->isInProgress());
        
        collect(range(1,5))->each(function ($i) use ($game, $players) {
            if ($game->isInProgress()) $this->lobby->playerMoveGame($game, $players->first(), $this->getValidMove($game));
            if ($game->isInProgress()) $this->lobby->playerMoveGame($game, $players->last(), $this->getValidMove($game));
        });

        $this->assertTrue($game->finished);

        if (!$game->winner) {
            $this->assertTrue($game->isDraw);   
        }
        
        // isDraw vs. winner ??

    }
}
