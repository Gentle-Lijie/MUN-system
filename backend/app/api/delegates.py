"""Delegate management endpoints for committee assignments."""

from __future__ import annotations

import csv
import io
from typing import Any

from flask import make_response, request
from flask_restful import Resource, abort
from sqlalchemy import func, or_
from sqlalchemy.exc import IntegrityError
from sqlalchemy.orm import joinedload

from ..extensions import db
from ..models.committee import Committee
from ..models.delegate import Delegate
from ..models.user import User
from .utils import require_presidium_access


def _delegate_query():
    return Delegate.query.options(
        joinedload(Delegate.user),
        joinedload(Delegate.committee),
    )


def _parse_int(value: Any, field_name: str) -> int:
    try:
        return int(value)
    except (TypeError, ValueError):
        abort(400, message=f'{field_name} must be an integer')


def _parse_bool(value: Any) -> bool:
    if isinstance(value, bool):
        return value
    if isinstance(value, (int, float)):
        return int(value) == 1
    if isinstance(value, str):
        normalized = value.strip().lower()
        return normalized in {'1', 'true', 'yes', 'y', '是'}
    return False


def _ensure_delegate_user(user_id: int) -> User:
    user = User.query.get(user_id)
    if user is None:
        abort(400, message='指定的用户不存在')
    if user.role != 'delegate':
        abort(400, message='仅允许将代表赋予角色为 delegate 的用户')
    return user


def _ensure_committee(committee_id: int) -> Committee:
    committee = Committee.query.get(committee_id)
    if committee is None:
        abort(400, message='指定的会场不存在')
    return committee


def _apply_filters(query, args: Any):
    join_user = False
    join_committee = False

    committee_id = args.get('committeeId') or args.get('committee_id')
    if committee_id not in (None, ''):
        query = query.filter(Delegate.committee_id ==
                             _parse_int(committee_id, 'committeeId'))

    user_id = args.get('userId') or args.get('user_id')
    if user_id not in (None, ''):
        query = query.filter(Delegate.user_id == _parse_int(user_id, 'userId'))

    committee_code = args.get('committeeCode') or args.get('committee_code')
    if committee_code:
        join_committee = True
        query = query.join(Delegate.committee).filter(
            func.lower(Committee.code) == str(committee_code).strip().lower())

    search = args.get('search')
    if search:
        pattern = f"%{search.strip().lower()}%"
        if not join_user:
            query = query.join(Delegate.user)
            join_user = True
        if not join_committee:
            query = query.join(Delegate.committee)
            join_committee = True
        query = query.filter(
            or_(
                func.lower(User.name).like(pattern),
                func.lower(User.email).like(pattern),
                func.lower(Delegate.country).like(pattern),
                func.lower(Committee.name).like(pattern),
                func.lower(Committee.code).like(pattern),
            )
        )

    return query


def _get_delegate_or_404(delegate_id: int) -> Delegate:
    delegate = _delegate_query().filter(Delegate.id == delegate_id).first()
    if delegate is None:
        abort(404, message=f'Delegate {delegate_id} not found')
    return delegate


class DelegateCollectionResource(Resource):
    """Collection resource for listing and mutating delegates."""

    def get(self) -> tuple[dict[str, Any], int]:
        require_presidium_access()
        args = request.args or {}
        query = _apply_filters(_delegate_query(), args)

        page = max(1, _parse_int(args.get('page', 1), 'page'))
        page_size = _parse_int(args.get('pageSize', 200), 'pageSize')
        page_size = max(1, min(page_size, 500))

        total = query.count()
        delegates = (
            query.order_by(Delegate.updated_at.desc())
            .offset((page - 1) * page_size)
            .limit(page_size)
            .all()
        )

        return (
            {
                'items': [delegate.to_dict() for delegate in delegates],
                'total': total,
                'page': page,
                'pageSize': page_size,
            },
            200,
        )

    def post(self) -> tuple[dict[str, Any], int]:
        require_presidium_access()
        payload = request.get_json(silent=True) or {}
        delegate_id = payload.get('id')

        if delegate_id:
            delegate = _get_delegate_or_404(_parse_int(delegate_id, 'id'))
        else:
            required = {'userId', 'committeeId', 'country'}
            missing = [field for field in required if not payload.get(field)]
            if missing:
                abort(
                    400, message=f"Missing required fields: {', '.join(missing)}")
            delegate = Delegate()

        # Resolve relations
        if payload.get('userId'):
            user = _ensure_delegate_user(
                _parse_int(payload['userId'], 'userId'))
            delegate.user_id = user.id
            delegate.user = user
        elif not delegate_id:
            abort(400, message='userId is required for new delegates')

        if payload.get('committeeId'):
            committee = _ensure_committee(_parse_int(
                payload['committeeId'], 'committeeId'))
            delegate.committee_id = committee.id
            delegate.committee = committee
        elif not delegate_id:
            abort(400, message='committeeId is required for new delegates')

        if 'country' in payload:
            country = str(payload['country']).strip()
            if not country:
                abort(400, message='country cannot be empty')
            delegate.country = country
        elif not delegate_id:
            abort(400, message='country is required for new delegates')

        if 'vetoAllowed' in payload:
            delegate.veto_allowed = _parse_bool(payload['vetoAllowed'])

        db.session.add(delegate)
        try:
            db.session.commit()
        except IntegrityError as exc:
            db.session.rollback()
            abort(400, message='该代表已被分配至该会场或数据无效', errors=str(exc.orig))

        return delegate.to_dict(), 200 if delegate_id else 201


