<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

final class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $id, string $email, string $name = 'Google User'): void
    {
        Config::set('services.google', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect' => null,
        ]);

        $socialiteUser = (new SocialiteUser)->setRaw([])->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'avatar' => 'https://example.com/avatar.png',
        ]);

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('userFromToken')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    /**
     * BR-16: new google_id, no matching email -> create new user, password null,
     * email_verified_at set.
     */
    public function test_br16_creates_new_user_when_no_existing_account(): void
    {
        $this->fakeGoogleUser('google-sub-1', 'newuser@example.com');

        $response = $this->postJson('/api/v1/auth/google', ['access_token' => 'fake-token']);

        $response->assertStatus(200)->assertJsonPath('data.user.email', 'newuser@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'google_id' => 'google-sub-1',
            'password' => null,
        ]);

        // email_verified_at isn't in User's #[Fillable] (must never be mass
        // assignable), so a regression that passes it through User::create()
        // instead of markEmailAsVerified() would silently leave it null —
        // assertDatabaseHas above can't catch that (it doesn't assert the
        // column at all), so check it explicitly.
        $user = User::where('email', 'newuser@example.com')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);

        // Prove it end-to-end: the token from this response must actually
        // pass the `verified` middleware, not just have a non-null column.
        $token = $response->json('data.token');
        $this->withToken($token)->getJson('/api/v1/sectors')->assertOk();
    }

    /**
     * BR-16: register manual dulu, lalu login Google email sama -> ter-link,
     * bukan duplikat.
     */
    public function test_br16_links_existing_manual_account_by_email(): void
    {
        $existing = User::factory()->create([
            'email' => 'manual@example.com',
            'google_id' => null,
        ]);

        $this->fakeGoogleUser('google-sub-2', 'manual@example.com');

        $response = $this->postJson('/api/v1/auth/google', ['access_token' => 'fake-token']);

        $response->assertStatus(200)->assertJsonPath('data.user.id', $existing->id);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'id' => $existing->id,
            'google_id' => 'google-sub-2',
        ]);
    }
}
