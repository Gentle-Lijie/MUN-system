# Logs API overview

- Every database query issued by the PHP backend is intercepted via `DatabaseLogger` (see `app/Support/DatabaseLogger.php`).
  - The logger stores SQL verb, target table, bindings, and execution time into the `Logs` table.
  - `RequestContext` (in `app/Support/RequestContext.php`) captures the authenticated user per request so `actor_user_id` is filled automatically.
  - Logging is skipped for the `Logs` table itself and can be muted via `RequestContext::withoutQueryLogging()` for maintenance operations.
- `LogController` exposes two admin-only endpoints:
  - `GET /api/logs` accepts `actorId`, `action`, `table`, `start`, `end`, `page`, `pageSize` query params and returns `{ items, total, page, pageSize }`.
  - `DELETE /api/logs` purges all rows (muting auto logging) and then writes a synthetic `LOG_PURGE` entry describing the deletion count.
- Frontend `LogManagementView.vue` consumes these APIs to filter by operator, time range, or action type, and renders the paginated table with a "clear logs" action.
