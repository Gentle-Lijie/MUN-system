"""User management REST resources."""

from __future__ import annotations

import csv
import io
from datetime import datetime

from flask import Response, make_response, request
from flask_restful import Resource, abort
from sqlalchemy import or_

from ..extensions import db
from ..models.user import ROLE_CHOICES, User
from .utils import require_admin

USER_CSV_FIELDS = ['name', 'email', 'role', 'organization', 'phone']


def _normalize_role(raw_role: str | None) -> str | None:
    if not raw_role:
        return None
    normalized = raw_role.strip().lower()
    if normalized == 'chair':
        normalized = 'dais'
    if normalized not in ROLE_CHOICES:
        return None
    return normalized


def _get_user_or_404(user_id: int) -> User:
    user = User.query.get(user_id)
    if user is None:
        abort(404, message=f'User {user_id} not found')
    return user


class UserListResource(Resource):
    """Collection resource for listing and creating users."""

    def get(self) -> tuple[dict[str, object], int]:
        require_admin()
        role_param = _normalize_role(request.args.get('role'))
        committee_param = request.args.get('committee', '')
        search_param = request.args.get('search', '')

        query = User.query
        if role_param:
            query = query.filter(User.role == role_param)
        if committee_param:
            like_value = f"%{committee_param.strip()}%"
            query = query.filter(User.organization.ilike(like_value))
        if search_param:
            like_value = f"%{search_param.strip()}%"
            query = query.filter(
                or_(
                    User.name.ilike(like_value),
                    User.email.ilike(like_value),
                    User.organization.ilike(like_value),
                    User.phone.ilike(like_value),
                )
            )

        users = query.order_by(User.created_at.desc()).all()
        return {'items': [user.to_dict() for user in users], 'total': len(users)}, 200

    def post(self) -> tuple[dict[str, object], int]:
        require_admin()
        payload = request.get_json(silent=True) or {}
        name = str(payload.get('name', '')).strip()
        email = str(payload.get('email', '')).strip().lower()
        role = _normalize_role(payload.get('role'))
        organization_raw = payload.get(
            'organization') or payload.get('committee')
        organization = str(organization_raw).strip(
        ) if organization_raw else None
        phone_raw = payload.get('phone')
        phone = str(phone_raw).strip() if phone_raw else None

        if not name or not email or not role:
            abort(400, message='name, email and role are required')
        if User.query.filter_by(email=email).first():
            abort(409, message='Email already exists')

        user = User(name=name, email=email, role=role,
                    organization=organization, phone=phone)
        user.set_password(User.DEFAULT_PASSWORD)
        # Set default permissions
        if role == 'observer':
            user.permissions = ['observer:read']
        elif role == 'delegate':
            user.permissions = User.ROLE_PERMISSIONS['delegate']
        else:  # admin and dais
            all_perms = set()
            for perms in User.ROLE_PERMISSIONS.values():
                all_perms.update(perms)
            user.permissions = list(all_perms)
        db.session.add(user)
        db.session.commit()
        return user.to_dict(), 201


class UserDetailResource(Resource):
    """Single user resource."""

    def get(self, user_id: int) -> tuple[dict[str, object], int]:
        user = _get_user_or_404(user_id)
        return user.to_dict(), 200

    def post(self, user_id: int) -> tuple[dict[str, object], int]:
        require_admin()
        user = _get_user_or_404(user_id)
        payload = request.get_json(silent=True) or {}

        updatable_fields = {
            'name': str,
            'email': str,
            'role': _normalize_role,
            'organization': str,
            'committee': str,
            'phone': str,
        }
        for field, caster in updatable_fields.items():
            if field not in payload:
                continue
            value = payload[field]
            if value is None:
                continue
            if field == 'role':
                normalized = _normalize_role(value)
                if not normalized:
                    abort(400, message='Unsupported role value')
                user.role = normalized
            elif field == 'committee':
                user.organization = str(value).strip()
            elif field == 'email':
                new_email = str(value).strip().lower()
                if not new_email:
                    abort(400, message='Email cannot be blank')
                if new_email != user.email and User.query.filter_by(email=new_email).first():
                    abort(409, message='Email already exists')
                user.email = new_email
            else:
                setattr(user, field, str(value).strip())

        if payload.get('resetPassword'):
            user.set_password(User.DEFAULT_PASSWORD)

        db.session.commit()
        return user.to_dict(), 200


