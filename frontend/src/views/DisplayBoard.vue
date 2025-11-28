<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import PopupDelegate from '@/components/PopupDelegate.vue'
import PopupMotion from '@/components/PopupMotion.vue'
import type { VoteResultPayload } from '@/components/PopupMotion.vue'
import PopupRollCall, { type AttendanceStatus, type RollCallDelegate } from '@/components/PopupRollCall.vue'
import { API_BASE } from '@/services/api'

const route = useRoute()
const committeeId = computed(() => route.params.committeeId as string)

const showPopupDelegate = ref(false)
const showPopupMotion = ref(false)
const showRollCallModal = ref(false)
const showStartSessionOverlay = ref(false)

// 数据状态
const committee = ref<any>(null)
const statistics = ref({
  total: 0,
  present: 0,
  threeQuarters: 0,
  twoThirds: 0,
  half: 0,
  twentyPercent: 0,
})
const speakerQueue = ref<any[]>([])
type HistoryVoteResult = {
  passed: boolean
  yes: number
  no: number
  abstain: number
}

const historyEvents = ref<Array<Record<string, any> & { voteResult?: HistoryVoteResult | null }>>([])
const delegates = ref<RollCallDelegate[]>([])
const rollCallAttendance = ref<Record<string, AttendanceStatus>>({})
const speakerListId = ref<string | null>(null)
const speakerListCurrentIndex = ref(0)
const speakerListTotalCount = ref(0)

const apiFetch = (path: string, options: RequestInit = {}) =>
  fetch(`${API_BASE}${path}`, {
    credentials: 'include',
    ...options,
  })

// 计时器状态
const timerRunning = ref(false)
const speakerTimerSeconds = ref(0)
const speakerTimerDefaultSeconds = ref(120) // 默认120秒，后续由动议覆盖
const caucusTotalSeconds = ref<number | null>(null)
const caucusRemainingSeconds = ref<number | null>(null)
const activeMotionMeta = ref<{
  motionType?: string | null
  unitTimeSeconds?: number | null
  totalTimeSeconds?: number | null
} | null>(null)
const caucusStorageKey = 'mun-caucus-remaining'
const caucusRemainingState = ref<Record<string, number>>({})
let timerInterval: number | null = null
// 响铃音频资源
const bellAudio = new Audio('/bell.mp3')
const playBell = (count = 1) => {
  if (count === 1) {
    bellAudio.currentTime = 0
    bellAudio.play()
  } else if (count === 2) {
    bellAudio.currentTime = 0
    bellAudio.play()
    bellAudio.currentTime = 0
    bellAudio.play()
  }
}

// 自由计时弹窗状态
const showFreeTimerModal = ref(false)
const freeTimerRunning = ref(false)
const freeTimerTotalSeconds = ref(600)
const freeTimerRemainingSeconds = ref(600)
const freeTimerInputMinutes = ref(10)
const freeTimerInputSeconds = ref(0)
let freeTimerInterval: number | null = null

// 时间显示状态
const currentDisplayTime = ref('00:00')
let timeUpdateInterval: number | null = null

type MotionSubmission = {
  motion: {
    id: string
    title: string
    requires: Record<'country' | 'unitTime' | 'totalTime', boolean>
  }
  form: {
    country: string
    unitTime: number
    totalTime: number
    notes: string
  }
  voteResult?: VoteResultPayload | null
}

const summarizeVoteResult = (payload?: VoteResultPayload | null): HistoryVoteResult | null => {
  const summary = payload?.summary
  if (!summary) return null
  return {
    passed: Boolean(summary.passed),
    yes: summary.yes ?? 0,
    no: summary.no ?? 0,
    abstain: summary.abstain ?? 0,
  }
}

const formatTimer = (seconds: number): string => {
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}

const isModeratedCaucus = computed(() => activeMotionMeta.value?.motionType === 'moderate_caucus')
const showCaucusTimer = computed(
  () => isModeratedCaucus.value && typeof caucusRemainingSeconds.value === 'number'
)

const loadCaucusState = () => {
  if (typeof window === 'undefined') return
  try {
    const raw = window.localStorage.getItem(caucusStorageKey)
    if (!raw) return
    const parsed = JSON.parse(raw)
    if (parsed && typeof parsed === 'object') {
      caucusRemainingState.value = parsed
    }
  } catch (error) {
    console.warn('Failed to load caucus timing cache', error)
  }
}

