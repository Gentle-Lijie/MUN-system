<template>
    <div class="p-8">
        <h1 class="text-2xl mb-4">测试 PopupDelegate</h1>

        <div class="mb-4">
            <p>showPopupDelegate: {{ showPopupDelegate }}</p>
            <p>speakerListId: {{ speakerListId }}</p>
            <p>committeeId: {{ committeeId }}</p>
        </div>

        <button class="btn btn-primary" @click="handleOpenDialog">
            打开对话框
        </button>

        <PopupDelegate v-model="showPopupDelegate" :committee-id="committeeId" @confirm="handleConfirm" />

        <div v-if="lastDelegate" class="mt-4 p-4 bg-base-200 rounded">
            <h2 class="font-bold">最后选择的代表：</h2>
            <pre>{{ JSON.stringify(lastDelegate, null, 2) }}</pre>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import PopupDelegate from '@/components/PopupDelegate.vue'
import { API_BASE } from '@/services/api'

const showPopupDelegate = ref(false)
const speakerListId = ref('9')
const committeeId = ref('1')
const lastDelegate = ref<any>(null)

function handleOpenDialog() {
    console.log('=== 打开对话框 ===')
    showPopupDelegate.value = true
}

function handleConfirm(delegate: any) {
    console.log('=== handleConfirm 被调用 ===', delegate)
    lastDelegate.value = delegate

    if (!delegate || !speakerListId.value) {
        console.warn('缺少 delegate 或 speakerListId')
        return
    }

    console.log('准备发送 POST 请求...')
    fetch(`${API_BASE}/api/display/speakers`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            speakerListId: speakerListId.value,
            delegateId: delegate.id
        })
    })
        .then(response => {
            console.log('Response status:', response.status)
            return response.json()
        })
        .then(data => {
            console.log('Response data:', data)
            alert('添加成功！')
        })
        .catch(error => {
            console.error('Error:', error)
            alert('添加失败：' + error.message)
        })
}
</script>
