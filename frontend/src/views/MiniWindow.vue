<script setup lang="ts">
import { ref } from 'vue'

type TabKey = 'files' | 'messages'

const tabs: { key: TabKey; label: string }[] = [
    { key: 'files', label: '提交文件' },
    { key: 'messages', label: '发送消息' },
]

const activeTab = ref<TabKey>('files')

function setTab(key: TabKey) {
    activeTab.value = key
}
</script>

<template>
    <section class="mx-auto max-w-xl">
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body flex flex-col gap-8">
                <div class="flex justify-center">
                    <div class="tabs tabs-border">
                        <template v-for="(tab, idx) in tabs" :key="tab.key">
                            <button class="tab" :class="{ 'tab-active': activeTab === tab.key }"
                                @click="setTab(tab.key)">
                                {{ tab.label }}
                            </button>
                            <span v-if="idx < tabs.length - 1" class="mx-4"></span>
                        </template>
                    </div>
                </div>

                <div v-if="activeTab === 'files'" class="flex flex-col gap-6">
                    <h2 class="text-xl font-semibold text-center">上传文件给主席团</h2>
                    <fieldset class="fieldset fieldset-primary">
                        <legend class="fieldset-legend mb-3">文件标题</legend>
                        <label class="input validator w-full flex items-center gap-3">
                            <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                fill="none">
                                <path d="M4 4h7v4a1 1 0 001 1h4v11a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M15 4l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            <input type="input" required placeholder="文件标题" pattern="[A-Za-z][A-Za-z0-9\-]*"
                                minlength="3" maxlength="30" title="Only letters, numbers or dash" class="grow" />
                        </label>
                    </fieldset>
                    <fieldset class="fieldset fieldset-primary">
                        <legend class="fieldset-legend mb-3">选择文件</legend>
                        <input type="file" class="file-input file-input-primary file-input-bordered w-full" />
                        <div class="fieldset-label">支持多种格式</div>
                    </fieldset>
                    <fieldset class="fieldset fieldset-primary">
                        <legend class="fieldset-legend mb-3">备注/致辞</legend>
                        <textarea class="textarea h-24 w-full" placeholder="可附上背景说明"></textarea>
                        <div class="fieldset-label">选填</div>
                    </fieldset>
                    <button class="btn btn-primary w-full">提交</button>
                </div>

                <div v-else class="flex flex-col gap-6">
                    <h2 class="text-xl font-semibold text-center">发送即时消息</h2>
                    <fieldset class="fieldset fieldset-primary">
                        <legend class="fieldset-legend mb-3">目标频道</legend>
                        <label class="input validator w-full flex items-center gap-3">
                            <svg class="h-[1.2em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                fill="none">
                                <path d="M12 12c2.7 0 8 1.34 8 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2c0-2.66 5.3-4 8-4z"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <select class="select select-bordered w-full">
                                <option>主席团频道</option>
                                <option>全体代表</option>
                                <option>危机控制室</option>
                            </select>
                        </label>
                        <div class="fieldset-label">请选择发送对象</div>
                    </fieldset>
                    <fieldset class="fieldset fieldset-primary">
                        <legend class="fieldset-legend mb-3">消息内容</legend>
                        <textarea class="textarea textarea-bordered w-full" rows="4" placeholder="输入广播内容"></textarea>
                    </fieldset>
                    <button class="btn btn-primary w-full">发送</button>
                </div>
            </div>
        </div>
    </section>
</template>
