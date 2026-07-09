<?php

namespace App\Traits;

use App\GameObjects\GameState;
use Illuminate\Support\Facades\Cache;

trait PersistInCache
{
    protected string $id;

    public static function find($id): ?GameState
    {
        return Cache::get('game-' . $id);
    }

    /**
     * @throws
     */
    public function save(): void
    {
        cache()->set('game-' . $this->id, $this);
        cache()->remember('game_list', 3600, function () {
            return [];
        });
        $gameList[] = $this->getId();
        cache()->put('game_list', $gameList);
    }

    /**
     * @return GameState[]
     */
    public static function list(): array
    {
        return Cache::remember('game_list', 3600, function () {
            return [];
        });
    }
}
