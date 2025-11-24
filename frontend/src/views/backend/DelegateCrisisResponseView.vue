<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { api, API_BASE, type Crisis } from '@/services/api'

const crises = ref<Crisis[]>([])
const loading = ref(false)
const submitting = ref(false)
const selectedId = ref<number | null>(null)

const form = reactive({
  feedback: '',
  file: null as File | null,
  filePath: null as string | null,
})

const fetchCrises = async () => {
  loading.value = true
  try {
    const result = await api.getCrises()
    crises.value = result.items
    if (!selectedId.value) {
      const target = result.items.find((item) => item.canRespond)
      selectedId.value = target?.id ?? result.items[0]?.id ?? null
    }
  } catch (error) {
    console.error('Failed to load crises', error)
  } finally {
    loading.value = false
  }
}

const currentCrisis = computed(() => crises.value.find((c) => c.id === selectedId.value))

const hydrateForm = () => {
  const response = currentCrisis.value?.myResponse
  const summary = response?.content.summary || ''
  const actions = response?.content.actions || ''
  const resources = response?.content.resources || ''
  form.feedback = [summary, actions, resources].filter(Boolean).join('\n\n') || ''
  form.file = null
  form.filePath = response?.filePath || null
}

watch(selectedId, () => {
  hydrateForm()
})

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  form.file = target.files?.[0] ?? null
}

const clearAttachment = () => {
  form.file = null
  form.filePath = null
}

const submitResponse = async () => {
  if (!currentCrisis.value) return
  if (!form.feedback.trim()) {
    window.alert('请先填写反馈')
    return
  }
  if (!currentCrisis.value.canRespond) {
    window.alert('该危机未开放反馈通道')
    return
  }

  submitting.value = true
  try {
    let filePath = form.filePath
    if (form.file) {
      const upload = await api.uploadFile(form.file)
      filePath = upload.fileUrl
    }

    await api.submitCrisisResponse(currentCrisis.value.id, {
      summary: form.feedback.trim(),
      file_path: filePath ?? null,
    })

    await fetchCrises()
    hydrateForm()
    window.alert('已提交反馈')
  } catch (error) {
    console.error('Failed to submit response', error)
    window.alert('提交失败，请稍后重试')
  } finally {
    submitting.value = false
  }
}

const targetSummary = (crisis: Crisis) => {
  if (!crisis.targetCommittees || crisis.targetCommittees.length === 0) return '面向：全部委员会'
  return `面向：${crisis.targetCommittees.length} 个委员会`
}

onMounted(fetchCrises)
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">危机响应</h2>
      <p class="text-sm text-base-content/70">查看主席团推送的危机并提交反馈，便于快速协同。</p>
    </header>

    <section class="grid gap-6 xl:grid-cols-[1.3fr,1fr]">
      <div class="space-y-4">
        <div v-if="loading" class="flex justify-center py-10">
          <span class="loading loading-spinner loading-lg"></span>
        </div>
        <p v-else-if="crises.length === 0" class="text-center text-base-content/60 py-8">目前没有分配给你的危机</p>
        <article
          v-for="crisis in crises"
          :key="crisis.id"
          class="border border-base-200 rounded-2xl p-4 space-y-3"
        >
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-lg font-semibold">{{ crisis.title }}</h3>
                <span class="badge" :class="crisis.status === 'active' ? 'badge-info' : crisis.status === 'resolved' ? 'badge-success' : 'badge-outline'">
                  {{ crisis.status === 'active' ? '进行中' : crisis.status === 'resolved' ? '已结案' : '已归档' }}
                </span>
                <span class="badge" :class="crisis.responsesAllowed ? 'badge-success' : 'badge-ghost'">
                  {{ crisis.responsesAllowed ? '开放反馈' : '关闭反馈' }}
                </span>
              </div>
              <p class="text-xs text-base-content/60">{{ targetSummary(crisis) }}</p>
            </div>
            <button class="btn btn-xs" @click="selectedId = crisis.id">{{ selectedId === crisis.id ? '已选中' : '选择响应' }}</button>
          </div>
          <p class="text-sm whitespace-pre-line">{{ crisis.content }}</p>
          <div class="flex flex-wrap items-center gap-3 text-sm">
            <a v-if="crisis.filePath" :href="`${API_BASE}${crisis.filePath}`" class="link" target="_blank" rel="noopener">查看附件</a>
            <span class="text-base-content/60">回应数：{{ crisis.responsesCount }}</span>
            <span v-if="crisis.myResponse" class="badge badge-outline">已提交反馈</span>
          </div>
          <div v-if="crisis.myResponse" class="bg-base-200/40 rounded-xl p-3 text-sm">
            <p class="font-medium mb-1">我的反馈</p>
            <p class="text-base-content/80 whitespace-pre-line">{{ crisis.myResponse.content.summary || '（无内容）' }}</p>
          </div>
        </article>
      </div>

      <form class="border border-base-200 rounded-2xl p-4 space-y-4" @submit.prevent="submitResponse">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold">提交反馈</h3>
          <button class="btn btn-xs btn-ghost" type="button" @click="fetchCrises">刷新</button>
        </div>
        <label class="form-control">
          <span class="label-text">选择危机</span>
          <select v-model="selectedId" class="select select-bordered">
            <option v-for="crisis in crises" :key="crisis.id" :value="crisis.id">
              {{ crisis.title }}
            </option>
          </select>
        </label>
        <p class="text-sm text-base-content/60" v-if="currentCrisis">
          状态：{{ currentCrisis.status === 'active' ? '进行中' : currentCrisis.status === 'resolved' ? '已结案' : '已归档' }} ·
          {{ currentCrisis.responsesAllowed ? '允许提交反馈' : '反馈通道关闭' }}
        </p>
        <label class="form-control">
          <span class="label-text">反馈 *</span>
          <textarea v-model="form.feedback" class="textarea textarea-bordered" rows="8" placeholder="请输入您的反馈，包括局势评估、行动计划和所需资源等" required></textarea>
        </label>
        <div class="space-y-2">
          <label class="form-control">
            <span class="label-text">附件（可选）</span>
            <input type="file" class="file-input file-input-bordered w-full" @change="handleFileChange" />
          </label>
          <div class="flex items-center gap-2 text-sm" v-if="form.filePath">
            <a :href="`${API_BASE}${form.filePath}`" target="_blank" class="link">已上传附件</a>
            <button type="button" class="btn btn-xs btn-ghost" @click="clearAttachment">移除</button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-full" :disabled="submitting">
          {{ submitting ? '提交中...' : '提交反馈' }}
        </button>
        <p class="text-xs text-base-content/60">如反馈通道关闭，请联系主席团开启或更新危机状态。</p>
      </form>
    </section>
  </div>
</template>