const persistCaucusState = () => {
  if (typeof window === 'undefined') return
  try {
    window.localStorage.setItem(caucusStorageKey, JSON.stringify(caucusRemainingState.value))
  } catch (error) {
    console.warn('Failed to persist caucus timing cache', error)
  }
}

const syncCaucusRemainingForCurrentList = () => {
  if (!speakerListId.value) return
  if (typeof caucusRemainingSeconds.value !== 'number') {
    const nextState = { ...caucusRemainingState.value }
    delete nextState[speakerListId.value]
    caucusRemainingState.value = nextState
  } else {
    caucusRemainingState.value = {
      ...caucusRemainingState.value,
      [speakerListId.value]: caucusRemainingSeconds.value,
    }
  }
  persistCaucusState()
}

const hydrateCaucusTiming = (listId: string | null, totalSeconds?: number | null) => {
  if (!listId || !totalSeconds) {
    caucusTotalSeconds.value = null
    caucusRemainingSeconds.value = null
    return
  }
  caucusTotalSeconds.value = totalSeconds
  const stored = caucusRemainingState.value[listId]
  if (typeof stored === 'number') {
    caucusRemainingSeconds.value = Math.min(Math.max(stored, 0), totalSeconds)
  } else {
    caucusRemainingSeconds.value = totalSeconds
    caucusRemainingState.value = {
      ...caucusRemainingState.value,
      [listId]: totalSeconds,
    }
    persistCaucusState()
  }
}

const configureMotionTiming = (motionMeta: any, listId: string | null) => {
  activeMotionMeta.value = motionMeta || null

  const unitSeconds = motionMeta?.unitTimeSeconds
  if (typeof unitSeconds === 'number' && unitSeconds > 0) {
    speakerTimerDefaultSeconds.value = unitSeconds
    if (!timerRunning.value) {
      speakerTimerSeconds.value = unitSeconds
    }
  } else if (!timerRunning.value && speakerTimerSeconds.value === 0) {
    speakerTimerSeconds.value = speakerTimerDefaultSeconds.value
  }

  if (motionMeta?.motionType === 'moderate_caucus' && motionMeta?.totalTimeSeconds && listId) {
    hydrateCaucusTiming(listId, motionMeta.totalTimeSeconds)
  } else {
    caucusTotalSeconds.value = null
    caucusRemainingSeconds.value = null
  }
}

// 时间计算函数
const calculateDisplayTime = () => {
  if (!committee.value?.timeConfig) return '00:00'

  const config = committee.value.timeConfig
  const flowSpeed = config.flowSpeed || 1
  const realTimeAnchor = config.realTimeAnchor ? new Date(config.realTimeAnchor) : new Date()
  const updateTime = config.updateTime ? new Date(config.updateTime) : new Date()
  const currentTime = new Date()

  // Formula: displayTime = realTimeAnchor + (currentTime - updateTime) * flowSpeed
  const elapsedMs = (currentTime.getTime() - updateTime.getTime()) * flowSpeed
  const displayDate = new Date(realTimeAnchor.getTime() + elapsedMs)

  const year = displayDate.getFullYear()
  const month = (displayDate.getMonth() + 1).toString().padStart(2, '0')
  const day = displayDate.getDate().toString().padStart(2, '0')
  const hours = displayDate.getHours().toString().padStart(2, '0')
  const minutes = displayDate.getMinutes().toString().padStart(2, '0')
  return `${year}/${month}/${day} ${hours}:${minutes}`
}

// 启动时间更新定时器
const startTimeUpdate = () => {
  if (timeUpdateInterval) clearInterval(timeUpdateInterval)
  timeUpdateInterval = setInterval(() => {
    currentDisplayTime.value = calculateDisplayTime()
  }, 1000) as unknown as number
}

// 停止时间更新定时器
const stopTimeUpdate = () => {
  if (timeUpdateInterval) {
    clearInterval(timeUpdateInterval)
    timeUpdateInterval = null
  }
}

