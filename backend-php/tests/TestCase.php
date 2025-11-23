<?php

namespace Tests;

use App\Application;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class TestCase extends BaseTestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(dirname(__DIR__));
        $this->runMigrations($this->app->capsule());
        $this->truncateTables();
    }

    protected function json(string $method, string $uri, array $payload = [], array $headers = [], ?string $token = null): Response
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        foreach ($headers as $key => $value) {
            $server[$key] = $value;
        }
        $cookies = [];
        if ($token) {
            $cookies[$this->app->config('session.cookie', 'mun_session')] = $token;
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }
        $request = Request::create($uri, $method, [], $cookies, [], $server, json_encode($payload));
        return $this->app->handle($request);
    }

    protected function createUser(array $attributes = []): \App\Models\User
    {
        $user = new \App\Models\User();
        $user->name = $attributes['name'] ?? 'Test User';
        $user->email = $attributes['email'] ?? uniqid('user', true) . '@example.com';
        $user->role = $attributes['role'] ?? 'delegate';
        $user->organization = $attributes['organization'] ?? null;
        $user->phone = $attributes['phone'] ?? null;
        $user->setPassword($attributes['password'] ?? \App\Models\User::DEFAULT_PASSWORD);
        $permissions = $attributes['permissions'] ?? \App\Models\User::defaultPermissions($user->role);
        $user->setEffectivePermissions($permissions);
        $user->save();
        return $user;
    }

    private function runMigrations(Capsule $capsule): void
    {
        static $migrated = false;
        if ($migrated) {
            return;
        }
        foreach (glob(dirname(__DIR__) . '/database/migrations/*.php') as $file) {
            $migration = require $file;
            if (is_callable($migration)) {
                $migration($capsule);
            }
        }
        $migrated = true;
    }

    private function truncateTables(): void
    {
        $connection = $this->app->capsule()->getConnection();
        $connection->statement('PRAGMA foreign_keys = OFF');
        foreach (['Delegates', 'CommitteeSessions', 'Committees', 'Users'] as $table) {
            $connection->table($table)->delete();
        }
        $connection->statement('PRAGMA foreign_keys = ON');
    }
}
