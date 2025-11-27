<div align="center">
# MUN System · 模拟联合国大会全流程管理系统

一套为**模拟联合国大会（MUN）**从筹备到闭幕全流程设计的后台系统：

- 🔐 统一认证 & 权限
- 🧑‍⚖️ 主席团业务（动议 / 发言名单 / 点名 / 电子大屏）
- 🧑‍🎓 代表端文件提交 & 危机反馈
- 🏛 会场 / 代表 / 日程 管理
- 📄 文件流转（提交、审批、发布）
- 📊 日志审计与操作追踪

> 面向学校社团、会务公司、学术会议组委会，可直接用于多场次、多会场 MUN 会议管理。

</div>

---

## ✨ 功能总览

从“招新 & 报名”之后，到会议结束归档，这个系统覆盖了主要的实体和流程：

### 1. 账户体系 & 权限

- 登录 / 登出 / 修改密码 `/api/auth/*`
- 用户角色：`admin`、`dais`（主席团）、`delegate`（代表）、`observer`
- 每个用户可单独配置**细粒度权限**（覆盖默认角色权限）
- 后台用户管理：
   - 列表、搜索、按角色 / 委员会过滤
   - 批量导入 / 导出 CSV（方便 Excel 批量录入）
   - 重置密码、一键初始化代表账号

> 推荐截图：用户管理列表 + 权限配置弹窗

### 2. 会场 & 代表管理

- 会场（Committee / Venue）管理：
   - 基础信息：名称 / 代码 / 地点 / 状态 / 容量
   - 主席团配置、时间配置、议程 Session 管理
- 代表（Delegate）管理：
   - 绑定用户 + 委员会 + 国家，支持是否拥有否决权
   - CSV 导入 / 导出，一键为某委员会生成代表列表
   - 按委员会快速筛选当前在场代表

> 推荐截图：会场列表 + 单个会场详情（含主席团配置 / 议程）

### 3. 会议流程 & 电子大屏

- `/api/display/board`：为**每个委员会**提供一块实时刷新的大屏数据：
   - 当前会场信息、会议状态（准备中 / 进行中 / 暂停）
   - 出席情况统计：代表总数、在场人数、简单 / 绝对多数阈值
   - 发言名单队列、当前发言代表
   - 当前 Session 信息、最近动议通过 / 否决记录
- 点名 & 出席管理：
   - 支持会前整体点名 / 通过动议后重新点名
   - 出席状态直接驱动票数阈值计算
- 发言名单：
   - 主席团一键添加 / 调整队列
   - 状态流转：等待中 → 发言中 → 已结束

> 推荐截图：某委员会的大屏显示页面（发言名单 + 动议历史 + 出席统计）

### 4. 动议管理

- 支持开放发言名单、主持核心磋商等多种动议类型（详见 `动议类型说明.md`）
- 后端 `/api/motions/*`：
   - 新建动议，记录提案人、时间限制、投票结果
   - 与 Session / Speaker List 关联
   - 记录历史，便于赛后回顾与公示

> 推荐截图：动议列表 / 新建动议表单

### 5. 文件 & 文书流转

- `/api/files/*` 模块覆盖：
   - 代表提交文件（决议草案、工作文件等），支持上传附件
   - 主席团审核：通过 / 驳回 + 评语
   - 审核通过后可发布为“大会正式文件”，供全体查阅
- 支持按委员会筛选查看全部公开文件
- 上传文件统一落盘至 `backend-php/attachments/`（可挂载持久卷）

> 推荐截图：文件审批列表 + 单条审批详情

### 6. 危机 & 代表反馈

- 主席团通过 `/api/crises/*` 发布“危机事件”：
   - 支持草稿 / 生效 / 解决 / 归档多状态
   - 可精确指定影响的委员会 & 是否允许代表反馈
- 代表端通过 `/api/crises/{id}/responses` 提交反馈：
   - 概要 / 行动计划 / 所需资源
   - 支持附件上传
- 主席团可查看每位代表的响应详情，支撑危机委员会玩法

> 推荐截图：危机列表 + 单条危机详情 & 反馈列表

### 7. 操作日志 & 审计

- 所有 PHP 后端数据库写入 / 关键查询会记录到 `Logs` 表：
   - 操作者 ID、动作类型、目标表、执行耗时
- `/api/logs` 提供后台查看 / 清理接口：
   - 按操作者、数据表、时间区间过滤
   - 一键清空日志，并生成一条“清空日志”记录用于审计

> 推荐截图：日志列表页，展示筛选条件与操作人信息

---

## 🧱 技术栈

### 前端

- Vue 3 Composition API
- Vite 构建
- TypeScript
- Tailwind CSS v4 + daisyUI v5（自定义主题 `munlight`）
- 路由 & 视图：典型的 Admin Dashboard 布局

