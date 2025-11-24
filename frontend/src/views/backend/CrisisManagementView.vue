<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { api, API_BASE, type Crisis, type CrisisStatus, type CrisisResponseItem, type Venue } from '@/services/api'

const crises = ref<Crisis[]>([])
const loading = ref(false)
const saving = ref(false)
const filterStatus = ref<'all' | CrisisStatus>('all')
const venues = ref<Venue[]>([])
const editingId = ref<number | null>(null)

const form = reactive({
  title: '',
  content: '',
  status: 'active' as CrisisStatus,
  responsesAllowed: false,
  targetCommittees: [] as number[],
  filePath: null as string | null,
  file: null as File | null,
})

const responseModal = reactive({
  open: false,
  loading: false,
  crisisId: 0,
  crisisTitle: '',
  items: [] as CrisisResponseItem[],
  search: '',
})

const statusLabels: Record<CrisisStatus, string> = {
  draft: '草稿',
  active: '进行中',
  resolved: '已结案',
  archived: '已归档',
}

const statusClass: Record<CrisisStatus, string> = {
  draft: 'badge-neutral',
  active: 'badge-info',
  resolved: 'badge-success',
  archived: 'badge-ghost',
}

const filteredItems = computed(() => {
  if (!responseModal.search.trim()) return responseModal.items
  const search = responseModal.search.toLowerCase()
  return responseModal.items.filter(item =>
    item.user?.name?.toLowerCase().includes(search) ||
    item.committee?.name?.toLowerCase().includes(search) ||
    item.country?.toLowerCase().includes(search)
  )
})

const filteredCrises = computed(() => {
  if (filterStatus.value === 'all') return crises.value
  return crises.value.filter((item) => item.status === filterStatus.value)
})

const fetchCrises = async () => {
  loading.value = true
  try {
    const result = await api.getCrises()
    crises.value = result.items
  } catch (error) {
    console.error('Failed to load crises', error)
  } finally {
    loading.value = false
  }
}

const fetchVenues = async () => {
  try {
    const result = await api.getVenues()
    venues.value = result.items
  } catch (error) {
    console.error('Failed to load committees', error)
  }
}

const resetForm = () => {
  editingId.value = null
  form.title = ''
  form.content = ''
  form.status = 'active'
  form.responsesAllowed = false
  form.targetCommittees = []
  form.filePath = null
  form.file = null
}

const startEdit = (crisis: Crisis) => {
  editingId.value = crisis.id
  form.title = crisis.title
  form.content = crisis.content
  form.status = crisis.status
  form.responsesAllowed = crisis.responsesAllowed
  form.targetCommittees = crisis.targetCommittees ? [...crisis.targetCommittees] : []
  form.filePath = crisis.filePath ?? null
  form.file = null
}

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  form.file = target.files?.[0] ?? null
}

const saveCrisis = async () => {
  if (!form.title.trim() || !form.content.trim()) {
    window.alert('请完善标题和正文内容')
    return
  }
  saving.value = true
  try {
    let filePath = form.filePath
    if (form.file) {
      const upload = await api.uploadFile(form.file)
      filePath = upload.fileUrl
    }

    const payload = {
      title: form.title.trim(),
      content: form.content.trim(),
      responses_allowed: form.responsesAllowed,
      status: form.status,
      target_committees: form.targetCommittees.length ? [...form.targetCommittees] : null,
      file_path: filePath ?? null,
    }

    if (editingId.value) {
      await api.updateCrisis(editingId.value, payload)
    } else {
      await api.createCrisis(payload)
    }

    await fetchCrises()
    resetForm()
  } catch (error) {
    console.error('Failed to save crisis', error)
    window.alert('保存失败，请稍后重试')
  } finally {
    saving.value = false
  }
}

const openResponseModal = async (crisis: Crisis) => {
  responseModal.open = true
  responseModal.loading = true
  responseModal.crisisId = crisis.id
  responseModal.crisisTitle = crisis.title
  responseModal.items = []
  responseModal.search = ''
  try {
    const result = await api.getCrisisResponses(crisis.id)
    responseModal.items = result.items
  } catch (error) {
    console.error('Failed to load responses', error)
    window.alert('加载反馈失败')
  } finally {
    responseModal.loading = false
  }
}