class DelegateImportResource(Resource):
    """Import delegates from CSV (idempotent)."""

    def post(self) -> tuple[dict[str, Any], int]:
        require_presidium_access()
        upload = request.files.get('file')
        if upload is None or not upload.filename:
            abort(400, message='请上传 CSV 文件')

        raw = upload.read()
        try:
            text = raw.decode('utf-8-sig')
        except UnicodeDecodeError:
            text = raw.decode('gbk', errors='ignore')
        stream = io.StringIO(text)
        reader = csv.DictReader(stream)
        if not reader.fieldnames:
            abort(400, message='CSV 文件缺少表头')

        created = 0
        updated = 0
        errors: list[str] = []

        for row_index, row in enumerate(reader, start=2):  # header is row 1
            try:
                user_email = (row.get('userEmail') or '').strip().lower()
                committee_code = (row.get('committeeCode')
                                  or '').strip().upper()
                country = (row.get('country') or '').strip()
                veto_allowed = _parse_bool(row.get('vetoAllowed'))

                if not user_email or not committee_code or not country:
                    raise ValueError('userEmail、committeeCode 和 country 为必填列')

                user = User.query.filter(func.lower(
                    User.email) == user_email).first()
                if user is None:
                    raise ValueError(f'找不到邮箱为 {user_email} 的用户')
                if user.role != 'delegate':
                    raise ValueError(f'用户 {user_email} 不是 delegate 角色')

                committee = Committee.query.filter(func.upper(
                    Committee.code) == committee_code).first()
                if committee is None:
                    raise ValueError(f'找不到会场代码 {committee_code}')

                delegate = Delegate.query.filter_by(
                    user_id=user.id,
                    committee_id=committee.id,
                ).first()

                if delegate:
                    delegate.country = country
                    delegate.veto_allowed = veto_allowed
                    updated += 1
                else:
                    delegate = Delegate(
                        user_id=user.id,
                        committee_id=committee.id,
                        country=country,
                        veto_allowed=veto_allowed,
                    )
                    delegate.user = user
                    delegate.committee = committee
                    db.session.add(delegate)
                    created += 1
            except ValueError as exc:
                errors.append(f'第 {row_index} 行：{exc}')

        if created or updated:
            db.session.commit()
        else:
            db.session.rollback()

        return {
            'created': created,
            'updated': updated,
            'errors': errors,
        }, 200


class DelegateExportResource(Resource):
    """Export delegates as CSV compatible with the import format."""

    def get(self):  # type: ignore[override]
        require_presidium_access()
        args = request.args or {}
        delegates = _apply_filters(_delegate_query(), args).order_by(
            Delegate.committee_id.asc(), func.lower(Delegate.country).asc()
        ).all()

        output = io.StringIO()
        fieldnames = ['userEmail', 'userName', 'committeeCode',
                      'committeeName', 'country', 'vetoAllowed']
        writer = csv.DictWriter(output, fieldnames=fieldnames)
        writer.writeheader()

        for delegate in delegates:
            writer.writerow({
                'userEmail': delegate.user.email if delegate.user else '',
                'userName': delegate.user.name if delegate.user else '',
                'committeeCode': delegate.committee.code if delegate.committee else '',
                'committeeName': delegate.committee.name if delegate.committee else '',
                'country': delegate.country,
                'vetoAllowed': '1' if delegate.veto_allowed else '0',
            })

        response = make_response(output.getvalue())
        response.headers['Content-Type'] = 'text/csv; charset=gbk'
        response.headers['Content-Disposition'] = 'attachment; filename=delegates.csv'
        return response


class CommitteeDelegateResource(Resource):
    """Return delegates for a single committee."""

    def get(self, committee_id: int) -> tuple[dict[str, Any], int]:
        require_presidium_access()
        committee = _ensure_committee(committee_id)
        delegates = (
            _delegate_query()
            .filter(Delegate.committee_id == committee.id)
            .order_by(func.lower(Delegate.country).asc())
            .all()
        )
        return (
            {
                'items': [delegate.to_dict() for delegate in delegates],
                'total': len(delegates),
                'committee': {
                    'id': committee.id,
                    'name': committee.name,
                    'code': committee.code,
                },
            },
            200,
        )
