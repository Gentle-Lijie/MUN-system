<template>
    <dialog v-if="modelValue" class="modal" open>
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">选择发言者国家</h3>
            <input v-model="search" type="text" placeholder="输入国家名" class="input input-bordered w-full mb-4" />
            <select v-model="selectedCountry" class="select select-bordered w-full mb-4">
                <option disabled selected value="">请选择国家</option>
                <option v-for="country in filteredCountries" :key="country" :value="country">
                    {{ country }}
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
import { ref, computed, defineProps, defineEmits } from 'vue'

defineProps<{ modelValue: boolean }>()
const emit = defineEmits(['update:modelValue', 'confirm'])

const search = ref('')
const selectedCountry = ref('')
const countries = [
    '中国', '美国', '英国', '法国', '俄罗斯', '德国', '日本', '韩国', '加拿大', '澳大利亚'
    // ...可以补充完整国家列表
]

const filteredCountries = computed(() =>
    countries.filter(c => c.includes(search.value))
)

function handleConfirm() {
    emit('confirm', selectedCountry.value)
    emit('update:modelValue', false)
}
function handleClose() {
    emit('update:modelValue', false)
}
</script>
