<?php

namespace App\Http\Controllers;

use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\HttpFoundation\Request;

class HealthController extends Controller
{
    public function ping(Request $request, array $params = [])
    {
        $timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM);
        return $this->json([
            'status' => 'ok',
            'timestamp' => $timestamp,
        ]);
    }
}
