<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import FormField from '@/components/common/FormField.vue'

type CommitteeStatus = 'preparation' | 'in_session' | 'paused' | 'closed'

const statusOptions: { value: CommitteeStatus; label: string; badge: string }[] = [
  { value: 'preparation', label: '筹备中', badge: 'badge-warning' },
  { value: 'in_session', label: '进行中', badge: 'badge-success' },
  { value: 'paused', label: '暂停中', badge: 'badge-info' },
  { value: 'closed', label: '已闭幕', badge: 'badge-neutral' },
]

type CommitteeSession = {
  id: number
  topic: string
  chair: string | null
  start: string | null
  durationMinutes: number
}

type DaisMember = {
  id: number
  name: string
  role: string
  contact?: string
}

type User = {
  id: number
  name: string
  email: string
  phone?: string
}

type TimeConfig = {
  realTimeAnchor: string | null
  flowSpeed: number
}

type CommitteeRecord = {
  id: number
  code: string
  name: string
  venue: string | null
  status: string
  capacity: number
  description: string | null
  sessions: CommitteeSession[]
  dais: DaisMember[]
  timeConfig: TimeConfig
}

const committees = ref<CommitteeRecord[]>([])
const users = ref<User[]>([])

const fetchVenues = async () => {
  try {
    const response = await fetch('/api/venues', {
      credentials: 'include'
    })
    if (!response.ok) throw new Error('Failed to fetch venues')
    const data = await response.json()
    committees.value = data.items || []
  } catch (error) {
    console.error('Error fetching venues:', error)
  }
}

const fetchUsers = async () => {
  try {
    const response = await fetch('/api/users', {
      credentials: 'include'
    })
    if (!response.ok) throw new Error('Failed to fetch users')
    const data = await response.json()
    users.value = (data.items || []).filter((user: any) => user.role === 'admin' || user.role === 'dais')
  } catch (error) {
    console.error('Error fetching users:', error)
  }
}

