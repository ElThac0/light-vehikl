<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Base for game-rule violations. Each subclass carries the HTTP status it should
 * surface as; render() turns it into a JSON response so controllers don't have
 * to translate rule failures into responses themselves.
 */
abstract class GameException extends RuntimeException implements ShouldntReport
{
    protected int $status = 422;

    public function render(Request $request)
    {
        return response()->json($this->getMessage(), $this->status);
    }
}
