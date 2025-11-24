<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DelegateController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MotionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPermissionController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CrisisController;
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

    $router->get('/api/display/board', [DisplayController::class, 'getBoard']);
    $router->get('/api/display/sessions', [DisplayController::class, 'getSessions']);
    $router->post('/api/display/speakers', [DisplayController::class, 'addSpeaker']);
    $router->post('/api/display/roll-call', [DisplayController::class, 'rollCall']);
    $router->post('/api/display/start-session', [DisplayController::class, 'startSession']);
    $router->post('/api/display/switch-speaker-list', [DisplayController::class, 'switchSpeakerList']);
    $router->post('/api/display/timer/start', [DisplayController::class, 'startTimer']);
    $router->post('/api/display/timer/stop', [DisplayController::class, 'stopTimer']);
    $router->post('/api/display/speaker/next', [DisplayController::class, 'nextSpeaker']);

    $router->post('/api/motions', [MotionController::class, 'create']);
    $router->post('/api/motions/{motionId:\d+}/{listId:\d+}', [MotionController::class, 'updateSpeakerList']);

    $router->get('/api/files/submissions', [FilesController::class, 'getSubmissions']);
    $router->post('/api/files/submissions', [FilesController::class, 'submitFile']);
    $router->patch('/api/files/submissions/{submissionId:\d+}', [FilesController::class, 'updateSubmission']);
    $router->post('/api/files/submissions/{submissionId:\d+}/decision', [FilesController::class, 'decideSubmission']);
    $router->get('/api/files/published', [FilesController::class, 'getPublished']);
    $router->post('/api/files/published', [FilesController::class, 'publishFile']);
    $router->patch('/api/files/published/{fileId:\d+}', [FilesController::class, 'updatePublished']);
    $router->delete('/api/files/published/{fileId:\d+}', [FilesController::class, 'deletePublished']);
    $router->get('/api/files/reference', [FilesController::class, 'getReference']);
    $router->post('/api/files/upload', [FilesController::class, 'uploadFile']);

    $router->get('/api/messages', [MessageController::class, 'index']);
    $router->post('/api/messages', [MessageController::class, 'send']);

    $router->get('/api/crises', [CrisisController::class, 'index']);
    $router->post('/api/crises', [CrisisController::class, 'store']);
    $router->patch('/api/crises/{crisisId:\\d+}', [CrisisController::class, 'update']);
    $router->get('/api/crises/{crisisId:\\d+}/responses', [CrisisController::class, 'responses']);
    $router->get('/api/crises/{crisisId:\\d+}/responses/export', [CrisisController::class, 'exportResponses']);
    $router->post('/api/crises/{crisisId:\\d+}/responses', [CrisisController::class, 'storeResponse']);
    // Serve attachments uploaded to the attachments directory (safe handling in controller)
    $router->get('/attachments/{filename:.+}', [AttachmentController::class, 'serve']);
};
