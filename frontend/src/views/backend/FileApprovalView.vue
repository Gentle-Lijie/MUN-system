<script setup lang="ts">
import { ref } from 'vue'

type FileStatus = 'pending' | 'approved' | 'rejected'

type SubmittedFile = {
  id: string
  title: string
  author: string
  committee: string
  category: '立场文件' | '工作文件'
  status: FileStatus
  submittedAt: string
  summary: string
}

const pendingFiles = ref<SubmittedFile[]>([
  {
    id: 'F-101',
    title: '关于网络安全的立场',
    author: '韩国代表团',
    committee: '联合国大会一委',
    category: '立场文件',
    status: 'pending',
    submittedAt: '2025-05-12 10:21',
    summary: '阐述了对全球网络安全治理框架的建议。',
  },
  {
    id: 'F-102',
    title: '紧急危机回应方案',
    author: '法国代表团',
    committee: '安理会',
    category: '工作文件',
    status: 'pending',
    submittedAt: '2025-05-12 11:05',
    summary: '针对当前危机的多步骤行动计划。',
  },
])

const approveFile = (record: SubmittedFile) => {
  record.status = 'approved'
}

const rejectFile = (record: SubmittedFile) => {
  record.status = 'rejected'
}
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">文件审批</h2>
      <p class="text-sm text-base-content/70">查看代表提交的文件，给出审批意见与反馈。</p>
    </header>

    <section class="grid gap-6 lg:grid-cols-[2fr,1fr]">
      <div class="space-y-4">
        <article
          v-for="file in pendingFiles"
          :key="file.id"
          class="border border-base-200 rounded-2xl p-4 bg-base-100 space-y-3"
        >
          <div class="flex justify-between items-start">
            <div>
              <p class="text-xs text-base-content/60">{{ file.id }}</p>
              <h3 class="text-lg font-semibold">{{ file.title }}</h3>
              <p class="text-sm text-base-content/70">{{ file.author }} · {{ file.committee }}</p>
            </div>
            <span class="badge" :class="{
              'badge-warning': file.status === 'pending',
              'badge-success': file.status === 'approved',
              'badge-error': file.status === 'rejected'
            }">
              {{ file.status === 'pending' ? '待审批' : file.status === 'approved' ? '已通过' : '已驳回' }}
            </span>
          </div>
          <p class="text-sm">{{ file.summary }}</p>
          <p class="text-xs text-base-content/50">提交时间：{{ file.submittedAt }}</p>
          <div class="flex flex-wrap gap-2">
            <button class="btn btn-success btn-sm" @click="approveFile(file)">通过</button>
            <button class="btn btn-error btn-sm" @click="rejectFile(file)">驳回</button>
            <button class="btn btn-ghost btn-sm">查看正文</button>
          </div>
        </article>
        <div v-if="pendingFiles.length === 0" class="alert alert-info">暂无待审批文件</div>
      </div>

      <div class="space-y-4">
        <div class="border border-base-200 rounded-2xl p-4">
          <h3 class="font-semibold">审批模板</h3>
          <textarea class="textarea textarea-bordered w-full" rows="5" placeholder="在此撰写审批意见模板"></textarea>
          <div class="form-control mt-3">
            <label class="label cursor-pointer justify-start gap-2">
              <input type="checkbox" class="checkbox checkbox-primary" />
              <span class="label-text">审批通过自动通知</span>
            </label>
            <label class="label cursor-pointer justify-start gap-2">
              <input type="checkbox" class="checkbox checkbox-secondary" />
              <span class="label-text">驳回时附带备注</span>
            </label>
          </div>
          <button class="btn btn-primary w-full mt-4">保存模板</button>
        </div>

        <div class="border border-base-200 rounded-2xl p-4">
          <h3 class="font-semibold">审批统计</h3>
          <div class="radial-progress text-primary" style="--value:55;" role="progressbar">55%</div>
          <p class="text-sm text-base-content/60 mt-2">本轮文件审批完成度</p>
          <div class="mt-3 space-y-1 text-sm">
            <p>待审批：{{ pendingFiles.filter((f) => f.status === 'pending').length }} 份</p>
            <p>已通过：{{ pendingFiles.filter((f) => f.status === 'approved').length }} 份</p>
            <p>已驳回：{{ pendingFiles.filter((f) => f.status === 'rejected').length }} 份</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
