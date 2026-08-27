<?php

namespace App\Traits;

use App\Exceptions\GameNotFound;
use App\GameObjects\GameState;
use Closure;
use Illuminate\Support\Facades\Cache;

trait PersistInCache
{
    protected string $id;

    /**
     * How long a held lock survives if the process holding it dies (seconds).
     */
    protected static int $lockTtl = 10;

    /**
     * How long to wait to acquire a lock before giving up (seconds).
     */
    protected static int $lockWait = 5;

    public static function find($id): ?GameState
    {
        return Cache::get('game-'.$id);
    }

    /**
     * Run a load-mutate-save against a single game while holding an exclusive
     * lock on it, so concurrent ticks / moves / joins can't clobber each other.
     *
     * The freshly loaded GameState is passed to the callback and whatever the
     * callback leaves it in is persisted once the callback returns. If the game
     * doesn't exist a GameNotFound is thrown before the callback runs; if the
     * callback itself throws, nothing is saved.
     *
     * @template T
     *
     * @param  Closure(GameState): T  $callback
     * @return T
     *
     * @throws GameNotFound
     */
    public static function mutate(string $id, Closure $callback): mixed
    {
        return Cache::lock('game-'.$id.'-lock', static::$lockTtl)->block(static::$lockWait, function () use ($id, $callback) {
            $game = static::find($id) ?? throw GameNotFound::for($id);

            $result = $callback($game);

            $game->save();

            return $result;
        });
    }

    /**
     * @throws
     */
    public function save(): void
    {
        cache()->set('game-'.$this->id, $this);
    }

    /**
     * Add this game to the list of active games the tick loop walks.
     */
    public function track(): void
    {
        Cache::lock('game_list-lock', static::$lockTtl)->block(static::$lockWait, function () {
            $gameList = cache()->get('game_list', []);
            $gameList[] = $this->getId();
            cache()->put('game_list', array_values(array_unique($gameList)));
        });
    }

    /**
     * Remove a game from the active list under lock.
     */
    public static function forget(string $id): void
    {
        Cache::lock('game_list-lock', static::$lockTtl)->block(static::$lockWait, function () use ($id) {
            $gameList = cache()->get('game_list', []);
            cache()->put('game_list', array_values(array_diff($gameList, [$id])));
        });

        cache()->forget('game-'.$id);
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
