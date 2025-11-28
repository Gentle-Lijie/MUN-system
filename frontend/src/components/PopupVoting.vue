<template>
  <dialog v-if="modelValue" class="modal" open>
    <div class="modal-box w-11/12 max-w-5xl bg-transparent p-0 h-[90vh] overflow-y-auto">
      <div class="flex flex-col gap-4 rounded-3xl bg-base-100 p-6 lg:p-10 h-full min-h-0">
        <div class="flex flex-col gap-2 border-b border-base-200 pb-6">
          <p class="text-base-content/60 text-base">文件投票</p>
          <h3 class="text-3xl font-bold">依次收集代表投票</h3>
          <p class="text-sm text-base-content/70">{{ progressText }}</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_300px] flex-1 min-h-0">
          <!-- 左侧代表列表 -->
          <section
            class="flex h-full flex-col gap-4 rounded-3xl border border-base-200 bg-base-200/40 p-4 lg:p-6 overflow-hidden">
            <header class="flex items-center justify-between">
              <h4 class="text-xl font-semibold">代表列表</h4>
              <span class="badge badge-neutral badge-lg">共 {{ delegates.length }} 人</span>
            </header>
            <div class="flex-1 space-y-2 overflow-y-auto pr-1">
              <div
                v-for="(delegate, index) in delegates"
                :key="delegate.id"
                class="flex items-center gap-3 p-3 rounded-lg transition"
                :class="isCurrentDelegate(delegate.id)
                  ? 'bg-primary/20 border border-primary'
                  : 'bg-base-100/70'"
              >
                <div class="avatar placeholder">
                  <div class="bg-neutral text-neutral-content rounded-full w-8">
                    <span class="text-xs">{{ delegate.country.substring(0, 2).toUpperCase() }}</span>
                  </div>
                </div>
                <div class="flex-1">
                  <p class="font-semibold">{{ delegate.country }}</p>
                  <p class="text-sm text-base-content/70">{{ delegate.userName }}</p>
                </div>
                <div v-if="votes[delegate.id]" class="flex items-center gap-2">
                  <span
                    class="badge badge-sm"
                    :class="{
                      'badge-success': votes[delegate.id] === 'yes',
                      'badge-error': votes[delegate.id] === 'no',
                      'badge-info': votes[delegate.id] === 'pass',
                      'badge-warning': votes[delegate.id] === 'abstain'
                    }"
                  >
                    {{ getVoteLabel(delegate.id) }}
                  </span>
                </div>
                <div v-else-if="shouldShowPending(delegate.id, index)" class="text-base-content/50">
                  未投票
                </div>
              </div>
            </div>
          </section>

          <!-- 右侧投票按钮 -->
          <section
            class="flex h-full flex-col gap-6 rounded-3xl border border-base-200 bg-base-200/40 p-4 lg:p-6 overflow-hidden">
            <header class="text-center">
              <h4 class="text-2xl font-semibold mb-2">{{ currentDelegate?.country }}</h4>
              <p class="text-base-content/70">{{ currentDelegate?.userName }}</p>
            </header>

            <div class="flex flex-col gap-4 flex-1 justify-center overflow-y-auto">
              <button
                type="button"
                class="btn btn-success btn-lg text-lg"
                :disabled="busy"
                @click="submitVote('yes')"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                通过
              </button>

              <button
                type="button"
                class="btn btn-error btn-lg text-lg"
                :disabled="busy"
                @click="submitVote('no')"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                否决
              </button>

              <template v-if="!revoteMode">
                <button
                  type="button"
                  class="btn btn-info btn-lg text-lg"
                  :disabled="busy"
                  @click="submitVote('pass')"
                >
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                  </svg>
                  过
                </button>

                <button
                  type="button"
                  class="btn btn-warning btn-lg text-lg"
                  :disabled="busy"
                  @click="submitVote('abstain')"
                >
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                  </svg>
                  弃权
                </button>
              </template>
            </div>

            <div v-if="error" class="alert alert-error">
              <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>{{ error }}</span>
            </div>
          </section>
        </div>

        <div class="flex gap-4 justify-end pt-4 border-t border-base-200 mt-4">
          <button type="button" class="btn btn-ghost" @click="handleCancel">
            取消投票
          </button>
        </div>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click.prevent="handleCancel">关闭</button>
    </form>
  </dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

export type Delegate = {
  id: number
  country: string
  userName: string
}

export type VoteRecord = {
  delegateId: number
  country: string
  vote: 'yes' | 'no' | 'abstain'
}

export type VotingSummary = {
  votes: VoteRecord[]
  summary: {
    yes: number
    no: number
    abstain: number
    effectiveTotal: number
    ratio: number
  }
}

