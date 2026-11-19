<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin pages (FR-A1). This is the real security for them: the Laravel
 * connection skips Supabase's row level security, so this check is what
 * stops a normal user from changing anything.
 */
class RequireAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $me = $request->attributes->get('me');
        abort_unless($me && $me->isAdmin(), 403, 'Admins only.');

        return $next($request);
    }
}
