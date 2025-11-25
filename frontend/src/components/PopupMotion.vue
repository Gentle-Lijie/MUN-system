<template>
    <dialog v-if="modelValue" class="modal" open>
        <div class="modal-box w-11/12 max-w-6xl bg-transparent p-0 h-[90vh]">
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
                                    : 'border-base-300 bg-base-100/70 hover:border-base-200'"
                                @click="selectedMotionId = motion.id">
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
                                <div v-if="activeMotion?.requires.country"
                                    class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_auto]">
                                    <FormField legend="发起国家" label="手动输入发起国家">
                                        <div class="input input-bordered flex items-center gap-2">
                                            <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24" fill="none">
                                                <path d="M12 12c2.7 0 8 1.34 8 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2c0-2.66 5.3-4 8-4z"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                                <circle cx="12" cy="7" r="4" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                            <input v-model="formState.country" type="text" placeholder="请输入发起国家"
                                                class="grow bg-transparent focus:outline-none" required />
                                        </div>
                                    </FormField>
                                    <FormField legend="代表名单" label="或从代表名单中选择">
                                        <select @change="onDelegateSelect" class="select select-bordered w-full">
                                            <option value="">选择代表</option>
                                            <option v-for="delegate in delegates" :key="delegate.id"
                                                :value="delegate.id">
                                                {{ delegate.country }} - {{ delegate.userName }}
                                            </option>
                                        </select>
                                    </FormField>
                                </div>
                                <template v-if="activeMotion?.requires.unitTime && activeMotion?.requires.totalTime">
                                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                        <FormField legend="单位时间（分钟）" label="每位发言代表的时间">
                                            <div class="input input-bordered flex items-center gap-2">
                                                <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24" fill="none">
                                                    <circle cx="12" cy="12" r="10" stroke="currentColor"
                                                        stroke-width="2" />
                                                    <polyline points="12,6 12,12 16,14" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                                <input v-model.number="formState.unitTime" type="number" min="1"
                                                    max="120" placeholder="例如 2"
                                                    class="grow bg-transparent focus:outline-none" required />
                                            </div>
                                        </FormField>
                                        <FormField legend="总时间（分钟）" label="整个动议的总时长">
                                            <div class="input input-bordered flex items-center gap-2">
                                                <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24" fill="none">
                                                    <circle cx="12" cy="12" r="10" stroke="currentColor"
                                                        stroke-width="2" />
                                                    <polyline points="12,6 12,12 16,14" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                                <input v-model.number="formState.totalTime" type="number" min="1"
                                                    max="480" placeholder="例如 20"
                                                    class="grow bg-transparent focus:outline-none" required />
                                            </div>
                                        </FormField>
                                    </div>
                                </template>
                                <FormField v-else-if="activeMotion?.requires.unitTime" legend="单位时间（分钟）"
                                    label="每位发言代表的时间">
                                    <div class="input input-bordered flex items-center gap-2">
                                        <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24" fill="none">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="2" />
                                            <polyline points="12,6 12,12 16,14" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <input v-model.number="formState.unitTime" type="number" min="1" max="120"
                                            placeholder="例如 2" class="grow bg-transparent focus:outline-none" required />
                                    </div>
                                </FormField>
                                <FormField v-else-if="activeMotion?.requires.totalTime" legend="总时间（分钟）"
                                    label="整个动议的总时长">
                                    <div class="input input-bordered flex items-center gap-2">
                                        <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24" fill="none">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="2" />
                                            <polyline points="12,6 12,12 16,14" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <input v-model.number="formState.totalTime" type="number" min="1" max="480"
                                            placeholder="例如 20" class="grow bg-transparent focus:outline-none" required />
                                    </div>
                                </FormField>
                                <FormField legend="附加说明（可选）" label="补充主持要点或文件信息">
                                    <textarea v-model="formState.notes" class="textarea textarea-bordered text-base"
                                        rows="4" placeholder="输入补充说明或主持要点"></textarea>
                                </FormField>
                                <button v-if="activeMotion?.id === 'reading' || activeMotion?.id === 'voting-doc'"
                                    type="button" class="btn btn-outline btn-lg text-lg mb-3"
                                    @click="showFileSelect = true">关联文件</button>

                                <FormField legend="自动发起点名" label="动议通过后立即点名"
                                    description="勾选后，动议获得通过时会自动触发代表点名"
                                    :label-class="'flex items-center justify-between gap-4'">
                                    <input type="checkbox" v-model="formState.triggerRollCall" class="checkbox" />
                                </FormField>
                            </div>
                            <div class="grid gap-4 lg:grid-cols-[4fr_4fr_2fr]">
                                <button type="button" class="btn btn-success btn-lg text-lg"
                                    @click="handlePass">获得通过</button>
                                <button type="button" class="btn btn-error btn-lg text-lg"
                                    @click="handleFail">未获通过</button>
                                <button type="button" class="btn btn-ghost btn-lg text-lg"
                                    @click="handleClose">取消</button>
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
    </dialog>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
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
}

const motions: MotionDefinition[] = [
    {
        id: 'main-speakers',
        title: '开启主发言名单',
        description: '开启新的主发言名单流程，设置单位与总时间。',
        badges: ['发起国家', '单位时间', '总时间'],
        requires: { country: true, unitTime: true, totalTime: true },
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
    (e: 'update:modelValue', value: boolean): void
    (e: 'pass', payload: { motion: MotionDefinition; form: MotionFormState }): void
    (e: 'fail', payload: { motion: MotionDefinition; form: MotionFormState }): void
}>()

const delegates = ref<any[]>([])

const selectedMotionId = ref(motions[0]?.id ?? '')
const formState = reactive<MotionFormState>({
    country: '',
    unitTime: 2,
    totalTime: 20,
    notes: '',
    triggerRollCall: false,
    proposerId: undefined,
})
const showFileSelect = ref(false)

const activeMotion = computed(() => motions.find((motion) => motion.id === selectedMotionId.value))

// 加载代表列表
const loadDelegates = async () => {
    if (!props.committeeId) return
    try {
        const response = await fetch(`${API_BASE}/api/venues/${props.committeeId}/delegate`, {
            credentials: 'include'
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
    formState.unitTime = 2
    formState.totalTime = 20
    formState.notes = ''
    formState.triggerRollCall = false
    formState.proposerId = undefined
    selectedMotionId.value = motions[0]?.id ?? ''
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
        const delegate = delegates.value.find(d => d.id.toString() === delegateId)
        if (delegate) {
            formState.country = delegate.country
            formState.proposerId = delegate.id
        }
    }
}

// 监听手动输入国家，如果不匹配代表则清除proposerId
watch(() => formState.country, (newCountry) => {
    const matchedDelegate = delegates.value.find(d => d.country === newCountry)
    if (matchedDelegate) {
        formState.proposerId = matchedDelegate.id
    } else {
        formState.proposerId = undefined
    }
})

function handlePass() {
    if (!activeMotion.value) return
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
    const linked = `关联文件: ${file.title}`
    formState.notes = [formState.notes, linked].filter(Boolean).join('\n')
}

function handleClose() {
    emit('update:modelValue', false)
    resetForm()
}
</script>