interface Props {
  modelValue: boolean
  delegates: Delegate[]
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'complete', result: VotingSummary): void
  (e: 'cancel'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const regularIndex = ref(0)
const revoteMode = ref(false)
const revoteIds = ref<number[]>([])
const revoteIndex = ref(0)
const votes = ref<Record<number, 'yes' | 'no' | 'pass' | 'abstain'>>({})
const busy = ref(false)
const error = ref<string | null>(null)

const voteLabels = {
  yes: '通过',
  no: '否决',
  pass: '过',
  abstain: '弃权'
}

const progressText = computed(() => {
  if (revoteMode.value) {
    if (revoteIds.value.length === 0) return '复投阶段'
    return `复投：${Math.min(revoteIndex.value + 1, revoteIds.value.length)} / ${revoteIds.value.length}`
  }
  if (props.delegates.length === 0) return '暂无代表'
  return `当前进度：${Math.min(regularIndex.value + 1, props.delegates.length)} / ${props.delegates.length}`
})

const currentDelegateId = computed(() => {
  if (revoteMode.value) {
    return revoteIds.value[revoteIndex.value]
  }
  return props.delegates[regularIndex.value]?.id
})

const currentDelegate = computed(() =>
  props.delegates.find((delegate) => delegate.id === currentDelegateId.value)
)

const isCurrentDelegate = (delegateId: number) => currentDelegateId.value === delegateId

const shouldShowPending = (delegateId: number, index: number) => {
  if (!revoteMode.value) {
    return index < regularIndex.value && !votes.value[delegateId]
  }
  const position = revoteIds.value.indexOf(delegateId)
  if (position === -1) {
    return false
  }
  return position < revoteIndex.value && votes.value[delegateId] === 'pass'
}

const submitVote = async (choice: 'yes' | 'no' | 'pass' | 'abstain') => {
  if (!currentDelegate.value) return

  if (revoteMode.value && (choice === 'pass' || choice === 'abstain')) {
    error.value = '复投阶段只能选择通过或否决'
    return
  }

  busy.value = true
  error.value = null

  try {
    const delegateId = currentDelegate.value.id
    votes.value[delegateId] = choice

    if (revoteMode.value) {
      moveToNextRevote()
      return
    }

    if (choice === 'pass' && !revoteIds.value.includes(delegateId)) {
      revoteIds.value = [...revoteIds.value, delegateId]
    }

    if (regularIndex.value < props.delegates.length - 1) {
      regularIndex.value++
    } else {
      beginRevoteOrFinalize()
    }
  } catch (err) {
    console.error('Failed to submit vote:', err)
    error.value = '提交投票失败，请重试'
  } finally {
    busy.value = false
  }
}

const beginRevoteOrFinalize = () => {
  if (revoteIds.value.length > 0) {
    revoteMode.value = true
    revoteIndex.value = 0
    focusNextRevote()
  } else {
    finalizeVoting()
  }
}

const moveToNextRevote = () => {
  revoteIndex.value++
  focusNextRevote()
}

const focusNextRevote = () => {
  while (revoteIndex.value < revoteIds.value.length) {
    const candidate = revoteIds.value[revoteIndex.value]
    if (typeof candidate !== 'number') {
      break
    }
    const delegateId = candidate
    if (votes.value[delegateId] === 'pass') {
      return
    }
    revoteIndex.value++
  }
  finalizeVoting()
}

const finalizeVoting = () => {
  const voteRecords: VoteRecord[] = props.delegates.map((delegate) => ({
    delegateId: delegate.id,
    country: delegate.country,
    vote:
      votes.value[delegate.id] && votes.value[delegate.id] !== 'pass'
        ? (votes.value[delegate.id] as 'yes' | 'no' | 'abstain')
        : 'abstain'
  }))

  const yes = voteRecords.filter((r) => r.vote === 'yes').length
  const no = voteRecords.filter((r) => r.vote === 'no').length
  const abstain = voteRecords.filter((r) => r.vote === 'abstain').length
  const effectiveTotal = voteRecords.length - abstain

  emit('complete', {
    votes: voteRecords,
    summary: {
      yes,
      no,
      abstain,
      effectiveTotal,
      ratio: effectiveTotal > 0 ? yes / effectiveTotal : 0
    }
  })
  emit('update:modelValue', false)
  reset()
}

const handleCancel = () => {
  emit('cancel')
  emit('update:modelValue', false)
  reset()
}

const reset = () => {
  regularIndex.value = 0
  revoteMode.value = false
  revoteIds.value = []
  revoteIndex.value = 0
  Object.keys(votes.value).forEach((key) => delete votes.value[parseInt(key)])
  busy.value = false
  error.value = null
}

const getVoteLabel = (delegateId: number) => {
  const value = votes.value[delegateId]
  if (!value) return ''
  return voteLabels[value]
}

watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      reset()
    }
  }
)

watch(
  () => props.delegates,
  () => {
    reset()
  }
)
</script>