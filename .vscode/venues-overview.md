# Venue Management Overview

- `GET /api/venues` 获取全部会场（含主席团/时间配置/议程），由 PHP 后端 `VenueController::index`（`backend-php/app/Http/Controllers/VenueController.php`）实现，返回 `{ items, total }`。
- `POST /api/venues` 新建会场，使用 `VenueController::store`，校验代码/名称唯一性并可填写地点、状态、容量、主席团、时间配置；响应创建后的 `Committee.toApiResponse()`。
- `POST /api/venues/{venueId}` 更新会场基础信息（名称/代码/地点/状态/容量/主席团/时间配置），由 `VenueController::update` 处理。
- `POST /api/venues/{venueId}/sessions` 为会场追加议程，委托 `VenueController::addSession` 使用 `CommitteeSession` 模型记录 `topic/chair/start/durationMinutes`。
- 以上请求均调用 `AuthSupport::requirePresidium`，需管理员、主席团或具备 `presidium:manage` 权限的用户。
- 对应的 OpenAPI 文档位于 `backend/json/venues.openapi.json`，供前端/工具同步使用。
