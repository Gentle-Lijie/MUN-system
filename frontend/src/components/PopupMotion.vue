<template>
  <dialog v-if="modelValue" class="modal" open>
    <div class="modal-box w-11/12 max-w-6xl bg-transparent p-0 h-[90vh] overflow-y-auto">
      <div class="flex flex-col gap-4 rounded-3xl bg-base-100 p-6 lg:p-10 h-full">
        <div class="flex flex-col gap-2 border-b border-base-200 pb-6">
          <p class="text-base-content/60 text-base">动议控制面板</p>
          <h3 class="text-3xl font-bold">选择并配置要发起的动议</h3>
        </div>

        <div class="grid gap-6 lg:grid-cols-[35%_65%] h-[70vh] min-h-[360px]">
          <section
            class="flex flex-col gap-4 rounded-3xl border border-base-200 bg-base-200/40 p-4 lg:p-6 h-full overflow-hidden">
            <header class="flex items-center justify-between">
              <h4 class="text-xl font-semibold">可用动议</h4>
              <span class="badge badge-neutral badge-lg">共 {{ motions.length }} 项</span>
            </header>
            <div class="space-y-3 overflow-y-auto pr-1 max-h-full">
              <button v-for="motion in motions" :key="motion.id" type="button"
                class="w-full rounded-2xl border px-4 py-4 text-left transition" :class="selectedMotionId === motion.id
                  ? 'border-primary bg-primary/10 shadow-lg'
                  : 'border-base-300 bg-base-100/70 hover:border-base-200'
                  " @click="selectedMotionId = motion.id">
                <div>
                  <p class="text-xl font-semibold">{{ motion.title }}</p>
                  <p class="text-sm text-base-content/70">{{ motion.description }}</p>
                </div>
              </button>
            </div>
          </section>

          <section
            class="flex flex-col gap-6 rounded-3xl border border-base-200 bg-base-200/40 p-4 lg:p-6 h-full overflow-y-auto">
            <header class="flex flex-col gap-2">
              <h4 class="text-3xl font-semibold">{{ activeMotion?.title }}</h4>
              <p class="text-sm text-base-content/70">{{ activeMotion?.description }}</p>
            </header>
            <div class="flex flex-col gap-5">
              <div class="grid gap-4">
                <div v-if="activeMotion?.requires.country" class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_auto]">
                  <FormField legend="发起国家" label="手动输入发起国家">
                    <div class="input input-bordered flex items-center gap-2">
                      <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                        fill="none">
                        <path d="M12 12c2.7 0 8 1.34 8 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2c0-2.66 5.3-4 8-4z"
                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                          stroke-linejoin="round" />
                      </svg>
                      <input v-model="formState.country" type="text" class="grow bg-transparent focus:outline-none"
                        required />
                    </div>
                  </FormField>
                  <FormField legend="代表名单" label="或从代表名单中选择">
                    <select class="select select-bordered w-full" @change="onDelegateSelect">
                      <option value="">选择代表</option>
                      <option v-for="delegate in delegates" :key="delegate.id" :value="delegate.id">
                        {{ delegate.country }} - {{ delegate.userName }}
                      </option>
                    </select>
                  </FormField>
                </div>
                <template v-if="activeMotion?.requires.unitTime && activeMotion?.requires.totalTime">
                  <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <FormField legend="单位时间（秒）" label="每位发言代表的时间">
                      <div class="input input-bordered flex items-center gap-2">
                        <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                          fill="none">
                          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                          <polyline points="12,6 12,12 16,14" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <input v-model.number="formState.unitTime" type="number" min="10" max="3600"
                          class="grow bg-transparent focus:outline-none" required />
                      </div>
                    </FormField>
                    <FormField legend="总时间（秒）" label="整个动议的总时长">
                      <div class="input input-bordered flex items-center gap-2">
                        <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                          fill="none">
                          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                          <polyline points="12,6 12,12 16,14" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <input v-model.number="formState.totalTime" type="number" min="30" max="7200"
                          class="grow bg-transparent focus:outline-none" required />
                      </div>
                    </FormField>
                  </div>
                </template>
                <FormField v-else-if="activeMotion?.requires.unitTime" legend="单位时间（秒）" label="每位发言代表的时间">
                  <div class="input input-bordered flex items-center gap-2">
                    <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                      fill="none">
                      <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                      <polyline points="12,6 12,12 16,14" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    </svg>
                    <input v-model.number="formState.unitTime" type="number" min="10" max="3600"
                      class="grow bg-transparent focus:outline-none" required />
                  </div>
                </FormField>
                <FormField v-else-if="activeMotion?.requires.totalTime" legend="总时间（秒）" label="整个动议的总时长">
                  <div class="input input-bordered flex items-center gap-2">
                    <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                      fill="none">
                      <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                      <polyline points="12,6 12,12 16,14" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    </svg>
                    <input v-model.number="formState.totalTime" type="number" min="30" max="7200"
                      class="grow bg-transparent focus:outline-none" required />
                  </div>
                </FormField>
                <FormField legend="附加说明（可选）" label="补充主持要点或文件信息">
                  <textarea v-model="formState.notes" class="textarea textarea-bordered text-base" rows="4"></textarea>
                </FormField>
                <button v-if="activeMotion?.id === 'reading' || activeMotion?.id === 'voting-doc'" type="button"
                  class="btn btn-outline btn-lg text-lg mb-3" @click="showFileSelect = true">
                  关联文件
                </button>

                <FormField legend="自动发起点名" label="动议通过后立即点名" description="勾选后，动议获得通过时会自动触发代表点名"
                  :label-class="'flex items-center justify-between gap-4'">
                  <input v-model="formState.triggerRollCall" type="checkbox" class="checkbox" />
                </FormField>
              </div>
              <div class="grid gap-4 lg:grid-cols-[4fr_4fr_2fr]">
                <button type="button" class="btn btn-success btn-lg text-lg" @click="handlePass">
                  获得通过
                </button>
                <button type="button" class="btn btn-error btn-lg text-lg" @click="handleFail">
                  未获通过
                </button>
                <button type="button" class="btn btn-ghost btn-lg text-lg" @click="handleClose">
                  取消
                </button>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click.prevent="handleClose">关闭</button>
    </form>
    <PopupFileSelect v-model="showFileSelect" @select="handleFileSelect" />
    <PopupRollCall
      v-model="showRollCall"
      :delegates="delegates"
      @confirm="handleRollCallConfirm"
      @cancel="handleRollCallCancel"
    />
    <PopupMajority
      v-model="showMajority"
      :stats="majorityStats"
      @select="handleMajoritySelect"
      @cancel="handleMajorityCancel"
    />
    <PopupVoting
      v-model="showVoting"
      :delegates="votingDelegates"
      @complete="handleVotingComplete"
      @cancel="handleVotingCancel"
    />
    <PopupVoteResult
      v-model="showVoteResult"
      :result="voteResultPayload"
      @confirm="handleVoteResultConfirm"
      @retry="handleVoteResultRetry"
    />

    <!-- 计时窗口 -->
    <dialog v-if="showTimer" class="modal" open>
      <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">{{ activeMotion?.title }} 计时</h3>
        <div class="text-center">
          <div class="text-6xl font-bold mb-4">
            {{ formatTimer(timerRemainingSeconds) }}
          </div>
          <div class="text-sm text-base-content/70 mb-6">
            总时长：{{ formatTimer(timerTotalSeconds) }}
          </div>
          <div class="flex gap-4 justify-center">
            <button class="btn btn-primary" :disabled="timerRunning" @click="startTimer">
              开始
            </button>
            <button class="btn btn-secondary" :disabled="!timerRunning" @click="pauseTimer">
              暂停
            </button>
            <button class="btn btn-accent" @click="stopTimer">
              停止
            </button>
          </div>
        </div>
        <div class="modal-action">
          <button class="btn btn-success" @click="confirmTimer">确认完成</button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click.prevent="() => { showTimer = false; stopTimer() }">关闭</button>
      </form>
    </dialog>
  </dialog>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import PopupVoting, { type VotingSummary, type VoteRecord as VotingRecord, type Delegate as VotingDelegate } from '@/components/PopupVoting.vue'