// 加载大屏数据
const loadBoardData = async () => {
  try {
    const response = await apiFetch(`/api/display/board?committeeId=${committeeId.value}`)
    if (!response.ok) throw new Error('Failed to load board data')

    const data = await response.json()
    committee.value = data.committee
    statistics.value = data.statistics
    speakerQueue.value = data.speakerQueue || []
    speakerListId.value = data.speakerListId || null
    historyEvents.value = data.historyEvents || []
    speakerListCurrentIndex.value = data.currentIndex !== undefined ? data.currentIndex + 1 : 0
    speakerListTotalCount.value = data.totalLists || 0
    configureMotionTiming(data.activeMotion || null, speakerListId.value)

    // 检查是否需要显示开始会议按钮
    showStartSessionOverlay.value = ['preparation', 'paused'].includes(data.committee.status)

    // 设置初始时间显示
    currentDisplayTime.value = calculateDisplayTime()

    // 启动时间更新定时器
    startTimeUpdate()
  } catch (error) {
    console.error('Failed to load board data:', error)
  }
}

// 加载代表列表（用于点名）
const loadDelegates = async () => {
  try {
    const response = await apiFetch(`/api/venues/${committeeId.value}/delegate`)
    if (!response.ok) throw new Error('Failed to load delegates')

    const data = await response.json()
    delegates.value = (data.items || []) as RollCallDelegate[]

    const attendance: Record<string, AttendanceStatus> = {}
    delegates.value.forEach((d) => {
      const status = (d.status as AttendanceStatus) ?? 'present'
      attendance[d.id.toString()] = status
    })
    rollCallAttendance.value = attendance
  } catch (error) {
    console.error('Failed to load delegates:', error)
  }
}

// 处理点名按钮点击
const handleRollCallClick = async () => {
  await loadDelegates()
  showRollCallModal.value = true
}

// 开始会议
const startSession = async () => {
  try {
    const response = await apiFetch('/api/display/start-session', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ committeeId: committeeId.value }),
    })

    if (!response.ok) throw new Error('Failed to start session')

    // 隐藏覆盖层
    showStartSessionOverlay.value = false

    // 加载代表列表并触发点名
    await loadDelegates()
    showRollCallModal.value = true
  } catch (error) {
    console.error('Failed to start session:', error)
    alert('开始会议失败')
  }
}

// 提交点名结果
const submitRollCall = async (attendanceOverride?: Record<string, AttendanceStatus>) => {
  try {
    const attendancePayload = attendanceOverride ?? rollCallAttendance.value
    const response = await apiFetch('/api/display/roll-call', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        committeeId: committeeId.value,
        attendance: attendancePayload,
      }),
    })

    if (!response.ok) throw new Error('Failed to submit roll call')

    const data = await response.json()
    statistics.value = data
    rollCallAttendance.value = attendancePayload

    showRollCallModal.value = false
    await loadBoardData()
    return true
  } catch (error) {
    console.error('Failed to submit roll call:', error)
    alert('提交点名失败')
    return false
  }
}

const handleRollCallConfirm = async (attendance: Record<string, AttendanceStatus>) => {
  rollCallAttendance.value = attendance
  const ok = await submitRollCall(attendance)
  if (!ok) {
    showRollCallModal.value = true
  }
}

const handleRollCallCancel = () => {
  showRollCallModal.value = false
}

const onDelegateConfirm = async (delegate?: any) => {
  console.log('onDelegateConfirm called', { delegate, speakerListId: speakerListId.value })
  if (!delegate || !speakerListId.value) {
    console.warn('Missing delegate or speakerListId', {
      delegate,
      speakerListId: speakerListId.value,
    })
    return
  }

  console.log('Sending POST request to add speaker...')
  try {
    const response = await apiFetch('/api/display/speakers', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        speakerListId: speakerListId.value,
        delegateId: delegate.id,
      }),
    })

    if (!response.ok) throw new Error('Failed to add speaker')

    const data = await response.json()

    // 添加到发言队列（使用API返回的完整数据）
    speakerQueue.value.push({
      id: data.entry.id,
      country: data.entry.country || delegate.country,
      delegate: data.entry.delegateName || delegate.userName,
      status: data.entry.status,
      position: data.entry.position,
    })

    historyEvents.value.unshift({
      title: `添加发言者：${data.entry.country || delegate.country}`,
      description: `${data.entry.country || delegate.country} 已加入主发言名单。`,
    })
  } catch (error) {
    console.error('Failed to add speaker:', error)
    alert('添加发言者失败')
  }
}

