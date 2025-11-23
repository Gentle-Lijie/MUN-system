<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Models\User;
use App\Support\Auth as AuthSupport;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserPermissionController extends Controller
{
    public function show(Request $request, array $params): JsonResponse
    {
        AuthSupport::requireAdmin($this->app, $request);
        $user = $this->findUser((int) $params['userId']);
        return $this->json(['permissions' => $user->effectivePermissions()]);
    }

    public function update(Request $request, array $params): JsonResponse
    {
        AuthSupport::requireAdmin($this->app, $request);
        $user = $this->findUser((int) $params['userId']);
        $payload = $this->body($request);
        $permissions = $payload['permissions'] ?? [];
        if (!is_array($permissions)) {
            throw new HttpException('permissions must be a list', 400);
        }
        foreach ($permissions as $item) {
            if (!is_string($item)) {
                throw new HttpException('permissions must be strings', 400);
            }
        }
        $user->setEffectivePermissions($permissions);
        $user->save();
        return $this->json(['permissions' => $user->effectivePermissions()]);
    }

    private function findUser(int $id): User
    {
        $user = User::query()->find($id);
        if (!$user) {
            throw new HttpException(sprintf('User %d not found', $id), 404);
        }
        return $user;
    }
}
