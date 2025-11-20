# Python Backend (FastAPI + MySQL)

This service exposes REST endpoints for the MUN 控制系统, including the daisyUI-focused MCP server.

## 环境准备

1. 建议使用 Python 3.11+
2. 安装依赖：

   ```bash
   cd backend
   python -m venv .venv && source .venv/bin/activate
   pip install -r requirements.txt
   ```

3. 配置根目录下的 `.env`（示例已经创建，替换其中的 MySQL 信息）。

## 启动 API

```bash
cd backend
uvicorn app.main:app --reload --port 8000
```

## 启动 daisyUI MCP Server

专用 MCP 服务器只会暴露 `/mcp/...` 路由，方便与其它系统集成。

```bash
cd backend
uvicorn mcp_server:app --reload --port 9000
```

## 远程 MySQL

- 默认使用 `mysql+aiomysql://` DSN，可在 `.env` 中定制。
- 建议将远程 MySQL 的 IP 加入防火墙白名单，并强制 SSL。

## 健康检查

- `GET /health/ping`
- `GET /` 根路由会返回当前服务状态。

## MCP 路由示例

- `GET /mcp/components`：列出 daisyUI 组件词典。
- `POST /mcp/theme/preview`：预览主题（`munlight/business`）。
- `POST /mcp/components/action`：向后端广播生成/同步指令。
