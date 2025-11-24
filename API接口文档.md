# MUN 控制中心 API 文档

本文档基于前端现有功能梳理，并与 `frontend/openapi.json` 中的 OpenAPI 规范保持一致。所有接口默认以 `https://api.mun.local` 为根路径，可通过 `Authorization: Bearer <token>` 头携带会话令牌，除特殊说明以外所有接口均返回 JSON。

## 通用约定

- **认证**：完成 `/api/auth/login` 获得 `token` 后，后续请求都需要在 Header 中附带 `Authorization`。退出登录或令牌过期后将失效。
- **错误响应**：统一结构：

  ```json
  {
    "error": "ErrorCode",
    "message": "人类可读的提示",
    "code": "可选的业务码"
  }
  ```

- **分页/过滤**：列表接口若带有 `items`/`total` 字段则表示后端可以分页实现，常见过滤参数为 `status`、`role`、`keyword` 等。
- **时间**：除 `date` 另有说明，时间全部使用 ISO8601（UTC）字符串。

## 认证模块 (Authentication)

| 接口 | 方法 | 说明 |
| --- | --- | --- |
| `/api/auth/login` | POST | 邮箱+密码登录，返回 `token` 与 `user`（包括 `role`、`permissions`）。|
| `/api/auth/logout` | POST | 退出当前登录态，返回 204。|
| `/api/auth/profile` | GET | 获取当前登录用户信息，前端进站时调用。|
| `/api/auth/password` | PATCH | 修改当前用户密码，需要 `currentPassword` & `newPassword`（≥12 位）。|

## 用户与权限 (Users)

- `GET /api/users`：支持按 `role`、`status`、`search` 查询，返回 `{ items, total }`。
- `POST /api/users`：创建后台账号（name/email/role）。
- `PATCH /api/users/{userId}`：更新角色或启停用状态。
- `POST /api/users/{userId}/reset-password`：管理员为他人下发临时密码。

## 会场与会期 (Venues & Timeline)

- `GET /api/venues`：返回所有会场及其 `sessions`（议程节点）。
- `PATCH /api/venues/{venueId}`：调整名称、地点、状态或容纳人数。
- `POST /api/venues/{venueId}/sessions`：为会场新增议程时间段。

时间轴配置用于控制显示板的模拟进度：

| 接口 | 功能 |
| --- | --- |
| `GET /api/timeline/config` | 读取全局时间轴锚点（起止时间、倍速等）。|
| `PUT /api/timeline/config` | 更新锚点配置。|
| `GET /api/timeline/events` | 查询所有模拟事件（title / offsetMinutes）。|
| `POST /api/timeline/events` | 新增事件节点，用于危机或流程提示。|

## 代表管理 (Delegates)

- `GET /api/delegates`：按 `bloc`、`keyword` 检索会场代表。返回 `status`（present/late/absent）。
- `POST /api/delegates`：添加代表并绑定国家/集团。
- `GET /api/delegates/{delegateId}`：查看档案（联系方式、签到状态、备注）。
- `PUT /api/delegates/{delegateId}`：更新档案字段。
- `PATCH /api/delegates/{delegateId}/attendance`：快速变更点名状态（对应后台「签到管理」）。
- `POST /api/delegates/import`：批量导入 Excel/CSV，body 包含 `source` 和云端文件 `fileUrl`。
- `GET /api/delegates/countries`：前端在发言/动议弹窗获取可选国家列表。

### 代表自助区

- `GET /api/delegate/documents`：当前代表上传文件列表。
- `POST /api/delegate/documents`：上传立场/工作文件（title/type/fileUrl）。

## 显示板与主持控制 (Display)

- `GET /api/display/board`：大屏所需完整状态（委员会信息、统计、计时器、发言队列、历史记录）。
- `POST /api/display/speakers`：主席团添加发言者（country/delegate/notes）。
- `PATCH /api/display/speakers/{speakerId}`：更新发言状态或剩余时间。
- `POST /api/display/timer`：控制计时器动作 `start/pause/resume/reset`，可附 `durationSeconds`。
- `GET /api/motions`：读取可选动议模板（是否需要国家、单次/总时长）。
- `POST /api/motions/decisions`：记录动议投票结果及关联文件。

## 文件流转 (Files)

### 代表提交

- `GET /api/files/submissions`：主席团查看代表提交，支持按 `status` 过滤。
- `POST /api/files/submissions`：代表提交文件（title/category/summary/fileUrl）。
- `POST /api/files/submissions/{submissionId}/decision`：主席团审批，通过/驳回并写 `remarks`。

### 文件发布

- `GET /api/files/published`：所有对外发布的文件（含 `visibility`、`notes`）。
- `POST /api/files/published`：发布新的参考文件。
- `PATCH /api/files/published/{fileId}`：调整发布文件属性。
- `GET /api/files/reference`：给发言/动议等弹窗调用的下拉列表。

## 危机与反馈 (Crises)

- `GET /api/crises`：显示所有危机条目（severity/status）。
- `POST /api/crises`：主席团发布危机。
- `PATCH /api/crises/{crisisId}`：更新状态或补充描述。
- `GET /api/crises/{crisisId}/responses`：查看各代表团反馈。
- `POST /api/crises/{crisisId}/responses`：代表提交反馈（summary/actions/resources）。

## 消息与会话 (Messaging)

| 接口 | 功能 |
| --- | --- |
| `GET /api/messages` | 根据身份返回可见的消息列表，支持 target / committee / search 过滤。|
| `POST /api/messages` | 发送广播、主席团通道或会场/代表定向消息。|

## 系统日志 (Logs)

- `GET /api/logs`：支持按 `level`、时间范围、关键字查询，用于后台日志面板。
- `POST /api/logs/export`：导出日志，body 可附 `level`、`startDate`、`endDate`、`format (csv/xlsx)`，返回 `downloadUrl`。

## 附录：常用实体字段

| 实体 | 关键字段 |
| --- | --- |
| UserProfile | `id`, `username`, `role`, `email`, `permissions`, `avatarUrl` |
| Venue | `id`, `name`, `location`, `status`, `capacity`, `sessions[]` |
| Delegate | `id`, `name`, `country`, `bloc`, `status` |
| DelegateProfile | 额外包含 `committee`, `email`, `phone`, `attendance`, `notes` |
| DisplayBoardState | `committee`, `stats`, `timer`, `speakerQueue[]`, `history[]` |
| FileSubmission | `title`, `author`, `committee`, `category`, `status`, `summary`, `fileUrl` |
| Crisis | `title`, `severity`, `description`, `publishedAt`, `status` |
| MessageRecord | `channel`, `target`, `sender`, `content`, `time` |
| LogRecord | `operator`, `action`, `target`, `level`, `time` |

更多字段和 Schema 详见 `frontend/openapi.json`，可导入 Swagger UI / Stoplight / Postman 进行交互式调试。
