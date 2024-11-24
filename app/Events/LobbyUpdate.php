<?php

namespace App\Events;

use App\Services\GameLobby;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LobbyUpdate
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $lobby;

    /**
     * Create a new event instance.
     */
    public function __construct()
    {
        $this->lobby = app(GameLobby::class);
    }

    public function broadcastWith(): array
    {
        return [
            'players' => $this->lobby->getPlayers(),
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('lobby'),
        ];
    }
}
