<?php

namespace App\Http\Controllers;

use App\Services\SupabaseAuth;
use App\Support\AgosData;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Log in / sign up / log out, through Supabase Auth (same accounts as
 * the Flutter app). The session keeps only the Supabase user id.
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request, SupabaseAuth $auth)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ]);
        try {
            [$id, $token] = $auth->logIn($data['email'], $data['password']);
        } catch (RuntimeException $e) {
            return back()->withInput($request->only('email'))->with('error', $e->getMessage());
        }
        $request->session()->regenerate(); // new session id after login
        $request->session()->put(['user_id' => $id, 'supabase_token' => $token]);

        return redirect()->intended(route('home'))->with('ok', 'Welcome back.');
    }

    public function showRegister()
    {
        return view('auth.register', ['barangays' => AgosData::BARANGAYS]);
    }

    public function register(Request $request, SupabaseAuth $auth)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'min:6', 'confirmed'],
            'barangay' => ['required', Rule::in(AgosData::BARANGAYS)],
        ]);
        try {
            [$id, $token] = $auth->signUp($data['full_name'], $data['email'], $data['password'], $data['barangay']);
        } catch (RuntimeException $e) {
            return back()->withInput($request->except('password', 'password_confirmation'))->with('error', $e->getMessage());
        }
        $request->session()->regenerate();
        $request->session()->put(['user_id' => $id, 'supabase_token' => $token]);

        return redirect()->route('home')->with('ok', 'Account created. Welcome to the watch.');
    }

    public function forgot(Request $request, SupabaseAuth $auth)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $auth->sendReset($data['email']);

        // Same answer whether or not the email has an account.
        return back()->with('ok', "If {$data['email']} has an account, a reset link is on its way. Check Spam too.");
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing'); // back to the landing page, like the app
    }
}
