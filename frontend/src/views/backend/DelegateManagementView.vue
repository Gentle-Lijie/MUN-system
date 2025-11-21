<script setup lang="ts">
import { computed, reactive, ref } from 'vue'

type AttendanceStatus = 'present' | 'late' | 'absent'

type DelegateRecord = {
  id: string
  name: string
  country: string
  bloc: string
  status: AttendanceStatus
}

const delegates = ref<DelegateRecord[]>([
  { id: 'D-001', name: '陈思', country: '中国', bloc: 'P5', status: 'present' },
  { id: 'D-002', name: 'John', country: '美国', bloc: 'P5', status: 'present' },
  { id: 'D-003', name: 'Sara', country: '英国', bloc: 'P5', status: 'late' },
  { id: 'D-004', name: 'Maria', country: '西班牙', bloc: '欧盟', status: 'absent' },
])

const blocFilter = ref<'all' | string>('all')
const keyword = ref('')

const filteredDelegates = computed(() => {
  return delegates.value.filter((delegate) => {
    const blocOk = blocFilter.value === 'all' || delegate.bloc === blocFilter.value
    const keywordOk = keyword.value
      ? [delegate.name, delegate.country, delegate.id].some((field) =>
          field.toLowerCase().includes(keyword.value.toLowerCase()),
        )
      : true
    return blocOk && keywordOk
  })
})

const rollCallStats = computed(() => ({
  present: delegates.value.filter((d) => d.status === 'present').length,
  late: delegates.value.filter((d) => d.status === 'late').length,
  absent: delegates.value.filter((d) => d.status === 'absent').length,
}))

const importForm = reactive({
  source: '' as 'excel' | 'csv' | '',
  fileName: '',
})

const countryAssignments = reactive({
  name: '',
  country: '',
  bloc: '',
})

const updateStatus = (delegate: DelegateRecord, status: AttendanceStatus) => {
  delegate.status = status
}

const assignCountry = () => {
  if (!countryAssignments.name || !countryAssignments.country) return
  delegates.value.push({
    id: `D-${(delegates.value.length + 1).toString().padStart(3, '0')}`,
    name: countryAssignments.name,
    country: countryAssignments.country,
    bloc: countryAssignments.bloc || '未分类',
    status: 'present',
  })
  countryAssignments.name = ''
  countryAssignments.country = ''
  countryAssignments.bloc = ''
}
</script>

<template>
  <div class="p-6 space-y-6">
    <header class="border-b border-base-200 pb-4">
      <h2 class="text-2xl font-bold">代表管理</h2>
      <p class="text-sm text-base-content/70">支持数据导入、国家分配与点名状态记录。</p>
    </header>

    <section class="grid gap-6 xl:grid-cols-[1.8fr,1fr]">
      <div class="space-y-4">
        <div class="flex flex-wrap gap-3">
          <label class="input input-bordered flex items-center gap-2 grow min-w-[12rem]">
            <input v-model="keyword" type="text" placeholder="按姓名/国家搜索" class="grow" />
          </label>
          <select v-model="blocFilter" class="select select-bordered w-40">
            <option value="all">全部集团</option>
            <option value="P5">P5</option>
            <option value="欧盟">欧盟</option>
            <option value="亚太">亚太</option>
          </select>
          <button class="btn btn-outline">导出名单</button>
        </div>

        <div class="overflow-x-auto border border-base-200 rounded-2xl">
          <table class="table table-zebra">
            <thead>
              <tr>
                <th>ID</th>
                <th>姓名</th>
                <th>国家</th>
                <th>集团</th>
                <th>点名状态</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="delegate in filteredDelegates" :key="delegate.id">
                <td>{{ delegate.id }}</td>
                <td class="font-semibold">{{ delegate.name }}</td>
                <td>{{ delegate.country }}</td>
                <td>{{ delegate.bloc }}</td>
                <td>
                  <div class="join">
                    <button class="btn btn-xs join-item" :class="delegate.status === 'present' ? 'btn-success' : 'btn-ghost'"
                      @click="updateStatus(delegate, 'present')">到</button>
                    <button class="btn btn-xs join-item" :class="delegate.status === 'late' ? 'btn-warning' : 'btn-ghost'"
                      @click="updateStatus(delegate, 'late')">迟</button>
                    <button class="btn btn-xs join-item" :class="delegate.status === 'absent' ? 'btn-error' : 'btn-ghost'"
                      @click="updateStatus(delegate, 'absent')">缺</button>
                  </div>
                </td>
                <td>
                  <button class="btn btn-sm btn-ghost">编辑</button>
                </td>
              </tr>
              <tr v-if="filteredDelegates.length === 0">
                <td colspan="6" class="text-center text-base-content/60">暂无数据</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="space-y-4">
        <div class="border border-base-200 rounded-2xl p-4">
          <h3 class="font-semibold">点名状态</h3>
          <div class="stats stats-vertical lg:stats-horizontal shadow-none">
            <div class="stat">
              <div class="stat-title">到场</div>
              <div class="stat-value text-success">{{ rollCallStats.present }}</div>
            </div>
            <div class="stat">
              <div class="stat-title">迟到</div>
              <div class="stat-value text-warning">{{ rollCallStats.late }}</div>
            </div>
            <div class="stat">
              <div class="stat-title">缺席</div>
              <div class="stat-value text-error">{{ rollCallStats.absent }}</div>
            </div>
          </div>
        </div>

        <form class="border border-base-200 rounded-2xl p-4 space-y-3" @submit.prevent="assignCountry">
          <h3 class="font-semibold">国家分配</h3>
          <label class="form-control w-full">
            <span class="label-text">代表姓名</span>
            <input v-model="countryAssignments.name" type="text" class="input input-bordered" placeholder="姓名" />
          </label>
          <label class="form-control w-full">
            <span class="label-text">分配国家</span>
            <input v-model="countryAssignments.country" type="text" class="input input-bordered" placeholder="国家" />
          </label>
          <label class="form-control w-full">
            <span class="label-text">集团</span>
            <input v-model="countryAssignments.bloc" type="text" class="input input-bordered" placeholder="集团" />
          </label>
          <button class="btn btn-primary w-full" type="submit">确认分配</button>
        </form>

        <div class="border border-dashed border-base-300 rounded-2xl p-4">
          <h3 class="font-semibold">批量导入</h3>
          <p class="text-sm text-base-content/60">支持 Excel / CSV 模板，一键导入代表名单。</p>
          <div class="mt-3 space-y-2">
            <label class="label cursor-pointer">
              <span class="label-text">选择模板格式</span>
              <select v-model="importForm.source" class="select select-bordered">
                <option value="" disabled>请选择</option>
                <option value="excel">Excel 模板</option>
                <option value="csv">CSV 文件</option>
              </select>
            </label>
            <label class="label">
              <span class="label-text">上传文件</span>
              <input type="file" class="file-input file-input-bordered w-full" @change="importForm.fileName = '已选择文件'" />
            </label>
            <button class="btn btn-outline w-full">执行导入</button>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
