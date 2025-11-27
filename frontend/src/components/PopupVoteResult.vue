<template>
  <dialog v-if="modelValue && result" class="modal" open>
    <div class="modal-box w-11/12 max-w-4xl bg-transparent p-0">
      <div class="flex flex-col gap-4 rounded-3xl bg-base-100 p-6 lg:p-10">
        <div class="flex items-center justify-between border-b border-base-200 pb-4">
          <div>
            <p class="text-base-content/60 text-sm">投票结果</p>
            <h3 class="text-2xl font-bold">{{ result.summary.passed ? '动议通过' : '动议未通过' }}</h3>
            <p class="text-sm text-base-content/70">
              多数方案：{{ result.majority.label }}（至少 {{ result.summary.requiredVotes }} 票）
            </p>
          </div>
          <div class="text-right">
            <p class="text-sm text-base-content/60">出席代表</p>
            <p class="text-3xl font-semibold">{{ result.rollCall.present }} / {{ result.rollCall.total }}</p>
          </div>
        </div>

        <div class="stats stats-vertical lg:stats-horizontal shadow">
          <div class="stat">
            <div class="stat-title">支持</div>
            <div class="stat-value text-success">{{ result.summary.yes }}</div>
          </div>
          <div class="stat">
            <div class="stat-title">反对</div>
            <div class="stat-value text-error">{{ result.summary.no }}</div>
          </div>
          <div class="stat">
            <div class="stat-title">弃权</div>
            <div class="stat-value">{{ result.summary.abstain }}</div>
          </div>
          <div class="stat">
            <div class="stat-title">有效票</div>
            <div class="stat-value">{{ result.summary.effectiveTotal }}</div>
          </div>
          <div class="stat">
            <div class="stat-title">通过率</div>
            <div class="stat-value" :class="result.summary.passed ? 'text-success' : 'text-error'">
              {{ (result.summary.ratio * 100).toFixed(1) }}%
            </div>
          </div>
        </div>

        <div class="border rounded-2xl border-base-200 bg-base-200/40 p-4 text-sm text-base-content/70">
          <p class="font-semibold mb-2">投票记录</p>
          <div class="max-h-60 overflow-y-auto divide-y divide-base-200">
            <div v-for="vote in result.votes" :key="vote.delegateId" class="flex items-center justify-between py-2">
              <span>{{ vote.country }}</span>
              <span
                class="badge"
                :class="{
                  'badge-success': vote.vote === 'yes',
                  'badge-error': vote.vote === 'no',
                  'badge-warning': vote.vote === 'abstain'
                }"
              >
                {{ vote.vote === 'yes' ? '支持' : vote.vote === 'no' ? '反对' : '弃权' }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-base-200 pt-4">
          <button type="button" class="btn btn-ghost" @click="$emit('retry')">重新投票</button>
          <button type="button" class="btn btn-primary" @click="$emit('confirm')">确认结果</button>
        </div>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click.prevent="$emit('retry')">close</button>
    </form>
  </dialog>
</template>

<script setup lang="ts">
import type { VoteResultPayload } from './PopupMotion.vue'

defineProps<{ modelValue: boolean; result: VoteResultPayload | null }>()

defineEmits<{
  (e: 'confirm'): void
  (e: 'retry'): void
}>()
</script>
