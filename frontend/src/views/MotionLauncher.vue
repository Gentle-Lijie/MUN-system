<script setup lang="ts">
import { ref } from 'vue'
import PopupMotion from '@/components/PopupMotion.vue'

const showMotionModal = ref(true)
const lastResult = ref('暂未提交动议信息。')

function handleMotionPass(payload: {
    motion: { title: string }
    form: { country: string; unitTime: number; totalTime: number; notes: string }
}) {
    const fragments: string[] = ['获得通过']
    if (payload.form.country) fragments.push(`发起国：${payload.form.country}`)
    if (payload.form.unitTime) fragments.push(`单次 ${payload.form.unitTime} 分钟`)
    if (payload.form.totalTime) fragments.push(`总时长 ${payload.form.totalTime} 分钟`)
    if (payload.form.notes) fragments.push(payload.form.notes)

    lastResult.value = `动议通过「${payload.motion.title}」 · ${fragments.join(' · ') || '无补充说明'}`
    showMotionModal.value = false
}

function handleMotionFail(payload: {
    motion: { title: string }
    form: { country: string; unitTime: number; totalTime: number; notes: string }
}) {
    const fragments: string[] = ['未获通过']
    if (payload.form.country) fragments.push(`发起国：${payload.form.country}`)
    if (payload.form.unitTime) fragments.push(`单次 ${payload.form.unitTime} 分钟`)
    if (payload.form.totalTime) fragments.push(`总时长 ${payload.form.totalTime} 分钟`)
    if (payload.form.notes) fragments.push(payload.form.notes)

    lastResult.value = `动议未通过「${payload.motion.title}」 · ${fragments.join(' · ') || '无补充说明'}`
    showMotionModal.value = false
}
</script>

<template>
    <section class="min-h-screen bg-base-200 p-8">
        <div class="mx-auto flex max-w-5xl flex-col gap-6 rounded-3xl bg-base-100 p-8 shadow-xl">
            <header class="flex flex-col gap-2">
                <p class="text-base-content/60 text-lg">动议配置</p>
                <h1 class="text-4xl font-bold">动议弹窗演示</h1>
                <p class="text-base text-base-content/70">通过按钮可以独立打开弹窗，方便在路由层级单独调试。</p>
            </header>
            <div class="rounded-2xl border border-base-200 bg-base-200/40 p-4 text-lg">
                {{ lastResult }}
            </div>
            <div class="flex flex-wrap gap-4">
                <button class="btn btn-primary btn-lg" @click="showMotionModal = true">打开动议弹窗</button>
                <button class="btn btn-ghost btn-lg" @click="$router.push('/management')">返回会议管理</button>
            </div>
        </div>
        <PopupMotion v-model="showMotionModal" @pass="handleMotionPass" @fail="handleMotionFail" />
    </section>
</template>
