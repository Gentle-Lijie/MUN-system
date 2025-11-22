# Venue Management Overview

- `GET /api/venues` (GET) lists every committee with dais/time config/session data via `VenueListResource` (`backend/app/api/venues.py`). Response payload is `{ items, total }` where each venue is produced by `Committee.to_dict()`.
- `POST /api/venues/{venueId}` updates committee base info (name/code/location/status/capacity/dais/timeConfig). Validation and persistence live in `VenueDetailResource`, reusing the `Committee` SQLAlchemy model defined in `backend/app/models/committee.py`.
- `POST /api/venues/{venueId}/sessions` appends agenda slots using the `CommitteeSession` model. Sessions capture `topic`, `chair`, `start`, and `durationMinutes`.
- Authorization: all three routes call `require_presidium_access` so admin, dais, or any user with `presidium:manage` permission can manage venues.
- JSON schemas for these endpoints are published at `backend/json/venues.openapi.json`.
