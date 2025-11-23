# 危机 & 反馈模块速览

- **API 根路径**：`/api/crises`
  - `GET /api/crises`：依据用户身份返回可见的危机列表。普通代表仅能看到面向本委员会或全体的危机，并会带上 `canRespond`/`myResponse` 辅助字段。
  - `POST /api/crises`：主席团发布危机，可指定 `target_committees`、`responses_allowed`、`status`（draft/active/resolved/archived）与可选附件。
  - `PATCH /api/crises/{id}`：主席团更新标题、正文、目标委员会、附件、状态或反馈开关。
  - `GET /api/crises/{id}/responses`：主席团查看各代表反馈（包含委员会/国家信息）。
  - `POST /api/crises/{id}/responses`：代表提交或更新反馈，字段包含 `summary`、`actions`、`resources` 与可选附件。
- **数据模型**：
  - `Crises` 表新增 `status` ENUM（draft/active/resolved/archived），`responses_allowed` 控制反馈入口，`target_committees` JSON 记录定向面向。
  - `CrisisResponses` 表存储代表反馈内容（JSON）与附件路径，`crisis_id` 级联删除。
- **前端视图**：
  - `CrisisManagementView.vue`：主席团可筛选危机、查看反馈弹窗、编辑/新建危机并配置可见范围。
  - `DelegateCrisisResponseView.vue`：代表端展示与自身相关的危机、同步个人反馈并支持文件上传。
