<template>
    <dialog v-if="modelValue" class="modal" open>
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">选择发言者</h3>
            <input v-model="search" type="text" placeholder="输入国家名或代表名" class="input input-bordered w-full mb-4" />
            <select v-model="selectedDelegateId" class="select select-bordered w-full mb-4">
                <option disabled selected value="">请选择发言者</option>
                <option v-for="delegate in filteredDelegates" :key="delegate.id" :value="delegate.id">
                    {{ delegate.country }} - {{ delegate.userName }}
                </option>
            </select>
            <div class="modal-action">
                <button class="btn btn-primary w-full" @click="handleConfirm">确认</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button @click.prevent="handleClose">关闭</button>
        </form>
    </dialog>
</template>

<script setup lang="ts">
import { ref, computed, defineProps, defineEmits, watch } from 'vue'

const props = defineProps<{ modelValue: boolean; committeeId?: string }>()
const emit = defineEmits(['update:modelValue', 'confirm'])

const search = ref('')
const selectedDelegateId = ref('')
const delegates = ref<any[]>([])

const filteredDelegates = computed(() =>
    delegates.value.filter(d =>
        d.country.includes(search.value) ||
        d.userName.includes(search.value)
    )
)

// 当对话框打开时加载代表列表
watch(() => props.modelValue, async (isOpen) => {
    console.log('PopupDelegate dialog state changed', { isOpen, committeeId: props.committeeId })
    if (!isOpen || !props.committeeId) {
        if (!props.committeeId) {
            console.warn('No committeeId provided')
        }
        return
    }

    // 重置选择
    selectedDelegateId.value = ''
    search.value = ''

    try {
        const url = `http://localhost:8000/api/venues/${props.committeeId}/delegate`
        console.log('Fetching delegates from:', url)
        const response = await fetch(url, {
            credentials: 'include'
        })
        if (!response.ok) throw new Error('Failed to load delegates')

        const data = await response.json()
        delegates.value = data.items || []
        console.log('Loaded delegates:', delegates.value.length, delegates.value)
    } catch (error) {
        console.error('Failed to load delegates:', error)
    }
}, { immediate: true })

function handleConfirm() {
    console.log('PopupDelegate handleConfirm called', {
        selectedDelegateId: selectedDelegateId.value,
        delegates: delegates.value
    })
    if (!selectedDelegateId.value) {
        console.warn('No delegate selected')
        alert('请先选择一个代表')
        return
    }
    const delegate = delegates.value.find(d => String(d.id) === String(selectedDelegateId.value))
    console.log('Found delegate:', delegate)
    if (!delegate) {
        console.error('Delegate not found in list')
        alert('未找到选中的代表')
        return
    }
    console.log('Emitting confirm event with delegate:', delegate)
    emit('confirm', delegate)
    emit('update:modelValue', false)
}
function handleClose() {
    emit('update:modelValue', false)
}
</script>
