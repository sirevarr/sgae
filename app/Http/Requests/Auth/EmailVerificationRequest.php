<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Auth\EmailVerificationRequest as BaseEmailVerificationRequest;

class EmailVerificationRequest extends BaseEmailVerificationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user || $user->hasVerifiedEmail()) {
            return false;
        }

        if (! $this->hasValidSignature()) {
            return false;
        }

        return hash_equals(
            hash_hmac('sha256', $user->getEmailForVerification(), config('app.key')),
            (string) $this->route('hash')
        );
    }
}
