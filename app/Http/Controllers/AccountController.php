<?php

namespace App\Http\Controllers;

use App\Models\AgosModel;
use App\Models\AppUser;
use App\Models\FloodReport;
use App\Services\SupabaseAuth;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

/**
 * Settings > Account, same as the app's (settings_screen.dart +
 * account.dart): name, password, Trash, Delete account. Deleting is
 * Facebook style (supabase/09_trash.sql): a 7-day wait, cancelled by
 * logging in and tapping "Keep my account".
 */
class AccountController extends Controller
{
    public function settings(Request $request)
    {
        $me = $request->attributes->get('me');
        // The user's own reports in the trash (deleted, or past 14 days).
        $trash = rescue(fn () => FloodReport::where('user_id', $me->user_id)->trashed()
            ->latest('created_at')->get(), collect(), false);

        return view('settings', ['trash' => $trash]);
    }

    /** Everyone sees it on your flood reports. */
    public function name(Request $request)
    {
        $data = $request->validate(['full_name' => ['required', 'string', 'max:100']]);
        $name = preg_replace('/\s+/', ' ', trim($data['full_name']));
        $request->attributes->get('me')->update(['full_name' => $name]);

        return back()->with('ok', 'Name saved.');
    }

    /** Checks the current password by logging in with it (as the app does). */
    public function password(Request $request, SupabaseAuth $auth)
    {
        $data = $request->validate([
            'current' => ['required'],
            'password' => ['required', 'min:6', 'confirmed'],
        ], ['password.confirmed' => 'The two new passwords don\'t match.']);
        $me = $request->attributes->get('me');
        try {
            [, $token] = $auth->logIn($me->email, $data['current']);
        } catch (RuntimeException) {
            return back()->with(['error' => 'Your current password is wrong.', 'open' => 'password']);
        }
        try {
            $auth->changePassword($token, $data['password']);
        } catch (RuntimeException $e) {
            return back()->with(['error' => $e->getMessage(), 'open' => 'password']);
        }
        $request->session()->put('supabase_token', $token);

        return back()->with('ok', 'Password changed. Use the new one next time you log in.');
    }

    /** Starts the 7-day wait, then logs out (everywhere) to the login page. */
    public function destroy(Request $request, SupabaseAuth $auth)
    {
        $me = $request->attributes->get('me');
        try {
            AgosModel::asUser($me->user_id, fn ($db) => $db->select('select public.request_account_deletion()'));
        } catch (Throwable $e) {
            return back()->with('error', str_contains($e->getMessage(), 'admin_has_alerts')
                ? 'Admin accounts that posted alerts can\'t be deleted here. Email us (see Privacy Policy).'
                : 'Couldn\'t delete it. Try again.');
        }
        $auth->logOutEverywhere((string) $request->session()->get('supabase_token'));
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $until = now()->addDays(AppUser::DELETE_DAYS)->format('M j');

        return redirect()->route('login')
            ->with('tugak', "Your account goes for good on $until. Changed your mind? Log in before then.");
    }

    /** After logging in to an account waiting to be deleted. */
    public function showKeep(Request $request)
    {
        $user = rescue(fn () => AppUser::find($request->session()->get('pending_user_id')), null, false);
        if (! $user?->deletion_requested_at) {
            return redirect()->route('login');
        }

        return view('auth.keep', ['when' => $user->deletesAt()->format('M j')]);
    }

    public function keep(Request $request)
    {
        $id = $request->session()->pull('pending_user_id');
        abort_unless($id, 403);
        AgosModel::asUser($id, fn ($db) => $db->select('select public.cancel_account_deletion()'));
        $request->session()->put('user_id', $id);

        return redirect()->route('home')->with('ok', 'Welcome back. Your account stays.');
    }

    /** "Log out" on the keep page: the deletion goes ahead. */
    public function leave(Request $request)
    {
        $user = rescue(fn () => AppUser::find($request->session()->get('pending_user_id')), null, false);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $line = $user?->deletesAt() ? 'Logged out. Your account still goes for good on '.$user->deletesAt()->format('M j').'.' : null;

        return redirect()->route('login')->with('tugak', $line);
    }
}
