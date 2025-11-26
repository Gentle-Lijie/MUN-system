<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import FormField from '@/components/common/FormField.vue'
import { api, type LogRecord, type UserSummary } from '@/services/api'

type ActionFilter = 'all' | 'SELECT' | 'INSERT' | 'UPDATE' | 'DELETE' | 'LOG_PURGE' | 'QUERY'

const logs = ref<LogRecord[]>([])
const loading = ref(false)
const isClearing = ref(false)
const page = ref(1)
const pageSize = ref(25)
const total = ref(0)
const operatorOptions = ref<UserSummary[]>([])

const filters = reactive({
  actorId: '',
  action: 'all' as ActionFilter,
  start: '',
  end: '',
})

const actionOptions: ActionFilter[] = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'LOG_PURGE', 'QUERY']

const totalPages = computed(() => Math.max(1, Math.ceil(total.value / pageSize.value)))

type LogQueryParams = {
  actorId?: number
  action?: string
  table?: string
  start?: string
  end?: string
  page?: number
  pageSize?: number
}

const toIso = (value: string, endOfDay = false): string | undefined => {
  const suffix = endOfDay ? 'T23:59:59' : 'T00:00:00'
  const date = new Date(`${value}${suffix}`)
  if (Number.isNaN(date.getTime())) {
    return undefined
  }
  return date.toISOString()
}

const buildQueryParams = (): LogQueryParams => {
  const params: LogQueryParams = {
    page: page.value,
    pageSize: pageSize.value,
  }
  if (filters.actorId) {
    params.actorId = Number(filters.actorId)
  }
  if (filters.action !== 'all') {
    params.action = filters.action
  }
  if (filters.start) {
    const iso = toIso(filters.start, false)
    if (iso) params.start = iso
  }
  if (filters.end) {
    const iso = toIso(filters.end, true)
    if (iso) params.end = iso
  }
  return params
}

const fetchLogs = async () => {
  loading.value = true
  try {
    const response = await api.getLogs(buildQueryParams())
    logs.value = response.items
    total.value = response.total
    page.value = response.page
    pageSize.value = response.pageSize
  } catch (error) {
    console.error('Failed to load logs', error)
    logs.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

const fetchOperators = async () => {
  try {
    const response = await api.getUsers()
    operatorOptions.value = response.items
  } catch (error) {
    console.error('Failed to load operator list', error)
  }
}

const handleSearch = () => {
  page.value = 1
  fetchLogs()
}

const resetFilters = () => {
  filters.actorId = ''
  filters.action = 'all'
  filters.start = ''
  filters.end = ''
  handleSearch()
}

const handleClearLogs = async () => {
  if (!window.confirm('确定要清空所有日志记录吗？该操作不可恢复。')) {
    return
  }
  isClearing.value = true
  try {
    await api.clearLogs()
    await fetchLogs()
  } catch (error) {
    console.error('Failed to clear logs', error)
  } finally {
    isClearing.value = false
  }
}

const handlePrevPage = () => {
  if (page.value === 1) return
  page.value -= 1
  fetchLogs()
}

const handleNextPage = () => {
  if (page.value >= totalPages.value) return
  page.value += 1
  fetchLogs()
}

const formatTimestamp = (value?: string | null) => {
  if (!value) return '—'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString()
}

const operatorLabel = (log: LogRecord) => {
  if (!log.actor) return '系统/匿名'
  return `${log.actor.name} (${log.actor.role})`
}

const metaPreview = (log: LogRecord) => {
  const meta = log.meta
  if (meta && typeof (meta as { sql?: unknown }).sql === 'string') {
    return (meta as { sql: string }).sql
  }
  if (meta) {
    return JSON.stringify(meta)
  }
  return '—'
}

onMounted(() => {
  fetchOperators()
  fetchLogs()
})
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">日志管理</h2>
      <p class="text-sm text-base-content/70">追踪所有数据库读写操作，支持筛选、分页与清空功能。</p>
    </header>

    <section class="space-y-4">
      <div class="grid md:grid-cols-4 gap-3">
        <FormField legend="操作人" label="选择操作人">
          <select v-model="filters.actorId" class="select select-bordered">
            <option value="">全部</option>
            <option v-for="operator in operatorOptions" :key="operator.id" :value="operator.id">
              {{ operator.name }} ({{ operator.role }})
            </option>
          </select>
        </FormField>
        <FormField legend="动作类型" label="选择 SQL 或系统动作">
          <select v-model="filters.action" class="select select-bordered">
            <option value="all">全部</option>
            <option v-for="action in actionOptions" :key="action" :value="action">
              {{ action }}
            </option>
          </select>
        </FormField>
        <FormField legend="起始日期" label="筛选开始日期">
          <input v-model="filters.start" type="date" class="input input-bordered" />
        </FormField>
        <FormField legend="结束日期" label="筛选结束日期">
          <input v-model="filters.end" type="date" class="input input-bordered" />
        </FormField>
      </div>

      <div class="flex flex-wrap gap-3">
        <button class="btn btn-primary" :disabled="loading" @click="handleSearch">
          <span v-if="loading" class="loading loading-spinner loading-xs"></span>
          <span>查询</span>
        </button>
        <button class="btn btn-outline" :disabled="loading" @click="resetFilters">重置</button>
        <div class="flex-1"></div>
        <button
          class="btn btn-outline btn-error"
          :disabled="isClearing || loading"
          @click="handleClearLogs"
        >
          <span v-if="isClearing" class="loading loading-spinner loading-xs"></span>
          清空日志
        </button>
      </div>

      <div class="flex items-center justify-between text-sm text-base-content/70">
        <p>共 {{ total }} 条记录，当前第 {{ page }} / {{ totalPages }} 页</p>
        <div class="join">
          <button
            class="btn btn-sm join-item"
            :disabled="page === 1 || loading"
            @click="handlePrevPage"
          >
            上一页
          </button>
          <button
            class="btn btn-sm join-item"
            :disabled="page >= totalPages || loading"
            @click="handleNextPage"
          >
            下一页
          </button>
        </div>
      </div>

      <div class="overflow-x-auto border border-base-200 rounded-2xl">
        <table class="table table-sm">
          <thead>
            <tr>
              <th>时间</th>
              <th>操作人</th>
              <th>动作</th>
              <th>目标表</th>
              <th>详情</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td class="whitespace-nowrap">{{ formatTimestamp(log.timestamp) }}</td>
              <td>
                <div class="font-semibold">{{ operatorLabel(log) }}</div>
                <p v-if="log.actor?.email" class="text-xs text-base-content/60">
                  {{ log.actor.email }}
                </p>
              </td>
              <td>
                <span class="badge badge-outline">{{ log.action }}</span>
              </td>
              <td>
                <div>{{ log.targetTable || '—' }}</div>
                <p v-if="log.targetId" class="text-xs text-base-content/60">
                  ID: {{ log.targetId }}
                </p>
              </td>
              <td class="max-w-[18rem]">
                <p class="text-xs font-mono truncate">{{ metaPreview(log) }}</p>
              </td>
            </tr>
            <tr v-if="!loading && logs.length === 0">
              <td colspan="5" class="text-center text-base-content/60">暂无日志记录</td>
            </tr>
            <tr v-if="loading">
              <td colspan="5" class="text-center">
                <span class="loading loading-dots loading-sm"></span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>
