# User management API overview

Backend `/api/users` endpoints (Flask-RESTful) provide admin-only CRUD helpers used by the Vue User Management view. Key behaviors:

- `GET /api/users` supports `role`, `committee`, `search` query params and returns `{ items, total }`. Role values align with `admin|dais|delegate|observer`; `committee` queries the `organization` column server-side.
- `POST /api/users` creates staff accounts (default password `123456`). Payload accepts `name`, `email`, `role`, and optional `organization` / `phone`.
- `GET /api/users/{id}` fetches a single profile; `POST /api/users/{id}` updates fields or `{ "resetPassword": true }` to revert password to `123456`.
- `POST /api/users/import` ingests CSV (`name,email,role,organization,phone`). `GET /api/users/export` streams a matching CSV to guarantee round-trip compatibility.
- All POST routes plus the list/export endpoints enforce admin auth via the shared `require_admin` helper.

Frontend `UserManagementView.vue` now calls these APIs (with `credentials: 'include'`) for listing, filtering, creating, importing/exporting, and password resets.
