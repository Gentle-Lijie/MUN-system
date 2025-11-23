<script setup lang="ts">
import { ref, reactive } from 'vue'
import { api } from '@/services/api'

type TabKey = 'files' | 'messages'

const tabs: { key: TabKey; label: string }[] = [
    { key: 'files', label: '提交文件' },
    { key: 'messages', label: '发送消息' },
]

const activeTab = ref<TabKey>('files')

const fileForm = reactive({
    title: '',
    type: 'position_paper',
    description: '',
    file: null as File | null,
})

const messageForm = reactive({
    channel: 'presidium',
    content: '',
})

const submitting = ref(false)

const submitting = ref(false)

function setTab(key: TabKey) {
    activeTab.value = key
}

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement
    fileForm.file = target.files?.[0] || null
}

const submitFile = async () => {
    if (!fileForm.title || !fileForm.file) return

    submitting.value = true
    try {
        // Upload file first
        const uploadResult = await api.uploadFile(fileForm.file)

        // Then submit the file info
        await api.submitFile({
            title: fileForm.title,
            type: fileForm.type,
            description: fileForm.description || undefined,
            content_path: uploadResult.fileUrl,
        })

        // Reset form
        Object.assign(fileForm, {
            title: '',
            type: 'position_paper',
            description: '',
            file: null,
        })

        // Reset file input
        const fileInput = document.querySelector('input[type="file"]') as HTMLInputElement
        if (fileInput) fileInput.value = ''

        alert('文件提交成功！')
    } catch (error) {
        console.error('Failed to submit file:', error)
        alert('文件提交失败，请重试')
    } finally {
        submitting.value = false
    }
}

const sendMessage = async () => {
    if (!messageForm.content) return

    submitting.value = true
    try {
        await api.sendMessage({
            channel: messageForm.channel,
            content: messageForm.content,
        })

        alert('消息发送成功！')
        messageForm.content = ''
    } catch (error) {
        console.error('Failed to send message:', error)
        alert('消息发送失败，请重试')
    } finally {
        submitting.value = false
    }
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
                        <input v-model="fileForm.title" type="text" class="input input-bordered w-full"
                            placeholder="文件标题" required />
                    </fieldset>
                    <fieldset class="fieldset fieldset-primary">
                        <legend class="fieldset-legend mb-3">文件类型</legend>
                        <select v-model="fileForm.type" class="select select-bordered w-full">
                            <option value="position_paper">立场文件</option>
                            <option value="working_paper">工作文件</option>
                            <option value="draft_resolution">决议草案</option>
                            <option value="press_release">新闻稿</option>
                            <option value="other">其他</option>
                        </select>
                    </fieldset>
                    <fieldset class="fieldset fieldset-primary">
                        <legend class="fieldset-legend mb-3">选择文件</legend>
                        <input type="file" class="file-input file-input-primary file-input-bordered w-full"
                            @change="handleFileChange" required />
                        <div class="fieldset-label">支持多种格式</div>
                    </fieldset>
                    <fieldset class="fieldset fieldset-primary">
                        <legend class="fieldset-legend mb-3">备注/致辞</legend>
                        <textarea v-model="fileForm.description" class="textarea h-24 w-full"
                            placeholder="可附上背景说明"></textarea>
                        <div class="fieldset-label">选填</div>
                    </fieldset>
                    <button class="btn btn-primary w-full" :disabled="submitting || !fileForm.title || !fileForm.file"
                        @click="submitFile">
                        <span v-if="submitting" class="loading loading-spinner loading-sm"></span>
                        提交
                    </button>
                </div>

                <div v-else class="flex flex-col gap-6">
                    <h2 class="text-xl font-semibold text-center">发送即时消息</h2>
                    <fieldset class="fieldset fieldset-primary">
                        <legend class="fieldset-legend mb-3">目标频道</legend>
                        <select v-model="messageForm.channel" class="select select-bordered w-full">
                            <option value="presidium">主席团频道</option>
                            <option value="all">全体代表</option>
                            <option value="crisis">危机控制室</option>
                        </select>
                    </fieldset>
                    <fieldset class="fieldset fieldset-primary">
                        <legend class="fieldset-legend mb-3">消息内容</legend>
                        <textarea v-model="messageForm.content" class="textarea textarea-bordered w-full" rows="4"
                            placeholder="输入广播内容" required></textarea>
                    </fieldset>
                    <button class="btn btn-primary w-full" :disabled="submitting || !messageForm.content"
                        @click="sendMessage">
                        <span v-if="submitting" class="loading loading-spinner loading-sm"></span>
                        发送
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
