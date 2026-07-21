<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email = 'user@example.com', ?string $emailVerifiedAt = null): User
    {
        $user = new User();
        $user->id = 1;
        $user->id_usuario = 1;
        $user->email = $email;
        $user->email_verified_at = $emailVerifiedAt;

        return $user;
    }

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = $this->makeUser();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => hash_hmac('sha256', (string) $user->getEmailForVerification(), config('app.key'))]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class, function (Verified $event) use ($user) {
            return $event->user->getKey() === $user->getKey();
        });
        $this->assertTrue($user->hasVerifiedEmail());
        $response->assertRedirect(RouteServiceProvider::HOME.'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = $this->makeUser();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => hash_hmac('sha256', 'wrong-email', config('app.key'))]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->hasVerifiedEmail());
    }
}
