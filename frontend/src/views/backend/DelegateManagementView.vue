<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import FormField from '@/components/common/FormField.vue'

type CommitteeStatus = 'preparation' | 'in_session' | 'paused' | 'closed'

type CommitteeSummary = {
  id: number
  code: string
  name: string
  venue?: string | null
  status: CommitteeStatus
  capacity?: number
  dais?: Array<{ id: number }>
}

type DelegateRecord = {
  id: number
  userId: number | null
  userName: string | null
  userEmail: string | null
  userOrganization?: string | null
  committeeId: number
  committeeName: string | null
  committeeCode: string | null
  country: string
  vetoAllowed: boolean
  createdAt?: string | null
  updatedAt?: string | null
}

type DelegateUser = {
  id: number
  name: string
  email: string
  organization?: string | null
}

const statusOptions: { value: CommitteeStatus; label: string; badge: string }[] = [
  { value: 'preparation', label: '筹备中', badge: 'badge-warning' },
  { value: 'in_session', label: '进行中', badge: 'badge-success' },
  { value: 'paused', label: '暂停中', badge: 'badge-info' },
  { value: 'closed', label: '已闭幕', badge: 'badge-neutral' },
]

const committees = ref<CommitteeSummary[]>([])
const delegates = ref<DelegateRecord[]>([])
const delegateUsers = ref<DelegateUser[]>([])

const committeeFilters = reactive({
  keyword: '',
  status: 'all' as 'all' | CommitteeStatus,
})

const delegateKeyword = ref('')
const selectedCommitteeId = ref<number | null>(null)

const loadingCommittees = ref(false)
const loadingDelegates = ref(false)
const loadingUsers = ref(false)
const importing = ref(false)
const exporting = ref(false)
const savingDelegate = ref(false)

const errorMessage = ref('')
const successMessage = ref('')
const importInputRef = ref<HTMLInputElement | null>(null)

const editModalOpen = ref(false)
const userSearch = ref('')

const editForm = reactive({
  id: null as number | null,
  committeeId: null as number | null,
  userId: null as number | null,
  country: '',
  vetoAllowed: false,
})

const filteredCommittees = computed(() => {
  const keyword = committeeFilters.keyword.trim().toLowerCase()
  return committees.value.filter((committee) => {
    const keywordOk = keyword
      ? [committee.code, committee.name, committee.venue || ''].some(
          (field) => field && field.toLowerCase().includes(keyword)
        )
      : true
    const statusOk =
      committeeFilters.status === 'all' || committee.status === committeeFilters.status
    return keywordOk && statusOk
  })
})

const selectedCommittee = computed(
  () => committees.value.find((item) => item.id === selectedCommitteeId.value) ?? null
)

const filteredDelegates = computed(() => {
  const keyword = delegateKeyword.value.trim().toLowerCase()
  return delegates.value.filter((delegate) => {
    const committeeOk = selectedCommitteeId.value
      ? delegate.committeeId === selectedCommitteeId.value
      : true
    if (!committeeOk) return false
    if (!keyword) return true
    return [
      delegate.userName || '',
      delegate.userEmail || '',
      delegate.country || '',
      delegate.committeeName || '',
      delegate.committeeCode || '',
    ].some((field) => field.toLowerCase().includes(keyword))
  })
})

const committeeStats = computed(() => ({
  total: committees.value.length,
  running: committees.value.filter((c) => c.status === 'in_session').length,
  paused: committees.value.filter((c) => c.status === 'paused').length,
  closed: committees.value.filter((c) => c.status === 'closed').length,
}))

const delegateStats = computed(() => ({
  total: delegates.value.length,
  visible: filteredDelegates.value.length,
}))

const delegateCountByCommittee = computed(() => {
  return delegates.value.reduce<Record<number, number>>((acc, current) => {
    acc[current.committeeId] = (acc[current.committeeId] || 0) + 1
    return acc
  }, {})
})

const filteredDelegateUsers = computed(() => {
  const keyword = userSearch.value.trim().toLowerCase()
  if (!keyword) return delegateUsers.value
  return delegateUsers.value.filter((user) =>
    [user.name, user.email, user.organization || ''].some((field) =>
      field.toLowerCase().includes(keyword)
    )
  )
})

const resetMessages = () => {
  errorMessage.value = ''
  successMessage.value = ''
}

