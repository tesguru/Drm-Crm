<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GmailAccount;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    // ============================================================
    // MAIN LOGIN
    // ============================================================
    public function redirect()
    {
        session(['google_intent' => 'login']);

        return Socialite::driver('google')
            ->with([
                'access_type' => 'offline',
                'prompt'      => 'consent',
            ])
            ->scopes([
                'https://www.googleapis.com/auth/gmail.send',
                'https://www.googleapis.com/auth/gmail.modify',
                'https://www.googleapis.com/auth/gmail.labels',
                'https://www.googleapis.com/auth/gmail.readonly',
            ])
            ->redirect();
    }

    // ============================================================
    // SINGLE CALLBACK — handles both login and add account
    // ============================================================
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $intent     = session('google_intent', 'login');

            Log::info('Google callback', [
                'intent' => $intent,
                'email'  => $googleUser->getEmail()
            ]);

            session()->forget('google_intent');

            // ====================================================
            // ADD ACCOUNT INTENT
            // ====================================================
            if ($intent === 'add_account') {

                if (!Auth::check()) {
                    return redirect()->route('login')
                        ->with('error', 'Please login first.');
                }

                $existing = GmailAccount::where(
                    'email',
                    $googleUser->getEmail()
                )->first();

                if ($existing) {
                    // Refresh token
                    $existing->update([
                        'google_token' => json_encode([
                            'access_token' => $googleUser->token,
                            'expires_in'   => 3600,
                            'created'      => time(),
                        ]),
                        'google_refresh_token' =>
                            $googleUser->refreshToken
                            ?? $existing->google_refresh_token,
                        'name'   => $googleUser->getName(),
                        'avatar' => $googleUser->getAvatar(),
                    ]);

                    return redirect()
                        ->route('gmail-accounts.index')
                        ->with('success',
                            '✅ Token refreshed for '
                            . $googleUser->getEmail()
                        );
                }

                // Create new account
                GmailAccount::create([
                    'user_id' => Auth::id(),
                    'email'   => $googleUser->getEmail(),
                    'name'    => $googleUser->getName(),
                    'avatar'  => $googleUser->getAvatar(),
                    'google_token' => json_encode([
                        'access_token' => $googleUser->token,
                        'expires_in'   => 3600,
                        'created'      => time(),
                    ]),
                    'google_refresh_token' =>
                        $googleUser->refreshToken,
                    'daily_limit' => 50,
                    'is_active'   => true,
                ]);

                return redirect()
                    ->route('gmail-accounts.index')
                    ->with('success',
                        '✅ '
                        . $googleUser->getEmail()
                        . ' connected successfully!'
                    );
            }

            // ====================================================
            // LOGIN INTENT
            // ====================================================
            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name'      => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'avatar'    => $googleUser->getAvatar(),
                    'google_token' => json_encode([
                        'access_token' => $googleUser->token,
                        'expires_in'   => 3600,
                        'created'      => time(),
                    ]),
                    'google_refresh_token' =>
                        $googleUser->refreshToken,
                ]
            );

            Auth::login($user, true);

            return redirect()
                ->route('dashboard')
                ->with('success', 'Welcome back!');

        } catch (\Exception $e) {
            Log::error('Google callback error', [
                'error' => $e->getMessage(),
            ]);

            if (Auth::check()) {
                return redirect()
                    ->route('gmail-accounts.index')
                    ->with('error',
                        'Failed: ' . $e->getMessage());
            }

            return redirect()
                ->route('login')
                ->with('error',
                    'Login failed: ' . $e->getMessage());
        }
    }

    // ============================================================
    // ADD GMAIL ACCOUNT — sets intent then redirects to OAuth
    // ============================================================
    public function redirectAccount()
    {
        session(['google_intent' => 'add_account']);

        return Socialite::driver('google')
            ->with([
                'access_type' => 'offline',
                'prompt'      => 'consent',
            ])
            ->scopes([
                'https://www.googleapis.com/auth/gmail.send',
                'https://www.googleapis.com/auth/gmail.modify',
                'https://www.googleapis.com/auth/gmail.labels',
                'https://www.googleapis.com/auth/gmail.readonly',
            ])
            ->redirect();
    }

    // ============================================================
    // LOGOUT
    // ============================================================
    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Logged out!');
    }
}