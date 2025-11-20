<script setup lang="ts">
import { computed } from 'vue'

const speakerQueue = [
    { country: 'France', delegate: 'Alice Martin', status: 'speaking', timeLeft: '01:30' },
    { country: 'China', delegate: 'Li Wei', status: 'waiting', timeLeft: '02:00' },
    { country: 'Brazil', delegate: 'Carla Souza', status: 'waiting', timeLeft: '02:00' },
    { country: 'Egypt', delegate: 'Omar Hassan', status: 'waiting', timeLeft: '02:00' },
    { country: 'Egypt', delegate: 'Omar Hassan', status: 'waiting', timeLeft: '02:00' },
]

const historyEvents = [
    { title: '开启主发言名单', description: '主持人宣布正式议程开始。' },
    { title: '动议：主持核心磋商', description: '通过 20 分钟主持核心磋商。' },
    { title: '提交工作文件', description: 'GA1/2025/WP.3 已提交。' },
    { title: '休会', description: '午餐休息 30 分钟。' },
]

const currentTime = computed(() => {
    const now = new Date()
    return now.toLocaleDateString('zh-CN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
})
</script>

<template>
    <section class="h-screen overflow-hidden bg-base-200 p-2">
        <div class="flex h-full min-h-0 flex-col gap-4">
            <header class="flex items-center justify-between rounded-3xl bg-base-100 px-8 py-3 shadow-xl">
                <h1 class="text-4xl font-bold">当前会场：GA1 - 核裁军</h1>
                <div class="text-center text-3xl font-semibold">{{ currentTime }}</div>
                <div class="stats stats-horizontal gap-2 text-right">
                    <div class="stat place-items-end">
                        <div class="stat-title text-lg">代表总数</div>
                        <div class="stat-value text-5xl">183</div>
                    </div>
                    <div class="stat place-items-end">
                        <div class="stat-title text-lg">2/3 多数</div>
                        <div class="stat-value text-5xl">122</div>
                    </div>
                    <div class="stat place-items-end">
                        <div class="stat-title text-lg">1/2 多数</div>
                        <div class="stat-value text-5xl">91</div>
                    </div>
                    <div class="stat place-items-end">
                        <div class="stat-title text-lg">20% 多数</div>
                        <div class="stat-value text-5xl">36</div>
                    </div>
                </div>
            </header>

            <div class="grid flex-1 min-h-0 gap-4 lg:grid-cols-[35%_65%]">
                <div class="flex h-full min-h-0 flex-col rounded-3xl bg-base-100 shadow-xl">
                    <div class="px-8 py-6 text-center text-6xl font-bold">00:00</div>
                    <div class="px-8 pb-6">
                        <div class="grid grid-cols-3 gap-3">
                            <button class="btn btn-primary w-full text-1.5xl">开始计时</button>
                            <button class="btn btn-secondary w-full text-1.5xl">停止计时</button>
                            <button class="btn btn-accent w-full text-1.5xl">下一个发言者</button>
                        </div>
                    </div>
                    <div class="flex-1 min-h-0 overflow-y-auto px-8 pb-6">
                        <h2 class="card-title mb-4 text-3xl">当前发言名单</h2>
                        <div class="space-y-4">
                            <article v-for="item in speakerQueue" :key="item.country"
                                class="rounded-2xl border border-base-300 bg-base-200/40 px-4 py-3"
                                :class="item.status === 'speaking' ? 'border-primary bg-primary/10' : ''">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-3xl font-semibold">{{ item.country }}</p>
                                        <p class="text-lg text-base-content/70">{{ item.delegate }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg uppercase text-base-content/50">{{ item.status }}</p>
                                        <p class="text-4xl font-bold">{{ item.timeLeft }}</p>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                    <div class="px-8 pb-8">
                        <button class="btn btn-primary w-full text-1.5xl">添加发言者</button>
                    </div>
                </div>

                <div class="flex h-full min-h-0 flex-col rounded-3xl bg-base-100 shadow-xl">
                    <div class="px-8 py-6 border-b border-base-200">
                        <h2 class="card-title text-3xl">会议历程</h2>
                    </div>
                    <div class="flex-1 min-h-0 overflow-y-auto px-8 py-6 space-y-4">
                        <div v-for="event in historyEvents" :key="event.title"
                            class="rounded-2xl border border-base-300 bg-base-200/40 px-4 py-3">
                            <div>
                                <h3 class="font-semibold text-xl">{{ event.title }}</h3>
                                <p class="text-lg text-base-content/70">{{ event.description }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-8 pb-8">
                        <button class="btn btn-accent w-full text-1.5xl">发起动议</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
