<?php

namespace App\Http\Controllers;

use App\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class Controller
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    protected function json(array $payload, int $status = 200): JsonResponse
    {
        return new JsonResponse($payload, $status);
    }

    /**
     * @return array<string, mixed>
     */
    protected function body(Request $request): array
    {
        // Try to parse body as JSON for any HTTP method. Some clients send JSON with
        // PATCH/PUT and getContentTypeFormat may not always return 'json'. Prefer
        // parsing raw content first and fall back to form parameters.
        $data = json_decode($request->getContent(), true);
        if (is_array($data)) {
            return $data;
        }
        return $request->request->all();
    }
}
