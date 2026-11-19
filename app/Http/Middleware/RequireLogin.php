<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pages for logged-in users only (FR-G10: guests get sent to log in). */
class RequireLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->attributes->get('me')) {
            return redirect()->guest(route('login'))
                ->with('error', 'Log in first. Guests can only look around.');
        }

        return $next($request);
    }
}
