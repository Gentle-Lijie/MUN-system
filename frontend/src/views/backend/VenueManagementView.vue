<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'

type CommitteeStatus = 'preparation' | 'in_session' | 'paused' | 'closed'

const statusOptions: { value: CommitteeStatus; label: string; badge: string }[] = [
  { value: 'preparation', label: '筹备中', badge: 'badge-warning' },
  { value: 'in_session', label: '进行中', badge: 'badge-success' },
  { value: 'paused', label: '暂停中', badge: 'badge-info' },
  { value: 'closed', label: '已闭幕', badge: 'badge-neutral' },
]

type CommitteeSession = {
  id: string
  topic: string
  chair: string
  start: string
  durationMinutes: number
}

type DaisMember = {
  id: string
  name: string
  role: string
  contact?: string
}

type TimeConfig = {
  start: string
  end: string
  flowSpeed: number
}

type CommitteeRecord = {
  id: string
  code: string
  name: string
  venue: string
  status: CommitteeStatus
  capacity: number
  agenda: CommitteeSession[]
  dais: DaisMember[]
  timeConfig: TimeConfig
}

const generateId = () => crypto.randomUUID?.() ?? Math.random().toString(36).slice(2, 9)

const committees = ref<CommitteeRecord[]>([
  {
    id: 'committee-sc',
    code: 'SC',
    name: '联合国安理会',
    venue: '会议中心 A1',
    status: 'in_session',
    capacity: 45,
    agenda: [
      { id: 'sc-s1', topic: '危机通报', chair: 'Alice', start: '2025-05-18T09:00', durationMinutes: 60 },
      { id: 'sc-s2', topic: '主要辩论', chair: 'Bob', start: '2025-05-18T10:15', durationMinutes: 90 },
    ],
    dais: [
      { id: 'alice', name: 'Alice', role: '主席', contact: '+86 138****0001' },
      { id: 'bob', name: 'Bob', role: '副主席', contact: '+1 202***1111' },
    ],
    timeConfig: {
      start: '2025-05-18T08:45',
      end: '2025-05-18T18:00',
      flowSpeed: 2,
    },
  },
  {
    id: 'committee-hrc',
    code: 'HRC',
    name: '人权理事会',
    venue: '会议中心 B3',
    status: 'preparation',
    capacity: 60,
    agenda: [{ id: 'hrc-s1', topic: '国家陈述', chair: 'Carol', start: '2025-05-19T13:30', durationMinutes: 75 }],
    dais: [
      { id: 'carol', name: 'Carol', role: '主席', contact: '+44 20****3333' },
    ],
    timeConfig: {
      start: '2025-05-19T09:30',
      end: '2025-05-19T19:00',
      flowSpeed: 1,
    },
  },
  {
    id: 'committee-crisis',
    code: 'Crisis',
    name: '危机特别会议',
    venue: '作战室 C2',
    status: 'paused',
    capacity: 30,
    agenda: [],
    dais: [
      { id: 'dawn', name: 'Dawn', role: '危机导演', contact: 'dawn@mun.local' },
    ],
    timeConfig: {
      start: '2025-05-18T00:00',
      end: '2025-05-20T23:59',
      flowSpeed: 4,
    },
  },
])

const filters = reactive({
  keyword: '',
  status: 'all' as 'all' | CommitteeStatus,
})

const selectedId = ref(committees.value[0]?.id ?? '')

