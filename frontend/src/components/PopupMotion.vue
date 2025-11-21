<template>
    <dialog v-if="modelValue" class="modal" open>
        <div class="modal-box w-11/12 max-w-6xl bg-transparent p-0">
            <div class="flex flex-col gap-4 rounded-3xl bg-base-100 p-6 lg:p-10">
                <div class="flex flex-col gap-2 border-b border-base-200 pb-6">
                    <p class="text-base-content/60 text-base">动议控制面板</p>
                    <h3 class="text-3xl font-bold">选择并配置要发起的动议</h3>
                </div>

                <div class="grid gap-6 lg:grid-cols-[35%_65%]">
                    <section class="flex flex-col gap-4 rounded-3xl border border-base-200 bg-base-200/40 p-4 lg:p-6">
                        <header class="flex items-center justify-between">
                            <h4 class="text-xl font-semibold">可用动议</h4>
                            <span class="badge badge-neutral badge-lg">共 {{ motions.length }} 项</span>
                        </header>
                        <div class="space-y-3 overflow-y-auto pr-1" style="max-height: 24rem">
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

                    <section class="flex flex-col gap-6 rounded-3xl border border-base-200 bg-base-200/40 p-4 lg:p-6">
                        <header class="flex flex-col gap-2">
                            <p class="text-base-content/60 text-base">当前选择</p>
                            <h4 class="text-3xl font-semibold">{{ activeMotion?.title }}</h4>
                            <p class="text-sm text-base-content/70">{{ activeMotion?.description }}</p>
                        </header>
                        <div class="flex flex-col gap-5">
                            <div class="grid gap-4">
                                <fieldset v-if="activeMotion?.requires.country" class="fieldset mb-3">
                                    <legend class="fieldset-legend text-base font-semibold mb-3">发起国家</legend>
                                    <label class="input input-bordered flex items-center gap-2">
                                        <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M12 12c2.7 0 8 1.34 8 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2c0-2.66 5.3-4 8-4z"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <input v-model="formState.country" type="text" placeholder="请输入发起国家"
                                            class="grow" required />
                                    </label>
                                </fieldset>
                                <div v-if="activeMotion?.requires.unitTime && activeMotion?.requires.totalTime"
                                    class="grid grid-cols-2 gap-4">
                                    <fieldset class="fieldset mb-3">
                                        <legend class="fieldset-legend text-base font-semibold mb-3">单位时间（分钟）</legend>
                                        <label class="input input-bordered flex items-center gap-2">
                                            <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24" fill="none">
                                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                                                <polyline points="12,6 12,12 16,14" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            <input v-model.number="formState.unitTime" type="number" min="1" max="120"
                                                placeholder="例如 2" class="grow" required />
                                        </label>
                                    </fieldset>
                                    <fieldset class="fieldset mb-3">
                                        <legend class="fieldset-legend text-base font-semibold mb-3">总时间（分钟）</legend>
                                        <label class="input input-bordered flex items-center gap-2">
                                            <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24" fill="none">
                                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                                                <polyline points="12,6 12,12 16,14" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            <input v-model.number="formState.totalTime" type="number" min="1" max="480"
                                                placeholder="例如 20" class="grow" required />
                                        </label>
                                    </fieldset>
                                </div>
                                <fieldset v-else-if="activeMotion?.requires.unitTime" class="fieldset mb-3">
                                    <legend class="fieldset-legend text-base font-semibold">单位时间（分钟）</legend>
                                    <label class="input input-bordered flex items-center gap-2">
                                        <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24" fill="none">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                                            <polyline points="12,6 12,12 16,14" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <input v-model.number="formState.unitTime" type="number" min="1" max="120"
                                            placeholder="例如 2" class="grow" required />
                                    </label>
                                </fieldset>
                                <fieldset v-else-if="activeMotion?.requires.totalTime" class="fieldset mb-3">
                                    <legend class="fieldset-legend text-base font-semibold">总时间（分钟）</legend>
                                    <label class="input input-bordered flex items-center gap-2">
                                        <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24" fill="none">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                                            <polyline points="12,6 12,12 16,14" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <input v-model.number="formState.totalTime" type="number" min="1" max="480"
                                            placeholder="例如 20" class="grow" required />
                                    </label>
                                </fieldset>
                                <fieldset class="fieldset mb-3">
                                    <legend class="fieldset-legend text-base font-semibold mb-3">附加说明（可选）</legend>
                                    <textarea v-model="formState.notes" class="textarea textarea-bordered text-base"
                                        rows="4" placeholder="输入补充说明或主持要点"></textarea>
                                </fieldset>
                                <button v-if="activeMotion?.id === 'reading' || activeMotion?.id === 'voting-doc'"
                                    type="button" class="btn btn-outline btn-lg text-lg mb-3"
                                    @click="showFileSelect = true">关联文件</button>
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

const props = defineProps<{ modelValue: boolean }>()
const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'pass', payload: { motion: MotionDefinition; form: MotionFormState }): void
    (e: 'fail', payload: { motion: MotionDefinition; form: MotionFormState }): void
}>()

const selectedMotionId = ref(motions[0]?.id ?? '')
const formState = reactive<MotionFormState>({
    country: '',
    unitTime: 2,
    totalTime: 20,
    notes: '',
})
const showFileSelect = ref(false)

const activeMotion = computed(() => motions.find((motion) => motion.id === selectedMotionId.value))

watch(
    () => props.modelValue,
    (visible) => {
        if (visible) return
        resetForm()
    }
)

function resetForm() {
    formState.country = ''
    formState.unitTime = 2
    formState.totalTime = 20
    formState.notes = ''
    selectedMotionId.value = motions[0]?.id ?? ''
}

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

function handleFileSelect(file: string) {
    formState.notes += `\n关联文件: ${file}`
}

function handleClose() {
    emit('update:modelValue', false)
    resetForm()
}
</script>
