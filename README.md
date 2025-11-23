# MUN System Monorepo

该仓库包含一个使用 **Vue 3 + Vite + TailwindCSS + daisyUI** 打造的前端，以及两套后端实现：

- 既有的 Flask RESTful 服务（`backend/`）
- 全新重写的 PHP 8.5 服务（`backend-php/`）

## 目录结构

```text
frontend/    # Vue 3 + Vite + pnpm (Tailwind + daisyUI)
backend/     # 既有 Flask 服务（保留以供参考）
backend-php/ # 新的 PHP 8.5 后端
.env         # 远程 MySQL 占位配置（请立即替换）
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

## Flask 后端

详见 `backend/README.md`，核心命令：

```bash
cd backend
python -m venv .venv && source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8000    # 主 API
```

## PHP 后端

全新的 PHP 版本位于 `backend-php/`，使用 FastRoute + Illuminate Database 重现所有 REST API：

```powershell
cd backend-php
composer install
# 如需自定义，可 copy .env.example .env，否则将自动读取项目根目录 .env
```

随后使用根目录 `.env` 中的 MySQL 连接信息执行初始化脚本（取代 PHP 目录下的迁移）：

```powershell
mysql -h your.mysql.host -P 3306 -u mun_user -p"change_me" < ..\backend\create_db.sql
```

> PHP 8.5 已安装在 `C:\php8.5\php.exe`。若未添加到 `PATH`，可在 PowerShell 中通过 `C:\php8.5\php.exe` 显式调用。

最后启动服务：

```powershell
composer serve
```

更详细的使用说明、测试指令等可参考 `backend-php/README.md`。

## 环境变量

`.env` 已包含远端 MySQL 的占位键值：

```text
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
