<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'date_of_birth' => '1995-05-05',
            'password' => 'password123',
        ]);

        // No token here on purpose: the account isn't usable until the OTP
        // just emailed to it is confirmed via /auth/verify-email.
        $response->assertStatus(201)
            ->assertJsonPath('data.user.email', 'budi@example.com')
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email']]])
            ->assertJsonMissingPath('data.token');

        $this->assertDatabaseHas('users', ['email' => 'budi@example.com', 'email_verified_at' => null]);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dupe@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Dupe',
            'email' => 'dupe@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }
}
