<template>
  <dialog v-if="modelValue" class="modal" open>
    <div class="modal-box max-w-4xl overflow-y-auto">
      <h3 class="text-2xl font-bold mb-4">{{ dialogTitle }}</h3>
      <div class="max-h-96 overflow-y-auto space-y-2">
        <div
          v-for="delegate in delegates"
          :key="delegate.id"
          class="flex items-center justify-between p-3 bg-base-200 rounded"
        >
          <span class="text-lg">{{ delegate.country }} - {{ delegate.userName }}</span>
          <div class="flex gap-2">
            <button
              class="btn btn-sm"
              :class="attendanceMap[delegate.id]?.value === 'present' ? 'btn-success' : 'btn-ghost'"
              @click="setAttendance(delegate.id, 'present')"
            >
              出席
            </button>
            <button
              class="btn btn-sm"
              :class="attendanceMap[delegate.id]?.value === 'absent' ? 'btn-error' : 'btn-ghost'"
              @click="setAttendance(delegate.id, 'absent')"
            >
              缺席
            </button>
          </div>
        </div>
      </div>
      <div class="modal-action">
        <div class="flex-1 text-sm text-base-content/60">
          出席：{{ presentCount }} ｜ 缺席：{{ delegates.length - presentCount }}
        </div>
        <button class="btn btn-ghost" @click="handleCancel">{{ cancelLabel }}</button>
        <button class="btn btn-primary" @click="handleConfirm">{{ confirmLabel }}</button>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click.prevent="handleCancel">close</button>
    </form>
  </dialog>
</template>

<script setup lang="ts">
import { computed, reactive, watch } from 'vue'

export type AttendanceStatus = 'present' | 'absent'

export interface RollCallDelegate {
  id: number
  country: string
  userName: string
  status?: AttendanceStatus
}

const props = defineProps<{
  modelValue: boolean
  delegates: RollCallDelegate[]
  title?: string
  confirmText?: string
  cancelText?: string
  initialAttendance?: Record<string | number, AttendanceStatus>
}>()
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'confirm', attendance: Record<string, AttendanceStatus>): void
  (e: 'cancel'): void
}>()

const attendanceMap = reactive<Record<number, { value: AttendanceStatus }>>({})

const dialogTitle = computed(() => props.title ?? '点名')
const confirmLabel = computed(() => props.confirmText ?? '确认')
const cancelLabel = computed(() => props.cancelText ?? '取消')

const resolveInitialStatus = (delegateId: number, fallback?: AttendanceStatus) => {
  const attendance = props.initialAttendance
  if (!attendance) return fallback
  const byNumber = (attendance as Record<number, AttendanceStatus>)[delegateId]
  if (byNumber) return byNumber
  const byString = attendance[delegateId.toString() as keyof typeof attendance]
  return (byString as AttendanceStatus) ?? fallback
}

const initializeAttendance = () => {
  Object.keys(attendanceMap).forEach((key) => delete attendanceMap[Number(key)])
  props.delegates.forEach((delegate) => {
    const status = resolveInitialStatus(delegate.id, delegate.status ?? 'present') ?? 'present'
    attendanceMap[delegate.id] = {
      value: attendanceMap[delegate.id]?.value ?? status,
    }
  })
}

watch(
  () => props.modelValue,
  (visible) => {
    if (visible) {
      initializeAttendance()
    }
  }
)

watch(
  () => props.initialAttendance,
  () => {
    if (props.modelValue) {
      initializeAttendance()
    }
  },
  { deep: true }
)

watch(
  () => props.delegates,
  () => {
    if (props.modelValue) {
      initializeAttendance()
    }
  },
  { deep: true }
)

const presentCount = computed(() =>
  props.delegates.reduce((count, delegate) => {
    const status = attendanceMap[delegate.id]?.value ?? 'present'
    return status === 'present' ? count + 1 : count
  }, 0)
)

const setAttendance = (id: number, status: AttendanceStatus) => {
  if (!attendanceMap[id]) {
    attendanceMap[id] = { value: status }
  } else {
    attendanceMap[id].value = status
  }
}

const handleConfirm = () => {
  const payload: Record<string, AttendanceStatus> = {}
  props.delegates.forEach((delegate) => {
    payload[delegate.id.toString()] = attendanceMap[delegate.id]?.value ?? 'present'
  })
  emit('confirm', payload)
  emit('update:modelValue', false)
}

const handleCancel = () => {
  emit('cancel')
  emit('update:modelValue', false)
}
</script>
