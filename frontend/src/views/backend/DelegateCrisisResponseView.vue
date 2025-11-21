<script setup lang="ts">
import { reactive, ref } from 'vue'

type CrisisBrief = {
  id: string
  title: string
  detail: string
  deadline: string
}

const briefs = ref<CrisisBrief[]>([
  {
    id: 'CR-01',
    title: '航线封锁危机',
    detail: '主要航线被未知舰队封锁，请在 2 小时内提交应对策略。',
    deadline: '14:30',
  },
  {
    id: 'CR-02',
    title: '舆论失控',
    detail: '多家媒体对会议进程产生质疑，需要统一口径。',
    deadline: '16:00',
  },
])

const responseForm = reactive({
  crisisId: 'CR-01',
  summary: '',
  actions: '',
  resources: '',
})

const submitResponse = () => {
  if (!responseForm.summary) return
  window.alert('已保存草稿，后续可对接后台 API 提交。')
  responseForm.summary = ''
  responseForm.actions = ''
  responseForm.resources = ''
}
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">危机响应</h2>
      <p class="text-sm text-base-content/70">查看危机详情，快速提交代表反馈。</p>
    </header>

    <section class="grid gap-6 xl:grid-cols-[1.3fr,1fr]">
      <div class="space-y-4">
        <article v-for="brief in briefs" :key="brief.id" class="border border-base-200 rounded-2xl p-4">
          <div class="flex justify-between items-start">
            <div>
              <h3 class="text-lg font-semibold">{{ brief.title }}</h3>
              <p class="text-sm text-base-content/70">截止时间：{{ brief.deadline }}</p>
            </div>
            <span class="badge badge-outline">{{ brief.id }}</span>
          </div>
          <p class="text-sm mt-3">{{ brief.detail }}</p>
        </article>
      </div>

      <form class="border border-base-200 rounded-2xl p-4 space-y-3" @submit.prevent="submitResponse">
        <h3 class="font-semibold">提交反馈</h3>
        <label class="form-control">
          <span class="label-text">选择危机</span>
          <select v-model="responseForm.crisisId" class="select select-bordered">
            <option v-for="brief in briefs" :key="brief.id" :value="brief.id">{{ brief.title }}</option>
          </select>
        </label>
        <label class="form-control">
          <span class="label-text">局势评估</span>
          <textarea v-model="responseForm.summary" class="textarea textarea-bordered" rows="3"></textarea>
        </label>
        <label class="form-control">
          <span class="label-text">行动计划</span>
          <textarea v-model="responseForm.actions" class="textarea textarea-bordered" rows="3"></textarea>
        </label>
        <label class="form-control">
          <span class="label-text">所需资源</span>
          <textarea v-model="responseForm.resources" class="textarea textarea-bordered" rows="2"></textarea>
        </label>
        <div class="flex gap-3">
          <button class="btn btn-primary flex-1" type="submit">提交反馈</button>
          <button class="btn btn-outline flex-1" type="button">保存草稿</button>
        </div>
      </form>
    </section>
  </div>
</template>
