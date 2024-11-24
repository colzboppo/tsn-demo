<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\LobbyController;
use Illuminate\Support\Facades\Route;

// game routes!! //
Route::get('/', fn () => view('register'));

// create player
Route::post('/player', [LobbyController::class, 'create_player']);

// join lobby
Route::put('/join/{player}', [LobbyController::class, 'join_lobby']);

// create game
Route::post('/game/{player}', [GameController::class, 'create_game']);

// join game
Route::put('/game/{game}/{player}', [GameController::class, 'join_game']);

// play game move
Route::post('/move/{game}/{player}', [GameController::class, 'player_move']);