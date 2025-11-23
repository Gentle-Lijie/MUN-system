"""Configuration helpers for the Flask application."""

from __future__ import annotations

import os
from functools import lru_cache
from pathlib import Path
from typing import Type
from urllib.parse import quote_plus

from dotenv import load_dotenv

BASE_DIR = Path(__file__).resolve().parent.parent
ENV_PATH = BASE_DIR / '.env'

if ENV_PATH.exists():
    load_dotenv(ENV_PATH)
else:
    load_dotenv()


def _build_mysql_uri() -> str:
    user = quote_plus(os.getenv('MYSQL_USER', 'root'))
    password = quote_plus(os.getenv('MYSQL_PASSWORD', '123456'))
    host = os.getenv('MYSQL_HOST', 'localhost')
    port = os.getenv('MYSQL_PORT', '3306')
    database = os.getenv('MYSQL_DATABASE', 'mun')
    return f'mysql+pymysql://{user}:{password}@{host}:{port}/{database}?charset=utf8mb4'


class BaseConfig:
    """Default configuration used for development and production."""

    APP_NAME = os.getenv('APP_NAME', 'MUN Control Flask API')
    SQLALCHEMY_DATABASE_URI = os.getenv('DATABASE_URL') or _build_mysql_uri()
    SQLALCHEMY_TRACK_MODIFICATIONS = False
    SQLALCHEMY_ENGINE_OPTIONS = {'pool_pre_ping': True}
    JSON_SORT_KEYS = False


class TestingConfig(BaseConfig):
    TESTING = True
    SQLALCHEMY_DATABASE_URI = 'sqlite:///:memory:'


CONFIG_MAP: dict[str, Type[BaseConfig]] = {
    'default': BaseConfig,
    'testing': TestingConfig,
}


@lru_cache(maxsize=1)
def get_config(config_name: str | None = None) -> Type[BaseConfig]:
    return CONFIG_MAP.get(config_name or 'default', BaseConfig)


__all__ = ['get_config', 'BaseConfig']
7