### 后端

- PHP 8.3+/8.5
- FastRoute 路由
- Illuminate Database / Eloquent 风格 ORM
- 自定义中间件：认证 / 鉴权 / CORS / 日志记录
- 单独 `backend-php/` 目录可独立部署

### 数据库

- MySQL 8.4
- 已提供 `create_db.sql` 和 `database_clone.sql`，可一键创建 / 复制数据库
- 所有结构更新都在 `数据库结构 - 更新.md` 中有说明

---

## 📦 部署方式

### 方式一：一键 Docker Compose

> 需要 Docker Engine 24+ 与 docker compose v2。

1. 在项目根目录创建并检查 `.env`（可参考仓库中的示例，同步修改数据库密码和 API 地址）。

2. 构建并启动所有服务：

    ```bash
    docker compose up --build -d
    ```

    默认会启动 3 个容器：

    | 服务     | 端口 (主机) | 描述                                           |
    |----------|-------------|------------------------------------------------|
    | frontend | 4173        | Nginx 托管的前端构建产物                       |
    | backend  | 8000        | PHP 8.x API；负责认证、业务逻辑、文件上传      |
    | db       | 3307        | MySQL 8.4（容器内 3306，对宿主映射 3307）      |

3. 首次启动会自动完成：

    - `backend` 容器执行 `composer install`，配置 Apache / PHP
    - `frontend` 依据 `VITE_API_BASE_URL` 进行 `pnpm build`
    - 挂载名为 `attachments-data` 的卷，持久化 `/var/www/html/attachments` 中的上传文件

4. 查看日志 / 调试：

    ```bash
    docker compose logs -f backend
    docker compose logs -f frontend
    ```

5. 停止并清理：

    ```bash
    docker compose down -v
    ```

如需自定义 MySQL、API 或前端端口，请同时修改根目录 `.env` 与 `docker-compose.yml` 中对应字段，并注意 `attachments-data` 卷保留历史上传文件。

### 方式二：本地开发环境（前后端拆分）

#### 前端（Vue 3 + Vite）

```bash
cd frontend
pnpm install
pnpm start      # 本地开发 (默认 5173 端口)
pnpm build      # 生产构建
```

- 在 `frontend/.env` 或 `VITE_API_BASE_URL` 中指向后端 API 地址（例如 `http://localhost:8000`）。

#### 后端（PHP API）

```bash
cd backend-php
composer install
cp .env.example .env   # 如已存在可跳过
```

配置 `.env` 中的数据库连接信息，导入 `create_db.sql` 或 `database_clone.sql` 后即可启动：

```bash
composer serve
```

生产环境推荐使用 Nginx/Apache + PHP-FPM 或直接使用仓库内的 `Dockerfile`。

---

## ⚙️ 核心配置 & 环境变量

根目录 `.env` 示例（仅展示关键字段）：

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

> 生产部署时请务必：
> - 替换为真实、复杂的数据库账号密码；
> - 将数据库访问限制在后端所在内网；
> - 配置 HTTPS 与安全的 Cookie（HttpOnly / Secure）。

---

## 🧪 接口文档 & 开发辅助

后端所有主要模块都提供了 OpenAPI 3 描述文件，位于 `backend/json/`：

- `auth.openapi.json`          登录 / 认证
- `users.openapi.json`         用户管理 & 权限
- `permissions.openapi.json`   细粒度权限
- `venues.openapi.json`        会场 / 会议室
- `delegates.openapi.json`     代表管理
- `display.openapi.json`       电子大屏 & 发言名单
- `files.openapi.json`         文件提交 & 审批 & 发布
- `crises.openapi.json`        危机 & 代表反馈
- `logs.openapi.json`          系统日志审计
- `messages.openapi.json`      站内消息（如后续扩展）

前端 `frontend/openapi.json` 聚合了关键接口，可用于生成 SDK 或导入 Postman / Apifox。

---

## 🚀 适用场景 & 二次开发建议

适合以下场景直接落地：

- 学校 / 学院级模拟联合国大会
- 社团 / 联合会长期维护的 MUN 品牌会议
- 需要多会场、多角色、多轮危机的教学活动

二次开发建议：

- 结合学校统一认证（SSO）接入登录流程
- 扩展代表报名 / 报名审核模块，与现有用户体系打通
- 为主席团和代表端增加移动端适配或独立小程序壳

---

## 📞 联系与商业合作

- 如需**部署服务 / 二次开发 / 私有化定制**，可通过以下方式联系作者：
   - 在 GitHub 仓库提交 Issue 简要说明需求
   - 或在你的小红书 / 公众号文案中留下联系方式，潜在客户可直接加你沟通

欢迎在真实会议中试用，如有新的业务需求也可以在此仓库提 Issue 讨论。

