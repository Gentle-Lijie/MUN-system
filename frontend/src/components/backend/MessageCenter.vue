<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import FormField from '@/components/common/FormField.vue'
import {
  api,
  type MessageListResponse,
  type MessageRecord,
  type MessageTarget,
} from '@/services/api'

type TargetFilter = MessageTarget | 'all'

const props = defineProps<{ title: string; subtitle?: string }>()

const messages = ref<MessageRecord[]>([])
const loading = ref(false)
const errorText = ref('')
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })
const filters = reactive<{ target: TargetFilter; committeeId: number; search: string }>({
  target: 'all',
  committeeId: 0,
  search: '',
})
const recipients = ref<MessageListResponse['recipients']>({ committees: [], delegates: [] })
const allowedTargets = ref<MessageTarget[]>([])

const sendModalOpen = ref(false)
const sending = ref(false)
const formError = ref('')
const sendForm = reactive<{
  target: MessageTarget
  targetId: number | null
  committeeId: number | null
  content: string
}>({
  target: 'everyone',
  targetId: null,
  committeeId: null,
  content: '',
})

const targetFilterOptions: Array<{ value: TargetFilter; label: string }> = [
  { value: 'all', label: '全部类型' },
  { value: 'everyone', label: '全体广播' },
  { value: 'committee', label: '指定会场' },
  { value: 'dias', label: '主席团通道' },
  { value: 'delegate', label: '代表私信' },
]

const targetLabelMap: Record<MessageTarget, string> = {
  everyone: '全体广播',
  committee: '指定会场',
  dias: '主席团通道',
  delegate: '代表私信',
}

const committeeOptions = computed(() => recipients.value.committees)
const delegateOptions = computed(() => recipients.value.delegates)
const totalPages = computed(() => Math.max(1, Math.ceil(pagination.total / pagination.pageSize)))
const canPrev = computed(() => pagination.page > 1)
const canNext = computed(() => pagination.page < totalPages.value)

const availableTargetOptions = computed(() =>
  allowedTargets.value.map((target) => ({ value: target, label: targetLabelMap[target] }))
)

watch(
  () => sendForm.target,
  () => {
    sendForm.targetId = null
    sendForm.committeeId = null
  }
)

