<script setup lang="ts">
import { onMounted, ref } from 'vue'
import FormField from '@/components/common/FormField.vue'
import { api, type FileSubmission, API_BASE } from '@/services/api'

const submissions = ref<FileSubmission[]>([])
const loading = ref(false)
const selectedFile = ref<FileSubmission | null>(null)
const searchQuery = ref('')
const decisionForm = ref({
  decision: 'approved' as 'approved' | 'rejected',
  dias_fb: '',
})

const fetchSubmissions = async () => {
  loading.value = true
  try {
    // 默认获取待审批与草稿（submitted 或 draft），方便主席团同时审阅草稿和已提交项
    const params: { status?: string; search?: string } = { status: 'submitted' }
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim()
    const response = await api.getFileSubmissions(params)
    submissions.value = response.items
  } catch (error) {
    console.error('Failed to fetch submissions:', error)
  } finally {
    loading.value = false
  }
}

const selectFile = (file: FileSubmission) => {
  selectedFile.value = file
  decisionForm.value = { decision: 'approved', dias_fb: '' }
}

const submitDecision = async () => {
  if (!selectedFile.value) return

  try {
    await api.decideSubmission(selectedFile.value.id, decisionForm.value)
    await fetchSubmissions()
    selectedFile.value = null
  } catch (error) {
    console.error('Failed to submit decision:', error)
  }
}

onMounted(fetchSubmissions)
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">文件审批</h2>
      <p class="text-sm text-base-content/70">查看代表提交的文件，给出审批意见与反馈。</p>
    </header>

    <section class="grid gap-6 lg:grid-cols-[2fr,1fr]">
      <div class="space-y-4">
        <div class="flex gap-4 mb-4">
          <FormField legend="搜索" label="按标题或描述" fieldsetClass="w-full">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="搜索文件标题或描述"
              class="input input-bordered w-full"
              @input="fetchSubmissions"
            />
          </FormField>
        </div>
        <div v-if="loading" class="flex justify-center">
          <span class="loading loading-spinner loading-lg"></span>
        </div>
        <article
          v-for="file in submissions"
          :key="file.id"
          class="border border-base-200 rounded-2xl p-4 bg-base-100 space-y-3 cursor-pointer hover:bg-base-200/50"
          @click="selectFile(file)"
        >
          <div class="flex justify-between items-start">
            <div>
              <p class="text-xs text-base-content/60">{{ file.id }}</p>
              <h3 class="text-lg font-semibold">{{ file.title }}</h3>
              <p class="text-sm text-base-content/70">
                {{ file.submitted_by.name }}
                {{ file.submitted_by.organization ? `· ${file.submitted_by.organization}` : '' }}
                {{ file.committee ? `· ${file.committee.name}` : '' }}
              </p>
            </div>
            <div class="flex gap-2">
              <a
                :href="`${API_BASE}${file.content_path}`"
                target="_blank"
                class="btn btn-xs btn-outline"
                >查看文件</a
              >
              <span class="badge badge-warning">待审批</span>
            </div>
          </div>
          <p class="text-sm">{{ file.description }}</p>
          <p class="text-xs text-base-content/50">
            提交时间：{{
              file.submitted_at ? new Date(file.submitted_at).toLocaleString() : '未知'
            }}
          </p>
        </article>
        <div v-if="submissions.length === 0 && !loading" class="alert alert-info">
          暂无待审批文件
        </div>
      </div>

      <div v-if="selectedFile" class="space-y-4">
        <div class="border border-base-200 rounded-2xl p-4">
          <h3 class="font-semibold">审批文件</h3>
          <div class="mt-3 space-y-3">
            <div>
              <p class="font-medium">{{ selectedFile.title }}</p>
              <p class="text-sm text-base-content/70">{{ selectedFile.description }}</p>
            </div>
            <FormField legend="审批意见" label="请选择结果">
              <select v-model="decisionForm.decision" class="select select-bordered">
                <option value="approved">通过</option>
                <option value="rejected">驳回</option>
              </select>
            </FormField>
            <FormField legend="反馈意见" label="可选备注">
              <textarea
                v-model="decisionForm.dias_fb"
                class="textarea textarea-bordered"
                rows="3"
                placeholder="可选的反馈意见"
              ></textarea>
            </FormField>
            <button class="btn btn-primary w-full" @click="submitDecision">提交审批</button>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
