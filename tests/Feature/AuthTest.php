<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $res = $this->postJson('/api/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertStatus(201)
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $res = $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $res->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create(['email' => 'test@test.com', 'password' => bcrypt('correct')]);

        $this->postJson('/api/auth/login', ['email' => 'test@test.com', 'password' => 'wrong'])
            ->assertStatus(422);
    }

    public function test_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/dashboard')->assertStatus(401);
    }
}
