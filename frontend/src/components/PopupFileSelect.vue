<template>
  <dialog v-if="modelValue" class="modal" open>
    <div class="modal-box">
      <h3 class="font-bold text-lg mb-4">选择关联文件</h3>
      <div v-if="loading" class="flex justify-center mb-4">
        <span class="loading loading-spinner"></span>
      </div>
      <div v-else class="space-y-2 max-h-60 overflow-y-auto">
        <div
          v-for="file in files"
          :key="file.id"
          class="p-2 border border-base-200 rounded cursor-pointer hover:bg-base-200"
          @click="selectFile(file)"
        >
          <div class="font-medium">{{ file.title }}</div>
          <div class="text-sm text-base-content/70 flex items-center gap-2">
            <span>{{ file.type }}</span>
            <span v-if="file.committee">· {{ file.committee.name }}</span>
            <span class="badge badge-ghost badge-sm">{{ visibilityLabel(file.visibility) }}</span>
          </div>
        </div>
        <div v-if="files.length === 0" class="text-center text-base-content/60 py-4">
          暂无可选文件
        </div>
      </div>
      <div class="modal-action">
        <button class="btn btn-ghost" @click="handleClose">取消</button>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click.prevent="handleClose">关闭</button>
    </form>
  </dialog>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api, type FileReference } from '@/services/api'

defineProps<{ modelValue: boolean }>()

const emit = defineEmits<{
  (_e: 'update:modelValue', _value: boolean): void
  (_e: 'select', file: FileReference): void
}>()

const files = ref<FileReference[]>([])
const loading = ref(false)

const fetchFiles = async () => {
  loading.value = true
  try {
    const response = await api.getFileReferences({ visibility: ['all_committees', 'public'] })
    files.value = response.items
  } catch (error) {
    console.error('Failed to fetch files:', error)
  } finally {
    loading.value = false
  }
}

const visibilityLabel = (visibility?: FileReference['visibility']) => {
  if (visibility === 'all_committees') return '全部会场'
  if (visibility === 'public') return '公开'
  if (visibility === 'committee_only') return '主席团'
  return '公开'
}

const selectFile = (file: FileReference) => {
  emit('select', file)
  emit('update:modelValue', false)
}

const handleClose = () => {
  emit('update:modelValue', false)
}

onMounted(fetchFiles)
</script>
