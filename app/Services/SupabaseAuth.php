<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Logging in through Supabase Auth's REST API: the SAME accounts as the
 * Flutter app. Laravel never stores passwords; Supabase checks them and
 * gives back the user's id. The `users` row (name, barangay, role) is
 * made by the database trigger in supabase/01_users.sql.
 */
class SupabaseAuth
{
    private function http()
    {
        return Http::baseUrl(config('services.supabase.url').'/auth/v1')
            ->withHeaders(['apikey' => config('services.supabase.key')])
            ->acceptJson()
            ->timeout(15);
    }

    /**
     * Email + password -> [user id, access token], or an exception with
     * Supabase's reason. The token lets Laravel upload report photos to
     * Supabase Storage as that user.
     */
    public function logIn(string $email, string $password): array
    {
        $res = $this->http()->post('/token?grant_type=password', [
            'email' => $email,
            'password' => $password,
        ]);
        if (! $res->ok()) {
            throw new RuntimeException($this->reason($res->json()));
        }

        return [$res->json('user.id'), $res->json('access_token')];
    }

    /** Makes the account; name + barangay ride along for the trigger. */
    public function signUp(string $name, string $email, string $password, string $barangay): array
    {
        $res = $this->http()->post('/signup', [
            'email' => $email,
            'password' => $password,
            'data' => ['full_name' => $name, 'barangay' => $barangay],
        ]);
        if (! $res->ok()) {
            throw new RuntimeException($this->reason($res->json()));
        }

        // "Confirm email" is off in the dashboard, so a session comes back.
        return [$res->json('user.id'), $res->json('access_token')];
    }

    /** "Forgot password": Supabase emails the reset link (Gmail SMTP). */
    public function sendReset(string $email): void
    {
        $this->http()->post('/recover', ['email' => $email]);
    }

    /** Settings > Password: needs a fresh token (logIn with the current password gives one). */
    public function changePassword(string $token, string $password): void
    {
        $res = $this->http()->withToken($token)->put('/user', ['password' => $password]);
        if (! $res->ok()) {
            throw new RuntimeException($this->reason($res->json()));
        }
    }

    /**
     * Signs this login out on every device (the app's
     * signOut(scope: global)). Best effort: an hour-old token is refused,
     * and the app also drops a saved login of an account being deleted.
     */
    public function logOutEverywhere(string $token): void
    {
        rescue(fn () => $this->http()->withToken($token)->post('/logout?scope=global'), null, false);
    }

    /**
     * Tugak's lines for Supabase's error CODES: tugakAuthLine() in the app
     * (login_screen.dart). Fix first, then a dry joke; none for anything
     * serious (a suspended account gets Supabase's own words).
     */
    private function reason(?array $body): string
    {
        return match ($body['error_code'] ?? $body['code'] ?? null) {
            'invalid_credentials' => "Wrong email or password. I'd let you in, but the door has standards.",
            'user_already_exists', 'email_exists' => "That email already has an account. Log in instead, we've met.",
            'weak_password' => 'That password is too easy to guess. Mix in numbers or symbols, make it work for it.',
            'same_password' => "That's your current password. Pick a new one.",
            'email_address_invalid', 'validation_failed' => "That email doesn't look right. Check for typos, I already squinted.",
            'over_request_rate_limit', 'over_email_send_rate_limit' => "Too many tries in a row. Wait a minute, then try again. I'll hold your spot.",
            'email_not_confirmed', 'confirm_email' => 'Confirm your email first: the link is in your inbox. Then log in here.',
            'signup_disabled' => 'Sign-ups are closed for now. You can still look around as a guest.',
            default => $body['msg'] ?? $body['error_description']
                ?? "I can't reach the server. Check your connection and try again. It's not you, it's the Wi-Fi.",
        };
    }
}
