"""Shared Flask extensions initialized in a central place."""

from __future__ import annotations

import json
import logging
import re
import time
from contextlib import contextmanager
from contextvars import ContextVar
from datetime import datetime, timezone
from logging.handlers import RotatingFileHandler
from pathlib import Path
from typing import Any

from flask import g, has_request_context, request, current_app
from flask_migrate import Migrate
from flask_sqlalchemy import SQLAlchemy
from sqlalchemy import event, text


_AUDIT_SUPPRESSED = ContextVar('mun_audit_suppressed', default=0)
_IDENTIFIER_RE = re.compile(r'^[A-Za-z_][A-Za-z0-9_]*$')


class AuditedSQLAlchemy(SQLAlchemy):
	"""SQLAlchemy extension that captures query metadata for auditing."""

	def __init__(self, *args: Any, **kwargs: Any) -> None:
		super().__init__(*args, **kwargs)
		self._audit_logger: logging.Logger | None = None
		self._audit_table_qualified: str | None = None
		self._table_logging_error_logged = False

	def init_app(self, app) -> None:  # type: ignore[override]
		super().init_app(app)
		self._configure_audit_logger(app)
		self._configure_table_logging(app)
		# Try to register listeners inside an application context so
		# get_engine()/current_app access succeeds. If that fails (e.g.
		# engines not yet created), schedule a late registration for the
		# first request as a fallback.
		try:
			with app.app_context():
				self._register_query_listeners()
		except Exception as exc:  # pragma: no cover - defensive logging
			app.logger.debug('Deferred registration of SQL audit listeners: %s', exc)

			def _do_late_registration():
				if getattr(app, '_mun_audit_listeners_registered', False):
					return
				try:
					with app.app_context():
						self._register_query_listeners()
					app._mun_audit_listeners_registered = True
				except Exception:  # pragma: no cover - last resort
					app.logger.exception('Late registration of SQL audit listeners failed')

			# Prefer before_first_request if available; otherwise use before_request
			if hasattr(app, 'before_first_request'):
				@app.before_first_request
				def _late_register():
					_do_late_registration()
			else:
				@app.before_request
				def _late_register_once():
					_do_late_registration()

	def _configure_table_logging(self, app) -> None:
		table_name = app.config.get('SQL_AUDIT_LOG_TABLE', 'Logs')
		if not table_name:
			return
		schema = app.config.get('SQL_AUDIT_LOG_SCHEMA')
		try:
			self._audit_table_qualified = _qualify_table_name(schema, table_name)
		except ValueError:
			self._audit_table_qualified = None
			app.logger.warning(
				'SQL audit table configuration invalid, disabling table logging')
			return
		self._table_logging_error_logged = False

	def _configure_audit_logger(self, app) -> None:
		if self._audit_logger is not None:
			return

		log_path_cfg = app.config.get('SQL_AUDIT_LOG_PATH')
		if log_path_cfg:
			log_path = Path(log_path_cfg)
		else:
			log_path = Path(app.instance_path) / 'logs' / 'db_queries.log'
		log_path.parent.mkdir(parents=True, exist_ok=True)

		handler = RotatingFileHandler(
			log_path,
			maxBytes=5 * 1024 * 1024,  # 5 MB
			backupCount=5,
			encoding='utf-8',
		)
		handler.setFormatter(logging.Formatter('%(message)s'))
		handler.setLevel(logging.INFO)

		logger_name = app.config.get('SQL_AUDIT_LOGGER_NAME', 'mun.sql.audit')
		logger = logging.getLogger(logger_name)
		logger.setLevel(logging.INFO)
		if not any(isinstance(h, RotatingFileHandler) and getattr(h, 'baseFilename', '') == str(log_path) for h in logger.handlers):
			logger.addHandler(handler)

		self._audit_logger = logger
		app.logger.info('SQL audit logs will be written to %s', log_path)

	def _register_query_listeners(self) -> None:
		app = current_app
		engine = self.get_engine()
		if getattr(engine, '_has_audit_listeners', False):  # pragma: no cover - defensive
			return
		app_logger = app.logger

		@event.listens_for(engine, 'before_cursor_execute')
		def before_cursor_execute(conn, cursor, statement, parameters, context, executemany):  # noqa: ANN001
			exec_opts = getattr(context, 'execution_options', {}) or {}
			if _AUDIT_SUPPRESSED.get() > 0 or exec_opts.get('mun_skip_audit'):
				context._skip_audit = True
				return
			context._skip_audit = False
			context._audit_start_time = time.perf_counter()

		@event.listens_for(engine, 'after_cursor_execute')
		def after_cursor_execute(conn, cursor, statement, parameters, context, executemany):  # noqa: ANN001
			if getattr(context, '_skip_audit', False):
				return
			if self._audit_logger is None and self._audit_table_qualified is None:
				return
			start = getattr(context, '_audit_start_time', None)
			duration_ms = ((time.perf_counter() - start) * 1000) if start else None
			record = {
				'timestamp': datetime.now(timezone.utc).isoformat(),
				'statement': statement,
				'parameters': _stringify(parameters),
				'duration_ms': round(duration_ms, 3) if duration_ms is not None else None,
				'session_id': _current_session_id(),
				'request_path': request.path if has_request_context() else None,
				'method': request.method if has_request_context() else None,
			}
			self._emit_audit_record(app, engine, record, app_logger)

		engine._has_audit_listeners = True

	def _emit_audit_record(self, app, engine, record: dict[str, Any], app_logger: logging.Logger) -> None:
		if self._audit_logger is not None:
			try:
				self._audit_logger.info(json.dumps(record, ensure_ascii=False))
			except Exception:  # pragma: no cover - logging must never break queries
				app_logger.exception('Failed to record SQL audit entry')
		if self._audit_table_qualified is not None and not self._table_logging_error_logged:
			try:
				self._write_record_to_table(engine, record)
			except Exception:
				self._table_logging_error_logged = True
				app_logger.exception(
					'Failed to persist SQL audit entry to %s', self._audit_table_qualified)

	def _write_record_to_table(self, engine, record: dict[str, Any]) -> None:
		statement = record.get('statement') or ''
		action, target_table = _infer_action_and_table(statement)
		target_id = _extract_target_id(record.get('parameters'))
		actor_id = _resolve_actor_id(engine)
		meta_json = json.dumps(record, ensure_ascii=False)
		insert_stmt = text(
			f"INSERT INTO {self._audit_table_qualified} (actor_user_id, action, target_table, target_id, meta_json) "
			"VALUES (:actor_user_id, :action, :target_table, :target_id, :meta_json)"
		)
		params = {
			'actor_user_id': actor_id,
			'action': action,
			'target_table': target_table,
			'target_id': target_id,
			'meta_json': meta_json,
		}
		with _suspend_audit():
			with engine.begin() as conn:
				conn.execute(insert_stmt, params)


