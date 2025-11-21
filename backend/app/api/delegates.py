"""Delegate-related REST resources."""

from __future__ import annotations

from typing import Any

from flask import request
from flask_restful import Resource, abort

from ..extensions import db
from ..models.delegate import Delegate


class DelegateListResource(Resource):
    """Collection resource for delegates."""

    def get(self) -> tuple[dict[str, Any], int]:
        delegates = Delegate.query.order_by(Delegate.created_at.desc()).all()
        return (
            {
                'items': [delegate.to_dict() for delegate in delegates],
                'total': len(delegates),
            },
            200,
        )

    def post(self) -> tuple[dict[str, Any], int]:
        payload = request.get_json(silent=True) or {}
        required_fields = {'name', 'country', 'committee'}
        missing = [
            field for field in required_fields if not payload.get(field)]
        if missing:
            abort(
                400, message=f"Missing required fields: {', '.join(missing)}")

        notes_raw = payload.get('notes', '')
        notes = notes_raw.strip() if isinstance(notes_raw, str) else ''
        delegate = Delegate(
            name=str(payload['name']).strip(),
            country=str(payload['country']).strip(),
            committee=str(payload['committee']).strip(),
            notes=notes,
        )
        db.session.add(delegate)
        db.session.commit()
        return delegate.to_dict(), 201


class DelegateResource(Resource):
    """Single delegate resource."""

    def get(self, delegate_id: int) -> tuple[dict[str, Any], int]:
        delegate = self._get_or_404(delegate_id)
        return delegate.to_dict(), 200

    def put(self, delegate_id: int) -> tuple[dict[str, Any], int]:
        delegate = self._get_or_404(delegate_id)
        payload = request.get_json(silent=True) or {}
        for field in ['name', 'country', 'committee', 'notes']:
            if field in payload and payload[field] is not None:
                value = payload[field]
                if isinstance(value, str):
                    value = value.strip()
                setattr(delegate, field, value)
        db.session.commit()
        return delegate.to_dict(), 200

    def delete(self, delegate_id: int) -> tuple[dict[str, str], int]:
        delegate = self._get_or_404(delegate_id)
        db.session.delete(delegate)
        db.session.commit()
        return {'message': f'Delegate {delegate_id} deleted'}, 200

    @staticmethod
    def _get_or_404(delegate_id: int) -> Delegate:
        delegate = Delegate.query.get(delegate_id)
        if delegate is None:
            abort(404, message=f'Delegate {delegate_id} not found')
        return delegate
