<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DelegateController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPermissionController;
use App\Http\Controllers\VenueController;
use App\Routing\Router;

return static function (Router $router): void {
    $router->get('/api/health/ping', [HealthController::class, 'ping']);
    $router->post('/api/auth/login', [AuthController::class, 'login']);
    $router->post('/api/auth/logout', [AuthController::class, 'logout']);
    $router->get('/api/auth/profile', [AuthController::class, 'profile']);
    $router->patch('/api/auth/password', [AuthController::class, 'updatePassword']);

    $router->get('/api/users', [UserController::class, 'index']);
    $router->post('/api/users', [UserController::class, 'store']);
    $router->get('/api/users/{userId:\d+}', [UserController::class, 'show']);
    $router->post('/api/users/{userId:\d+}', [UserController::class, 'update']);
    $router->post('/api/users/import', [UserController::class, 'import']);
    $router->get('/api/users/export', [UserController::class, 'export']);
    $router->get('/api/users/{userId:\d+}/permissions', [UserPermissionController::class, 'show']);
    $router->post('/api/users/{userId:\d+}/permissions', [UserPermissionController::class, 'update']);

    $router->get('/api/delegates', [DelegateController::class, 'index']);
    $router->post('/api/delegates', [DelegateController::class, 'store']);
    $router->post('/api/delegates/import', [DelegateController::class, 'import']);
    $router->get('/api/delegates/export', [DelegateController::class, 'export']);
    $router->get('/api/venues/{committeeId:\d+}/delegate', [DelegateController::class, 'byCommittee']);

    $router->get('/api/venues', [VenueController::class, 'index']);
    $router->post('/api/venues/{venueId:\d+}', [VenueController::class, 'update']);
    $router->post('/api/venues/{venueId:\d+}/sessions', [VenueController::class, 'addSession']);
};
