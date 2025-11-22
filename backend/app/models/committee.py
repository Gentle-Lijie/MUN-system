"""Committee/venue ORM models."""

from __future__ import annotations

from datetime import datetime
from typing import Any, Iterable

from ..extensions import db
from .user import User

COMMITTEE_STATUSES = ('preparation', 'in_session', 'paused', 'closed')


class Committee(db.Model):
    __tablename__ = 'Committees'

    id = db.Column(db.Integer, primary_key=True)
    code = db.Column(db.String(10), unique=True, nullable=False)
    name = db.Column(db.String(255), nullable=False)
    venue = db.Column(db.String(255))
    description = db.Column(db.Text)
    status = db.Column(
        db.Enum(*COMMITTEE_STATUSES, name='committee_status'),
        nullable=False,
        default='preparation',
    )
    capacity = db.Column(db.Integer, default=40)
    agenda_order = db.Column(db.JSON)
    dais_json = db.Column(db.JSON)
    time_config = db.Column(db.JSON)
    created_by = db.Column(db.Integer, db.ForeignKey('Users.id'))
    created_at = db.Column(db.DateTime, nullable=False,
                           default=datetime.utcnow)
    updated_at = db.Column(
        db.DateTime,
        nullable=False,
        default=datetime.utcnow,
        onupdate=datetime.utcnow,
    )

    sessions = db.relationship(
        'CommitteeSession',
        backref='committee',
        lazy='selectin',
        cascade='all, delete-orphan',
        order_by='CommitteeSession.start_time',
    )

    def to_dict(self) -> dict[str, Any]:
        return {
            'id': self.id,
            'code': self.code,
            'name': self.name,
            'venue': self.venue,
            'status': self.status,
            'capacity': self.capacity or 0,
            'description': self.description,
            'dais': self.dais,
            'sessions': [session.to_dict() for session in self.sessions],
            'timeConfig': self.time_config_data,
            'createdAt': self.created_at.isoformat() if self.created_at else None,
            'updatedAt': self.updated_at.isoformat() if self.updated_at else None,
        }

    @property
    def dais(self) -> list[dict[str, Any]]:
        if not self.dais_json:
            return []
        ids = [d['id']
               for d in self.dais_json if isinstance(d, dict) and 'id' in d]
        if not ids:
            return []
        users = {u.id: u for u in User.query.filter(User.id.in_(ids)).all()}
        return [
            {
                'id': d['id'],
                'name': users[d['id']].name if d['id'] in users else 'Unknown',
                'role': d.get('role', '主席团'),
                'contact': users[d['id']].phone if d['id'] in users else None,
            }
            for d in self.dais_json
            if isinstance(d, dict) and 'id' in d
        ]

    @dais.setter
    def dais(self, value: list[dict[str, Any]] | None) -> None:
        if value is None:
            self.dais_json = []
        else:
            self.dais_json = [{'id': int(v['id']), 'role': str(
                v.get('role', '主席团'))} for v in value]

    @property
    def time_config_data(self) -> dict[str, Any]:
        if isinstance(self.time_config, dict):
            return self.time_config
        return {'realTimeAnchor': None, 'flowSpeed': 1}

    @time_config_data.setter
    def time_config_data(self, value: dict[str, Any]) -> None:
        self.time_config = value or None


class CommitteeSession(db.Model):
    __tablename__ = 'CommitteeSessions'

    id = db.Column(db.Integer, primary_key=True)
    committee_id = db.Column(
        db.Integer, db.ForeignKey('Committees.id', ondelete='CASCADE'), nullable=False, index=True)
    topic = db.Column(db.String(255), nullable=False)
    chair = db.Column(db.String(255))
    start_time = db.Column(db.DateTime)
    duration_minutes = db.Column(db.Integer, nullable=False, default=20)
    created_at = db.Column(db.DateTime, nullable=False,
                           default=datetime.utcnow)
    updated_at = db.Column(
        db.DateTime,
        nullable=False,
        default=datetime.utcnow,
        onupdate=datetime.utcnow,
    )

    def to_dict(self) -> dict[str, Any]:
        return {
            'id': str(self.id),
            'topic': self.topic,
            'chair': self.chair,
            'start': self.start_time.isoformat() if self.start_time else None,
            'durationMinutes': self.duration_minutes,
        }


__all__ = ['Committee', 'CommitteeSession', 'COMMITTEE_STATUSES']
