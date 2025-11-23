<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_login_requires_credentials(): void
    {
        $response = $this->json('POST', '/api/auth/login', []);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_user_can_login_and_fetch_profile(): void
    {
        $user = $this->createUser([
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => 'SuperSecretPass123',
        ]);

        $response = $this->json('POST', '/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'SuperSecretPass123',
        ]);
        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('token', $payload);
        $token = $payload['token'];

        $profileResponse = $this->json('GET', '/api/auth/profile', [], [], $token);
        $this->assertSame(200, $profileResponse->getStatusCode());
        $profile = json_decode($profileResponse->getContent(), true);
        $this->assertSame($user->email, $profile['email']);
    }
}
