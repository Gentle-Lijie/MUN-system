<script setup lang="ts">
import { ref } from 'vue'
import PopupDelegate from '@/components/PopupDelegate.vue'

const showPopup = ref(true)
const lastCountry = ref('（尚未选择国家）')

function handleConfirm(country: string) {
    if (country) {
        lastCountry.value = `已选择：${country}`
    }
}
</script>

<template>
    <section class="min-h-screen bg-base-200 p-8">
        <div class="mx-auto flex max-w-3xl flex-col gap-4 rounded-3xl bg-base-100 p-8 shadow-xl">
            <h1 class="text-4xl font-bold">发言者选择弹窗</h1>
            <p class="text-lg text-base-content/70">{{ lastCountry }}</p>
            <div class="flex gap-4">
                <button class="btn btn-primary" @click="showPopup = true">打开弹窗</button>
                <button class="btn" @click="$router.push('/backend')">返回后台工作台</button>
            </div>
        </div>
        <PopupDelegate v-model="showPopup" @confirm="handleConfirm" />
    </section>
</template>
