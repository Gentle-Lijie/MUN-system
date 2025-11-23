# MUN Control PHP Backend

该目录包含使用 PHP 8.5 + FastRoute + Illuminate Database 重写的后台 API，完全复刻原 Flask 版的接口与业务逻辑，包括认证、用户管理、代表管理及会场管理等功能。

## 目录结构

```text
backend-php/
├── app/                # 核心代码（控制器、模型、辅助类）
├── bootstrap/          # 应用初始化
├── config/             # 配置文件（应用、数据库）
├── database/           # 保留的历史迁移脚本（当前部署无需执行）
├── public/             # Web 入口（index.php）
├── routes/             # 路由定义
├── tests/              # PHPUnit 测试
├── composer.json
└── README.md
```

## 环境要求

- PHP 8.5（含 `pdo_mysql` / `pdo_sqlite`）
- Composer（用于安装依赖）
- MySQL 8+（生产环境）或 SQLite（测试环境）

> 注：当前主机已在 `C:\php8.5\php.exe` 安装 PHP，可将其加入 `PATH`，或在 PowerShell 中通过 `C:\php8.5\php.exe` 显式调用。

## 快速开始

1. **安装依赖**

   ```powershell
   cd backend-php
   composer install
   ```

2. **配置环境变量（可选）**

   ```powershell
   copy .env.example .env  # 如无特殊需求，可直接复用项目根目录下的 .env
   ```

   > 若未在 `backend-php/` 创建 `.env`，应用会自动读取仓库根目录的 `.env`（与 Python/Flask 服务共用的数据库主机、账号和密码）。

3. **初始化数据库**

   PHP 版后端直接复用 `backend/create_db.sql`。在加载根目录 `.env` 中 MySQL 连接配置后，执行：

   ```powershell
   mysql -h your.mysql.host -P 3306 -u mun_user -p"change_me" < ..\backend\create_db.sql
   ```

   > 请将连接参数替换为根目录 `.env` 中的 `MYSQL_HOST`、`MYSQL_PORT`、`MYSQL_USER`、`MYSQL_PASSWORD`、`MYSQL_DATABASE`。

4. **启动开发服务器**

   ```powershell
   composer serve
   # 服务器默认监听 http://localhost:8000
   ```

## 运行测试

项目提供 PHPUnit 测试，默认使用 SQLite 内存库：

```powershell
composer test
```

> **提示**：当前工作站缺少 PHP/Composer，因此测试尚未实际执行。待安装完 PHP 8.5 与 Composer 后，运行上述命令即可完成自动化测试。

## 与前端的对接

- API 前缀同样为 `/api`
- 所有受保护接口兼容 Bearer Token 与名为 `mun_session` 的 HttpOnly Cookie
- CSV 导入/导出保持与原 Flask 版本一致的列名与编码（导出为 GBK）

如需与现有 Vue 前端联调，只需将前端请求的后端地址切换到新的 PHP 服务即可。
