# MUN System Monorepo

该仓库包含一个使用 **Vue 3 + Vite + TailwindCSS + daisyUI** 打造的前端与一个基于 **FastAPI + MySQL** 的后端。

## 目录结构

```
frontend/   # Vue 3 + Vite + pnpm (Tailwind + daisyUI)
backend/    # FastAPI 服务
.env        # 远程 MySQL 占位配置（请立即替换）
```

## 前端（pnpm）

```bash
cd frontend
pnpm install            # 已初始化，可按需重新安装
pnpm start              # 启动开发服务器 (Vite --host 0.0.0.0 --port 5173)
pnpm build              # 生产构建
```

### 一键启动所有服务

在根目录运行 `./start.sh` 可同时启动前端、后端 API（支持热重载）。

### Tailwind & daisyUI

- `tailwind.config.js` 已启用 daisyUI，并新增 `munlight` 主题。
- `src/App.vue` 演示了 hero、card、timeline 等 daisyUI 组件，可直接扩展。

## 后端（FastAPI）

详见 `backend/README.md`，核心命令：

```bash
cd backend
python -m venv .venv && source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8000    # 主 API
```

## 环境变量

`.env` 已包含远端 MySQL 的占位键值：

```
MYSQL_HOST=your.mysql.host
MYSQL_PORT=3306
MYSQL_USER=mun_user
MYSQL_PASSWORD=change_me
MYSQL_DATABASE=mun_system
```

请务必替换为真实凭据，并在部署时限制访问范围（如仅允许后端服务器 IP）。

## 下一步建议

1. 将 FastAPI 与真实远程 MySQL 串联，并编写 Alembic 迁移。
2. 配置 CI（例如 GitHub Actions）自动运行 `pnpm build` 与 `pytest`/`uvicorn` 启动检查。
