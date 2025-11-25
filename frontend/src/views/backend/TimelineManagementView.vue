<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import FormField from '@/components/common/FormField.vue'

type SimulatedEvent = {
  id: string
  title: string
  owner: string
  note: string
  offsetMinutes: number
}

const speedOptions = [
  { label: '1:3', value: 3, helper: '轻度加速' },
  { label: '1:60', value: 60, helper: '标准模拟' },
  { label: '1:180', value: 180, helper: '危机模式' },
]

const simConfig = reactive({
  anchorLabel: 'Day 1 · 09:00',
  speed: 60,
})

const timelineEvents = ref<SimulatedEvent[]>([
  { id: 'T-01', title: '开幕式', owner: '秘书处', note: '全体会场同步', offsetMinutes: 0 },
  { id: 'T-02', title: '危机引入', owner: '危机组', note: '安理会优先', offsetMinutes: 240 },
  { id: 'T-03', title: '闭幕准备', owner: '主席团', note: '提交决议草案', offsetMinutes: 480 },
])

const eventForm = reactive({
  offsetMinutes: 0,
  title: '',
  owner: '',
  note: '',
})

const orderedEvents = computed(() =>
  [...timelineEvents.value].sort((a, b) => a.offsetMinutes - b.offsetMinutes),
)

const speedLabel = computed(
  () => speedOptions.find((opt) => opt.value === simConfig.speed)?.label ?? `1:${simConfig.speed}`,
)

const realHourEquivalent = computed(() => (60 / simConfig.speed).toFixed(1))

const formatSimTime = (offset: number) => {
  const hours = Math.floor(offset / 60)
  const minutes = offset % 60
  if (hours === 0) return `T+${minutes}m`
  const minuteLabel = minutes.toString().padStart(2, '0')
  return `T+${hours}h ${minuteLabel}m`
}

const describeRealDuration = (offset: number) => {
  const realMinutes = offset / simConfig.speed
  if (realMinutes < 1) {
    return `${Math.max(1, Math.round(realMinutes * 60))} 秒`
  }
  if (realMinutes < 60) {
    return `${realMinutes.toFixed(1)} 分钟`
  }
  const hours = realMinutes / 60
  return `${hours.toFixed(1)} 小时`
}

const addTimelineEvent = () => {
  if (!eventForm.title) return
  const normalizedOffset = Math.max(0, Number(eventForm.offsetMinutes) || 0)
  timelineEvents.value.push({
    id: `T-${(timelineEvents.value.length + 1).toString().padStart(2, '0')}`,
    title: eventForm.title,
    owner: eventForm.owner || '未指定',
    note: eventForm.note,
    offsetMinutes: normalizedOffset,
  })
  eventForm.offsetMinutes = 0
  eventForm.title = ''
  eventForm.owner = ''
  eventForm.note = ''
}

