"""Venue (committee) management endpoints."""

from __future__ import annotations

from datetime import datetime
from typing import Any

from flask import request
from flask_restful import Resource, abort
from sqlalchemy.exc import IntegrityError
from sqlalchemy.orm import selectinload

from ..extensions import db
from ..models.committee import (
    COMMITTEE_STATUSES,
    Committee,
    CommitteeSession,
)
from .utils import require_presidium_access


def _parse_iso_datetime(raw: Any) -> datetime | None:
    if raw in (None, '', False):
        return None
    if not isinstance(raw, str):
        abort(400, message='start must be an ISO timestamp string')
    normalized = raw.strip()
    if not normalized:
        return None
    if normalized.endswith('Z'):
        normalized = normalized[:-1] + '+00:00'
    try:
        return datetime.fromisoformat(normalized)
    except ValueError:  # pragma: no cover - handled via abort above
        abort(400, message='Invalid datetime format, expected ISO8601 string')


def _get_committee_or_404(venue_id: int) -> Committee:
    committee = Committee.query.options(
        selectinload(Committee.sessions)).get(venue_id)
    if committee is None:
        abort(404, message=f'Venue {venue_id} not found')
    return committee


class VenueListResource(Resource):
    """List all venues with their current sessions."""

    def get(self) -> tuple[dict[str, Any], int]:
        require_presidium_access()
        committees = (
            Committee.query.options(selectinload(Committee.sessions))
            .order_by(Committee.code.asc())
            .all()
        )
        payload = [committee.to_dict() for committee in committees]
        return {'items': payload, 'total': len(payload)}, 200


class VenueDetailResource(Resource):
    """Update immutable venue metadata."""

    def post(self, venue_id: int) -> tuple[dict[str, Any], int]:
        require_presidium_access()
        committee = _get_committee_or_404(venue_id)
        payload = request.get_json(silent=True) or {}
        if not payload:
            abort(400, message='Request body is required')

        if 'name' in payload:
            name = str(payload['name']).strip()
            if not name:
                abort(400, message='name cannot be empty')
            committee.name = name

        if 'code' in payload:
            new_code = str(payload['code']).strip()
            if not new_code:
                abort(400, message='code cannot be empty')
            committee.code = new_code.upper()

        if 'venue' in payload or 'location' in payload:
            venue_value = payload.get('venue', payload.get('location'))
            committee.venue = str(venue_value).strip() if venue_value else None

        if 'description' in payload:
            desc = payload['description']
            committee.description = str(desc).strip() if desc else None

        if 'status' in payload:
            status = str(payload['status']).strip().lower()
            if status not in COMMITTEE_STATUSES:
                abort(400, message='Unsupported status value')
            committee.status = status

        if 'capacity' in payload:
            try:
                capacity = int(payload['capacity'])
            except (TypeError, ValueError):  # pragma: no cover - abort handles
                abort(400, message='capacity must be an integer')
            if capacity <= 0:
                abort(400, message='capacity must be greater than zero')
            committee.capacity = capacity

        if 'dais' in payload:
            dais_value = payload['dais']
            if dais_value is None:
                committee.dais = []
            elif not isinstance(dais_value, list):
                abort(400, message='dais must be a list when provided')
            elif not all(isinstance(d, dict) and 'id' in d and 'role' in d for d in dais_value):
                abort(400, message='each dais item must have id and role')
            else:
                committee.dais = dais_value

        if 'timeConfig' in payload:
            time_config = payload['timeConfig']
            if time_config is None:
                committee.time_config = None
            elif not isinstance(time_config, dict):
                abort(400, message='timeConfig must be an object')
            else:
                committee.time_config = {
                    'realTimeAnchor': time_config.get('realTimeAnchor'),
                    'flowSpeed': time_config.get('flowSpeed', 1),
                }

        try:
            db.session.commit()
        except IntegrityError as exc:  # pragma: no cover - depends on DB
            db.session.rollback()
            abort(400, message=str(exc.orig))
        return committee.to_dict(), 200


class VenueSessionCollectionResource(Resource):
    """Create new agenda items for a venue."""

    def post(self, venue_id: int) -> tuple[dict[str, Any], int]:
        require_presidium_access()
        committee = _get_committee_or_404(venue_id)
        payload = request.get_json(silent=True) or {}

        topic_raw = payload.get('topic') or payload.get('name')
        if not topic_raw:
            abort(400, message='topic is required')
        topic = str(topic_raw).strip()
        if not topic:
            abort(400, message='topic is required')

        start_raw = payload.get('start') or payload.get('time')
        start_time = _parse_iso_datetime(start_raw)

        duration_value = payload.get(
            'durationMinutes') or payload.get('duration')
        if duration_value is None:
            duration = 30
        else:
            try:
                duration = int(duration_value)
            except (TypeError, ValueError):
                abort(400, message='durationMinutes must be an integer')
        if duration <= 0:
            abort(400, message='durationMinutes must be positive')

        chair = payload.get('chair')
        chair_value = str(chair).strip() if chair else None

        session = CommitteeSession(
            committee=committee,
            topic=topic,
            chair=chair_value,
            start_time=start_time,
            duration_minutes=duration,
        )
        db.session.add(session)
        db.session.commit()
        return session.to_dict(), 201