const fetchCommittees = async () => {
  loadingCommittees.value = true
  try {
    const response = await fetch('/api/venues', { credentials: 'include' })
    if (!response.ok) throw new Error('无法加载会场列表')
    const data = await response.json()
    committees.value = Array.isArray(data.items) ? data.items : []
  } catch (error) {
    console.error(error)
    errorMessage.value = error instanceof Error ? error.message : '加载会场失败'
  } finally {
    loadingCommittees.value = false
  }
}

const fetchDelegates = async () => {
  loadingDelegates.value = true
  try {
    const query = new URLSearchParams({ pageSize: '500' })
    const response = await fetch(`/api/delegates?${query.toString()}`, { credentials: 'include' })
    if (!response.ok) throw new Error('无法加载代表数据')
    const data = await response.json()
    delegates.value = Array.isArray(data.items) ? data.items : []
  } catch (error) {
    console.error(error)
    errorMessage.value = error instanceof Error ? error.message : '加载代表失败'
  } finally {
    loadingDelegates.value = false
  }
}

const fetchDelegateUsers = async () => {
  loadingUsers.value = true
  try {
    const response = await fetch('/api/users?role=delegate', { credentials: 'include' })
    if (!response.ok) throw new Error('无法加载代表用户列表')
    const data = await response.json()
    delegateUsers.value = Array.isArray(data.items) ? data.items : []
  } catch (error) {
    console.error(error)
    errorMessage.value = error instanceof Error ? error.message : '加载代表用户失败'
  } finally {
    loadingUsers.value = false
  }
}

const refreshData = async () => {
  resetMessages()
  await Promise.all([fetchCommittees(), fetchDelegates()])
}

const handleSelectCommittee = (committeeId: number) => {
  selectedCommitteeId.value = selectedCommitteeId.value === committeeId ? null : committeeId
}

const handleClearSelection = () => {
  selectedCommitteeId.value = null
}

const openDelegateModal = (record?: DelegateRecord) => {
  resetMessages()
  if (!record && !selectedCommitteeId.value) {
    errorMessage.value = '请先在左侧选择一个会场，再添加代表'
    return
  }
  editForm.id = record ? record.id : null
  editForm.committeeId = record ? record.committeeId : selectedCommitteeId.value
  editForm.userId = record ? record.userId : null
  editForm.country = record ? record.country : ''
  editForm.vetoAllowed = record ? record.vetoAllowed : false
  userSearch.value = ''
  editModalOpen.value = true
}

const closeDelegateModal = () => {
  editModalOpen.value = false
}

const saveDelegate = async () => {
  if (!editForm.committeeId) {
    errorMessage.value = '请选择所属会场'
    return
  }
  if (!editForm.userId) {
    errorMessage.value = '请选择代表用户'
    return
  }
  if (!editForm.country.trim()) {
    errorMessage.value = '请填写国家名称'
    return
  }
  savingDelegate.value = true
  try {
    const payload: Record<string, unknown> = {
      userId: editForm.userId,
      committeeId: editForm.committeeId,
      country: editForm.country.trim(),
      vetoAllowed: editForm.vetoAllowed,
    }
    if (editForm.id) payload.id = editForm.id
    const response = await fetch('/api/delegates', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(payload),
    })
    if (!response.ok) {
      const err = await response.json().catch(() => ({}))
      throw new Error(err?.message || '保存代表失败')
    }
    successMessage.value = editForm.id ? '代表信息已更新' : '代表添加成功'
    editModalOpen.value = false
    await fetchDelegates()
  } catch (error) {
    console.error(error)
    errorMessage.value = error instanceof Error ? error.message : '保存代表失败'
  } finally {
    savingDelegate.value = false
  }
}

const triggerImport = () => {
  importInputRef.value?.click()
}

const handleImportFile = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = target.files
  if (!files || files.length === 0) return
  const file = files.item(0)
  if (!file) return
  target.value = ''
  importing.value = true
  resetMessages()
  try {
    const formData = new FormData()
    formData.append('file', file)
    const response = await fetch('/api/delegates/import', {
      method: 'POST',
      credentials: 'include',
      body: formData,
    })
    if (!response.ok) {
      const err = await response.json().catch(() => ({}))
      throw new Error(err?.message || '导入失败')
    }
    const result = await response.json()
    successMessage.value = `导入完成：新增 ${result.created ?? 0} 条，更新 ${result.updated ?? 0} 条${result.errors?.length ? '，部分行被跳过' : ''}`
    if (Array.isArray(result.errors) && result.errors.length) {
      console.warn('Delegate import warnings:', result.errors)
    }
    await fetchDelegates()
  } catch (error) {
    console.error(error)
    errorMessage.value = error instanceof Error ? error.message : '导入失败'
  } finally {
    importing.value = false
  }
}

