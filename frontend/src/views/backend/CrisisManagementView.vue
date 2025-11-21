<script setup lang="ts">
import { reactive, ref } from 'vue'

type CrisisSeverity = '低' | '中' | '高'

type CrisisEvent = {
  id: string
  title: string
  severity: CrisisSeverity
  publishedAt: string
  description: string
  status: '开放反馈' | '已结案'
}

const crises = ref<CrisisEvent[]>([
  {
    id: 'C-201',
    title: '能源补给受阻',
    severity: '高',
    publishedAt: '2025-05-12 14:00',
    description: '主航道遭封锁，需制定应急补给方案。',
    status: '开放反馈',
  },
  {
    id: 'C-202',
    title: '舆论危机',
    severity: '中',
    publishedAt: '2025-05-11 09:30',
    description: '社交媒体出现不利报道，需要统一口径。',
    status: '已结案',
  },
])

const crisisForm = reactive({
  title: '',
  severity: '中' as CrisisSeverity,
  description: '',
})

const publishCrisis = () => {
  if (!crisisForm.title) return
  crises.value.unshift({
    id: `C-${(crises.value.length + 201).toString()}`,
    title: crisisForm.title,
    severity: crisisForm.severity,
    description: crisisForm.description,
    publishedAt: new Date().toLocaleString(),
    status: '开放反馈',
  })
  crisisForm.title = ''
  crisisForm.description = ''
  crisisForm.severity = '中'
}

const closeCrisis = (crisis: CrisisEvent) => {
  crisis.status = '已结案'
}
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">危机管理</h2>
      <p class="text-sm text-base-content/70">配置危机事件、广播内容，并跟踪代表反馈。</p>
    </header>

    <section class="grid gap-6 xl:grid-cols-[1.5fr,1fr]">
      <div class="space-y-4">
        <article
          v-for="crisis in crises"
          :key="crisis.id"
          class="border border-base-200 rounded-2xl p-4 space-y-2"
        >
          <div class="flex justify-between items-start">
            <div>
              <p class="text-xs text-base-content/60">{{ crisis.id }}</p>
              <h3 class="text-lg font-semibold">{{ crisis.title }}</h3>
              <p class="text-sm text-base-content/70">发布日期：{{ crisis.publishedAt }}</p>
            </div>
            <span class="badge" :class="{
              'badge-error': crisis.severity === '高',
              'badge-warning': crisis.severity === '中',
              'badge-info': crisis.severity === '低'
            }">
              {{ crisis.severity }}级
            </span>
          </div>
          <p class="text-sm">{{ crisis.description }}</p>
          <div class="flex justify-between text-sm">
            <span class="badge" :class="crisis.status === '开放反馈' ? 'badge-success' : 'badge-neutral'">{{ crisis.status }}</span>
            <div class="space-x-2">
              <button class="btn btn-xs btn-ghost">查看反馈</button>
              <button class="btn btn-xs" @click="closeCrisis(crisis)" :disabled="crisis.status === '已结案'">结案</button>
            </div>
          </div>
        </article>
      </div>

      <form class="border border-base-200 rounded-2xl p-4 space-y-3" @submit.prevent="publishCrisis">
        <h3 class="font-semibold">发布危机</h3>
        <label class="form-control">
          <span class="label-text">标题</span>
          <input v-model="crisisForm.title" type="text" class="input input-bordered" placeholder="危机标题" />
        </label>
        <label class="form-control">
          <span class="label-text">等级</span>
          <select v-model="crisisForm.severity" class="select select-bordered">
            <option value="低">低</option>
            <option value="中">中</option>
            <option value="高">高</option>
          </select>
        </label>
        <label class="form-control">
          <span class="label-text">危机内容</span>
          <textarea v-model="crisisForm.description" class="textarea textarea-bordered" rows="4"
            placeholder="描述危机背景、任务目标、时间线"></textarea>
        </label>
        <button class="btn btn-primary w-full" type="submit">发布并推送</button>
      </form>
    </section>
  </div>
</template>
