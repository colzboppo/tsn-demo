<?php

namespace App\Enums;

enum PlayerStatus: string
{
    case ONLINE = 'online';
    case PLAYING = 'playing';
    case OFFLINE = 'offline';
}
