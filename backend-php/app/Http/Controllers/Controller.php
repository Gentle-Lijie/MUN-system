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
        if ($request->getContentTypeFormat() === 'json') {
            $data = json_decode($request->getContent(), true);
            if (is_array($data)) {
                return $data;
            }
        }
        return $request->request->all();
    }
}
