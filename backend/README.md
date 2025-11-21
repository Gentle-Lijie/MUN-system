# Flask RESTful Backend (Flask + MySQL)

基于 Flask + Flask-RESTful + SQLAlchemy 的后端模板，为前端 Vue 应用提供可扩展的 REST API。默认连接 MySQL，并附带示例委托(Delegate)资源，便于根据业务继续扩展。

```text
backend/
├─ app/
│  ├─ api/              # REST 资源（Health、Delegate 示例）
│  ├─ models/           # SQLAlchemy 模型
│  ├─ extensions.py     # CORS / DB / Migrate 实例
│  ├─ config.py         # 统一配置 & MySQL DSN 构造
│  ├─ __init__.py       # create_app 工厂，注册 Blueprint、CORS、DB
│  └─ main.py           # WSGI 入口，供 flask run / gunicorn 使用
├─ create_db.sql        # 可选：初始化数据库的 SQL 脚本
├─ requirements.txt     # Flask + MySQL 依赖
└─ README.md
```

## 环境准备

1. 建议 Python 3.11+
2. 安装依赖

   ```bash
   cd backend
   python -m venv .venv && source .venv/bin/activate
   pip install -r requirements.txt
   ```

3. 在仓库根目录 `.env` 中配置数据库连接（示例已存在，可按需调整）：

   ```dotenv
   APP_NAME=MUN Control Flask API
   CORS_ORIGINS=http://localhost:5173
   MYSQL_HOST=localhost
   MYSQL_PORT=3306
   MYSQL_USER=root
   MYSQL_PASSWORD=123456
   MYSQL_DATABASE=mun_system
   # 或者直接提供 DATABASE_URL=mysql+pymysql://user:pass@host:3306/db
   ```

4. 初始化数据库（任选其一）：

   - 通过 SQL 脚本：

     ```bash
     mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS mun_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
     mysql -u root -p mun_system < create_db.sql
     ```

   - 或者使用 Flask-Migrate：

     ```bash
     flask --app app.main db init
     flask --app app.main db migrate -m "init"
     flask --app app.main db upgrade
     ```

## 启动 API

开发模式下建议启用自动重载：

```bash
cd backend
flask --app app.main run --debug --port 8000
```

若部署到生产，可使用 `gunicorn 'app.main:app'` 或任意 WSGI 服务器。

## 核心功能概览

| 模块 | 说明 |
| --- | --- |
| `/` | 根路由，返回服务名称与健康状态 |
| `/api/health/ping` | 健康检查，返回 `status` 与 UTC `timestamp` |
| `/api/delegates` | 示例资源，支持 `GET`（列表）与 `POST`（创建） |
| `/api/delegates/<id>` | 示例资源，支持 `GET / PUT / DELETE` |

Delegate 资源演示了如何：

- 定义 SQLAlchemy 模型 (`app/models/delegate.py`)
- 在 Resource 中注入 `db.session` 进行 CRUD
- 返回结构化 JSON，便于前端直接使用

新增业务时可按照此模板在 `app/models` + `app/api` 中扩展。

## CORS & Vue 调试

`app/__init__.py` 中通过 `flask-cors` 自动读取 `CORS_ORIGINS`，默认允许 `http://localhost:5173`。如需多个来源，用逗号分隔即可，例如：

```dotenv
CORS_ORIGINS=http://localhost:5173,https://staging.example.com
```

## 提示

- 生产环境请为数据库账号设置强密码，并在服务器侧限制白名单 IP。
- 所有返回均为 JSON，前端可直接对接。
- `flask shell` 会自动注入 `db`，便于快速调试：

  ```bash
  flask --app app.main shell
  >>> from app.models import Delegate
  >>> Delegate.query.all()
  ```
