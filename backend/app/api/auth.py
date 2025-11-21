"""Authentication-related REST resources."""

from __future__ import annotations

from datetime import datetime
from typing import Any

from flask import current_app, jsonify, request
from flask_restful import Resource, abort

from ..extensions import db
from ..models.user import User
from .utils import SESSION_COOKIE_NAME, get_user_from_request


class AuthLoginResource(Resource):
    """Handle email/password login."""

    def post(self) -> tuple[Any, int]:
        payload = request.get_json(silent=True) or {}
        email = str(payload.get('email', '')).strip().lower()
        password = payload.get('password')

        if not email or not password:
            abort(400, message='Email and password are required')

        user = User.query.filter_by(email=email).first()
        if not user or not user.check_password(str(password)):
            abort(401, message='邮箱或密码不正确')

        token = user.issue_session_token()
        user.last_login = datetime.utcnow()
        db.session.commit()

        response_body = {'token': token, 'user': user.to_dict()}
        response = jsonify(response_body)
        cookie_secure = bool(current_app.config.get(
            'SESSION_COOKIE_SECURE', False))
        response.set_cookie(
            SESSION_COOKIE_NAME,
            token,
            httponly=True,
            secure=cookie_secure,
            samesite='Lax',
            max_age=60 * 60 * 8,
        )
        return response


class AuthLogoutResource(Resource):
    """Terminate current session."""

    def post(self) -> tuple[Any, int]:
        user = get_user_from_request(require=False)
        if user:
            user.clear_session_token()
            db.session.commit()
            response = jsonify(
                {'success': True, 'message': 'Logged out successfully'})
            response.status_code = 200
        else:
            response = jsonify(
                {'success': False, 'message': 'No active session'})
            response.status_code = 401
        response.delete_cookie(SESSION_COOKIE_NAME)
        return response


class AuthProfileResource(Resource):
    """Return current authenticated user profile."""

    def get(self) -> tuple[dict[str, Any], int]:
        user = get_user_from_request(require=True)
        return user.to_dict(), 200


class AuthPasswordResource(Resource):
    """Allow authenticated users to change their password."""

    def patch(self) -> tuple[dict[str, str], int]:
        user = get_user_from_request(require=True)
        payload = request.get_json(silent=True) or {}
        current_password = payload.get('currentPassword')
        new_password = payload.get('newPassword')

        if not current_password or not new_password:
            abort(400, message='currentPassword and newPassword are required')
        if not user.check_password(str(current_password)):
            abort(401, message='Current password is incorrect')
        if len(str(new_password)) < 6:
            abort(400, message='New password must be at least 6 characters long')

        user.set_password(str(new_password))
        db.session.commit()

        return {'message': 'Password updated successfully'}, 200
