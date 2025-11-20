<script setup lang="ts">
type QuickAction = {
  label: string
  description: string
  icon: string
}

const quickActions: QuickAction[] = [
  {
    label: '创建议程',
    description: '定义背景文件与委员会流程',
    icon: '📝',
  },
  {
    label: '分配代表',
    description: '同步远端数据库分配情况',
    icon: '🧑‍🤝‍🧑',
  },
  {
    label: '发布公告',
    description: '通过 MCP 服务广播最新日程',
    icon: '📣',
  },
]

const timeline = [
  { time: '09:00', title: '注册 & 签到', status: 'done' },
  { time: '10:00', title: '正式辩论', status: 'active' },
  { time: '12:30', title: '工作午餐', status: 'pending' },
  { time: '14:00', title: '起草决议', status: 'pending' },
]

const committeeMetrics = [
  { label: '委员会', value: 8, trend: '+2 新增' },
  { label: '注册代表', value: 240, trend: '+35 本周' },
  { label: '待审议题', value: 12, trend: '3 份草案' },
]
</script>

<template>
  <div class="min-h-screen bg-base-200">
    <div class="mx-auto flex max-w-6xl flex-col gap-8 px-4 py-10">
      <header class="hero rounded-2xl bg-base-100 shadow-lg">
        <div class="hero-content flex-col lg:flex-row">
          <div>
            <p class="badge badge-primary badge-lg mb-4">MUN 后台系统</p>
            <h1 class="text-4xl font-bold">欢迎回来，秘书长！</h1>
            <p class="py-6 text-base-content/70">
              使用 TailwindCSS + daisyUI 预置的组件快速搭建界面，
              并通过 MCP 服务与远程 Python/MySQL 后端交换委员会数据。
            </p>
            <div class="flex flex-wrap gap-3">
              <button class="btn btn-primary">新建会议</button>
              <button class="btn btn-outline">查看决议进度</button>
            </div>
          </div>
        </div>
      </header>

      <section class="grid gap-5 md:grid-cols-3">
        <article
          v-for="metric in committeeMetrics"
          :key="metric.label"
          class="card bg-base-100 shadow-md"
        >
          <div class="card-body">
            <p class="text-sm uppercase text-base-content/60">{{ metric.label }}</p>
            <p class="text-4xl font-semibold">{{ metric.value }}</p>
            <p class="text-sm text-success">{{ metric.trend }}</p>
          </div>
        </article>
      </section>

      <section class="grid gap-6 lg:grid-cols-3">
        <div class="card col-span-2 bg-base-100 shadow-md">
          <div class="card-body">
            <h2 class="card-title">快速操作</h2>
            <p class="text-base-content/70">
              触发后端 MCP 服务器的动作，保持 DaisyUI 组件与数据库状态一致。
            </p>
            <div class="mt-6 grid gap-4 md:grid-cols-3">
              <div
                v-for="action in quickActions"
                :key="action.label"
                class="rounded-xl border border-base-200 bg-base-100 p-4"
              >
                <div class="text-4xl">{{ action.icon }}</div>
                <h3 class="mt-2 text-lg font-semibold">{{ action.label }}</h3>
                <p class="text-sm text-base-content/70">{{ action.description }}</p>
                <button class="btn btn-sm btn-primary mt-4 w-full">执行</button>
              </div>
            </div>
          </div>
        </div>

        <div class="card bg-base-100 shadow-md">
          <div class="card-body">
            <h2 class="card-title">会议时间线</h2>
            <ul class="timeline timeline-vertical">
              <li v-for="item in timeline" :key="item.time">
                <div :class="['timeline-start', item.status === 'active' && 'text-primary']">
                  {{ item.time }}
                </div>
                <div class="timeline-middle">
                  <span class="badge" :class="item.status === 'active' ? 'badge-primary' : 'badge-ghost'">●</span>
                </div>
                <div class="timeline-end timeline-box">
                  {{ item.title }}
                </div>
                <hr />
              </li>
            </ul>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>