// 切换发言列表
const switchSpeakerList = async (direction: 'prev' | 'next') => {
  try {
    // 如果当前发言列表为空，先删除它
    const deleteEmpty = speakerQueue.value.length === 0 && speakerListId.value

    const response = await apiFetch('/api/display/switch-speaker-list', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        committeeId: committeeId.value,
        direction,
        deleteEmptyList: deleteEmpty ? speakerListId.value : null,
      }),
    })

    if (!response.ok) throw new Error('Failed to switch speaker list')

    const data = await response.json()
    speakerListCurrentIndex.value = data.currentIndex + 1 // 转为1-based
    speakerListTotalCount.value = data.totalLists

    // 重新加载大屏数据以更新发言队列
    await loadBoardData()
  } catch (error) {
    console.error('Failed to switch speaker list:', error)
    alert('切换发言列表失败')
  }
}

const handleMotionPass = async ({ motion, form, voteResult }: MotionSubmission) => {
  try {
    // 映射前端动议ID到后端motionType (必须与数据库 ENUM 一致)
    const motionTypeMap: Record<string, string> = {
      'main-speakers': 'open_main_list',
      'moderated-caucus': 'moderate_caucus',
      'unmoderated-caucus': 'unmoderated_caucus',
      'free-debate': 'unmoderated_debate',
      'point-of-inquiry': 'right_of_query',
      'enter-special': 'begin_special_state',
      'exit-special': 'end_special_state',
      adjourn: 'adjourn_meeting',
      reading: 'document_reading',
      'personal-speech': 'personal_speech',
      'voting-doc': 'vote',
      'right-of-reply': 'right_of_reply',
    }

    const motionType = motionTypeMap[motion.id] || motion.id
    const summarizedVote = summarizeVoteResult(voteResult)
    const motionState = summarizedVote ? (summarizedVote.passed ? 'passed' : 'rejected') : 'passed'

    // 调用后端API创建动议
    const payload: Record<string, any> = {
      committeeId: committeeId.value,
      motionType: motionType,
      proposerId: (form as any).proposerId || null,
      unitTimeSeconds: form.unitTime || null,
      totalTimeSeconds: form.totalTime || null,
      state: motionState,
      description: form.notes || null,
    }

    if (voteResult) {
      payload.voteResult = voteResult
    }

    const response = await apiFetch('/api/motions', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })

    if (!response.ok) throw new Error('Failed to create motion')

    const data = await response.json()

    // 如果动议创建了新的发言名单，更新speakerListId
    if (data.motion.speakerListId) {
      speakerListId.value = data.motion.speakerListId
    }

    const summary: string[] = [motionState === 'passed' ? '获得通过' : '未获通过']
    if (motion.requires.country && form.country) {
      summary.push(`由 ${form.country} 发起`)
    }
    if (motion.requires.unitTime && form.unitTime) {
      summary.push(`单次 ${form.unitTime} 秒`)
    }
    if (motion.requires.totalTime && form.totalTime) {
      summary.push(`总时长 ${form.totalTime} 秒`)
    }
    if (form.notes) {
      summary.push(form.notes)
    }
    if (summarizedVote) {
      summary.push(
        `投票${summarizedVote.passed ? '通过' : '未通过'}（赞成 ${summarizedVote.yes} · 反对 ${summarizedVote.no} · 弃权 ${summarizedVote.abstain}）`
      )
    }

    historyEvents.value.unshift({
      title: `${motionState === 'passed' ? '动议通过' : '动议未通过'}：${motion.title}`,
      description: summary.join(' · ') || '请查看主持人备注。',
      voteResult: summarizedVote,
    })

    // 如果是休会，通过后设置会议状态为paused
    if (motion.id === 'adjourn' && motionState === 'passed') {
      await setCommitteeStatus('paused')
    }

    // 刷新大屏数据
    await loadBoardData()
  } catch (error) {
    console.error('Failed to handle motion pass:', error)
    alert('提交动议失败')
  }
}

