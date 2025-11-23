<?php

namespace App\Support;

use App\Application;
use App\Exceptions\HttpException;
use App\Models\User;
use Symfony\Component\HttpFoundation\Request;

class Auth
{
    public static function extractToken(Application $app, Request $request): ?string
    {
        $authHeader = (string) $request->headers->get('Authorization');
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = trim(substr($authHeader, 7));
            if ($token !== '') {
                return $token;
            }
        }

        $cookieName = (string) $app->config('session.cookie', 'mun_session');
        $cookieToken = $request->cookies->get($cookieName);
        if (is_string($cookieToken) && $cookieToken !== '') {
            return $cookieToken;
        }

        if ($request->getContentTypeFormat() === 'json') {
            $payload = json_decode($request->getContent(), true);
            if (is_array($payload) && isset($payload['token']) && is_string($payload['token'])) {
                $fallback = trim($payload['token']);
                if ($fallback !== '') {
                    return $fallback;
                }
            }
        }

        return null;
    }

    public static function user(Application $app, Request $request, bool $require = true): ?User
    {
        $token = self::extractToken($app, $request);
        if (!$token) {
            if ($require) {
                throw new HttpException('Authentication token is required', 401);
            }
            return null;
        }
        $user = User::query()->where('session_token', $token)->first();
        if (!$user && $require) {
            throw new HttpException('Invalid or expired session token', 401);
        }
        return $user;
    }

    public static function requireAdmin(Application $app, Request $request): User
    {
        $user = self::user($app, $request, true);
        if ($user->role !== 'admin') {
            throw new HttpException('Administrator privileges are required for this action', 403);
        }
        return $user;
    }

    public static function requirePresidium(Application $app, Request $request): User
    {
        $user = self::user($app, $request, true);
        if (in_array($user->role, ['admin', 'dais'], true)) {
            return $user;
        }
        $permissions = $user->effectivePermissions();
        if (!in_array('presidium:manage', $permissions, true)) {
            throw new HttpException('Presidium privileges are required for this action', 403);
        }
        return $user;
    }
}