const handleExport = async () => {
  exporting.value = true
  resetMessages()
  try {
    const params = new URLSearchParams()
    if (selectedCommitteeId.value) params.set('committeeId', String(selectedCommitteeId.value))
    const response = await fetch(
      `/api/delegates/export${params.size ? `?${params.toString()}` : ''}`,
      {
        credentials: 'include',
      }
    )
    if (!response.ok) throw new Error('导出失败，请稍后重试')
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = selectedCommittee.value
      ? `${selectedCommittee.value.code}-delegates.csv`
      : 'delegates.csv'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    successMessage.value = '已开始下载代表 CSV, 显示乱码请用WPS打开'
  } catch (error) {
    console.error(error)
    errorMessage.value = error instanceof Error ? error.message : '导出失败'
  } finally {
    exporting.value = false
  }
}

onMounted(() => {
  refreshData()
  fetchDelegateUsers()
})
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">代表管理</h2>
      <p class="text-sm text-base-content/70">
        左侧选择会场，右侧管理该会场的代表，支持导入导出与一键编辑。
      </p>
    </header>

    <section class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap gap-2">
        <button class="btn btn-outline" :disabled="importing" @click="triggerImport">
          <span v-if="importing" class="loading loading-spinner loading-xs"></span>
          <span>导入 CSV</span>
        </button>
        <button class="btn btn-outline" :disabled="exporting" @click="handleExport">
          <span v-if="exporting" class="loading loading-spinner loading-xs"></span>
          <span>导出 CSV</span>
        </button>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          class="btn btn-primary"
          :disabled="!selectedCommittee || savingDelegate"
          @click="openDelegateModal()"
        >
          添加代表
        </button>
        <button
          class="btn btn-ghost"
          :disabled="loadingDelegates || loadingCommittees"
          @click="refreshData"
        >
          <span
            v-if="loadingDelegates || loadingCommittees"
            class="loading loading-spinner loading-xs"
          ></span>
          刷新
        </button>
      </div>
    </section>

    <FormField legend="代表导入" label="上传 CSV 文件" fieldsetClass="hidden">
      <input
        ref="importInputRef"
        type="file"
        accept=".csv"
        class="file-input file-input-bordered"
        @change="handleImportFile"
      />
    </FormField>

    <div v-if="errorMessage" class="alert alert-error alert-soft text-sm">
      <span>{{ errorMessage }}</span>
    </div>
    <div v-if="successMessage" class="alert alert-success alert-soft text-sm">
      <span>{{ successMessage }}</span>
    </div>

    <section class="grid gap-6 xl:grid-cols-[0.58fr,1fr]">
      <aside class="space-y-4">
        <div class="flex flex-wrap gap-3">
          <FormField
            legend="会场搜索"
            label="按名称/代码/地点筛选"
            fieldsetClass="flex-1 min-w-[14rem]"
          >
            <div class="input input-bordered flex items-center gap-2 w-full">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                class="h-4 w-4 opacity-50"
                fill="currentColor"
              >
                <path
                  d="M11 4a7 7 0 015.618 11.16l3.11 3.11a1 1 0 01-1.414 1.415l-3.11-3.112A7 7 0 1111 4zm0 2a5 5 0 100 10 5 5 0 000-10z"
                />
              </svg>
              <input
                v-model="committeeFilters.keyword"
                type="text"
                class="grow"
                placeholder="搜索会场/地点"
              />
            </div>
          </FormField>
          <FormField legend="状态筛选" label="显示指定会场状态" fieldsetClass="w-40 shrink-0">
            <select v-model="committeeFilters.status" class="select select-bordered w-full">
              <option value="all">全部状态</option>
              <option v-for="item in statusOptions" :key="item.value" :value="item.value">
                {{ item.label }}
              </option>
            </select>
          </FormField>
        </div>

        <div class="stats stats-vertical md:stats-horizontal shadow-none">
          <div class="stat">
            <div class="stat-title">会场总数</div>
            <div class="stat-value">{{ committeeStats.total }}</div>
          </div>
          <div class="stat">
            <div class="stat-title">进行中</div>
            <div class="stat-value text-success">{{ committeeStats.running }}</div>
          </div>
          <div class="stat">
            <div class="stat-title">暂停</div>
            <div class="stat-value text-info">{{ committeeStats.paused }}</div>
          </div>
          <div class="stat">
            <div class="stat-title">已闭幕</div>
            <div class="stat-value text-base-content/60">{{ committeeStats.closed }}</div>
          </div>
        </div>

        <div class="grid gap-4">
          <div v-if="loadingCommittees" class="skeleton h-32 w-full"></div>
          <article
            v-for="committee in filteredCommittees"
            :key="committee.id"
            class="border border-base-200 rounded-2xl p-4 space-y-3 hover:border-primary cursor-pointer"
            :class="{ 'border-primary shadow-lg': committee.id === selectedCommitteeId }"
            @click="handleSelectCommittee(committee.id)"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-xs text-base-content/60">{{ committee.code }}</p>
                <h3 class="text-lg font-semibold leading-tight">{{ committee.name }}</h3>
                <p class="text-sm text-base-content/70">{{ committee.venue || '暂未设置地点' }}</p>
              </div>
              <span
                class="badge"
                :class="statusOptions.find((s) => s.value === committee.status)?.badge"
              >
                {{ statusOptions.find((s) => s.value === committee.status)?.label }}
              </span>
            </div>
            <div class="flex flex-wrap gap-4 text-sm text-base-content/70">
              <span>容量 {{ committee.capacity ?? 0 }} 人</span>
              <span>代表 {{ delegateCountByCommittee[committee.id] ?? 0 }} 人</span>
            </div>
          </article>
          <article
            v-if="!loadingCommittees && filteredCommittees.length === 0"
            class="border border-dashed rounded-2xl p-6"
          >
            <p class="text-base-content/60">暂无匹配的会场，请调整筛选条件。</p>
          </article>
        </div>
      </aside>

      <div class="space-y-4">
        <section class="border border-base-200 rounded-2xl p-4 space-y-3">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h3 class="text-lg font-semibold">
                {{
                  selectedCommittee
                    ? `${selectedCommittee.name} · ${selectedCommittee.code}`
                    : '全部代表'
                }}
              </h3>
              <p class="text-sm text-base-content/70">
                显示 {{ delegateStats.visible }} / {{ delegateStats.total }} 条记录
              </p>
            </div>
            <div class="flex flex-wrap gap-2 items-center">
              <FormField legend="代表搜索" label="按姓名/国家/邮箱" fieldsetClass="w-60 shrink-0">
                <div class="input input-bordered flex items-center gap-2 w-full">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    class="h-4 w-4 opacity-50"
                    fill="currentColor"
                  >
                    <path
                      d="M11 4a7 7 0 015.618 11.16l3.11 3.11a1 1 0 01-1.414 1.415l-3.11-3.112A7 7 0 1111 4zm0 2a5 5 0 100 10 5 5 0 000-10z"
                    />
                  </svg>
                  <input
                    v-model="delegateKeyword"
                    type="text"
                    class="grow"
                    placeholder="搜索代表/国家"
                  />
                </div>
              </FormField>
              <button v-if="selectedCommittee" class="btn btn-ghost" @click="handleClearSelection">
                显示全部
              </button>
            </div>
          </div>

          <div class="overflow-x-auto border border-base-200 rounded-2xl">
            <table class="table table-zebra">
              <thead>
                <tr>
                  <th>代表</th>
                  <th>邮箱</th>
                  <th>所属会场</th>
                  <th>国家</th>
                  <th>否决权</th>
                  <th class="w-24 text-right">操作</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loadingDelegates">
                  <td colspan="6" class="text-center">
                    <span class="loading loading-spinner"></span>
                  </td>
                </tr>
                <tr v-for="delegate in filteredDelegates" :key="delegate.id">
                  <td>
                    <div class="font-semibold">{{ delegate.userName || '未关联' }}</div>
                    <p class="text-sm text-base-content/70">ID: {{ delegate.userId ?? '—' }}</p>
                  </td>
                  <td>
                    <span class="text-sm">{{ delegate.userEmail || '—' }}</span>
                    <p v-if="delegate.userOrganization" class="text-xs text-base-content/60">
                      {{ delegate.userOrganization }}
                    </p>
                  </td>
                  <td>
                    <div class="font-semibold">{{ delegate.committeeName || '—' }}</div>
                    <p class="text-xs text-base-content/60">{{ delegate.committeeCode || '' }}</p>
                  </td>
                  <td class="font-semibold">{{ delegate.country }}</td>
                  <td>
                    <span
                      class="badge"
                      :class="delegate.vetoAllowed ? 'badge-error' : 'badge-ghost'"
                    >
                      {{ delegate.vetoAllowed ? '可否决' : '无' }}
                    </span>
                  </td>
                  <td class="text-right">
                    <button class="btn btn-sm" @click="openDelegateModal(delegate)">编辑</button>
                  </td>
                </tr>
                <tr v-if="!loadingDelegates && filteredDelegates.length === 0">
                  <td colspan="6" class="text-center text-base-content/60">暂无代表数据</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </section>

    <div v-if="editModalOpen" class="modal modal-open">
      <div class="modal-box max-w-4xl space-y-4">
        <h3 class="text-lg font-semibold">{{ editForm.id ? '编辑代表' : '新增代表' }}</h3>
        <form class="space-y-4" @submit.prevent="saveDelegate">
          <div class="grid gap-4 md:grid-cols-2">
            <FormField legend="所属会场" label="请选择代表所属会场" fieldsetClass="w-full">
              <select v-model.number="editForm.committeeId" class="select select-bordered w-full">
                <option v-for="committee in committees" :key="committee.id" :value="committee.id">
                  {{ committee.name }} · {{ committee.code }}
                </option>
              </select>
            </FormField>
            <FormField legend="国家/地区" label="如 China" fieldsetClass="w-full">
              <input
                v-model="editForm.country"
                type="text"
                class="input input-bordered"
                placeholder="例如 China"
              />
            </FormField>
            <FormField legend="否决权" label="代表是否拥有否决权" fieldsetClass="w-full">
              <select v-model="editForm.vetoAllowed" class="select select-bordered w-full">
                <option :value="false">无否决权</option>
                <option :value="true">拥有否决权</option>
              </select>
            </FormField>
          </div>
          <fieldset class="fieldset w-full space-y-3">
            <legend class="fieldset-legend text-base font-semibold mb-3">选择代表用户</legend>
            <p class="text-sm text-base-content/70">搜索姓名或邮箱后选择对应代表账户。</p>
            <div class="input input-bordered flex items-center gap-2 w-full">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                class="h-4 w-4 opacity-50"
                fill="currentColor"
              >
                <path
                  d="M11 4a7 7 0 015.618 11.16l3.11 3.11a1 1 0 01-1.414 1.415l-3.11-3.112A7 7 0 1111 4zm0 2a5 5 0 100 10 5 5 0 000-10z"
                />
              </svg>
              <input v-model="userSearch" type="text" class="grow" placeholder="搜索姓名/邮箱" />
            </div>
            <div class="max-h-60 overflow-y-auto border border-base-200 rounded-2xl p-3 space-y-2">
              <label
                v-for="user in filteredDelegateUsers"
                :key="user.id"
                class="flex items-center gap-3 p-2 rounded-xl border cursor-pointer"
                :class="{ 'border-primary bg-primary/5': editForm.userId === user.id }"
              >
                <input
                  v-model.number="editForm.userId"
                  type="radio"
                  class="radio radio-primary"
                  :value="user.id"
                />
                <div>
                  <div class="font-semibold">{{ user.name }}</div>
                  <p class="text-sm text-base-content/70">{{ user.email }}</p>
                  <p v-if="user.organization" class="text-xs text-base-content/60">
                    {{ user.organization }}
                  </p>
                </div>
              </label>
              <p
                v-if="!loadingUsers && filteredDelegateUsers.length === 0"
                class="text-center text-sm text-base-content/60"
              >
                未找到符合条件的用户
              </p>
              <div v-if="loadingUsers" class="text-center">
                <span class="loading loading-spinner"></span>
              </div>
            </div>
          </fieldset>
          <div class="modal-action">
            <button type="button" class="btn btn-ghost" @click="closeDelegateModal">取消</button>
            <button class="btn btn-primary" type="submit" :disabled="savingDelegate">
              <span v-if="savingDelegate" class="loading loading-spinner loading-xs"></span>
              保存
            </button>
          </div>
        </form>
      </div>
    </div>
    <div v-if="editModalOpen" class="modal-backdrop bg-black/40" @click="closeDelegateModal"></div>
  </div>
</template>