import PopupRollCall, { type AttendanceStatus } from '@/components/PopupRollCall.vue'
import PopupMajority, { type MajorityOption, type MajorityStats } from '@/components/PopupMajority.vue'
import PopupVoteResult from '@/components/PopupVoteResult.vue'
import PopupFileSelect from '@/components/PopupFileSelect.vue'
import FormField from '@/components/common/FormField.vue'
import { API_BASE } from '@/services/api'
import type { FileReference } from '@/services/api'

type MotionField = 'country' | 'unitTime' | 'totalTime'

type MotionDefinition = {
  id: string
  title: string
  description: string
  badges: string[]
  requires: Record<MotionField, boolean>
}

type MotionFormState = {
  country: string
  unitTime: number
  totalTime: number
  notes: string
  triggerRollCall: boolean
  proposerId?: number
  fileId?: number | null
}

export type VoteResultPayload = {
  majority: MajorityOption
  rollCall: { total: number; present: number }
  summary: {
    yes: number
    no: number
    abstain: number
    effectiveTotal: number
    ratio: number
    requiredVotes: number
    requiredRatio: number
    passed: boolean
  }
  votes: VotingRecord[]
}

const motions: MotionDefinition[] = [
  {
    id: 'main-speakers',
    title: '开启主发言名单',
    description: '开启新的主发言名单流程，仅需设置单位发言时长。',
    badges: ['发起国家', '单位时间'],
    requires: { country: true, unitTime: true, totalTime: false },
  },
  {
    id: 'moderated-caucus',
    title: '有主持核心磋商',
    description: '设置主持核心磋商的主题与时间。',
    badges: ['发起国家', '单位时间', '总时间'],
    requires: { country: true, unitTime: true, totalTime: true },
  },
  {
    id: 'unmoderated-caucus',
    title: '自由磋商',
    description: '开启自由磋商时间，不需要单位时间。',
    badges: ['发起国家', '总时间'],
    requires: { country: true, unitTime: false, totalTime: true },
  },
  {
    id: 'free-debate',
    title: '自由辩论',
    description: '发起自由辩论环节，设置总时间。',
    badges: ['发起国家', '总时间'],
    requires: { country: true, unitTime: false, totalTime: true },
  },
  {
    id: 'point-of-inquiry',
    title: '质询权',
    description: '发起质询，需要记录发起国家。',
    badges: ['发起国家'],
    requires: { country: true, unitTime: false, totalTime: false },
  },
  {
    id: 'enter-special',
    title: '进入特殊状态（时间轴改变）',
    description: '进入特殊状态，记录发起国即可。',
    badges: ['发起国家'],
    requires: { country: true, unitTime: false, totalTime: false },
  },
  {
    id: 'exit-special',
    title: '退出特殊状态（时间轴改变）',
    description: '结束特殊状态，标记发起国。',
    badges: ['发起国家'],
    requires: { country: true, unitTime: false, totalTime: false },
  },
  {
    id: 'adjourn',
    title: '休会',
    description: '请求临时休会或延长休息时间。',
    badges: ['发起国家'],
    requires: { country: true, unitTime: false, totalTime: false },
  },
  {
    id: 'reading',
    title: '阅读文件',
    description: '安排时间阅读文件，可设置总时间。',
    badges: ['发起国家', '总时间'],
    requires: { country: true, unitTime: false, totalTime: true },
  },
  {
    id: 'personal-speech',
    title: '个人演讲',
    description: '个人发起演讲，需设置时间。',
    badges: ['发起国家', '总时间'],
    requires: { country: true, unitTime: false, totalTime: true },
  },
  {
    id: 'voting-doc',
    title: '对文件投票',
    description: '请求对文件进行投票，记录发起国家。',
    badges: ['发起国家'],
    requires: { country: true, unitTime: false, totalTime: false },
  },
  {
    id: 'right-of-reply',
    title: '答辩权',
    description: '申请答辩权，需要发起国家与时间。',
    badges: ['发起国家', '总时间'],
    requires: { country: true, unitTime: false, totalTime: true },
  },
]

