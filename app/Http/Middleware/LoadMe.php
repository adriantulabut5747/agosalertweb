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
            // Waiting to be deleted: only a fresh login (which asks "Keep
            // your account?") brings it back, never an old session.
            if ($me?->deletion_requested_at) {
                $request->session()->forget('user_id');
                $me = null;
            }
        }
        $request->attributes->set('me', $me);
        View::share('me', $me);
        // The bell's sheet (notifications.dart): every alert, newest first, and
        // which ones this person has opened: alert_reads when logged in, the
        // session for guests (the app keeps a guest's marks in memory).
        $alerts = rescue(fn () => \App\Models\Alert::latest('created_at')->get(), collect(), false);
        $read = $me
            ? rescue(fn () => \Illuminate\Support\Facades\DB::connection('supabase')->table('alert_reads')
                ->where('user_id', $me->user_id)->pluck('alert_id')->map(fn ($id) => (int) $id)->all(), [], false)
            : $request->session()->get('read_alerts', []);
        View::share('alerts', $alerts);
        View::share('readAlerts', $read);
        View::share('alertCount', $alerts->whereNotIn('alert_id', $read)->count());

        return $next($request);
    }
}
