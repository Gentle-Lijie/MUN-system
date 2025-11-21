<script setup lang="ts">
import { reactive, ref } from 'vue'

type FileVisibility = '全体可见' | '主席团可见' | '指定会场'

type ManagedFile = {
  id: string
  title: string
  category: string
  visibility: FileVisibility
  updatedAt: string
  owner: string
}

const managedFiles = ref<ManagedFile[]>([
  { id: 'FM-01', title: '会议议程V3', category: '议程', visibility: '全体可见', updatedAt: '2025-05-10', owner: '秘书处' },
  { id: 'FM-02', title: '危机背景包', category: '危机', visibility: '主席团可见', updatedAt: '2025-05-12', owner: '危机组' },
  { id: 'FM-03', title: '立场文件模板', category: '模板', visibility: '全体可见', updatedAt: '2025-05-09', owner: '教研组' },
])

const publishForm = reactive({
  title: '',
  category: '',
  visibility: '全体可见' as FileVisibility,
  notes: '',
})

const uploadFile = () => {
  if (!publishForm.title || !publishForm.category) return
  managedFiles.value.unshift({
    id: `FM-${(managedFiles.value.length + 1).toString().padStart(2, '0')}`,
    title: publishForm.title,
    category: publishForm.category,
    visibility: publishForm.visibility,
    updatedAt: new Date().toISOString().slice(0, 10),
    owner: '当前用户',
  })
  publishForm.title = ''
  publishForm.category = ''
  publishForm.visibility = '全体可见'
  publishForm.notes = ''
}
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">文件管理</h2>
      <p class="text-sm text-base-content/70">统一管理发布文件、分类与可见性设置。</p>
    </header>

    <section class="grid gap-6 xl:grid-cols-[1.6fr,1fr]">
      <div class="space-y-4">
        <div class="overflow-x-auto border border-base-200 rounded-2xl">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>标题</th>
                <th>分类</th>
                <th>可见性</th>
                <th>更新时间</th>
                <th>维护者</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="file in managedFiles" :key="file.id">
                <td>{{ file.title }}</td>
                <td>
                  <span class="badge badge-outline">{{ file.category }}</span>
                </td>
                <td>{{ file.visibility }}</td>
                <td>{{ file.updatedAt }}</td>
                <td>{{ file.owner }}</td>
              </tr>
              <tr v-if="managedFiles.length === 0">
                <td colspan="5" class="text-center text-base-content/60">暂无文件</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="alert alert-info">
          <span>提示：支持通过 API / WebDAV 批量同步文件。</span>
        </div>
      </div>

      <form class="border border-base-200 rounded-2xl p-4 space-y-3" @submit.prevent="uploadFile">
        <h3 class="font-semibold">发布文件</h3>
        <label class="form-control">
          <span class="label-text">文件标题</span>
          <input v-model="publishForm.title" type="text" class="input input-bordered" placeholder="如：会议公告" />
        </label>
        <label class="form-control">
          <span class="label-text">分类</span>
          <input v-model="publishForm.category" type="text" class="input input-bordered" placeholder="危机/公告/模板" />
        </label>
        <label class="form-control">
          <span class="label-text">可见范围</span>
          <select v-model="publishForm.visibility" class="select select-bordered">
            <option value="全体可见">全体可见</option>
            <option value="主席团可见">主席团可见</option>
            <option value="指定会场">指定会场</option>
          </select>
        </label>
        <label class="form-control">
          <span class="label-text">备注</span>
          <textarea v-model="publishForm.notes" class="textarea textarea-bordered" rows="3" placeholder="补充说明"></textarea>
        </label>
        <button class="btn btn-primary w-full" type="submit">上传并发布</button>
      </form>
    </section>
  </div>
</template>