const props = defineProps<{ modelValue: boolean; committeeId?: string }>()
const emit = defineEmits<{
  (_e: 'update:modelValue', _value: boolean): void
  (_e: 'pass', payload: { motion: MotionDefinition; form: MotionFormState; voteResult?: VoteResultPayload | null }): void
  (_e: 'fail', payload: { motion: MotionDefinition; form: MotionFormState }): void
}>()

const delegates = ref<VotingDelegate[]>([])

const selectedMotionId = ref(motions[0]?.id ?? '')
const formState = reactive<MotionFormState>({
  country: '',
  unitTime: 120,
  totalTime: 600,
  notes: '',
  triggerRollCall: false,
  proposerId: undefined,
  fileId: null,
})
const showFileSelect = ref(false)
const showRollCall = ref(false)
const showMajority = ref(false)
const showVoting = ref(false)
const showVoteResult = ref(false)
const selectedFile = ref<FileReference | null>(null)
const majorityStats = ref<MajorityStats | null>(null)
const selectedMajority = ref<MajorityOption | null>(null)
const voteResultPayload = ref<VoteResultPayload | null>(null)
const rollCallStats = ref<{ total: number; present: number } | null>(null)
const rollCallAttendance = ref<Record<number, AttendanceStatus>>({})
const showTimer = ref(false)
const timerTotalSeconds = ref(0)
const timerRemainingSeconds = ref(0)
const timerRunning = ref(false)
let timerInterval: number | null = null