const handleMotionFail = async ({ motion, form }: MotionSubmission) => {
  try {
    // 映射前端动议ID到后端motionType
    const motionTypeMap: Record<string, string> = {
      'main-speakers': 'open_main_list',
      'moderated-caucus': 'moderated_caucus',
      'unmoderated-caucus': 'unmoderated_caucus',
      'free-debate': 'free_debate',
      'point-of-inquiry': 'point_of_inquiry',
      'enter-special': 'enter_special',
      'exit-special': 'exit_special',
      adjourn: 'adjourn',
      reading: 'reading',
      'personal-speech': 'personal_speech',
      'voting-doc': 'voting_doc',
      'right-of-reply': 'right_of_reply',
    }

    const motionType = motionTypeMap[motion.id] || motion.id

    // 调用后端API创建动议
    const response = await apiFetch('/api/motions', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        committeeId: committeeId.value,
        motionType: motionType,
        proposerId: (form as any).proposerId || null,
        unitTimeSeconds: form.unitTime || null,
        totalTimeSeconds: form.totalTime || null,
        state: 'rejected',
        description: form.notes || null,
      }),
    })

    if (!response.ok) throw new Error('Failed to create motion')

    const summary: string[] = ['未获通过']
    if (motion.requires.country && form.country) {
      summary.push(`由 ${form.country} 发起`)
    }
    if (motion.requires.unitTime && form.unitTime) {
      summary.push(`单次 ${form.unitTime} 秒`)
    }
    if (motion.requires.totalTime && form.totalTime) {
      summary.push(`总时长 ${form.totalTime} 秒`)
    }
    if (form.notes) {
      summary.push(form.notes)
    }

    historyEvents.value.unshift({
      title: `动议未通过：${motion.title}`,
      description: summary.join(' · ') || '请查看主持人备注。',
    })

    // 刷新大屏数据
    await loadBoardData()
  } catch (error) {
    console.error('Failed to handle motion fail:', error)
    alert('提交动议失败')
  }
}

// 开始计时（纯前端控制）
const startTimer = () => {
  if (!speakerListId.value) {
    alert('没有活跃的发言列表')
    return
  }

  if (
    isModeratedCaucus.value &&
    typeof caucusRemainingSeconds.value === 'number' &&
    caucusRemainingSeconds.value <= 0
  ) {
    alert('该主持核心磋商的总时间已用尽')
    return
  }

  const wasRunning = timerRunning.value
  timerRunning.value = true

  // 如果之前没在运行，才重置计时器；如果是暂停后继续，保持当前值
  if (!wasRunning) {
    const effectiveSpeakerSeconds =
      isModeratedCaucus.value && typeof caucusRemainingSeconds.value === 'number'
        ? Math.min(speakerTimerDefaultSeconds.value, Math.max(caucusRemainingSeconds.value, 0))
        : speakerTimerDefaultSeconds.value
    speakerTimerSeconds.value = effectiveSpeakerSeconds
  }

  if (timerInterval) clearInterval(timerInterval)
  timerInterval = setInterval(() => {
    let shouldStop = false

    if (speakerTimerSeconds.value > 0) {
      speakerTimerSeconds.value--
      if (speakerTimerSeconds.value === 15) {
        playBell(1)
      }
      if (speakerTimerSeconds.value === 0) {
        playBell(2)
      }
    } else {
      shouldStop = true
    }

    if (isModeratedCaucus.value && typeof caucusRemainingSeconds.value === 'number') {
      if (caucusRemainingSeconds.value > 0) {
        caucusRemainingSeconds.value = Math.max(caucusRemainingSeconds.value - 1, 0)
        syncCaucusRemainingForCurrentList()
      }
      if (caucusRemainingSeconds.value <= 0) {
        shouldStop = true
      }
    }

    if (shouldStop) {
      pauseTimer()
    }
  }, 1000) as unknown as number
}

// 暂停计时（纯前端控制）
const pauseTimer = () => {
  if (!speakerListId.value || !timerRunning.value) return

  timerRunning.value = false
  if (timerInterval) {
    clearInterval(timerInterval)
    timerInterval = null
  }

  syncCaucusRemainingForCurrentList()
}

// 自由计时器控制
const openFreeTimerModal = () => {
  showFreeTimerModal.value = true
  freeTimerInputMinutes.value = Math.floor(freeTimerTotalSeconds.value / 60)
  freeTimerInputSeconds.value = freeTimerTotalSeconds.value % 60
}

const closeFreeTimerModal = () => {
  showFreeTimerModal.value = false
  pauseFreeTimer()
}

