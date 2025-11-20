<script setup lang="ts">
import { ref } from 'vue'

type ModuleLink = {
  key: string
  icon: string
  title: string
  desc: string
}

const modules: ModuleLink[] = [
  { key: 'agenda', icon: '📋', title: '议程管理', desc: '调整议题顺序，推送更新。' },
  { key: 'delegates', icon: '🧑‍⚖️', title: '代表调度', desc: '分配代表、席位与角色。' },
  { key: 'motions', icon: '⚖️', title: '动议面板', desc: '审核并发布实时动议。' },
  { key: 'files', icon: '📁', title: '文件与草案', desc: '批注工作文件与决议草案。' },
]

const activeModule = ref<ModuleLink>(modules[0]!)

function setActiveModule(moduleKey: string) {
  const match = modules.find((item) => item.key === moduleKey)
  if (match) {
    activeModule.value = match
  }
}
</script>

<template>
  <section class="grid gap-6 lg:grid-cols-[280px_auto]">
    <aside class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title">我的权限</h2>
        <ul class="menu menu-lg mt-4 rounded-box bg-base-200/50">
          <li v-for="item in modules" :key="item.key">
            <a :class="{ active: activeModule.key === item.key }" @click="setActiveModule(item.key)">
              <span class="text-xl">{{ item.icon }}</span>
              <div>
                <p class="font-semibold">{{ item.title }}</p>
                <p class="text-sm text-base-content/70">{{ item.desc }}</p>
              </div>
            </a>
          </li>
        </ul>
      </div>
    </aside>

    <section class="card bg-base-100 shadow-lg">
      <div class="card-body space-y-4">
        <header class="flex items-start justify-between">
          <div>
            <p class="badge badge-secondary">{{ activeModule.title }}</p>
            <h2 class="mt-2 text-2xl font-bold">{{ activeModule.desc }}</h2>
            <p class="text-base-content/70">此处提供布局骨架。</p>
          </div>
          <button class="btn btn-primary btn-sm">打开操作面板</button>
        </header>

        <div class="grid gap-4 md:grid-cols-2">
          <div class="rounded-2xl border border-base-200 p-4">
            <h3 class="text-lg font-semibold">工作队列</h3>
            <p class="text-sm text-base-content/70">列出待处理的任务，支持拖拽和批量操作。</p>
            <ul class="mt-3 space-y-2">
              <li class="flex items-center justify-between rounded-xl bg-base-200 px-3 py-2">
                <span>同步最新发言名单</span>
                <button class="btn btn-xs btn-outline">执行</button>
              </li>
            </ul>
          </div>
          <div class="rounded-2xl border border-base-200 p-4">
            <h3 class="text-lg font-semibold">统计概览</h3>
            <div class="stats stats-vertical md:stats-horizontal shadow">
              <div class="stat">
                <div class="stat-title">待办</div>
                <div class="stat-value">8</div>
                <div class="stat-desc">3 条紧急</div>
              </div>
              <div class="stat">
                <div class="stat-title">提醒</div>
                <div class="stat-value">15</div>
                <div class="stat-desc text-warning">+5 新提醒</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </section>
</template>
