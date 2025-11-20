from functools import lru_cache
from typing import List
from urllib.parse import quote_plus

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    app_name: str = 'MUN Control API'
    cors_origins: List[str] = ['http://localhost:5173']
    mysql_host: str = 'remote.mysql.host'
    mysql_port: int = 3306
    mysql_user: str = 'mun_user'
    mysql_password: str = 'change_me'
    mysql_database: str = 'mun_system'

    model_config = SettingsConfigDict(env_file='.env', env_file_encoding='utf-8')

    @property
    def sqlalchemy_url(self) -> str:
        user = quote_plus(self.mysql_user)
        password = quote_plus(self.mysql_password)
        host = self.mysql_host
        database = self.mysql_database
        return f'mysql+aiomysql://{user}:{password}@{host}:{self.mysql_port}/{database}?charset=utf8mb4'


@lru_cache(maxsize=1)
def get_settings() -> Settings:
    return Settings()


settings = get_settings()
