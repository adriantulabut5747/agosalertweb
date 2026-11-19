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

    /** Plain words for Supabase's error codes (same ones the app maps). */
    private function reason(?array $body): string
    {
        return match ($body['error_code'] ?? $body['code'] ?? null) {
            'invalid_credentials' => 'Wrong email or password.',
            'user_already_exists', 'email_exists' => 'That email already has an account. Log in instead.',
            'weak_password' => 'That password is too easy to guess. Add numbers or symbols.',
            'email_address_invalid', 'validation_failed' => 'That email doesn\'t look right.',
            'over_request_rate_limit', 'over_email_send_rate_limit' => 'Too many tries. Wait a minute, then try again.',
            default => $body['msg'] ?? $body['error_description'] ?? 'Couldn\'t reach the login server. Try again.',
        };
    }
}
