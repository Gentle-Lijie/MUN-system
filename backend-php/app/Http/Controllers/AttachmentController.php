<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Support\Auth as AuthSupport;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController extends Controller
{
    public function serve(Request $request, array $params): Response
    {
        // filename may include subpaths; validate carefully
        $rawName = $params['filename'] ?? '';
        if (!is_string($rawName) || $rawName === '') {
            throw new HttpException('Filename is required', 400);
        }

        // normalize and prevent directory traversal
        $rawName = urldecode($rawName);
        $rawName = ltrim($rawName, '/\\');
        $attachmentsDir = realpath($this->app->path('attachments')) ?: $this->app->path('attachments');

        $target = $attachmentsDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rawName);
        $real = realpath($target);
        if ($real === false) {
            // not found
            return $this->json(['message' => 'Not Found', 'errors' => null], 404);
        }

        // ensure resolved path is inside attachments directory to prevent traversal
        $attachmentsReal = realpath($attachmentsDir) ?: $attachmentsDir;
        if (strpos($real, $attachmentsReal) !== 0 || !is_file($real)) {
            // Return JSON Not Found for API consistency
            return $this->json(['message' => 'Not Found', 'errors' => null], 404);
        }
        $mime = 'application/octet-stream';
        if (function_exists('mime_content_type')) {
            $m = mime_content_type($real);
            if ($m) $mime = $m;
        }

        $content = file_get_contents($real);
        $response = new Response($content, 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string) filesize($real),
            'Content-Disposition' => 'inline; filename="' . basename($real) . '"',
        ]);
        return $response;
    }
}
