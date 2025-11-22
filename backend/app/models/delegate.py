"""Delegate ORM model mapping committee assignments."""

from __future__ import annotations

from datetime import datetime
from typing import Any

from ..extensions import db


class Delegate(db.Model):
    __tablename__ = 'Delegates'

    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(
        db.Integer,
        db.ForeignKey('Users.id', ondelete='CASCADE'),
        nullable=False,
        index=True,
    )
    committee_id = db.Column(
        db.Integer,
        db.ForeignKey('Committees.id', ondelete='CASCADE'),
        nullable=False,
        index=True,
    )
    country = db.Column(db.String(255), nullable=False)
    veto_allowed = db.Column(
        db.Boolean,
        nullable=False,
        default=False,
        server_default=db.text('0'),
    )
    created_at = db.Column(db.DateTime, nullable=False,
                           default=datetime.utcnow)
    updated_at = db.Column(
        db.DateTime,
        nullable=False,
        default=datetime.utcnow,
        onupdate=datetime.utcnow,
    )

    user = db.relationship('User', lazy='joined')
    committee = db.relationship('Committee', lazy='joined')

    __table_args__ = (
        db.UniqueConstraint('user_id', 'committee_id',
                            name='uq_delegate_user_committee'),
    )

    def to_dict(self) -> dict[str, Any]:
        return {
            'id': self.id,
            'userId': self.user_id,
            'committeeId': self.committee_id,
            'country': self.country,
            'vetoAllowed': bool(self.veto_allowed),
            'userName': self.user.name if self.user else None,
            'userEmail': self.user.email if self.user else None,
            'userOrganization': self.user.organization if self.user else None,
            'committeeName': self.committee.name if self.committee else None,
            'committeeCode': self.committee.code if self.committee else None,
            'createdAt': self.created_at.isoformat() if self.created_at else None,
            'updatedAt': self.updated_at.isoformat() if self.updated_at else None,
        }
