<script setup lang="ts">
import { reactive, ref } from 'vue'

type DelegateDoc = {
  id: string
  title: string
  type: '立场文件' | '工作文件'
  status: '已发布' | '草稿' | '待审批'
  updatedAt: string
}

const documents = ref<DelegateDoc[]>([
  { id: 'DOC-01', title: '网络安全初稿', type: '工作文件', status: '待审批', updatedAt: '2025-05-12 08:30' },
  { id: 'DOC-02', title: '立场文件（终版）', type: '立场文件', status: '已发布', updatedAt: '2025-05-11 18:20' },
])

const uploadForm = reactive({
  title: '',
  type: '立场文件' as DelegateDoc['type'],
  notes: '',
})

const uploadDocument = () => {
  if (!uploadForm.title) return
  documents.value.unshift({
    id: `DOC-${(documents.value.length + 1).toString().padStart(2, '0')}`,
    title: uploadForm.title,
    type: uploadForm.type,
    status: '待审批',
    updatedAt: new Date().toLocaleString(),
  })
  uploadForm.title = ''
  uploadForm.notes = ''
  uploadForm.type = '立场文件'
}
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">选手文件中心</h2>
      <p class="text-sm text-base-content/70">用于代表上传、查看文件审核状态。</p>
    </header>

    <section class="grid gap-6 xl:grid-cols-[1.5fr,1fr]">
      <div class="space-y-4">
        <div class="overflow-x-auto border border-base-200 rounded-2xl">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>标题</th>
                <th>类型</th>
                <th>状态</th>
                <th>更新时间</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="doc in documents" :key="doc.id">
                <td>{{ doc.title }}</td>
                <td><span class="badge badge-outline">{{ doc.type }}</span></td>
                <td>
                  <span class="badge" :class="{
                    'badge-success': doc.status === '已发布',
                    'badge-info': doc.status === '草稿',
                    'badge-warning': doc.status === '待审批'
                  }">
                    {{ doc.status }}
                  </span>
                </td>
                <td>{{ doc.updatedAt }}</td>
              </tr>
              <tr v-if="documents.length === 0">
                <td colspan="4" class="text-center text-base-content/60">暂无文件</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <form class="border border-base-200 rounded-2xl p-4 space-y-3" @submit.prevent="uploadDocument">
        <h3 class="font-semibold">上传文件</h3>
        <label class="form-control">
          <span class="label-text">文件标题</span>
          <input v-model="uploadForm.title" type="text" class="input input-bordered" placeholder="文件标题" />
        </label>
        <label class="form-control">
          <span class="label-text">类型</span>
          <select v-model="uploadForm.type" class="select select-bordered">
            <option value="立场文件">立场文件</option>
            <option value="工作文件">工作文件</option>
          </select>
        </label>
        <label class="form-control">
          <span class="label-text">上传附件</span>
          <input type="file" class="file-input file-input-bordered w-full" />
        </label>
        <label class="form-control">
          <span class="label-text">备注</span>
          <textarea v-model="uploadForm.notes" class="textarea textarea-bordered" rows="3"></textarea>
        </label>
        <button class="btn btn-primary w-full" type="submit">提交审核</button>
      </form>
    </section>
  </div>
</template>
