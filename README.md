# MUN System Monorepo

该仓库包含一个使用 **Vue 3 + Vite + TailwindCSS + daisyUI** 打造的前端，以及基于 PHP 8.5（FastRoute + Illuminate Database）的后台 API（目录 `backend-php/`）。

## 目录结构

```text
frontend/    # Vue 3 + Vite + pnpm (Tailwind + daisyUI)
backend-php/ # PHP 8.5 后端
.env         # Docker/本地共用的环境占位配置
```

## Docker & Compose 快速部署

> 需要 Docker Engine 24+ 与 docker compose v2。

1. 构建并启动所有服务：

   ```bash
   docker compose up --build -d
   ```

   这会启动 3 个容器：

   | 服务     | 端口 (主机) | 描述                                           |
   |----------|-------------|------------------------------------------------|
   | frontend | 4173        | Nginx 托管的构建产物                           |
   | backend  | 8000        | PHP 8.3 + Apache API；负责文件上传、业务逻辑   |
   | db       | 3307        | MySQL 8.4（内部 3306，对宿主映射 3307）        |

2. 首次启动会自动执行：

   - `backend` 容器完成 `composer install`、启用 Apache rewrite，并挂载名为 `attachments-data` 的卷以保存上传文件
   - `frontend` 构建阶段根据 `VITE_API_BASE_URL` 进行 `pnpm build`

3. 查看日志 / 调试：

   ```bash
   docker compose logs -f backend
   docker compose logs -f frontend
   ```

4. 停止并清理：

   ```bash
   docker compose down -v
   ```

如需自定义 MySQL、API 或前端端口，请修改根目录 `.env` 与 `docker-compose.yml` 内对应字段，并留意 `attachments-data` 卷会持久化 `/var/www/html/attachments` 目录中的上传文件。

## 前端（pnpm）

```bash
cd frontend
pnpm install            # 已初始化，可按需重新安装
pnpm start              # 启动开发服务器 (Vite --host 0.0.0.0 --port 5173)
pnpm build              # 生产构建
```

### Tailwind & daisyUI

- `tailwind.config.js` 已启用 daisyUI，并新增 `munlight` 主题。
- `src/App.vue` 演示了 hero、card、timeline 等 daisyUI 组件，可直接扩展。

## PHP 后端

全新的 PHP 版本位于 `backend-php/`，使用 FastRoute + Illuminate Database 重现所有 REST API：

```powershell
cd backend-php
composer install
# 如需自定义，可 copy .env.example .env，否则将自动读取项目根目录 .env
```

> PHP 8.5 已安装在 `C:\php8.5\php.exe`。若未添加到 `PATH`，可在 PowerShell 中通过 `C:\php8.5\php.exe` 显式调用。

开发环境可直接执行 `composer serve`，生产部署建议使用 docker-compose。

## 环境变量

`.env` 已包含 docker-compose 可直接复用的占位键值：

```text
APP_NAME=MUN Control Stack
MYSQL_HOST=db
MYSQL_PORT=3306
MYSQL_DATABASE=mun
MYSQL_USER=mun_user
MYSQL_PASSWORD=mun_pass
SESSION_COOKIE=mun_session
CORS_ORIGINS=http://localhost:4173,http://127.0.0.1:4173
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_USERNAME=mun_user
DB_PASSWORD=mun_pass
VITE_API_BASE_URL=http://localhost:8000
```

如部署到生产环境，请务必替换为真实凭据，并限制数据库暴露范围（例如仅允许后端容器所在子网访问）。

## 下一步建议

1. 根据业务需求扩展 PHP API（可为上传、消息等模块补充测试）。
2. 配置 CI（例如 GitHub Actions）自动执行 `pnpm build` 与 `composer test`。