const updateVenue = async (venueId: number, updates: Partial<CommitteeRecord>) => {
  try {
    const response = await fetch(`/api/venues/${venueId}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(updates)
    })
    if (!response.ok) throw new Error('Failed to update venue')
    const updated = await response.json()
    const index = committees.value.findIndex(c => c.id === venueId)
    if (index !== -1) committees.value[index] = updated
  } catch (error) {
    console.error('Error updating venue:', error)
  }
}

const addSession = async (venueId: number, session: Omit<CommitteeSession, 'id'>) => {
  try {
    const response = await fetch(`/api/venues/${venueId}/sessions`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(session)
    })
    if (!response.ok) throw new Error('Failed to add session')
    const newSession = await response.json()
    const committee = committees.value.find(c => c.id === venueId)
    if (committee) committee.sessions.push(newSession)
  } catch (error) {
    console.error('Error adding session:', error)
  }
}

const deleteSession = async (venueId: number, sessionId: number) => {
  try {
    const response = await fetch(`/api/venues/${venueId}/sessions/${sessionId}`, {
      method: 'DELETE',
      credentials: 'include'
    })
    if (!response.ok) throw new Error('Failed to delete session')
    const committee = committees.value.find(c => c.id === venueId)
    if (committee) committee.sessions = committee.sessions.filter(s => s.id !== sessionId)
  } catch (error) {
    console.error('Error deleting session:', error)
  }
}

const filters = reactive({
  keyword: '',
  status: 'all' as 'all' | CommitteeStatus,
})

const selectedId = ref<number | null>(null)

const filteredCommittees = computed(() => {
  return committees.value.filter((committee) => {
    const keyword = filters.keyword.trim().toLowerCase()
    const keywordOk = keyword
      ? [committee.code, committee.name, committee.venue || '']
        .concat(committee.dais ? committee.dais.map((d) => d.name || '') : [])
        .some((field) => field && field.toLowerCase().includes(keyword))
      : true
    const statusOk = filters.status === 'all' || committee.status === filters.status
    return keywordOk && statusOk
  })
})

const selectedCommittee = computed(() => committees.value.find((item) => item.id === selectedId.value) ?? null)

const sessionForm = reactive({
  topic: '',
  chair: '',
  start: '',
  durationMinutes: 20,
})

const showDaisModal = ref(false)
const daisSearch = ref('')
const selectedUserIds = ref<number[]>([])

const filteredUsers = computed(() =>
  users.value.filter(u =>
    u.name.toLowerCase().includes(daisSearch.value.toLowerCase()) ||
    u.email.toLowerCase().includes(daisSearch.value.toLowerCase())
  )
)

const confirmDaisSelection = async () => {
  if (!selectedCommittee.value) return
  const newDais = [...selectedCommittee.value.dais]
  for (const id of selectedUserIds.value) {
    const user = users.value.find(u => u.id === id)
    if (user && !newDais.some(d => d.id === id)) {
      newDais.push({
        id,
        name: user.name,
        role: '主席团',
        contact: user.phone || ''
      })
    }
  }
  await updateVenue(selectedCommittee.value.id, { dais: newDais })
  selectedUserIds.value = []
  showDaisModal.value = false
}

const timeForm = reactive({
  realTimeAnchor: '',
  flowSpeed: 1,
})

watch(
  () => selectedCommittee.value,
  (next) => {
    if (next) {
      timeForm.realTimeAnchor = next.timeConfig.realTimeAnchor || ''
      timeForm.flowSpeed = next.timeConfig.flowSpeed
    }
  },
  { immediate: true },
)

const selectCommittee = (committeeId: number) => {
  selectedId.value = committeeId
}

const handleSaveBaseInfo = async () => {
  if (!selectedCommittee.value) return
  await updateVenue(selectedCommittee.value.id, {
    name: selectedCommittee.value.name,
    venue: selectedCommittee.value.venue,
    status: selectedCommittee.value.status,
    capacity: selectedCommittee.value.capacity
  })
}

const handleAddSession = async () => {
  if (!selectedCommittee.value || !sessionForm.topic) return
  await addSession(selectedCommittee.value.id, {
    topic: sessionForm.topic,
    chair: sessionForm.chair || null,
    start: sessionForm.start || null,
    durationMinutes: sessionForm.durationMinutes,
  })
  sessionForm.topic = ''
  sessionForm.chair = ''
  sessionForm.start = ''
  sessionForm.durationMinutes = 20
}

const handleRemoveSession = async (sessionId: number) => {
  if (!selectedCommittee.value) return
  await deleteSession(selectedCommittee.value.id, sessionId)
}

const handleRemoveDaisMember = async (memberId: number) => {
  if (!selectedCommittee.value) return
  const newDais = selectedCommittee.value.dais.filter((member) => member.id !== memberId)
  await updateVenue(selectedCommittee.value.id, { dais: newDais })
}

const handleSaveTimeConfig = async () => {
  if (!selectedCommittee.value) return
  await updateVenue(selectedCommittee.value.id, { timeConfig: { realTimeAnchor: timeForm.realTimeAnchor || null, flowSpeed: timeForm.flowSpeed } })
}

const handleDisplayBoard = (committee: CommitteeRecord, mode: 'preview' | 'sync') => {
  if (mode === 'preview') {
    const url = `/display/${committee.id}`
    window.open(url, '_blank', 'noopener')
    return
  }
  window.alert(`已向显示大屏推送 ${committee.name} 的最新状态（示例操作）`)
}

const committeeStats = computed(() => ({
  total: committees.value.length,
  running: committees.value.filter((c) => c.status === 'in_session').length,
  paused: committees.value.filter((c) => c.status === 'paused').length,
  closed: committees.value.filter((c) => c.status === 'closed').length,
}))

const showCreateModal = ref(false)
const creatingVenue = ref(false)
const createError = ref('')
const createForm = reactive({
  code: '',
  name: '',
  venue: '',
  status: 'preparation' as CommitteeStatus,
  capacity: 60,
})

const openCreateModal = () => {
  createForm.code = ''
  createForm.name = ''
  createForm.venue = ''
  createForm.status = 'preparation'
  createForm.capacity = 60
  createError.value = ''
  showCreateModal.value = true
}

const closeCreateModal = () => {
  showCreateModal.value = false
}

const handleCreateVenue = async () => {
  if (!createForm.code.trim() || !createForm.name.trim()) {
    createError.value = '请填写会场代码和名称'
    return
  }
  creatingVenue.value = true
  createError.value = ''
  try {
    const payload = {
      code: createForm.code.trim(),
      name: createForm.name.trim(),
      venue: createForm.venue.trim() || null,
      status: createForm.status,
      capacity: createForm.capacity,
    }
    const response = await fetch('/api/venues', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(payload),
    })
    if (!response.ok) {
      const detail = await response.json().catch(() => ({}))
      throw new Error(detail?.message || '创建会场失败')
    }
    const created = await response.json()
    await fetchVenues()
    selectedId.value = created?.id ?? null
    showCreateModal.value = false
  } catch (error) {
    console.error('Error creating venue:', error)
    createError.value = error instanceof Error ? error.message : '创建会场失败'
  } finally {
    creatingVenue.value = false
  }
}

// Load data on mount
fetchVenues()
fetchUsers()
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4 flex flex-wrap justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold">会场管理</h2>
        <p class="text-sm text-base-content/70">配置会场信息、议程、主席团，以及时间轴/大屏同步。</p>
      </div>
      <!-- <button class="btn btn-outline">导出配置</button> -->
    </header>

    <section class="grid gap-6 xl:grid-cols-[0.6fr,1fr]">
      <div class="space-y-4">
        <div class="flex flex-wrap gap-3">
          <FormField legend="关键词搜索" label="按会场/地点/主席团搜索" fieldsetClass="grow min-w-[14rem]">
            <div class="input input-bordered flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4 opacity-50"
                fill="currentColor">
                <path
                  d="M11 4a7 7 0 015.618 11.16l3.11 3.11a1 1 0 01-1.414 1.415l-3.11-3.112A7 7 0 1111 4zm0 2a5 5 0 100 10 5 5 0 000-10z" />
              </svg>
              <input v-model="filters.keyword" type="text" class="grow bg-transparent focus:outline-none"
                placeholder="按会场/地点/主席团搜索" />
            </div>
          </FormField>
          <FormField legend="状态筛选" label="选择会场状态" fieldsetClass="w-40">
            <select v-model="filters.status" class="select select-bordered w-full">
              <option value="all">全部状态</option>
              <option v-for="item in statusOptions" :key="item.value" :value="item.value">{{ item.label }}</option>
            </select>
          </FormField>
          <!-- <button class="btn btn-outline">导出配置</button> -->
        </div>

        <div class="flex flex-wrap items-stretch justify-between gap-4">
          <div class="stats stats-vertical md:stats-horizontal shadow-none flex-1 min-w-[16rem]">
            <div class="stat">
              <div class="stat-title">会场总数</div>
              <div class="stat-value">{{ committeeStats.total }}</div>
              <div class="stat-desc">含临时危机会场</div>
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
          <button class="btn btn-primary btn-lg shrink-0" @click="openCreateModal">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-5 h-5 mr-2 stroke-current"
              stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14" />
            </svg>
            添加会场
          </button>
        </div>

        <div class="grid gap-4 md:grid-cols-1">
          <article v-for="committee in filteredCommittees" :key="committee.id"
            class="border border-base-200 rounded-2xl p-4 space-y-3 hover:border-primary cursor-pointer"
            :class="{ 'border-primary shadow-lg': committee.id === selectedId }" @click="selectCommittee(committee.id)">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-xs text-base-content/60">{{ committee.code }}</p>
                <h3 class="text-lg font-semibold leading-tight">{{ committee.name }}</h3>
                <p class="text-sm text-base-content/70">{{ committee.venue }}</p>
              </div>
              <span class="badge" :class="statusOptions.find((s) => s.value === committee.status)?.badge">
                {{statusOptions.find((s) => s.value === committee.status)?.label}}
              </span>
            </div>
            <div class="flex flex-wrap gap-4 text-sm text-base-content/70">
              <span>容量 {{ committee.capacity }} 人</span>
              <span>议程 {{ committee.sessions.length }} 条</span>
              <span>主席团 {{ committee.dais.length }} 人</span>
            </div>
            <div class="flex flex-wrap gap-2">
              <button class="btn btn-xs" @click.stop="handleDisplayBoard(committee, 'preview')">预览大屏</button>
              <!-- <button class="btn btn-xs btn-outline" @click.stop="handleDisplayBoard(committee, 'sync')">推送状态</button> -->
            </div>
          </article>
          <article v-if="filteredCommittees.length === 0" class="col-span-full border border-dashed rounded-2xl p-6">
            <p class="text-base-content/60">暂无匹配的会场，请调整筛选条件。</p>
          </article>
        </div>
      </div>

      <div v-if="selectedCommittee" class="space-y-4">
        <section class="border border-base-200 rounded-2xl p-4 space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold">基础信息 · {{ selectedCommittee.code }}</h3>
            <button class="btn btn-sm" @click="handleSaveBaseInfo">保存</button>
          </div>
          <div class="grid gap-3 md:grid-cols-2">
            <FormField legend="会场名称" label="请输入会场名称">
              <input v-model="selectedCommittee.name" type="text" class="input input-bordered" />
            </FormField>
            <FormField legend="地点/房间" label="填写具体地点">
              <input v-model="selectedCommittee.venue" type="text" class="input input-bordered" />
            </FormField>
            <FormField legend="状态" label="选择当前状态">
              <select v-model="selectedCommittee.status" class="select select-bordered">
                <option v-for="item in statusOptions" :key="item.value" :value="item.value">{{ item.label }}</option>
              </select>
            </FormField>
            <FormField legend="容纳人数" label="输入最大容量">
              <input v-model.number="selectedCommittee.capacity" type="number" min="10" class="input input-bordered" />
            </FormField>
          </div>
        </section>

        <section class="border border-base-200 rounded-2xl p-4 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold">议程 · {{ selectedCommittee.sessions.length }} 条</h3>
          </div>
          <div class="overflow-x-auto rounded-xl border border-base-200">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>开始时间</th>
                  <th>议题/阶段</th>
                  <th>主持主席团</th>
                  <th>时长</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="session in selectedCommittee.sessions" :key="session.id">
                  <td>{{ session.start ? new Date(session.start).toLocaleString() : '未设置' }}</td>
                  <td class="font-semibold">{{ session.topic }}</td>
                  <td>{{ session.chair }}</td>
                  <td>{{ session.durationMinutes }} 分钟</td>
                  <td><button class="btn btn-ghost btn-xs" @click="handleRemoveSession(session.id)">移除</button></td>
                </tr>
                <tr v-if="selectedCommittee.sessions.length === 0">
                  <td colspan="5" class="text-center text-base-content/60">尚未设置议程</td>
                </tr>
              </tbody>
            </table>
          </div>
          <form class="grid lg:grid-cols-4 gap-3" @submit.prevent="handleAddSession">
            <FormField legend="议题/阶段" label="填写议题" fieldsetClass="w-full">
              <input v-model="sessionForm.topic" type="text" placeholder="议题/阶段" class="input input-bordered" />
            </FormField>
            <FormField legend="主持主席团" label="负责主持的主席团" fieldsetClass="w-full">
              <input v-model="sessionForm.chair" type="text" placeholder="主持主席团" class="input input-bordered" />
            </FormField>
            <FormField legend="开始时间" label="选择开始时间" fieldsetClass="w-full">
              <input v-model="sessionForm.start" type="datetime-local" class="input input-bordered" />
            </FormField>
            <FormField legend="时长（分钟）" label="设置议程时长" fieldsetClass="w-full">
              <div class="input input-bordered flex items-center gap-2">
                <span class="text-xs">分钟</span>
                <input v-model.number="sessionForm.durationMinutes" type="number" min="5"
                  class="grow bg-transparent focus:outline-none" />
              </div>
            </FormField>
            <button type="submit" class="btn btn-primary lg:col-span-4">添加议程</button>
          </form>
        </section>

        <section class="border border-base-200 rounded-2xl p-4 space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold">主席团配置 · {{ selectedCommittee.dais.length }} 人</h3>
          </div>
          <div class="flex flex-wrap gap-2">
            <div v-for="member in selectedCommittee.dais" :key="member.id" class="badge badge-outline gap-2">
              <span class="font-semibold">{{ member.role }}</span>
              <span>{{ member.name }}</span>
              <button class="btn btn-ghost btn-xs" @click="handleRemoveDaisMember(member.id)">×</button>
            </div>
            <p v-if="selectedCommittee.dais.length === 0" class="text-sm text-base-content/60">暂无主席团成员</p>
          </div>
          <button @click="showDaisModal = true" class="btn btn-outline">添加主席团成员</button>
        </section>

        <section class="border border-base-200 rounded-2xl p-4 space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold">时间轴控制</h3>
            <button class="btn btn-sm btn-primary" @click="handleSaveTimeConfig">保存</button>
          </div>
          <div class="space-y-3">
            <FormField legend="现实时间锚点" label="设置与真实时间的对应" fieldsetClass="w-full">
              <div class="flex gap-2">
                <input v-model="timeForm.realTimeAnchor" type="datetime-local" class="input input-bordered flex-1" />
                <button type="button" class="btn btn-outline"
                  @click="timeForm.realTimeAnchor = new Date().toISOString().slice(0, 16)">设为现在</button>
              </div>
            </FormField>
            <FormField legend="时间流速" label="选择或自定义流速" fieldsetClass="w-full">
              <div class="flex gap-2 items-center flex-wrap">
                <div class="join">
                  <button type="button" class="btn btn-outline btn-primary join-item w-16" :class="{ 'btn-active': timeForm.flowSpeed === 3 }"
                    @click="timeForm.flowSpeed = 3">3x</button>
                  <button type="button" class="btn btn-outline btn-primary join-item w-16" :class="{ 'btn-active': timeForm.flowSpeed === 60 }"
                    @click="timeForm.flowSpeed = 60">60x</button>
                  <button type="button" class="btn btn-outline btn-primary join-item w-16" :class="{ 'btn-active': timeForm.flowSpeed === 180 }"
                    @click="timeForm.flowSpeed = 180">180x</button>
                </div>
                <span class="text-sm">或自定义:</span>
                <input v-model.number="timeForm.flowSpeed" type="number" min="0.1" step="0.1"
                  class="input input-bordered w-24" />
              </div>
            </FormField>
          </div>
        </section>
      </div>

      <div v-else class="border border-dashed border-base-200 rounded-2xl p-6 text-base-content/60">
        请选择左侧会场以查看详情。
      </div>
    </section>
  </div>

  <dialog :open="showDaisModal" class="modal">
    <div class="modal-box max-w-2xl">
      <h3 class="font-bold text-lg mb-4">选择主席团成员</h3>
      <FormField legend="搜索成员" label="按姓名或邮箱筛选" fieldsetClass="mb-4">
        <input v-model="daisSearch" type="text" placeholder="搜索用户" class="input input-bordered w-full" />
      </FormField>
      <fieldset class="fieldset">
        <legend class="fieldset-legend text-base font-semibold mb-3">候选成员列表</legend>
        <div class="max-h-60 overflow-y-auto space-y-2">
          <label v-for="user in filteredUsers" :key="user.id" class="flex items-center gap-2 p-2 border rounded">
            <input type="checkbox" v-model="selectedUserIds" :value="user.id" class="checkbox" />
            <span>{{ user.name }} ({{ user.email }})</span>
          </label>
        </div>
      </fieldset>
      <div class="modal-action">
        <button @click="showDaisModal = false; selectedUserIds = []" class="btn">取消</button>
        <button @click="confirmDaisSelection" class="btn btn-primary">确定</button>
      </div>
    </div>
  </dialog>

  <dialog v-if="showCreateModal" class="modal" open>
    <div class="modal-box max-w-2xl space-y-4">
      <h3 class="text-lg font-semibold">添加新会场</h3>
      <p class="text-sm text-base-content/70">填写基础信息后立即创建一个新的会场。</p>
      <form class="space-y-4" @submit.prevent="handleCreateVenue">
        <div class="grid gap-4 md:grid-cols-2">
          <FormField legend="会场代码" label="例如 UNSC" fieldsetClass="w-full">
            <input v-model="createForm.code" type="text" class="input input-bordered" placeholder="代码" required />
          </FormField>
          <FormField legend="会场名称" label="填写会场名称" fieldsetClass="w-full">
            <input v-model="createForm.name" type="text" class="input input-bordered" placeholder="正式名称" required />
          </FormField>
          <FormField legend="地点" label="会议室或酒店" fieldsetClass="w-full">
            <input v-model="createForm.venue" type="text" class="input input-bordered" placeholder="会议地点" />
          </FormField>
          <FormField legend="状态" label="初始状态" fieldsetClass="w-full">
            <select v-model="createForm.status" class="select select-bordered">
              <option v-for="item in statusOptions" :key="item.value" :value="item.value">{{ item.label }}</option>
            </select>
          </FormField>
          <FormField legend="容量" label="可容纳代表人数" fieldsetClass="md:col-span-2">
            <div class="input input-bordered flex items-center gap-2">
              <input v-model.number="createForm.capacity" type="number" min="10"
                class="grow bg-transparent focus:outline-none" placeholder="例如 60" />
              <span class="text-xs text-base-content/60">人</span>
            </div>
          </FormField>
        </div>
        <div v-if="createError" class="alert alert-error alert-soft text-sm">
          <span>{{ createError }}</span>
        </div>
        <div class="modal-action">
          <button type="button" class="btn" @click="closeCreateModal">取消</button>
          <button type="submit" class="btn btn-primary" :disabled="creatingVenue">
            <span v-if="creatingVenue" class="loading loading-spinner loading-sm"></span>
            <span>创建会场</span>
          </button>
        </div>
      </form>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click="closeCreateModal">close</button>
    </form>
  </dialog>
</template>
