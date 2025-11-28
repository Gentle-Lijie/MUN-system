<template>
  <dialog v-if="modelValue" class="modal" open>
    <div class="modal-box">
      <h3 class="font-bold text-lg mb-4">选择发言者</h3>
      <FormField legend="快速筛选" label="输入国家名或代表名">
        <input v-model="search" type="text" placeholder="输入国家名或代表名" class="input input-bordered w-full"
          @keydown.enter="handleConfirm" />
      </FormField>
      <FormField legend="选择发言者" label="从代表名单中选择" description="列表会根据当前会场自动过滤">
        <select v-model="selectedDelegateId" class="select select-bordered w-full">
          <option disabled selected value="">请选择发言者</option>
          <option v-for="delegate in filteredDelegates" :key="delegate.id" :value="delegate.id">
            {{ delegate.country }} - {{ delegate.userName }}
          </option>
        </select>
      </FormField>
      <div class="modal-action">
        <button class="btn btn-primary w-80vw" @click="handleConfirm">确认添加</button>
        <button class="btn btn-ghost w-20vw" @click="handleClose">完成</button>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click.prevent="handleClose">关闭</button>
    </form>
  </dialog>
</template>

<script setup lang="ts">
import { ref, computed, defineProps, defineEmits, watch } from 'vue'
import FormField from '@/components/common/FormField.vue'
import { API_BASE } from '@/services/api'

const props = defineProps<{ modelValue: boolean; committeeId?: string }>()
const emit = defineEmits(['update:modelValue', 'confirm'])

const search = ref('')
const selectedDelegateId = ref('')
const delegates = ref<any[]>([])

const filteredDelegates = computed(() =>
  delegates.value.filter(
    (d) => d.country.includes(search.value) || d.userName.includes(search.value)
  )
)

// 当对话框打开时加载代表列表
watch(
  () => props.modelValue,
  async (isOpen) => {
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
      const url = `${API_BASE}/api/venues/${props.committeeId}/delegate`
      console.log('Fetching delegates from:', url)
      const response = await fetch(url, {
        credentials: 'include',
      })
      if (!response.ok) throw new Error('Failed to load delegates')

      const data = await response.json()
      delegates.value = data.items || []
      console.log('Loaded delegates:', delegates.value.length, delegates.value)
    } catch (error) {
      console.error('Failed to load delegates:', error)
    }
  },
  { immediate: true }
)

function handleConfirm() {
  console.log('PopupDelegate handleConfirm called', {
    selectedDelegateId: selectedDelegateId.value,
    search: search.value,
    delegates: delegates.value,
  })

  let delegate = null

  // 如果有选中的代表
  if (selectedDelegateId.value) {
    delegate = delegates.value.find((d) => String(d.id) === String(selectedDelegateId.value))
  }
  // 如果没有选中，但搜索框有内容，尝试严格匹配国家名
  else if (search.value.trim()) {
    delegate = delegates.value.find((d) => d.country === search.value.trim())
  }

  if (!delegate) {
    const message = selectedDelegateId.value ? '未找到选中的代表' : '未找到匹配国家的代表，请检查输入的国家名'
    console.error(message)
    alert(message)
    return
  }

  console.log('Found delegate:', delegate)
  emit('confirm', delegate)

  // 支持连续添加：重置选择，但不关闭弹窗
  selectedDelegateId.value = ''
  search.value = ''
}
function handleClose() {
  emit('update:modelValue', false)
}
</script>
