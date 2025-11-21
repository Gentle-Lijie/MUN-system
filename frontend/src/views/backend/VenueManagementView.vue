<script setup lang="ts">
import { reactive, ref } from 'vue'

type VenueStatus = '准备中' | '进行中' | '已结束'

type VenueSession = {
  time: string
  topic: string
  chair: string
}

type VenueRecord = {
  id: string
  name: string
  location: string
  status: VenueStatus
  capacity: number
  sessions: VenueSession[]
}

const venues = ref<VenueRecord[]>([
  {
    id: 'R1',
    name: '安理会',
    location: '会议中心 A1',
    status: '进行中',
    capacity: 45,
    sessions: [
      { time: '09:00 - 10:30', topic: '议程确认', chair: 'Alice' },
      { time: '10:45 - 12:00', topic: '危机简报', chair: 'Bob' },
    ],
  },
  {
    id: 'R2',
    name: '人权理事会',
    location: '会议中心 B3',
    status: '准备中',
    capacity: 60,
    sessions: [{ time: '13:30 - 15:00', topic: '国家陈述', chair: 'Carol' }],
  },
])

const selectedVenue = ref<VenueRecord | null>(venues.value[0] ?? null)
const timeConfig = reactive({
  startDate: '2025-05-15T09:00',
  endDate: '2025-05-18T18:00',
  speed: 1,
})

const newSession = reactive({
  time: '',
  topic: '',
  chair: '',
})

const selectVenue = (venue: VenueRecord) => {
  selectedVenue.value = venue
}

const addSession = () => {
  if (!selectedVenue.value || !newSession.time || !newSession.topic) return
  selectedVenue.value.sessions.push({
    time: newSession.time,
    topic: newSession.topic,
    chair: newSession.chair || '未指定',
  })
  newSession.time = ''
  newSession.topic = ''
  newSession.chair = ''
}

const toggleVenueStatus = (venue: VenueRecord) => {
  venue.status = venue.status === '进行中' ? '已结束' : venue.status === '准备中' ? '进行中' : '准备中'
}
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">会场管理</h2>
      <p class="text-sm text-base-content/70">配置会场信息、会期时间和主席团成员安排。</p>
    </header>

    <section class="grid gap-6 xl:grid-cols-[1.4fr,1fr]">
      <div class="space-y-4">
        <div class="grid md:grid-cols-2 gap-4">
          <article
            v-for="venue in venues"
            :key="venue.id"
            class="border border-base-200 rounded-2xl p-4 bg-base-100 hover:border-primary cursor-pointer"
            @click="selectVenue(venue)"
          >
            <div class="flex justify-between items-start">
              <div>
                <p class="text-xs text-base-content/60">{{ venue.id }}</p>
                <h3 class="text-lg font-semibold">{{ venue.name }}</h3>
                <p class="text-sm text-base-content/70">{{ venue.location }}</p>
              </div>
              <span class="badge" :class="{
                'badge-success': venue.status === '进行中',
                'badge-warning': venue.status === '准备中',
                'badge-neutral': venue.status === '已结束'
              }">
                {{ venue.status }}
              </span>
            </div>
            <div class="mt-3 flex justify-between text-sm">
              <p>容量：{{ venue.capacity }} 人</p>
              <button class="btn btn-xs" @click.stop="toggleVenueStatus(venue)">切换状态</button>
            </div>
          </article>
        </div>

        <div class="border border-base-200 rounded-2xl p-4" v-if="selectedVenue">
          <h3 class="text-lg font-semibold mb-2">{{ selectedVenue.name }} · 议程安排</h3>
          <div class="overflow-x-auto">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>时间</th>
                  <th>议题</th>
                  <th>主席团</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="session in selectedVenue.sessions" :key="session.time + session.topic">
                  <td>{{ session.time }}</td>
                  <td>{{ session.topic }}</td>
                  <td>{{ session.chair }}</td>
                </tr>
                <tr v-if="selectedVenue.sessions.length === 0">
                  <td colspan="3" class="text-center text-base-content/60">尚未安排议程</td>
                </tr>
              </tbody>
            </table>
          </div>
          <form class="grid md:grid-cols-3 gap-3 mt-4" @submit.prevent="addSession">
            <input v-model="newSession.time" type="text" placeholder="时间段" class="input input-bordered" />
            <input v-model="newSession.topic" type="text" placeholder="议题" class="input input-bordered" />
            <input v-model="newSession.chair" type="text" placeholder="主席团成员" class="input input-bordered" />
            <button class="btn btn-primary md:col-span-3" type="submit">添加议程条目</button>
          </form>
        </div>
      </div>

      <div class="space-y-4">
        <div class="border border-base-200 rounded-2xl p-4">
          <h3 class="font-semibold">会期时间配置</h3>
          <label class="form-control w-full">
            <span class="label-text">开始时间</span>
            <input v-model="timeConfig.startDate" type="datetime-local" class="input input-bordered" />
          </label>
          <label class="form-control w-full mt-3">
            <span class="label-text">结束时间</span>
            <input v-model="timeConfig.endDate" type="datetime-local" class="input input-bordered" />
          </label>
          <label class="form-control w-full mt-3">
            <span class="label-text">时间流速 (x{{ timeConfig.speed }})</span>
            <input v-model.number="timeConfig.speed" type="range" min="1" max="4" class="range range-primary" />
            <div class="flex justify-between text-xs text-base-content/60">
              <span>实时</span>
              <span>2x</span>
              <span>3x</span>
              <span>4x</span>
            </div>
          </label>
          <button class="btn btn-primary w-full mt-4">保存时间配置</button>
        </div>

        <div class="border border-base-200 rounded-2xl p-4">
          <h3 class="font-semibold">主席团成员管理</h3>
          <p class="text-sm text-base-content/70">为选中会场分配或调整主席团成员。</p>
          <div class="join join-vertical w-full mt-3">
            <label class="input input-bordered join-item flex items-center gap-2">
              <span class="text-xs">姓名</span>
              <input type="text" class="grow" placeholder="输入成员姓名" />
            </label>
            <label class="input input-bordered join-item flex items-center gap-2">
              <span class="text-xs">角色</span>
              <input type="text" class="grow" placeholder="主席/副主席/秘书" />
            </label>
            <button class="btn btn-outline join-item">添加成员</button>
          </div>
          <button class="btn btn-ghost w-full mt-2">查看完整成员列表</button>
        </div>
      </div>
    </section>
  </div>
</template>