const activeMotion = computed(() => motions.find((motion) => motion.id === selectedMotionId.value))
const isVotingMotion = computed(() => activeMotion.value?.id === 'voting-doc')
const votingDelegates = computed<VotingDelegate[]>(() => {
  if (!Object.keys(rollCallAttendance.value).length) {
    return delegates.value
  }
  return delegates.value.filter((delegate) => rollCallAttendance.value[delegate.id] !== 'absent')
})

// 加载代表列表
const loadDelegates = async () => {
  if (!props.committeeId) return
  try {
    const response = await fetch(`${API_BASE}/api/venues/${props.committeeId}/delegate`, {
      credentials: 'include',
    })
    if (!response.ok) throw new Error('Failed to load delegates')
    const data = await response.json()
    delegates.value = data.items || []
  } catch (error) {
    console.error('Failed to load delegates:', error)
  }
}

watch(
  () => props.modelValue,
  (visible) => {
    if (visible) {
      loadDelegates()
    } else {
      resetForm()
    }
  }
)

function resetForm() {
  formState.country = ''
  formState.unitTime = 120
  formState.totalTime = 600
  formState.notes = ''
  formState.triggerRollCall = false
  formState.proposerId = undefined
  selectedMotionId.value = motions[0]?.id ?? ''
  formState.fileId = null
  selectedFile.value = null
  resetVotingFlow()
}

// 监听动议类型变化，自动设置点名选项
watch(selectedMotionId, (newId) => {
  // 对文件投票默认勾选点名
  formState.triggerRollCall = newId === 'voting-doc'
})

// 处理代表选择
const onDelegateSelect = (event: Event) => {
  const target = event.target as HTMLSelectElement
  const delegateId = target.value
  if (delegateId) {
    const delegate = delegates.value.find((d) => d.id.toString() === delegateId)
    if (delegate) {
      formState.country = delegate.country
      formState.proposerId = delegate.id
    }
  }
}

// 监听手动输入国家，如果不匹配代表则清除proposerId
watch(
  () => formState.country,
  (newCountry) => {
    const matchedDelegate = delegates.value.find((d) => d.country === newCountry)
    if (matchedDelegate) {
      formState.proposerId = matchedDelegate.id
    } else {
      formState.proposerId = undefined
    }
  }
)

const handlePass = async () => {
  if (!activeMotion.value) return
  if (isVotingMotion.value) {
    startVotingWorkflow()
    return
  }

  // 对于不需要speakerList的动议，弹出计时窗口
  if (['unmoderated_caucus', 'free_debate', 'reading', 'personal_speech', 'right_of_reply'].includes(activeMotion.value.id)) {
    timerTotalSeconds.value = formState.totalTime || 0
    timerRemainingSeconds.value = timerTotalSeconds.value
    showTimer.value = true
    return
  }

  emit('pass', {
    motion: activeMotion.value,
    form: { ...formState },
  })
  emit('update:modelValue', false)
  resetForm()
}

function handleFail() {
  if (!activeMotion.value) return
  emit('fail', {
    motion: activeMotion.value,
    form: { ...formState },
  })
  emit('update:modelValue', false)
  resetForm()
}

function handleFileSelect(file: FileReference) {
  selectedFile.value = file
  formState.fileId = file.id
  const linked = `关联文件: ${file.title}`
  const existingNotes = formState.notes.split('\n').filter((line) => line.trim().length > 0)
  if (!existingNotes.includes(linked)) {
    formState.notes = [...existingNotes, linked].join('\n')
  }
}

const startVotingWorkflow = () => {
  resetVotingFlow()
  if (formState.triggerRollCall) {
    showRollCall.value = true
  } else {
    const attendance: Record<number, AttendanceStatus> = {}
    delegates.value.forEach((delegate) => {
      attendance[delegate.id] = 'present'
    })
    rollCallAttendance.value = attendance
    rollCallStats.value = { total: delegates.value.length, present: delegates.value.length }
    majorityStats.value = buildMajorityStats(delegates.value.length, delegates.value.length)
    showMajority.value = true
  }
}

const buildMajorityStats = (total: number, present: number): MajorityStats => {
  const safePresent = Math.max(present, 0)
  return {
    total,
    present: safePresent,
    threeQuarters: Math.max(0, Math.ceil(safePresent * 0.75)),
    twoThirds: Math.max(0, Math.ceil(safePresent * (2 / 3))),
    half: Math.max(0, Math.ceil(safePresent * 0.5)),
    twentyPercent: Math.max(0, Math.ceil(safePresent * 0.2))
  }
}

