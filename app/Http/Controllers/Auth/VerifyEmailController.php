<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || $user->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
        }

        if (! $request->hasValidSignature()) {
            return redirect()->intended(RouteServiceProvider::HOME.'?verified=0');
        }

        $expectedHash = hash_hmac('sha256', (string) $user->getEmailForVerification(), config('app.key'));

        if (! hash_equals($expectedHash, (string) $request->route('hash'))) {
            return redirect()->intended(RouteServiceProvider::HOME.'?verified=0');
        }

        if ($user->markEmailAsVerified()) {
            Event::dispatch(new Verified($user));
        }

        return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
    }
}
