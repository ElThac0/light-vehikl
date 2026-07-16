# Light-Vehikl

Light-Vehikl is a real-time multiplayer "light cycle" / Tron-style grid game built on Laravel 11 and Inertia (Vue 3). Players join a game, race around an arena leaving a trail behind them, and crash if they hit a wall or a trail — last rider standing wins. Game state updates are pushed to every connected client over WebSockets via Laravel Reverb.

## How it works

- Players create or join a game, get placed on a grid arena, and move with WASD input.
- Every tick, each active player (and any bots in the game) advances one tile in their current direction, leaving a trail tile behind them.
- A player crashes when they run into a wall or any trail. A game ends once one or zero players remain uncrashed.
- Bots can be added to a game and choose moves based on simple personality strategies.
- All state changes are broadcast in real time so every player's board stays in sync without polling.

The core game domain objects (`Arena`, `Player`, `Bot`, `Tile`, enums for direction/status, etc.) live in a separate package, [`light-vehikl/lvobjects`](https://github.com/light-vehikl), pulled in via Composer rather than kept in this repo.

## Getting started

### Requirements

- PHP with Composer
- Node.js with npm
- A configured `.env` (copy `.env.example`)

### Install dependencies

```bash
composer install
npm install
```

### Run the app locally

Running the game locally requires three processes running concurrently:

```bash
php artisan serve       # the Laravel app
npm run dev              # Vite/Inertia asset bundling with hot reload
php artisan reverb:start # WebSocket server for broadcasting game updates
```

### Tests

```bash
./vendor/bin/pest
```

### Formatting

```bash
./vendor/bin/pint
```

## Tech stack

- [Laravel 11](https://laravel.com) for the backend, HTTP layer, and game tick scheduling
- [Laravel Reverb](https://reverb.laravel.com) for WebSocket broadcasting
- [Inertia.js](https://inertiajs.com) + [Vue 3](https://vuejs.org) for the frontend SPA
- [Laravel Octane](https://laravel.com/docs/octane) for performance-sensitive game-tick handling

## License

The Light-Vehikl application code is proprietary. It is built on the [Laravel framework](https://laravel.com), which is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).