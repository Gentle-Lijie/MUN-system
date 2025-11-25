<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Models\User;
use App\Support\Auth as AuthSupport;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $payload = $this->body($request);
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');

        if ($email === '' || $password === '') {
            throw new HttpException('Email and password are required', 400);
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$user || !$user->checkPassword($password)) {
            throw new HttpException('邮箱或密码不正确', 401);
        }

        $token = $user->issueSessionToken();
        $user->last_login = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $user->save();

        $response = $this->json([
            'token' => $token,
            'user' => $user->toApiResponse(),
        ]);

        $cookieName = (string) $this->app->config('session.cookie', 'mun_session');
        $secure = (bool) $this->app->config('session.secure', false);
        $cookie = Cookie::create($cookieName, $token)
        $domain = (string) $this->app->config('session.domain');
        if ($domain !== '') {
            $cookie = $cookie->withDomain($domain);
        }
            ->withSecure($secure)
            ->withHttpOnly(true)
            ->withPath('/')
            ->withSameSite(Cookie::SAMESITE_LAX)
            ->withExpires((new DateTimeImmutable('+8 hours')));

        $response->headers->setCookie($cookie);
        return $response;
    }

    public function logout(Request $request): JsonResponse
    {
        $user = AuthSupport::user($this->app, $request, false);
        if ($user) {
            $user->clearSessionToken();
            $user->save();
        }

        $response = $this->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);

        $cookieName = (string) $this->app->config('session.cookie', 'mun_session');
        $response->headers->clearCookie($cookieName);
        return $response;
    }

    public function profile(Request $request): JsonResponse
    {
        $user = AuthSupport::user($this->app, $request, true);
        return $this->json($user->toApiResponse());
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = AuthSupport::user($this->app, $request, true);
        $payload = $this->body($request);
        $current = (string) ($payload['currentPassword'] ?? '');
        $newPassword = (string) ($payload['newPassword'] ?? '');

        if ($current === '' || $newPassword === '') {
            throw new HttpException('currentPassword and newPassword are required', 400);
        }
        if (!$user->checkPassword($current)) {
            throw new HttpException('Current password is incorrect', 401);
        }
        if (strlen($newPassword) < 12) {
            throw new HttpException('New password must be at least 12 characters long', 400);
        }

        $user->setPassword($newPassword);
        $user->save();
        return $this->json(['message' => 'Password updated successfully']);
    }
}