const applyFreeTimerSettings = () => {
  const minutes = Math.max(0, Math.floor(freeTimerInputMinutes.value))
  const seconds = Math.min(59, Math.max(0, Math.floor(freeTimerInputSeconds.value)))
  const total = Math.max(10, minutes * 60 + seconds)
  freeTimerTotalSeconds.value = total
  freeTimerRemainingSeconds.value = total
  freeTimerRunning.value = false
  if (freeTimerInterval) {
    clearInterval(freeTimerInterval)
    freeTimerInterval = null
  }
}

const startFreeTimer = () => {
  if (freeTimerRunning.value || freeTimerRemainingSeconds.value <= 0) return
  freeTimerRunning.value = true
  freeTimerInterval = setInterval(() => {
    if (freeTimerRemainingSeconds.value > 0) {
      freeTimerRemainingSeconds.value--
      if (freeTimerRemainingSeconds.value === 15) {
        playBell(1)
      }
      if (freeTimerRemainingSeconds.value === 0) {
        playBell(2)
      }
    } else {
      pauseFreeTimer()
    }
  }, 1000) as unknown as number
}

const pauseFreeTimer = () => {
  freeTimerRunning.value = false
  if (freeTimerInterval) {
    clearInterval(freeTimerInterval)
    freeTimerInterval = null
  }
}

const resetFreeTimer = () => {
  freeTimerRemainingSeconds.value = freeTimerTotalSeconds.value
  pauseFreeTimer()
}

// 下一个发言者
const nextSpeaker = async () => {
  if (!speakerListId.value) {
    alert('没有活跃的发言列表')
    return
  }

  if (!confirm('确定移除当前发言者并进入下一位？')) {
    return
  }

  try {
    const response = await apiFetch('/api/display/speaker/next', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ speakerListId: speakerListId.value }),
    })

    if (!response.ok) throw new Error('Failed to move to next speaker')

    const data = await response.json()
    speakerQueue.value = data.speakerQueue

    // 停止当前计时器并重置
    timerRunning.value = false
    if (timerInterval) {
      clearInterval(timerInterval)
      timerInterval = null
    }
    speakerTimerSeconds.value = speakerTimerDefaultSeconds.value

    // 添加历史记录
    historyEvents.value.unshift({
      title: '发言者完成',
      description: '当前发言者已完成发言',
    })
  } catch (error) {
    console.error('Failed to move to next speaker:', error)
    alert('切换发言者失败')
  }
}

// 设置委员会状态
const setCommitteeStatus = async (status: string) => {
  try {
    const response = await apiFetch('/api/display/set-status', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ committeeId: committeeId.value, status }),
    })

    if (!response.ok) throw new Error('Failed to set committee status')

    // 刷新数据
    await loadBoardData()
  } catch (error) {
    console.error('Failed to set committee status:', error)
  }
}

// 定时刷新（只在没有弹窗打开时刷新）
let refreshInterval: number | null = null

const shouldRefresh = computed(() => {
  return !showPopupDelegate.value && !showPopupMotion.value && !showRollCallModal.value
})

const conditionalRefresh = () => {
  if (shouldRefresh.value) {
    loadBoardData()
  }
}

onMounted(() => {
  loadCaucusState()
  loadBoardData()
  // 每5秒刷新一次（仅在没有弹窗时）
  refreshInterval = setInterval(conditionalRefresh, 5000) as unknown as number
})

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval)
  if (timerInterval) clearInterval(timerInterval)
  if (freeTimerInterval) clearInterval(freeTimerInterval)
  stopTimeUpdate()
})
</script>

