# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Laravel 11 + Inertia (Vue 3) app implementing a real-time multiplayer "light cycle" / Tron-style grid game. Players join a game, move around an arena leaving trails, and crash if they hit a wall or trail. Game state updates are pushed to clients over WebSockets (Laravel Reverb).

Core game domain objects (`Arena`, `Player`, `Bot`, `Tile`, `StartLocation`, and enums like `ContentType`, `Direction`, `GameStatus`, `PlayerStatus`) live in an external package, `light-vehikl/lvobjects` (namespace `LightVehikl\LvObjects\...`), pulled from a git repo (see `composer.json` repositories / `composer.lock`), not from this repo. When you need to see how those objects behave, look in `vendor/light-vehikl/lvobjects`.

## Commands

**PHP / backend**
- Install deps: `composer install`
- Run tests: `./vendor/bin/pest` (or `php artisan test`)
- Run a single test file: `./vendor/bin/pest tests/Unit/GameState/GameStateTest.php`
- Run a single test by name: `./vendor/bin/pest --filter="test name or description"`
- Format/lint PHP: `./vendor/bin/pint` (Laravel Pint)
- Tests use Pest with the `array` cache/session driver (see `phpunit.xml`); `RefreshDatabase` is bound only for `Feature` tests.

**JS / frontend**
- Install deps: `npm install`
- Dev server (Vite, hot reload): `npm run dev`
- Production build: `npm run build`

**Running the app locally** typically requires three processes running concurrently: the Laravel app (`php artisan serve` or Octane), `npm run dev` for Vite/Inertia assets, and a Reverb server (`php artisan reverb:start`) for broadcasting game updates. Octane is a dependency (`laravel/octane`) — check `config/octane.php` if working on performance-sensitive game-tick code.

## Architecture

### Game state and the tick loop
`App\GameObjects\GameState` (`app/GameObjects/GameState.php`) is the central mutable model of a single game: arena, players, bots, status, and tick count. It is **not an Eloquent model** — it's a plain PHP object serialized in/out of a store via the `PersistInCache` trait (`app/Traits/PersistInCache.php`), which persists `GameState` instances in the cache (`Cache::get('game-{id}')`) and tracks all active game IDs under the `game_list` cache key. There's also an unused/experimental `PersistInOctane` trait (`app/Traits/PersistInOctane.php`) that persists state into an Octane table instead — not currently wired up.

Game progression ("ticks") happen in two different places, so check both when touching tick logic:
- `routes/console.php` has a `Schedule::call(...)->everySecond()` that walks `game_list`, calls `GameState::find($id)->nextTick()`, and saves.
- `App\Providers\GameServiceProvider` registers an `Octane::tick(...)` callback that does the same thing every second — **but this provider is not currently registered in `bootstrap/providers.php`**, so it does not run. Be aware of this discrepancy if game ticking behavior seems inconsistent with what the code implies.
- `App\Console\Commands\RunGameCommand` (`run:game {gameId}`) is a standalone command that drives a single game to completion in a loop, independent of the schedule.

`GameState::nextTick()` updates bots first (`Personality` classes in `app/GameObjects/Personalities/` decide bot moves), then moves every non-crashed player, checks `shouldEnd()` (game ends when ≤1 player remains uncrashed), and dispatches `App\Events\GameUpdated` after every mutation.

### Real-time updates
Broadcasting uses Reverb (`config/reverb.php`, `config/broadcasting.php`). `App\Broadcasting\GameChannel` defines the per-game channel; `App\Events\GameCreated`, `GameUpdated`, `PlayerJoined` broadcast state changes. Frontend subscribes via `window.Echo` (`resources/js/echo.js`, configured for the `reverb` broadcaster) — see `resources/js/Components/Game/Game.vue` for the channel-join / listen pattern (`GameChannel-{gameId}`, `.game.updated` event).

### HTTP layer
Controllers are single-action invokable classes under `app/Http/Controllers` (e.g. `CreateGame`, `JoinGame`, `GameMove`, `StartGame`, `MarkReady`, `LeaveGame`, `AddBot`, `GetGame`, `GameList`), wired directly to routes in `routes/web.php`. Most game routes are grouped under `Route::withoutMiddleware([ValidateCsrfToken::class])` since the SPA/game client posts moves frequently. Auth scaffolding (`routes/auth.php`, `app/Http/Controllers/Auth/*`) comes from Laravel Breeze.

### Frontend
Inertia + Vue 3 SPA. Pages live in `resources/js/Pages`, shared layout/nav in `resources/js/Layouts`. Game UI is under `resources/js/Components/Game/`:
- `Game.vue` — top-level orchestrator: creates/joins/leaves games, listens for Echo updates, handles WASD keyboard input, posts moves via `axios` + Ziggy `route()` helpers.
- `GameList.vue`, `GameBoard.vue`, `Tile.vue`, `Players.vue` — list of joinable games, the rendered grid, individual tiles, and the player roster, respectively.

Path alias `@/*` maps to `resources/js/*` (see `jsconfig.json`); `ziggy-js` resolves to the vendored Ziggy package for named-route generation on the frontend.

### Testing conventions
- `tests/Unit/GameState/GameStateTest.php` and `tests/Feature/GameStateRulesTest.php` cover game-rule logic directly against `GameState`/`lvobjects` domain objects — prefer this style (constructing a `GameState` and asserting behavior) over HTTP-level tests when testing game rules.
- `tests/Feature/BotTest.php` covers bot personality/movement behavior.