<template>
  <dialog v-if="modelValue" class="modal" open>
    <div class="modal-box w-11/12 max-w-4xl bg-transparent p-0 h-[80vh] overflow-y-auto">
      <div class="flex flex-col gap-4 rounded-3xl bg-base-100 p-6 lg:p-10">
        <div class="flex items-center justify-between border-b border-base-200 pb-4">
          <div>
            <p class="text-base-content/60 text-sm">投票前置流程</p>
            <h3 class="text-2xl font-bold">选择多数方案</h3>
          </div>
          <div class="flex gap-4 text-sm text-base-content/70">
            <span>总代表：{{ stats?.total ?? 0 }}</span>
            <span>出席：{{ stats?.present ?? 0 }}</span>
          </div>
        </div>

        <div class="grid gap-3">
          <label
            v-for="option in majorityOptions"
            :key="option.key"
            class="flex flex-col gap-2 rounded-2xl border p-4 cursor-pointer transition"
            :class="selectedOption?.key === option.key ? 'border-primary bg-primary/10' : 'border-base-300 bg-base-100'"
          >
            <div class="flex items-center justify-between">
              <div>
                <p class="text-lg font-semibold">{{ option.label }}</p>
                <p class="text-sm text-base-content/60">需要 {{ option.requiredVotes }} 票（{{ (option.ratio * 100).toFixed(1) }}%）</p>
              </div>
              <input
                class="radio radio-primary"
                type="radio"
                :value="option.key"
                :checked="selectedOption?.key === option.key"
                @change="() => (selectedOption = option)"
              />
            </div>
          </label>
        </div>

        <div class="flex items-center justify-between border-t border-base-200 pt-4">
          <p class="text-sm text-error" v-if="error">{{ error }}</p>
          <span v-else class="text-sm text-base-content/70">确定所需多数比例后进入投票</span>
          <div class="flex gap-3">
            <button type="button" class="btn btn-ghost" @click="handleCancel">返回</button>
            <button type="button" class="btn btn-primary" :disabled="!selectedOption" @click="handleConfirm">
              确认多数
            </button>
          </div>
        </div>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click.prevent="handleCancel">close</button>
    </form>
  </dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

export type MajorityKey = 'threeQuarters' | 'twoThirds' | 'half' | 'twentyPercent'

export type MajorityOption = {
  key: MajorityKey
  label: string
  ratio: number
  requiredVotes: number
}

export interface MajorityStats {
  total: number
  present: number
  threeQuarters: number
  twoThirds: number
  half: number
  twentyPercent: number
}

const props = defineProps<{ modelValue: boolean; stats: MajorityStats | null }>()
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'select', option: MajorityOption): void
  (e: 'cancel'): void
}>()

const selectedOption = ref<MajorityOption | null>(null)
const error = ref<string | null>(null)

const majorityOptions = computed<MajorityOption[]>(() => {
  const stats = props.stats
  return [
    { key: 'threeQuarters', label: '3/4 多数', ratio: 0.75, requiredVotes: stats?.threeQuarters ?? 0 },
    { key: 'twoThirds', label: '2/3 多数', ratio: 2 / 3, requiredVotes: stats?.twoThirds ?? 0 },
    { key: 'half', label: '1/2 多数', ratio: 0.5, requiredVotes: stats?.half ?? 0 },
    { key: 'twentyPercent', label: '20% 多数', ratio: 0.2, requiredVotes: stats?.twentyPercent ?? 0 },
  ]
})

watch(
  () => props.modelValue,
  (visible) => {
    if (visible) {
      selectedOption.value = null
      error.value = null
    }
  }
)

const handleConfirm = () => {
  if (!selectedOption.value) {
    error.value = '请选择多数方案'
    return
  }
  emit('select', selectedOption.value)
  emit('update:modelValue', false)
}

const handleCancel = () => {
  emit('cancel')
  emit('update:modelValue', false)
}
</script>
