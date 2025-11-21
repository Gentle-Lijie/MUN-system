"""User ORM model and helpers for authentication flows."""

from __future__ import annotations

from datetime import datetime
from secrets import token_hex
from typing import Any

from werkzeug.security import check_password_hash, generate_password_hash

from ..extensions import db

ROLE_CHOICES = ('admin', 'dais', 'delegate')


class User(db.Model):
    __tablename__ = 'users'

    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(255), nullable=False)
    email = db.Column(db.String(255), nullable=False, unique=True, index=True)
    password_hash = db.Column(db.String(255), nullable=False)
    role = db.Column(db.Enum(*ROLE_CHOICES, name='user_roles'), nullable=False)
    organization = db.Column(db.String(255))
    phone = db.Column(db.String(20))
    last_login = db.Column(db.DateTime)
    created_at = db.Column(db.DateTime, nullable=False,
                           default=datetime.utcnow)
    updated_at = db.Column(
        db.DateTime,
        nullable=False,
        default=datetime.utcnow,
        onupdate=datetime.utcnow,
    )
    session_token = db.Column(db.String(255), unique=True)

    ROLE_PERMISSIONS: dict[str, list[str]] = {
        'admin': [
            'users:manage',
            'presidium:manage',
            'delegates:manage',
            'logs:read',
        ],
        'dais': [
            'presidium:manage',
            'timeline:update',
            'crisis:dispatch',
            'messages:broadcast',
        ],
        'delegate': [
            'delegate:self',
            'documents:submit',
            'messages:send',
        ],
    }

    def set_password(self, raw_password: str) -> None:
        self.password_hash = generate_password_hash(raw_password)

    def check_password(self, raw_password: str) -> bool:
        return check_password_hash(self.password_hash, raw_password)

    def issue_session_token(self) -> str:
        self.session_token = token_hex(32)
        return self.session_token

    def clear_session_token(self) -> None:
        self.session_token = None

    def to_dict(self) -> dict[str, Any]:
        return {
            'id': self.id,
            'name': self.name,
            'email': self.email,
            'role': self.role,
            'organization': self.organization,
            'phone': self.phone,
            'lastLogin': self.last_login.isoformat() if self.last_login else None,
            'createdAt': self.created_at.isoformat() if self.created_at else None,
            'updatedAt': self.updated_at.isoformat() if self.updated_at else None,
            'permissions': self.permissions,
        }

    @property
    def permissions(self) -> list[str]:
        return self.ROLE_PERMISSIONS.get(self.role, [])
