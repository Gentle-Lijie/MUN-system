"""Flask application factory and setup for the MUN Control backend."""

from __future__ import annotations

from flask import Flask, jsonify

from .api import api_bp
from .config import get_config
from .extensions import db, migrate


def create_app(config_name: str | None = None) -> Flask:
    """Application factory used by Flask CLI and WSGI servers."""

    app = Flask(__name__)
    config_object = get_config(config_name)
    app.config.from_object(config_object)

    db.init_app(app)
    migrate.init_app(app, db)

    from . import models  # noqa: F401  # Ensure models are registered with SQLAlchemy

    app.register_blueprint(api_bp)

    @app.get('/')
    def root() -> tuple[object, int]:
        return jsonify(status='ready', service=app.config['APP_NAME']), 200

    @app.shell_context_processor
    def shell_context() -> dict[str, object]:
        return {'db': db}

    return app


__all__ = ['create_app']
