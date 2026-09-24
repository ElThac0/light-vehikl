<?php

namespace App\Http\Controllers;

use Blaspsoft\Blasp\Facades\Blasp;
use Blaspsoft\Blasp\Rules\Profanity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SetPlayerName extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:20', 'regex:/^[\pL\pN _.-]+$/u', Profanity::make(), $this->noHiddenProfanity(...)],
        ], [
            'name.regex' => 'Names may only contain letters, numbers, spaces, dots, dashes and underscores.',
        ]);

        $request->session()->put('player_name', $validated['name']);

        return response()->json(['name' => $validated['name']]);
    }

    /**
     * Blasp only looks at whole words, so also check the name split at
     * capitals, catching run-together names like "BigFuckingTron".
     */
    protected function noHiddenProfanity(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && Blasp::check(Str::headline($value))->isOffensive()) {
            $fail('The :attribute contains profanity.');
        }
    }
}
