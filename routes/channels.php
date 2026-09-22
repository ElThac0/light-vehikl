<?php

use App\Exceptions\GameNotFound;
use App\Exceptions\PlayerNotInGame;
use App\GameObjects\GameState;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Presence channel used to notice when a game's players disconnect.
Broadcast::channel('game.{id}', function ($user, string $id) {
    $playerId = $user->getAuthIdentifier();

    try {
        GameState::mutate($id, fn (GameState $game) => $game->trackPresence($playerId));
    } catch (GameNotFound|PlayerNotInGame) {
        return false;
    }

    return ['id' => $playerId];
});
