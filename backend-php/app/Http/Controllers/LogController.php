<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Support\Auth;
use App\Support\DatabaseLogger;
use App\Support\RequestContext;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Auth::requireAdmin($this->app, $request);

        $query = Log::query()->with('actor');

        $actorId = $request->query->get('actorId');
        if ($actorId !== null && $actorId !== '') {
            $query->where('actor_user_id', (int) $actorId);
        }

        $action = $request->query->get('action');
        if (is_string($action) && $action !== '') {
            $query->where('action', strtoupper($action));
        }

        $table = $request->query->get('table');
        if (is_string($table) && $table !== '') {
            $query->where('target_table', $table);
        }

        $start = $request->query->get('start');
        if (is_string($start) && $start !== '') {
            $query->where('timestamp', '>=', Carbon::parse($start));
        }

        $end = $request->query->get('end');
        if (is_string($end) && $end !== '') {
            $query->where('timestamp', '<=', Carbon::parse($end));
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $pageSize = min(100, max(1, (int) $request->query->get('pageSize', 25)));

        $total = (clone $query)->count();
        $items = $query
            ->orderByDesc('timestamp')
            ->orderByDesc('id')
            ->forPage($page, $pageSize)
            ->get()
            ->map(static fn (Log $log) => $log->toApiResponse())
            ->all();

        return $this->json([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = Auth::requireAdmin($this->app, $request);

        $deleted = RequestContext::withoutQueryLogging(static function () {
            return Log::query()->delete();
        });

        DatabaseLogger::logManual('LOG_PURGE', 'Logs', null, [
            'deleted' => $deleted,
            'actor' => $user->id,
        ]);

        return $this->json([
            'deleted' => $deleted,
        ]);
    }
}
