<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\Request;
use RuntimeException;

class GameNotFound extends RuntimeException implements ShouldntReport
{
    public static function for(string $id): self
    {
        return new self("Game [{$id}] not found.");
    }

    public function render(Request $request)
    {
        return response()->json('Game not found', 404);
    }
}