class UserImportResource(Resource):
    """Bulk import users from CSV."""

    def post(self) -> tuple[dict[str, object], int]:
        require_admin()
        if 'file' not in request.files:
            abort(400, message='CSV file is required under "file" field')
        upload = request.files['file']
        if not upload.filename:
            abort(400, message='Uploaded file must have a filename')

        decoded = upload.stream.read().decode('utf-8-sig')
        if not decoded:
            abort(400, message='Uploaded file is empty')

        reader = csv.DictReader(io.StringIO(decoded))
        header = reader.fieldnames
        if not header:
            abort(400, message='CSV header row is required')
        missing_columns = [col for col in USER_CSV_FIELDS if col not in header]
        if missing_columns:
            abort(
                400, message=f'Missing columns in CSV: {", ".join(missing_columns)}')

        created = 0
        updated = 0
        errors: list[str] = []

        for idx, row in enumerate(reader, start=2):  # account for header row
            name = (row.get('name') or '').strip()
            email = (row.get('email') or '').strip().lower()
            role = _normalize_role(row.get('role'))
            organization = (row.get('organization') or row.get(
                'committee') or '').strip() or None
            phone = (row.get('phone') or '').strip() or None

            if not name or not email or not role:
                errors.append(f'Row {idx}: missing required field')
                continue

            existing = User.query.filter_by(email=email).first()
            if existing:
                existing.name = name
                existing.role = role
                existing.organization = organization
                existing.phone = phone
                updated += 1
            else:
                new_user = User(
                    name=name,
                    email=email,
                    role=role,
                    organization=organization,
                    phone=phone,
                )
                new_user.set_password(User.DEFAULT_PASSWORD)
                db.session.add(new_user)
                created += 1

        db.session.commit()
        return {'created': created, 'updated': updated, 'errors': errors}, 200


class UserExportResource(Resource):
    """Export users to CSV that can be re-imported."""

    def get(self) -> Response:
        require_admin()
        users = User.query.order_by(User.id).all()
        buffer = io.StringIO()
        writer = csv.DictWriter(buffer, fieldnames=USER_CSV_FIELDS)
        writer.writeheader()
        for user in users:
            writer.writerow({
                'name': user.name,
                'email': user.email,
                'role': user.role,
                'organization': user.organization or '',
                'phone': user.phone or '',
            })
        csv_data = buffer.getvalue()
        timestamp = datetime.utcnow().strftime('%Y%m%d_%H%M%S')
        response = make_response(csv_data)
        response.headers['Content-Type'] = 'text/csv; charset=utf-8'
        response.headers['Content-Disposition'] = f'attachment; filename=users_{timestamp}.csv'
        return response


class UserPermissionsResource(Resource):
    """Manage user permissions."""

    def get(self, user_id: int) -> tuple[dict[str, object], int]:
        require_admin()
        user = _get_user_or_404(user_id)
        return {'permissions': user.permissions}, 200

    def post(self, user_id: int) -> tuple[dict[str, object], int]:
        require_admin()
        user = _get_user_or_404(user_id)
        payload = request.get_json(silent=True) or {}
        permissions = payload.get('permissions', [])
        if not isinstance(permissions, list):
            abort(400, message='permissions must be a list')
        # Validate permissions are strings
        if not all(isinstance(p, str) for p in permissions):
            abort(400, message='permissions must be strings')
        try:
            user.permissions = permissions
            print(f"Setting permissions for user {user_id}: {permissions}")
            print(f"User permissions after set: {user.permissions}")
            db.session.flush()
            print(f"After flush, raw field: {user.__dict__.get('permissions')}")
            db.session.commit()
            print(f"Permissions saved for user {user_id}: {user.permissions}")
        except Exception as e:
            print(f"Error saving permissions: {e}")
            abort(500, message='Failed to save permissions')
        return {'permissions': user.permissions}, 200