const fetchMessages = async () => {
  loading.value = true
  errorText.value = ''
  try {
    const data = await api.getMessages({
      page: pagination.page,
      pageSize: pagination.pageSize,
      target: filters.target,
      committeeId: filters.committeeId > 0 ? filters.committeeId : undefined,
      search: filters.search || undefined,
    })
    messages.value = data.items
    pagination.total = data.total
    pagination.page = data.page
    pagination.pageSize = data.pageSize
    recipients.value = data.recipients
    allowedTargets.value = data.allowedTargets
    if (!allowedTargets.value.includes(sendForm.target) && allowedTargets.value.length) {
      sendForm.target = (allowedTargets.value[0] ?? 'dias') as MessageTarget
    }
  } catch (error) {
    console.error(error)
    errorText.value = error instanceof Error ? error.message : '加载消息失败'
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  pagination.page = 1
  fetchMessages()
}

const changePage = (delta: number) => {
  const next = pagination.page + delta
  if (next < 1 || next > totalPages.value) return
  pagination.page = next
  fetchMessages()
}

const openSendModal = () => {
  formError.value = ''
  sendForm.content = ''
  sendForm.targetId = null
  sendForm.committeeId = null
  if (allowedTargets.value.length) {
    sendForm.target = (allowedTargets.value[0] ?? 'dias') as MessageTarget
  }
  sendModalOpen.value = true
}

const closeSendModal = () => {
  sendModalOpen.value = false
}

const handleCommitteeChange = (event: Event) => {
  const value = Number((event.target as HTMLSelectElement).value)
  if (!value) {
    sendForm.targetId = null
    sendForm.committeeId = null
    return
  }
  sendForm.targetId = value
  sendForm.committeeId = value
}

const handleDelegateChange = (event: Event) => {
  const value = Number((event.target as HTMLSelectElement).value)
  if (!value) {
    sendForm.targetId = null
    sendForm.committeeId = null
    return
  }
  const delegate = delegateOptions.value.find((item) => item.userId === value)
  sendForm.targetId = value
  sendForm.committeeId = delegate?.committeeId ?? null
}

const submitMessage = async () => {
  if (!sendForm.content.trim()) {
    formError.value = '请输入消息内容'
    return
  }
  if (sendForm.target !== 'everyone' && !sendForm.targetId) {
    formError.value = '请选择接收对象'
    return
  }

  sending.value = true
  formError.value = ''
  try {
    await api.sendMessage({
      target: sendForm.target,
      content: sendForm.content.trim(),
      targetId: sendForm.targetId ?? undefined,
      committeeId: sendForm.committeeId ?? undefined,
    })
    sendModalOpen.value = false
    sendForm.content = ''
    await fetchMessages()
  } catch (error) {
    console.error(error)
    formError.value = error instanceof Error ? error.message : '发送失败'
  } finally {
    sending.value = false
  }
}

const formatTime = (value: string) => {
  const date = new Date(value)
  return `${date.toLocaleDateString('zh-CN')} ${date.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' })}`
}

const targetDisplay = (message: MessageRecord) => {
  const meta = message.targetMeta || {}
  switch (message.target) {
    case 'committee':
      return `会场 · ${meta.committeeName || message.committee?.name || '未指定'}`
    case 'dias':
      return `主席团 · ${meta.committeeName || message.committee?.name || '未指定'}`
    case 'delegate':
      return `代表 · ${meta.recipientName || '未知代表'}`
    default:
      return '全体参会者'
  }
}

onMounted(() => {
  fetchMessages()
})
</script>

<template>
  <div class="p-6 space-y-6">
    <header
      class="flex flex-col gap-2 border-b border-base-200 pb-4 md:flex-row md:items-center md:justify-between"
    >
      <div>
        <h2 class="text-2xl font-bold">{{ props.title }}</h2>
        <p class="text-sm text-base-content/70">
          {{ props.subtitle || '查看历史消息并推送公告。' }}
        </p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button class="btn btn-outline btn-sm" :disabled="loading" @click="fetchMessages">
          刷新列表
        </button>
        <button
          class="btn btn-primary btn-sm"
          :disabled="!allowedTargets.length"
          @click="openSendModal"
        >
          发送消息
        </button>
      </div>
    </header>

    <section class="rounded-2xl border border-base-200 bg-base-100 p-4 space-y-4">
      <div class="grid gap-3 md:grid-cols-4">
        <FormField legend="消息类型" label="筛选目标">
          <select
            v-model="filters.target"
            class="select select-bordered select-sm"
            @change="applyFilters"
          >
            <option v-for="option in targetFilterOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </FormField>
        <FormField legend="所属会场" label="限定可选会场">
          <select
            v-model.number="filters.committeeId"
            class="select select-bordered select-sm"
            @change="applyFilters"
          >
            <option :value="0">全部会场</option>
            <option
              v-for="committee in recipients.committees"
              :key="committee.id"
              :value="committee.id"
            >
              {{ committee.name }} ({{ committee.code }})
            </option>
          </select>
        </FormField>
        <FormField legend="关键词" label="输入内容关键词" fieldsetClass="md:col-span-2">
          <div class="join w-full">
            <input
              v-model="filters.search"
              type="text"
              class="input input-bordered join-item input-sm"
              placeholder="输入内容关键词"
              @keyup.enter="applyFilters"
            />
            <button class="btn btn-outline join-item btn-sm" @click="applyFilters">搜索</button>
          </div>
        </FormField>
      </div>

      <div class="overflow-x-auto rounded-xl border border-base-200">
        <table class="table">
          <thead>
            <tr>
              <th class="text-xs uppercase tracking-wider">时间</th>
              <th class="text-xs uppercase tracking-wider">发送人</th>
              <th class="text-xs uppercase tracking-wider">接收方</th>
              <th class="text-xs uppercase tracking-wider">内容</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="4" class="text-center">
                <span class="loading loading-spinner loading-sm"></span>
              </td>
            </tr>
            <tr v-else-if="errorText">
              <td colspan="4" class="text-center text-error">{{ errorText }}</td>
            </tr>
            <tr v-else-if="messages.length === 0">
              <td colspan="4" class="text-center text-base-content/60">暂无消息</td>
            </tr>
            <tr v-for="message in messages" :key="message.id">
              <td class="align-top text-sm text-base-content/70">{{ formatTime(message.time) }}</td>
              <td class="align-top">
                <div class="text-sm font-semibold">{{ message.sender?.name || '系统' }}</div>
                <div class="text-xs text-base-content/60">
                  {{ message.sender?.role || 'system' }}
                </div>
              </td>
              <td class="align-top">
                <div class="text-sm font-semibold">{{ targetLabelMap[message.target] }}</div>
                <div class="text-xs text-base-content/60">{{ targetDisplay(message) }}</div>
              </td>
              <td class="align-top text-sm leading-relaxed">{{ message.content }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex items-center justify-between text-sm text-base-content/70">
        <span>共 {{ pagination.total }} 条 · 第 {{ pagination.page }} / {{ totalPages }} 页</span>
        <div class="join">
          <button class="btn btn-sm join-item" :disabled="!canPrev" @click="changePage(-1)">
            上一页
          </button>
          <button class="btn btn-sm join-item" :disabled="!canNext" @click="changePage(1)">
            下一页
          </button>
        </div>
      </div>
    </section>

    <div class="modal" :class="{ 'modal-open': sendModalOpen }">
      <div class="modal-box space-y-4">
        <h3 class="text-lg font-semibold">发送消息</h3>
        <div class="grid gap-3">
          <FormField legend="消息类型" label="选择发送通道">
            <select v-model="sendForm.target" class="select select-bordered">
              <option
                v-for="option in availableTargetOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </FormField>

          <FormField
            v-if="sendForm.target === 'committee' || sendForm.target === 'dias'"
            legend="选择会场"
            label="请选择目标会场"
          >
            <select
              class="select select-bordered"
              :value="sendForm.targetId ?? 0"
              @change="handleCommitteeChange"
            >
              <option :value="0">请选择会场</option>
              <option
                v-for="committee in committeeOptions"
                :key="committee.id"
                :value="committee.id"
              >
                {{ committee.name }} ({{ committee.code }})
              </option>
            </select>
          </FormField>

          <FormField v-if="sendForm.target === 'delegate'" legend="选择代表" label="指定某位代表">
            <select
              class="select select-bordered"
              :value="sendForm.targetId ?? 0"
              @change="handleDelegateChange"
            >
              <option :value="0">请选择代表</option>
              <option
                v-for="delegate in delegateOptions"
                :key="delegate.userId"
                :value="delegate.userId"
              >
                {{ delegate.name }} · {{ delegate.committeeName || '未分配' }}
              </option>
            </select>
          </FormField>

          <FormField legend="消息内容" label="请输入要发送的通知">
            <textarea
              v-model="sendForm.content"
              class="textarea textarea-bordered"
              rows="4"
              placeholder="输入要发送的通知"
            ></textarea>
          </FormField>

          <p v-if="formError" class="text-sm text-error">{{ formError }}</p>
        </div>
        <div class="modal-action">
          <button class="btn" @click="closeSendModal">取消</button>
          <button class="btn btn-primary" :disabled="sending" @click="submitMessage">发送</button>
        </div>
      </div>
    </div>
  </div>
</template>
