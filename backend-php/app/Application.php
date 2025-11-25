<?php

namespace App;

use App\Exceptions\HttpException;
use App\Routing\Router;
use App\Support\DatabaseLogger;
use Dotenv\Dotenv;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Application
{
    private string $basePath;

    /** @var array<string, mixed> */
    private array $config = [];

    private Router $router;

    private Capsule $capsule;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->loadEnvironment();
        $this->config = require $this->path('config/app.php');
        $this->bootstrapDatabase();
        $this->router = new Router($this);
        $registrar = require $this->path('routes/api.php');
        if (is_callable($registrar)) {
            $registrar($this->router);
        }
    }

    public function run(): void
    {
        $request = Request::createFromGlobals();
        $response = $this->handle($request);
        $response->send();
    }

    public function handle(Request $request): Response
    {
        try {
            if ($request->getMethod() === 'OPTIONS') {
                $response = new Response('', 204);
                return $this->applyCors($response, $request);
            }

            $response = $this->router->dispatch($request);
            return $this->applyCors($response, $request);
        } catch (HttpException $exception) {
            return $this->applyCors(new JsonResponse([
                'message' => $exception->getMessage(),
                'errors' => $exception->getErrors(),
            ], $exception->getStatusCode()), $request);
        } catch (Throwable $throwable) {
            // Log the error
            error_log('Exception: ' . $throwable->getMessage());
            error_log('File: ' . $throwable->getFile() . ':' . $throwable->getLine());
            error_log('Trace: ' . $throwable->getTraceAsString());
            
            // Always return detailed error in development
            $errorData = [
                'error' => 'Internal Server Error',
                'message' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'trace' => explode("\n", $throwable->getTraceAsString()),
            ];
            
            if ($this->config('app.debug')) {
                return $this->applyCors(new JsonResponse($errorData, 500), $request);
            }
            
            // In production, still show message but not trace
            return $this->applyCors(new JsonResponse([
                'error' => 'Internal Server Error',
                'message' => $throwable->getMessage(),
            ], 500), $request);
        }
    }

    public function config(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->config;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public function capsule(): Capsule
    {
        return $this->capsule;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function path(string $path = ''): string
    {
        return rtrim($this->basePath . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);
    }

    private function loadEnvironment(): void
    {
        $localEnv = $this->path('.env');
        if (file_exists($localEnv)) {
            Dotenv::createImmutable($this->basePath)->safeLoad();
            return;
        }

        $rootEnv = dirname($this->basePath) . DIRECTORY_SEPARATOR . '.env';
        if (file_exists($rootEnv)) {
            Dotenv::createMutable(dirname($this->basePath))->safeLoad();
        }
    }

    private function bootstrapDatabase(): void
    {
        $config = require $this->path('config/database.php');
        $capsule = new Capsule();
        $capsule->addConnection($config);
        $capsule->setEventDispatcher(new Dispatcher(new Container()));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        $this->capsule = $capsule;
        DatabaseLogger::attach($capsule);
    }

    private function applyCors(Response $response, Request $request): Response
    {
        $allowedOrigins = $this->config('cors.origins', []);
        $origin = $request->headers->get('Origin');
        if ($origin if ($origin && (empty($allowedOrigins) || in_array($origin, $allowedOrigins, true))) {if ($origin && (empty($allowedOrigins) || in_array($origin, $allowedOrigins, true))) { (empty($allowedOrigins) || in_array($origin, $allowedOrigins, true) || $origin === "http://106.15.139.140:4173")) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Vary', 'Origin');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        $response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, DELETE, OPTIONS');
        return $response;
    }
}