<template>
  <section class="h-screen overflow-hidden bg-base-200 p-8">
    <!-- 开始会议覆盖层 -->
    <div v-if="showStartSessionOverlay"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
      <button class="btn btn-primary btn-lg text-4xl px-16 py-8 h-auto" @click="startSession">
        开始会议
      </button>
    </div>

    <PopupRollCall v-model="showRollCallModal" :delegates="delegates" :initial-attendance="rollCallAttendance"
      title="点名" @confirm="handleRollCallConfirm" @cancel="handleRollCallCancel" />

    <div class="flex h-full min-h-0 flex-col gap-4 w-full">
      <header class="flex items-center justify-between rounded-3xl bg-base-100 px-8 py-3 shadow-xl w-full">
        <h1 class="text-4xl font-bold">当前会场：{{ committee?.name || '加载中...' }}</h1>
        <div class="text-center text-3xl font-semibold">{{ currentDisplayTime }}</div>
        <div class="stats stats-horizontal gap-2 text-right">
          <div class="stat place-items-center">
            <button class="btn btn-sm btn-ghost" @click="handleRollCallClick">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
              </svg>
              修改出席情况
            </button>
          </div>
          <div class="stat place-items-end">
            <div class="stat-title text-lg">代表总数</div>
            <div class="stat-value text-5xl">{{ statistics.total }}</div>
          </div>
          <div class="stat place-items-end">
            <div class="stat-title text-lg">到场人数</div>
            <div class="stat-value text-5xl">{{ statistics.present }}</div>
          </div>
          <div class="stat place-items-end">
            <div class="stat-title text-lg">3/4 多数</div>
            <div class="stat-value text-5xl">{{ statistics.threeQuarters }}</div>
          </div>
          <div class="stat place-items-end">
            <div class="stat-title text-lg">2/3 多数</div>
            <div class="stat-value text-5xl">{{ statistics.twoThirds }}</div>
          </div>
          <div class="stat place-items-end">
            <div class="stat-title text-lg">1/2 多数</div>
            <div class="stat-value text-5xl">{{ statistics.half }}</div>
          </div>
          <div class="stat place-items-end">
            <div class="stat-title text-lg">20% 多数</div>
            <div class="stat-value text-5xl">{{ statistics.twentyPercent }}</div>
          </div>
        </div>
      </header>

      <div class="grid flex-1 min-h-0 lg:grid-cols-[34.5%_64.5%] w-full box-border" style="gap: 1%">
        <div class="flex h-full min-h-0 flex-col rounded-3xl bg-base-100 shadow-xl">
          <div class="px-8 py-6 text-center space-y-2">
            <div class="text-6xl font-bold" :class="timerRunning ? 'text-primary' : ''">
              {{ formatTimer(speakerTimerSeconds) }}
            </div>
            <div class="text-sm text-base-content/60">
              单位时长：{{ formatTimer(speakerTimerDefaultSeconds) }}
            </div>
            <div v-if="showCaucusTimer" class="text-sm text-secondary">
              总剩余：{{ formatTimer(caucusRemainingSeconds || 0) }} /
              {{ formatTimer(caucusTotalSeconds || 0) }}
            </div>
          </div>
          <div class="px-8 pb-6">
            <div class="grid grid-cols-3 gap-3">
              <button class="btn btn-primary w-full text-1.5xl" :disabled="timerRunning" @click="startTimer">
                开始计时
              </button>
              <button class="btn btn-secondary w-full text-1.5xl" :disabled="!timerRunning" @click="pauseTimer">
                暂停计时
              </button>
              <button class="btn btn-accent w-full text-1.5xl" @click="nextSpeaker">
                下一个发言者
              </button>
            </div>
            <button class="btn btn-outline w-full mt-4" @click="openFreeTimerModal">
              自由计时器
            </button>
          </div>
          <div class="flex-1 min-h-0 overflow-y-auto px-8 pb-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="card-title text-3xl">当前发言名单</h2>
              <div class="flex items-center gap-2">
                <span v-if="speakerListTotalCount > 0" class="text-sm text-base-content/70 mr-2">
                  {{ speakerListCurrentIndex }} / {{ speakerListTotalCount }}
                </span>
                <button class="btn btn-sm btn-circle btn-ghost" title="上一个发言列表" :disabled="speakerListTotalCount <= 1"
                  @click="switchSpeakerList('prev')">

                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                  </svg>
                </button>
                <button class="btn btn-sm btn-circle btn-ghost" title="下一个发言列表" :disabled="speakerListTotalCount <= 1"
                  @click="switchSpeakerList('next')">

                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                  </svg>
                </button>
              </div>
            </div>
            <div v-if="speakerQueue.length === 0" class="text-center text-base-content/50 py-8">
              暂无发言者
            </div>
            <div v-else class="space-y-4">
              <article v-for="item in speakerQueue" :key="item.id"
                class="rounded-2xl border border-base-300 bg-base-200/40 px-4 py-3"
                :class="item.status === 'speaking' ? 'border-primary bg-primary/10' : ''">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-3xl font-semibold">{{ item.country }}</p>
                    <p class="text-lg text-base-content/70">{{ item.delegate }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-lg uppercase text-base-content/50">{{ item.status }}</p>
                  </div>
                </div>
              </article>
            </div>
          </div>
          <div class="px-8 pb-8">
            <button class="btn btn-primary w-full text-1.5xl" @click="showPopupDelegate = true">
              添加发言者
            </button>
            <PopupDelegate v-model="showPopupDelegate" :committee-id="committeeId" @confirm="onDelegateConfirm" />
          </div>
        </div>

        <div class="flex h-full min-h-0 flex-col rounded-3xl bg-base-100 shadow-xl">
          <div class="px-8 py-6 border-b border-base-200">
            <h2 class="card-title text-3xl">会议历程</h2>
          </div>
          <div class="flex-1 min-h-0 overflow-y-auto px-8 py-6 space-y-4">
            <div v-for="event in historyEvents" :key="event.id ?? event.title"
              class="rounded-2xl border border-base-300 bg-base-200/40 px-4 py-3 space-y-3">
              <div>
                <h3 class="font-semibold text-xl">{{ event.title }}</h3>
                <p class="text-lg text-base-content/70" style="white-space: pre-line;">{{ event.description }}</p>
              </div>
              <div v-if="event.voteResult" class="flex flex-wrap items-center gap-3 text-base">
                <span class="badge badge-lg" :class="event.voteResult.passed ? 'badge-success' : 'badge-error'">
                  {{ event.voteResult.passed ? '通过' : '未通过' }}
                </span>
                <span class="badge badge-outline badge-lg">赞成 {{ event.voteResult.yes }}</span>
                <span class="badge badge-outline badge-lg">反对 {{ event.voteResult.no }}</span>
                <span class="badge badge-outline badge-lg">弃权 {{ event.voteResult.abstain }}</span>
              </div>
            </div>
          </div>
          <div class="px-8 pb-8">
            <button class="btn btn-accent w-full text-1.5xl" @click="showPopupMotion = true">
              发起动议
            </button>
            <PopupMotion v-model="showPopupMotion" :committee-id="committeeId" @pass="handleMotionPass"
              @fail="handleMotionFail" />
          </div>
        </div>
      </div>
    </div>

    <!-- 自由计时器弹窗 -->
    <dialog v-if="showFreeTimerModal" class="modal" open>
      <div class="modal-box w-11/12 max-w-3xl space-y-6">
        <header class="space-y-2">
          <p class="text-base-content/60">自由磋商/自由辩论计时</p>
          <h3 class="text-3xl font-bold">自定义计时器</h3>
        </header>

        <div class="grid gap-4 md:grid-cols-2">
          <label class="form-control">
            <span class="label-text">分钟</span>
            <input v-model.number="freeTimerInputMinutes" type="number" min="0" max="999"
              class="input input-bordered" />
          </label>
          <label class="form-control">
            <span class="label-text">秒</span>
            <input v-model.number="freeTimerInputSeconds" type="number" min="0" max="59" class="input input-bordered" />
          </label>
        </div>

        <div class="flex justify-end">
          <button type="button" class="btn btn-outline" @click="applyFreeTimerSettings">设定总时长</button>
        </div>

        <div class="text-center space-y-2">
          <div class="text-6xl font-bold">{{ formatTimer(freeTimerRemainingSeconds) }}</div>
          <p class="text-base-content/70">总时长：{{ formatTimer(freeTimerTotalSeconds) }}</p>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-3">
          <button type="button" class="btn btn-primary" :disabled="freeTimerRunning" @click="startFreeTimer">开始</button>
          <button type="button" class="btn btn-secondary" :disabled="!freeTimerRunning"
            @click="pauseFreeTimer">暂停</button>
          <button type="button" class="btn btn-ghost" :disabled="freeTimerRemainingSeconds === freeTimerTotalSeconds"
            @click="resetFreeTimer">
            重置
          </button>
        </div>

        <div class="modal-action">
          <button type="button" class="btn btn-success" @click="closeFreeTimerModal">完成</button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click.prevent="closeFreeTimerModal">关闭</button>
      </form>
    </dialog>
  </section>
</template>
