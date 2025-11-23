<?php

namespace App\Http\Controllers;

use App\Support\Auth as AuthSupport;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MessageController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        try {
            $user = AuthSupport::user($this->app, $request, true);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $data = $request->request->all();

        if (!isset($data['channel']) || !isset($data['content'])) {
            return new JsonResponse(['error' => 'Missing required fields'], 400);
        }

        $channel = $data['channel'];
        $content = $data['content'];

        if (!in_array($channel, ['presidium', 'all', 'crisis'])) {
            return new JsonResponse(['error' => 'Invalid channel'], 400);
        }

        if (strlen($content) > 1000) {
            return new JsonResponse(['error' => 'Message too long'], 400);
        }

        // TODO: Implement actual message sending logic
        // For now, just log the message
        error_log("Message sent by user {$user['id']} ({$user['name']}) to channel {$channel}: {$content}");

        return new JsonResponse([
            'success' => true,
            'message' => 'Message sent successfully'
        ]);
    }
}