<template>
    <dialog v-if="modelValue" class="modal" open>
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">选择关联文件</h3>
            <p class="text-base-content/70 mb-4">这里将显示可选择的文件列表。</p>
            <div class="modal-action">
                <button class="btn btn-primary" @click="handleSelect">选择文件</button>
                <button class="btn btn-ghost" @click="handleClose">取消</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button @click.prevent="handleClose">关闭</button>
        </form>
    </dialog>
</template>

<script setup lang="ts">
defineProps<{ modelValue: boolean }>()
const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'select', file: string): void
}>()

function handleSelect() {
    // 暂时选择一个示例文件
    emit('select', 'example-file.pdf')
    emit('update:modelValue', false)
}

function handleClose() {
    emit('update:modelValue', false)
}
</script>