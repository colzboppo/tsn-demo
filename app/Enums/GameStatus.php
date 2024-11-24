<?php

namespace App\Enums;

enum GameStatus: string
{
    case STARTING = 'starting';
    case IN_PROGRESS = 'in-progress';
    case FINISHED = 'finished';
}
