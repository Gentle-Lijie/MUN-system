<?php

namespace Tests\Feature;

use App\Models\Committee;
use Tests\TestCase;

class VenueTest extends TestCase
{
    public function test_admin_can_create_venue(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $token = 'token-' . uniqid('', true);
        $admin->session_token = $token;
        $admin->save();

        $payload = [
            'code' => 'UNSC',
            'name' => '联合国安理会',
            'venue' => '主会场 A',
            'status' => 'preparation',
            'capacity' => 80,
        ];

        $response = $this->json('POST', '/api/venues', $payload, [], $token);
        $this->assertSame(201, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame($payload['code'], $data['code']);
        $this->assertSame($payload['name'], $data['name']);
        $this->assertSame($payload['venue'], $data['venue']);
        $this->assertSame($payload['capacity'], $data['capacity']);

        $this->assertSame(1, Committee::query()->count());
        $created = Committee::query()->first();
        $this->assertSame($payload['code'], $created->code);
    }

    public function test_duplicate_code_returns_conflict(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $token = 'token-' . uniqid('', true);
        $admin->session_token = $token;
        $admin->save();

        Committee::query()->create([
            'code' => 'GA',
            'name' => '大会',
            'status' => 'preparation',
            'capacity' => 50,
        ]);

        $response = $this->json('POST', '/api/venues', [
            'code' => 'GA',
            'name' => '大会 2',
        ], [], $token);

        $this->assertSame(409, $response->getStatusCode());
    }
}
