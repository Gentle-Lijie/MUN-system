<script setup lang="ts">
import { computed, ref } from 'vue'
import FormField from '@/components/common/FormField.vue'

type LogLevel = 'info' | 'warning' | 'error'

type LogRecord = {
  id: string
  operator: string
  action: string
  time: string
  level: LogLevel
  target: string
}

const logs = ref<LogRecord[]>([
  { id: 'L-001', operator: '系统', action: '定时备份完成', time: '2025-05-13 02:10', level: 'info', target: '数据库' },
  { id: 'L-002', operator: '张三', action: '修改用户权限', time: '2025-05-12 11:20', level: 'warning', target: 'User U-004' },
  { id: 'L-003', operator: '李四', action: '批量导入代表', time: '2025-05-12 09:02', level: 'info', target: 'Delegates' },
  { id: 'L-004', operator: '系统', action: '多次登录失败', time: '2025-05-11 22:18', level: 'error', target: 'IP 192.168.10.8' },
])

const levelFilter = ref<'all' | LogLevel>('all')
const query = ref('')
const dateRange = ref({ start: '', end: '' })

const filteredLogs = computed(() => {
  return logs.value.filter((log) => {
    const matchLevel = levelFilter.value === 'all' || log.level === levelFilter.value
    const matchKeyword = query.value
      ? [log.operator, log.action, log.target].some((field) =>
          field.toLowerCase().includes(query.value.toLowerCase()),
        )
      : true
    const matchStart = dateRange.value.start ? log.time >= dateRange.value.start : true
    const matchEnd = dateRange.value.end ? log.time <= `${dateRange.value.end} 23:59` : true
    return matchLevel && matchKeyword && matchStart && matchEnd
  })
})
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">日志管理</h2>
      <p class="text-sm text-base-content/70">查看系统操作日志与审计记录，支持条件过滤与导出。</p>
    </header>

    <section class="space-y-4">
      <div class="grid md:grid-cols-4 gap-3">
        <FormField legend="起始日期" label="选择开始日期">
          <input v-model="dateRange.start" type="date" class="input input-bordered" />
        </FormField>
        <FormField legend="结束日期" label="选择截止日期">
          <input v-model="dateRange.end" type="date" class="input input-bordered" />
        </FormField>
        <FormField legend="日志级别" label="选择级别">
          <select v-model="levelFilter" class="select select-bordered">
            <option value="all">全部</option>
            <option value="info">信息</option>
            <option value="warning">警告</option>
            <option value="error">错误</option>
          </select>
        </FormField>
        <FormField legend="关键字" label="操作人/动作/目标">
          <input v-model="query" type="text" placeholder="操作人/动作/目标" class="input input-bordered" />
        </FormField>
      </div>

      <div class="flex gap-3">
        <button class="btn btn-primary">查询</button>
        <button class="btn btn-outline">导出结果</button>
      </div>

      <div class="overflow-x-auto border border-base-200 rounded-2xl">
        <table class="table table-sm">
          <thead>
            <tr>
              <th>时间</th>
              <th>操作人</th>
              <th>动作</th>
              <th>目标</th>
              <th>级别</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in filteredLogs" :key="log.id">
              <td>{{ log.time }}</td>
              <td>{{ log.operator }}</td>
              <td>{{ log.action }}</td>
              <td>{{ log.target }}</td>
              <td>
                <span class="badge" :class="{
                  'badge-info': log.level === 'info',
                  'badge-warning': log.level === 'warning',
                  'badge-error': log.level === 'error'
                }">
                  {{ log.level }}
                </span>
              </td>
            </tr>
            <tr v-if="filteredLogs.length === 0">
              <td colspan="5" class="text-center text-base-content/60">暂无日志记录</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>