const setSpeed = (value: number) => {
  simConfig.speed = value
}
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">时间轴管理</h2>
      <p class="text-sm text-base-content/70">主席团可在此配置模拟时间起点与倍率，所有事件按模拟时间推进。</p>
    </header>

    <section class="grid gap-6 xl:grid-cols-[1.6fr,1fr]">
      <div class="space-y-4">
        <div class="border border-base-200 rounded-2xl p-4 bg-base-100 flex flex-col gap-3">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="text-xs uppercase tracking-[0.18em] text-primary/70">模拟起点</p>
              <h3 class="text-lg font-semibold">{{ simConfig.anchorLabel }}</h3>
            </div>
            <div class="text-right">
              <p class="text-xs text-base-content/50">时间倍率</p>
              <p class="text-2xl font-bold text-primary">{{ speedLabel }}</p>
            </div>
          </div>
          <p class="text-sm text-base-content/70">
            现实时间 1 分钟 ≈ 模拟时间 {{ simConfig.speed }} 分钟；现实 1 小时 ≈ 模拟 {{ realHourEquivalent }} 小时，适合快速推进议程。
          </p>
        </div>

        <div class="timeline timeline-vertical timeline-compact bg-base-100 border border-base-200 rounded-2xl p-4">
          <div v-for="event in orderedEvents" :key="event.id" class="timeline-item">
            <div class="timeline-start text-sm font-semibold">
              {{ formatSimTime(event.offsetMinutes) }}
              <span class="block text-xs text-base-content/60">≈ {{ describeRealDuration(event.offsetMinutes) }} 现实</span>
            </div>
            <div class="timeline-middle">
              <span class="badge badge-primary"></span>
            </div>
            <div class="timeline-end mb-6">
              <div class="card bg-base-200 shadow-sm">
                <div class="card-body p-4">
                  <div class="flex items-center justify-between gap-2">
                    <h3 class="card-title text-base">{{ event.title }}</h3>
                    <span class="badge badge-outline">{{ event.owner }}</span>
                  </div>
                  <p class="text-sm text-base-content/70">{{ event.note || '暂无备注' }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="space-y-4">
        <div class="border border-base-200 rounded-2xl p-4 space-y-4">
          <h3 class="font-semibold">模拟调度设置</h3>
          <FormField legend="模拟锚点" label="设置起始时间"
            fieldsetClass="border border-base-200 rounded-2xl p-4">
            <div class="input input-bordered flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5 opacity-70" fill="none"
                stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l3 1.5" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5a7.5 7.5 0 1 1 0 15 7.5 7.5 0 0 1 0-15Z" />
              </svg>
              <input v-model="simConfig.anchorLabel" type="text" class="grow bg-transparent"
                placeholder="如：Day 1 · 09:00" />
            </div>
          </FormField>

          <fieldset class="fieldset border border-base-200 rounded-2xl p-4">
            <legend class="font-semibold mb-3">倍率选择</legend>
            <div class="join join-vertical w-full">
              <button
                v-for="option in speedOptions"
                :key="option.value"
                type="button"
                class="btn join-item justify-between"
                :class="option.value === simConfig.speed ? 'btn-primary' : 'btn-ghost'"
                @click="setSpeed(option.value)"
              >
                <span>{{ option.label }}</span>
                <span class="text-xs text-base-content/60">{{ option.helper }}</span>
              </button>
            </div>
          </fieldset>
        </div>

        <form class="border border-base-200 rounded-2xl p-4 space-y-4" @submit.prevent="addTimelineEvent">
          <h3 class="font-semibold">添加模拟事件</h3>

          <FormField legend="距离开始（模拟分钟）" label="输入正整数"
            fieldsetClass="border border-base-200 rounded-2xl p-4">
            <div class="input input-bordered flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5 opacity-70" fill="none"
                stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
              </svg>
              <input v-model.number="eventForm.offsetMinutes" type="number" min="0"
                class="grow bg-transparent" placeholder="如：120" />
            </div>
          </FormField>

          <FormField legend="事件标题" label="例如：危机简报"
            fieldsetClass="border border-base-200 rounded-2xl p-4">
            <div class="input input-bordered flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5 opacity-70" fill="none"
                stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h10" />
              </svg>
              <input v-model="eventForm.title" type="text" class="grow bg-transparent" placeholder="例如：危机简报" />
            </div>
          </FormField>

          <FormField legend="负责人" label="如：危机组"
            fieldsetClass="border border-base-200 rounded-2xl p-4">
            <div class="input input-bordered flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5 opacity-70" fill="none"
                stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5a7.5 7.5 0 0 1 15 0" />
              </svg>
              <input v-model="eventForm.owner" type="text" class="grow bg-transparent" placeholder="例如：危机组" />
            </div>
          </FormField>

          <FormField legend="备注" label="补充说明"
            fieldsetClass="border border-base-200 rounded-2xl p-4">
            <label class="flex items-center gap-2 text-sm text-base-content/70 mb-3">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5 opacity-70" fill="none"
                stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M5 12h14M5 17h8" />
              </svg>
              <span>补充说明</span>
            </label>
            <textarea v-model="eventForm.note" class="textarea textarea-bordered w-full" rows="3"
              placeholder="可描述任务目标、支援要求等"></textarea>
          </FormField>

          <button class="btn btn-primary w-full" type="submit">写入模拟时间轴</button>
        </form>
      </div>
    </section>
  </div>
</template>
