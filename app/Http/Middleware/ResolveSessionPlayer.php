<?php

namespace App\Http\Middleware;

use App\Support\SessionPlayer;
use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Players aren't user accounts - they're identified by their session's
 * player id. Expose that as the request's user so channel authorization (which
 * requires a user for private and presence channels) can identify them.
 */
class ResolveSessionPlayer
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->setUserResolver(fn () => new GenericUser(['id' => SessionPlayer::id($request->session())]));

        return $next($request);
    }
}