const closeResponseModal = () => {
  responseModal.open = false
}

const exportResponses = () => {
  const link = document.createElement('a')
  link.href = `${API_BASE}/api/crises/${responseModal.crisisId}/responses/export`
  link.download = `crisis_responses_${responseModal.crisisId}.csv`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const committeeLookup = computed(() => {
  return venues.value.reduce<Record<number, Venue>>((map, committee) => {
    map[committee.id] = committee
    return map
  }, {})
})

const targetLabel = (ids: number[] | null) => {
  if (!ids || ids.length === 0) return ['全部委员会']
  return ids
    .map((id) => committeeLookup.value[id]?.name || `#${id}`)
}

const removeAttachment = () => {
  form.filePath = null
  form.file = null
}

onMounted(() => {
  fetchVenues()
  fetchCrises()
})
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4 flex flex-wrap items-center gap-4">
      <div>
        <h2 class="text-2xl font-bold">危机管理</h2>
        <p class="text-sm text-base-content/70">主席团可以发布危机、限制面向的委员会，并查看各代表反馈。</p>
      </div>
      <div class="ml-auto flex flex-wrap gap-3 items-center">
        <select v-model="filterStatus" class="select select-bordered select-sm w-36">
          <option value="all">全部状态</option>
          <option value="draft">草稿</option>
          <option value="active">进行中</option>
          <option value="resolved">已结案</option>
          <option value="archived">已归档</option>
        </select>
        <button class="btn btn-sm" @click="fetchCrises" :disabled="loading">刷新</button>
      </div>
    </header>

    <section class="grid gap-6 xl:grid-cols-[1.6fr,1fr]">
      <div class="space-y-4">
        <div v-if="loading" class="flex justify-center py-10">
          <span class="loading loading-spinner loading-lg"></span>
        </div>
        <p v-else-if="filteredCrises.length === 0" class="text-center text-base-content/60 py-10">暂无危机记录</p>

        <article v-for="crisis in filteredCrises" :key="crisis.id"
          class="border border-base-200 rounded-2xl p-4 space-y-3">
          <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <h3 class="text-lg font-semibold">{{ crisis.title }}</h3>
                <span class="badge" :class="statusClass[crisis.status]">{{ statusLabels[crisis.status] }}</span>
                <span class="badge" :class="crisis.responsesAllowed ? 'badge-success' : 'badge-outline'">
                  {{ crisis.responsesAllowed ? '开放反馈' : '关闭反馈' }}
                </span>
              </div>
              <p class="text-sm text-base-content/70">
                发布于：{{ crisis.publishedAt ? new Date(crisis.publishedAt).toLocaleString() : '—' }}
                <span v-if="crisis.publishedBy"> · {{ crisis.publishedBy.name }}</span>
              </p>
              <div class="flex flex-wrap gap-2 text-xs text-base-content/70">
                <span class="badge badge-outline" v-for="(label, idx) in targetLabel(crisis.targetCommittees)"
                  :key="`${crisis.id}-${idx}`">{{ label }}</span>
              </div>
            </div>
            <div class="flex flex-col gap-2">
              <button class="btn btn-xs btn-outline" @click="openResponseModal(crisis)"
                :disabled="crisis.responsesCount === 0">
                查看反馈 ({{ crisis.responsesCount }})
              </button>
              <button class="btn btn-xs btn-secondary" @click="startEdit(crisis)">编辑</button>
            </div>
          </div>
          <p class="text-sm leading-relaxed whitespace-pre-line">{{ crisis.content }}</p>
          <div class="flex flex-wrap gap-3 items-center text-sm">
            <a v-if="crisis.filePath" class="link link-primary" :href="`${API_BASE}${crisis.filePath}`" target="_blank"
              rel="noopener">查看附件</a>
            <span class="text-base-content/60">回应数：{{ crisis.responsesCount }}</span>
          </div>
        </article>
      </div>

      <form class="border border-base-200 rounded-2xl space-y-4" @submit.prevent="saveCrisis">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold">{{ editingId ? '编辑危机' : '发布危机' }}</h3>
          <button v-if="editingId" type="button" class="btn btn-xs btn-ghost" @click="resetForm">新建</button>
        </div>
        <label class="form-control">
          <span class="label-text">标题</span>
          <input v-model="form.title" type="text" class="input input-bordered" placeholder="危机标题" required />
        </label>
        <label class="form-control">
          <span class="label-text">危机详情</span>
          <textarea v-model="form.content" class="textarea textarea-bordered" rows="5" placeholder="描述背景、任务目标、时间线"
            required></textarea>
        </label>
        <label class="form-control">
          <span class="label-text">面向委员会</span>
          <select v-model="form.targetCommittees" class="select select-bordered h-32" multiple>
            <option v-for="venue in venues" :key="venue.id" :value="venue.id">{{ venue.name }} ({{ venue.code }})
            </option>
          </select>
          <span class="label-text-alt text-base-content/60">不选择则默认推送至全部委员会</span>
        </label>
        <label class="form-control">
          <span class="label-text">状态</span>
          <select v-model="form.status" class="select select-bordered">
            <option value="draft">草稿</option>
            <option value="active">进行中</option>
            <option value="resolved">已结案</option>
            <option value="archived">已归档</option>
          </select>
        </label>
        <label class="label cursor-pointer justify-start gap-3">
          <span class="label-text">允许代表提交反馈</span>
          <input type="checkbox" class="toggle toggle-primary" v-model="form.responsesAllowed" />
        </label>
        <div class="space-y-2">
          <label class="form-control">
            <span class="label-text">附件（可选）</span>
            <input type="file" class="file-input file-input-bordered w-full" @change="handleFileChange" />
          </label>
          <div class="flex items-center gap-3 text-sm" v-if="form.filePath">
            <a :href="`${API_BASE}${form.filePath}`" class="link" target="_blank" rel="noopener">当前附件</a>
            <button class="btn btn-xs btn-ghost" type="button" @click="removeAttachment">移除</button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-full" :disabled="saving">
          {{ saving ? '保存中...' : editingId ? '保存修改' : '发布危机' }}
        </button>
      </form>
    </section>

    <dialog v-if="responseModal.open" class="modal !mt-0" open>
      <div class="modal-box max-w-4xl">
        <h3 class="font-semibold text-lg mb-3">{{ responseModal.crisisTitle }} · 反馈列表</h3>
        <div class="flex gap-3 mb-4">
          <input v-model="responseModal.search" type="text" placeholder="搜索代表姓名、委员会或国家"
            class="input input-bordered flex-1" />
          <button class="btn btn-outline" @click="exportResponses"
            :disabled="responseModal.items.length === 0">导出CSV</button>
        </div>
        <div class="min-h-40">
          <div v-if="responseModal.loading" class="flex justify-center py-10">
            <span class="loading loading-spinner loading-lg"></span>
          </div>
          <div v-else>
            <div v-if="filteredItems.length === 0" class="text-base-content/60 text-center py-6">{{ responseModal.search
              ? '无匹配结果' : '尚无反馈' }}</div>
            <div v-else class="overflow-x-auto">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>代表</th>
                    <th>所属委员会</th>
                    <th>国家/身份</th>
                    <th>反馈</th>
                    <th>时间</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in filteredItems" :key="item.id">
                    <td>{{ item.user?.name || '—' }}</td>
                    <td>{{ item.committee?.name || '—' }}</td>
                    <td>{{ item.country || '—' }}</td>
                    <td class="whitespace-pre-line">{{ item.content.summary || '—' }}</td>
                    <td class="whitespace-nowrap">
                      <div>{{ item.createdAt ? new Date(item.createdAt).toLocaleString() : '—' }}</div>
                      <a v-if="item.filePath" :href="`${API_BASE}${item.filePath}`" target="_blank" class="link">附件</a>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-action">
          <button class="btn" @click="closeResponseModal">关闭</button>
        </div>
      </div>
    </dialog>
  </div>
</template>
