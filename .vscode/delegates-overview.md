# Delegate management API overview

- Delegates are persisted in the `Delegates` table (FK to `Users` + `Committees`) with fields `user_id`, `committee_id`, `country`, and `veto_allowed`.
- `GET /api/delegates` requires presidium access and supports `committeeId`, `committeeCode`, `userId`, `search`, `page`, `pageSize` query params. Response: `{ items, total, page, pageSize }` where each item includes user/committee metadata plus `country` and `vetoAllowed`.
- `POST /api/delegates` upserts a record. Provide `{ userId, committeeId, country, vetoAllowed? }` to create; include `id` (and optional fields) to update.
- `POST /api/delegates/import` ingests a CSV with headers `userEmail,committeeCode,country,vetoAllowed`, updating existing assignments or creating new ones. Response mirrors other import endpoints: `{ created, updated, errors[] }`.
- `GET /api/delegates/export` streams an import-compatible CSV (same headers) and respects the same filters as the list endpoint.
- `GET /api/venues/{committeeId}/delegate` returns all delegates for a single committee along with a `{ id, name, code }` committee summary. Frontend uses this to populate the right-hand panel of `DelegateManagementView.vue`.
