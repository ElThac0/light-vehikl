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

    public function save(): void
    {
        Cache::set('game-' . $this->id, $this);
    }
}