const filteredCommittees = computed(() => {
  return committees.value.filter((committee) => {
    const keyword = filters.keyword.trim().toLowerCase()
    const keywordOk = keyword
      ? [committee.code, committee.name, committee.venue]
          .concat(committee.dais.map((d) => d.name))
          .some((field) => field.toLowerCase().includes(keyword))
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

const daisForm = reactive({
  name: '',
  role: '',
  contact: '',
})

const timeForm = reactive({
  start: '',
  end: '',
  flowSpeed: 1,
})

watch(
  () => selectedCommittee.value,
  (next) => {
    if (next) {
      timeForm.start = next.timeConfig.start
      timeForm.end = next.timeConfig.end
      timeForm.flowSpeed = next.timeConfig.flowSpeed
    }
  },
  { immediate: true },
)

const selectCommittee = (committeeId: string) => {
  selectedId.value = committeeId
}

const handleSaveBaseInfo = () => {
  if (!selectedCommittee.value) return
  window.alert(`已保存 ${selectedCommittee.value.name} 的基础信息（模拟）`)
}

const handleAddSession = () => {
  if (!selectedCommittee.value || !sessionForm.topic || !sessionForm.start) return
  selectedCommittee.value.agenda.push({
    id: generateId(),
    topic: sessionForm.topic,
    chair: sessionForm.chair || '未指定',
    start: sessionForm.start,
    durationMinutes: sessionForm.durationMinutes || 20,
  })
  sessionForm.topic = ''
  sessionForm.chair = ''
  sessionForm.start = ''
  sessionForm.durationMinutes = 20
}

const handleRemoveSession = (sessionId: string) => {
  if (!selectedCommittee.value) return
  selectedCommittee.value.agenda = selectedCommittee.value.agenda.filter((session) => session.id !== sessionId)
}

const handleAddDaisMember = () => {
  if (!selectedCommittee.value || !daisForm.name || !daisForm.role) return
  selectedCommittee.value.dais.push({
    id: generateId(),
    name: daisForm.name,
    role: daisForm.role,
    contact: daisForm.contact,
  })
  daisForm.name = ''
  daisForm.role = ''
  daisForm.contact = ''
}

const handleRemoveDaisMember = (memberId: string) => {
  if (!selectedCommittee.value) return
  selectedCommittee.value.dais = selectedCommittee.value.dais.filter((member) => member.id !== memberId)
}

const handleSaveTimeConfig = () => {
  if (!selectedCommittee.value) return
  selectedCommittee.value.timeConfig = {
    start: timeForm.start,
    end: timeForm.end,
    flowSpeed: timeForm.flowSpeed,
  }
  window.alert('时间轴配置已保存（示例）')
}

const handleDisplayBoard = (committee: CommitteeRecord, mode: 'preview' | 'sync') => {
  if (mode === 'preview') {
    const url = `/display?committee=${committee.id}`
    window.open(url, '_blank', 'noopener')
    return
  }
  window.alert(`已向显示大屏推送 ${committee.name} 的最新状态（示例操作）`)
}

const createModalOpen = ref(false)
const createForm = reactive({
  code: '',
  name: '',
  venue: '',
  capacity: 40,
  status: 'preparation' as CommitteeStatus,
  start: '',
  end: '',
  flowSpeed: 1,
})

const resetCreateForm = () => {
  createForm.code = ''
  createForm.name = ''
  createForm.venue = ''
  createForm.capacity = 40
  createForm.status = 'preparation'
  createForm.start = ''
  createForm.end = ''
  createForm.flowSpeed = 1
}

const handleCreateCommittee = () => {
  if (!createForm.code || !createForm.name || !createForm.venue) return
  const newRecord: CommitteeRecord = {
    id: `committee-${createForm.code.toLowerCase()}`,
    code: createForm.code,
    name: createForm.name,
    venue: createForm.venue,
    capacity: createForm.capacity,
    status: createForm.status,
    agenda: [],
    dais: [],
    timeConfig: {
      start: createForm.start || new Date().toISOString().slice(0, 16),
      end: createForm.end || new Date().toISOString().slice(0, 16),
      flowSpeed: createForm.flowSpeed,
    },
  }
  committees.value.unshift(newRecord)
  selectedId.value = newRecord.id
  createModalOpen.value = false
  resetCreateForm()
}

const committeeStats = computed(() => ({
  total: committees.value.length,
  running: committees.value.filter((c) => c.status === 'in_session').length,
  paused: committees.value.filter((c) => c.status === 'paused').length,
  closed: committees.value.filter((c) => c.status === 'closed').length,
}))
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4 flex flex-wrap justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold">会场管理</h2>
        <p class="text-sm text-base-content/70">配置会场信息、议程、主席团，以及时间轴/大屏同步。</p>
      </div>
      <button class="btn btn-primary" @click="createModalOpen = true">新增会场</button>
    </header>

    <section class="grid gap-6 xl:grid-cols-[1.6fr,1fr]">
      <div class="space-y-4">
        <div class="flex flex-wrap gap-3">
          <label class="input input-bordered flex items-center gap-2 grow min-w-[14rem]">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4 opacity-50" fill="currentColor">
              <path
                d="M11 4a7 7 0 015.618 11.16l3.11 3.11a1 1 0 01-1.414 1.415l-3.11-3.112A7 7 0 1111 4zm0 2a5 5 0 100 10 5 5 0 000-10z" />
            </svg>
            <input v-model="filters.keyword" type="text" class="grow" placeholder="按会场/地点/主席团搜索" />
          </label>
          <select v-model="filters.status" class="select select-bordered w-40">
            <option value="all">全部状态</option>
            <option v-for="item in statusOptions" :key="item.value" :value="item.value">{{ item.label }}</option>
          </select>
          <button class="btn btn-outline">导出配置</button>
        </div>

        <div class="stats stats-vertical md:stats-horizontal shadow-none">
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

        <div class="grid gap-4 md:grid-cols-2">
          <article
            v-for="committee in filteredCommittees"
            :key="committee.id"
            class="border border-base-200 rounded-2xl p-4 space-y-3 hover:border-primary cursor-pointer"
            :class="{ 'border-primary shadow-lg': committee.id === selectedId }"
            @click="selectCommittee(committee.id)"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-xs text-base-content/60">{{ committee.code }}</p>
                <h3 class="text-lg font-semibold leading-tight">{{ committee.name }}</h3>
                <p class="text-sm text-base-content/70">{{ committee.venue }}</p>
              </div>
              <span class="badge" :class="statusOptions.find((s) => s.value === committee.status)?.badge">
                {{ statusOptions.find((s) => s.value === committee.status)?.label }}
              </span>
            </div>
            <div class="flex flex-wrap gap-4 text-sm text-base-content/70">
              <span>容量 {{ committee.capacity }} 人</span>
              <span>议程 {{ committee.agenda.length }} 条</span>
              <span>主席团 {{ committee.dais.length }} 人</span>
            </div>
            <div class="flex flex-wrap gap-2">
              <button class="btn btn-xs" @click.stop="handleDisplayBoard(committee, 'preview')">预览大屏</button>
              <button class="btn btn-xs btn-outline" @click.stop="handleDisplayBoard(committee, 'sync')">推送状态</button>
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
            <label class="form-control">
              <span class="label-text">会场名称</span>
              <input v-model="selectedCommittee.name" type="text" class="input input-bordered" />
            </label>
            <label class="form-control">
              <span class="label-text">地点/房间</span>
              <input v-model="selectedCommittee.venue" type="text" class="input input-bordered" />
            </label>
            <label class="form-control">
              <span class="label-text">状态</span>
              <select v-model="selectedCommittee.status" class="select select-bordered">
                <option v-for="item in statusOptions" :key="item.value" :value="item.value">{{ item.label }}</option>
              </select>
            </label>
            <label class="form-control">
              <span class="label-text">容纳人数</span>
              <input v-model.number="selectedCommittee.capacity" type="number" min="10" class="input input-bordered" />
            </label>
          </div>
        </section>

        <section class="border border-base-200 rounded-2xl p-4 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold">议程 · {{ selectedCommittee.agenda.length }} 条</h3>
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
                <tr v-for="session in selectedCommittee.agenda" :key="session.id">
                  <td>{{ new Date(session.start).toLocaleString() }}</td>
                  <td class="font-semibold">{{ session.topic }}</td>
                  <td>{{ session.chair }}</td>
                  <td>{{ session.durationMinutes }} 分钟</td>
                  <td><button class="btn btn-ghost btn-xs" @click="handleRemoveSession(session.id)">移除</button></td>
                </tr>
                <tr v-if="selectedCommittee.agenda.length === 0">
                  <td colspan="5" class="text-center text-base-content/60">尚未设置议程</td>
                </tr>
              </tbody>
            </table>
          </div>
          <form class="grid lg:grid-cols-4 gap-3" @submit.prevent="handleAddSession">
            <input v-model="sessionForm.topic" type="text" placeholder="议题/阶段" class="input input-bordered" />
            <input v-model="sessionForm.chair" type="text" placeholder="主持主席团" class="input input-bordered" />
            <input v-model="sessionForm.start" type="datetime-local" class="input input-bordered" />
            <label class="input input-bordered flex items-center gap-2">
              <span class="text-xs">分钟</span>
              <input v-model.number="sessionForm.durationMinutes" type="number" min="5" class="grow" />
            </label>
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
          <form class="grid md:grid-cols-3 gap-3" @submit.prevent="handleAddDaisMember">
            <input v-model="daisForm.name" type="text" placeholder="姓名" class="input input-bordered" />
            <input v-model="daisForm.role" type="text" placeholder="角色" class="input input-bordered" />
            <input v-model="daisForm.contact" type="text" placeholder="联系方式" class="input input-bordered" />
            <button type="submit" class="btn btn-outline md:col-span-3">添加主席团成员</button>
          </form>
        </section>

        <section class="border border-base-200 rounded-2xl p-4 space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold">时间轴 & 时间流速</h3>
            <button class="btn btn-sm btn-primary" @click="handleSaveTimeConfig">保存</button>
          </div>
          <div class="grid gap-3 md:grid-cols-2">
            <label class="form-control">
              <span class="label-text">现实时间开始</span>
              <input v-model="timeForm.start" type="datetime-local" class="input input-bordered" />
            </label>
            <label class="form-control">
              <span class="label-text">现实时间结束</span>
              <input v-model="timeForm.end" type="datetime-local" class="input input-bordered" />
            </label>
          </div>
          <label class="form-control">
            <span class="label-text">时间流速 x{{ timeForm.flowSpeed }}</span>
            <input v-model.number="timeForm.flowSpeed" type="range" min="1" max="6" class="range range-primary" />
            <div class="flex justify-between text-xs text-base-content/60 mt-1">
              <span>1x</span>
              <span>2x</span>
              <span>3x</span>
              <span>4x</span>
              <span>5x</span>
              <span>6x</span>
            </div>
          </label>
        </section>
      </div>

      <div v-else class="border border-dashed border-base-200 rounded-2xl p-6 text-base-content/60">
        请选择左侧会场以查看详情。
      </div>
    </section>
  </div>

  <div class="modal" :class="{ 'modal-open': createModalOpen }">
    <div class="modal-box max-w-2xl">
      <h3 class="font-bold text-lg mb-4">新增会场</h3>
      <form class="space-y-4" @submit.prevent="handleCreateCommittee">
        <div class="grid gap-3 md:grid-cols-2">
          <label class="form-control">
            <span class="label-text">会场代号</span>
            <input v-model="createForm.code" type="text" class="input input-bordered" placeholder="例如 HRC" />
          </label>
          <label class="form-control">
            <span class="label-text">会场名称</span>
            <input v-model="createForm.name" type="text" class="input input-bordered" />
          </label>
          <label class="form-control">
            <span class="label-text">举办地点</span>
            <input v-model="createForm.venue" type="text" class="input input-bordered" />
          </label>
          <label class="form-control">
            <span class="label-text">容纳人数</span>
            <input v-model.number="createForm.capacity" type="number" min="10" class="input input-bordered" />
          </label>
          <label class="form-control">
            <span class="label-text">默认状态</span>
            <select v-model="createForm.status" class="select select-bordered">
              <option v-for="item in statusOptions" :key="item.value" :value="item.value">{{ item.label }}</option>
            </select>
          </label>
          <label class="form-control">
            <span class="label-text">时间流速</span>
            <input v-model.number="createForm.flowSpeed" type="number" min="1" max="6" class="input input-bordered" />
          </label>
        </div>
        <div class="grid gap-3 md:grid-cols-2">
          <label class="form-control">
            <span class="label-text">开始时间</span>
            <input v-model="createForm.start" type="datetime-local" class="input input-bordered" />
          </label>
          <label class="form-control">
            <span class="label-text">结束时间</span>
            <input v-model="createForm.end" type="datetime-local" class="input input-bordered" />
          </label>
        </div>
        <div class="modal-action">
          <button type="button" class="btn" @click="createModalOpen = false; resetCreateForm()">取消</button>
          <button type="submit" class="btn btn-primary">创建</button>
        </div>
      </form>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click="createModalOpen = false; resetCreateForm()">关闭</button>
    </form>
  </div>
</template>
