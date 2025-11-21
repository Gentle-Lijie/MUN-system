"""Delegate ORM model."""

from __future__ import annotations

from datetime import datetime

from ..extensions import db


class Delegate(db.Model):
    __tablename__ = 'delegates'

    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(128), nullable=False)
    country = db.Column(db.String(64), nullable=False)
    committee = db.Column(db.String(128), nullable=False)
    notes = db.Column(db.Text, nullable=False, default='')
    created_at = db.Column(db.DateTime, nullable=False,
                           default=datetime.utcnow)
    updated_at = db.Column(
        db.DateTime,
        nullable=False,
        default=datetime.utcnow,
        onupdate=datetime.utcnow,
    )

    def to_dict(self) -> dict[str, str | int]:
        return {
            'id': self.id,
            'name': self.name,
            'country': self.country,
            'committee': self.committee,
            'notes': self.notes,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None,
        }
