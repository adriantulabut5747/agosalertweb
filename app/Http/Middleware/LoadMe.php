<?php

namespace App\Http\Middleware;

use App\Models\AppUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * On every page: who is logged in. The session only keeps the Supabase
 * user id (set by AuthController); the `users` row is read fresh, so a
 * suspension or a new name shows at once. Every view gets $me (null for
 * a guest).
 */
class LoadMe
{
    public function handle(Request $request, Closure $next): Response
    {
        $me = null;
        if ($id = $request->session()->get('user_id')) {
            try {
                $me = AppUser::find($id);
            } catch (Throwable) {
                $me = null; // database unreachable: browse as a guest
            }
            if ($me && $me->account_status !== 'active') {
                $request->session()->forget('user_id');
                $request->session()->flash('error', 'This account is suspended. Contact the AgosAlert admin.');
                $me = null;
            }
        }
        $request->attributes->set('me', $me);
        View::share('me', $me);
        // The number on the bell: alerts from the last 7 days.
        View::share('alertCount', rescue(
            fn () => \App\Models\Alert::where('created_at', '>', now()->subDays(7))->count(), 0, false,
        ));

        return $next($request);
    }
}
