# Python Backend (FastAPI + MySQL)

This service exposes REST endpoints for the MUN 控制系统.

## 环境准备

1. 建议使用 Python 3.11+
2. 安装依赖：

   ```bash
   cd backend
   python -m venv .venv && source .venv/bin/activate
   pip install -r requirements.txt
   ```

3. 配置根目录下的 `.env`（示例已经创建，替换其中的 MySQL 信息）。
4. 创建数据库：在 MySQL 中创建数据库，然后运行 `create_db.sql` 创建表结构。

   ```bash
   mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS mun_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p mun_system < create_db.sql
   ```

## 启动 API

```bash
cd backend
uvicorn app.main:app --reload --port 8000
```

## 远程 MySQL

- 默认使用 `mysql+aiomysql://` DSN，可在 `.env` 中定制。
- 建议将远程 MySQL 的 IP 加入防火墙白名单，并强制 SSL。

## 健康检查

- `GET /health/ping`
- `GET /` 根路由会返回当前服务状态。
