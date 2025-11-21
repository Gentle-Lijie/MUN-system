# Authentication Module Overview

- Implements `/api/auth/login`, `/logout`, `/profile`, `/password` via Flask-RESTful resources (`backend/app/api/auth.py`).
- Uses `User` SQLAlchemy model (`backend/app/models/user.py`) with session tokens stored in `session_token` and reusable permission mapping per role.
- Tokens are returned in JSON and set as an HttpOnly `mun_session` cookie for the Vue frontend; every protected route accepts either bearer headers or the cookie.
- Password changes enforce a minimum length of 12 characters, and login responses always include the user's role plus derived permissions list for UI gating.
- See `backend/json/auth.openapi.json` for an OpenAPI 3 schema of these endpoints.
