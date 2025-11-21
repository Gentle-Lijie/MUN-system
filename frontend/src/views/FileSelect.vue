<script setup lang="ts">
import { ref } from 'vue'
import PopupFileSelect from '@/components/PopupFileSelect.vue'

const showFileSelect = ref(true)

function handleFileSelect(file: string) {
    console.log('Selected file:', file)
    showFileSelect.value = false
}
</script>

<template>
    <section class="min-h-screen bg-base-200 p-8">
        <div class="mx-auto flex max-w-3xl flex-col gap-4 rounded-3xl bg-base-100 p-8 shadow-xl">
            <h1 class="text-4xl font-bold">文件选择</h1>
            <p class="text-lg text-base-content/70">用于动议关联文件的独立页面。</p>
            <div class="flex gap-4">
                <button class="btn btn-primary" @click="showFileSelect = true">打开文件选择弹窗</button>
                <button class="btn" @click="$router.push('/display')">返回显示大屏</button>
            </div>
        </div>
        <PopupFileSelect v-model="showFileSelect" @select="handleFileSelect" />
    </section>
</template>