def _current_session_id() -> str | None:
	if not has_request_context():
		return None
	return request.cookies.get('mun_session') or request.headers.get('X-Session-ID')


def _qualify_table_name(schema: str | None, table: str) -> str:
	parts: list[str] = []
	if schema:
		if not _IDENTIFIER_RE.match(schema):
			raise ValueError('Invalid schema name')
		parts.append(schema)
	if not _IDENTIFIER_RE.match(table):
		raise ValueError('Invalid table name')
	parts.append(table)
	return '.'.join(parts)


def _infer_action_and_table(statement: str) -> tuple[str, str | None]:
	sql = statement.strip()
	if not sql:
		return 'UNKNOWN', None
	tokens = sql.split()
	action = tokens[0].upper()
	upper_sql = sql.upper()
	if action == 'SELECT':
		table = _extract_identifier_after_keyword(sql, upper_sql, 'FROM')
	elif action == 'INSERT':
		table = _extract_identifier_after_keyword(sql, upper_sql, 'INTO')
	elif action == 'UPDATE':
		table = tokens[1] if len(tokens) > 1 else None
	elif action == 'DELETE':
		table = _extract_identifier_after_keyword(sql, upper_sql, 'FROM')
	else:
		table = None
	return action, _clean_identifier(table)


def _extract_identifier_after_keyword(sql: str, upper_sql: str, keyword: str) -> str | None:
	needle = keyword + ' '
	idx = upper_sql.find(needle)
	if idx == -1:
		return None
	start = idx + len(needle)
	segment = sql[start:].strip()
	if not segment:
		return None
	return segment.split()[0]


def _clean_identifier(value: str | None) -> str | None:
	if not value:
		return None
	return value.strip('`"')


def _extract_target_id(params: Any) -> int | None:
	if params is None:
		return None
	if isinstance(params, dict):
		for key in ('id', 'user_id', 'target_id'):
			if key in params:
				coerced = _coerce_int(params[key])
				if coerced is not None:
					return coerced
		records = list(params.values())
		if records and isinstance(records[0], dict):
			return _extract_target_id(records[0])
	elif isinstance(params, (list, tuple)) and params:
		first = params[0]
		if isinstance(first, dict):
			return _extract_target_id(first)
	return None


def _coerce_int(value: Any) -> int | None:
	if isinstance(value, int):
		return value
	if isinstance(value, str) and value.isdigit():
		return int(value)
	return None


def _resolve_actor_id(engine) -> int | None:  # type: ignore[no-untyped-def]
	if not has_request_context():
		return None
	token_value = _current_session_id()
	if not token_value:
		return None
	cache = getattr(g, '_audit_actor_cache', None)
	if cache and cache.get('token') == token_value:
		return cache.get('user_id')
	query = text('SELECT id FROM Users WHERE session_token = :token LIMIT 1')
	with _suspend_audit():
		with engine.connect() as conn:
			result = conn.execute(query, {'token': token_value}).scalar()
	g._audit_actor_cache = {'token': token_value, 'user_id': result}
	return result


@contextmanager
def _suspend_audit():
	token = _AUDIT_SUPPRESSED.set(_AUDIT_SUPPRESSED.get() + 1)
	try:
		yield
	finally:
		_AUDIT_SUPPRESSED.reset(token)


def _stringify(value):  # type: ignore[no-untyped-def]
	try:
		if isinstance(value, dict):
			return {k: _stringify(v) for k, v in value.items()}
		if isinstance(value, (list, tuple, set)):
			return [_stringify(v) for v in value]
		if isinstance(value, (bytes, bytearray)):
			return value.decode('utf-8', errors='replace')
		if isinstance(value, (int, float, str)) or value is None:
			return value
		if hasattr(value, 'isoformat'):
			return value.isoformat()
		return repr(value)
	except Exception:  # pragma: no cover - fallback for non-serializable params
		return '<unserializable>'


db = AuditedSQLAlchemy()
migrate = Migrate()

__all__ = ['db', 'migrate']