const handleRollCallConfirm = async (attendance: Record<string, AttendanceStatus>) => {
  const normalized = Object.entries(attendance).reduce<Record<number, AttendanceStatus>>((acc, [id, status]) => {
    acc[Number(id)] = status
    return acc
  }, {})

  rollCallAttendance.value = normalized
  const present = Object.values(normalized).filter((status) => status === 'present').length
  rollCallStats.value = { total: delegates.value.length, present }
  majorityStats.value = buildMajorityStats(delegates.value.length, present)

  await submitRollCall(attendance)

  showMajority.value = true
}

const handleRollCallCancel = () => {
  resetVotingFlow()
}

const submitRollCall = async (attendance: Record<string, AttendanceStatus>) => {
  if (!props.committeeId) return
  try {
    await fetch(`${API_BASE}/api/display/roll-call`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ committeeId: Number(props.committeeId), attendance })
    })
  } catch (error) {
    console.error('Failed to submit roll call:', error)
  }
}

const handleMajoritySelect = (option: MajorityOption) => {
  selectedMajority.value = option
  showVoting.value = true
}

const handleMajorityCancel = () => {
  resetVotingFlow()
}

const handleVotingComplete = (result: VotingSummary) => {
  if (!selectedMajority.value || !rollCallStats.value) return
  const effectiveTotal = result.summary.effectiveTotal
  const ratio = effectiveTotal > 0 ? result.summary.yes / effectiveTotal : 0
  const requiredRatio = selectedMajority.value.ratio
  const requiredVotes = Math.ceil(requiredRatio * rollCallStats.value.present)
  voteResultPayload.value = {
    majority: selectedMajority.value,
    rollCall: rollCallStats.value,
    summary: {
      ...result.summary,
      requiredVotes,
      requiredRatio,
      ratio,
      passed: ratio >= requiredRatio
    },
    votes: result.votes
  }
  showVoting.value = false
  showVoteResult.value = true
}

const handleVotingCancel = () => {
  resetVotingFlow()
}

const handleVoteResultConfirm = () => {
  if (!activeMotion.value || !voteResultPayload.value) return

  const summary = voteResultPayload.value.summary
  const effectiveTotal = summary.effectiveTotal
  const ratio = effectiveTotal > 0 ? summary.yes / effectiveTotal : 0
  const requiredRatio = voteResultPayload.value.majority.ratio
  const requiredVotes = Math.ceil(requiredRatio * voteResultPayload.value.rollCall.present)
  const passed = ratio >= requiredRatio

  voteResultPayload.value.summary.passed = passed
  voteResultPayload.value.summary.ratio = ratio
  voteResultPayload.value.summary.requiredRatio = requiredRatio
  voteResultPayload.value.summary.requiredVotes = requiredVotes

  emit('pass', {
    motion: activeMotion.value,
    form: { ...formState },
    voteResult: voteResultPayload.value
  })
  emit('update:modelValue', false)
  resetForm()
}

const handleVoteResultRetry = () => {
  showVoteResult.value = false
  showVoting.value = true
  voteResultPayload.value = null
}

const resetVotingFlow = () => {
  showRollCall.value = false
  showMajority.value = false
  showVoting.value = false
  showVoteResult.value = false
  selectedMajority.value = null
  majorityStats.value = null
  voteResultPayload.value = null
  rollCallStats.value = null
  rollCallAttendance.value = {}
}

const formatTimer = (seconds: number): string => {
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}

const startTimer = () => {
  if (timerRunning.value) return
  timerRunning.value = true
  timerInterval = setInterval(() => {
    if (timerRemainingSeconds.value > 0) {
      timerRemainingSeconds.value--
    } else {
      stopTimer()
    }
  }, 1000)
}

const pauseTimer = () => {
  timerRunning.value = false
  if (timerInterval) {
    clearInterval(timerInterval)
    timerInterval = null
  }
}

const stopTimer = () => {
  timerRunning.value = false
  if (timerInterval) {
    clearInterval(timerInterval)
    timerInterval = null
  }
}

const confirmTimer = () => {
  if (!activeMotion.value) return
  emit('pass', {
    motion: activeMotion.value,
    form: { ...formState },
  })
  emit('update:modelValue', false)
  resetForm()
  showTimer.value = false
  stopTimer()
}

function handleClose() {
  emit('update:modelValue', false)
  resetForm()
}
</script>
