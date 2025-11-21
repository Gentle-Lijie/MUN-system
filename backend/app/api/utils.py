"""Shared helpers for API authentication and authorization."""

from __future__ import annotations

from typing import Any

from flask import request
from flask_restful import abort

from ..models.user import User

SESSION_COOKIE_NAME = 'mun_session'


def extract_token() -> str | None:
    """Return the bearer token from headers, cookies or JSON payload."""

    auth_header = request.headers.get('Authorization', '').strip()
    if auth_header.startswith('Bearer '):
        return auth_header.split(' ', 1)[1].strip() or None
    cookie_token = request.cookies.get(SESSION_COOKIE_NAME)
    if cookie_token:
        return cookie_token.strip()
    if request.is_json:
        body: dict[str, Any] = request.get_json(silent=True) or {}
        fallback = body.get('token')
        if isinstance(fallback, str):
            fallback = fallback.strip()
        return fallback or None
    return None


def get_user_from_request(require: bool = True) -> User | None:
    token = extract_token()
    if not token:
        if require:
            abort(401, message='Authentication token is required')
        return None
    user = User.query.filter_by(session_token=token).first()
    if user is None and require:
        abort(401, message='Invalid or expired session token')
    return user


def require_admin(user: User | None = None) -> User:
    """Ensure the current user exists and is an administrator."""

    current_user = user or get_user_from_request(require=True)
    if current_user.role != 'admin':
        abort(403, message='Administrator privileges are required for this action')
    return current_user
