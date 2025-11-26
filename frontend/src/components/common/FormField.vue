<template>
  <fieldset class="fieldset" :class="fieldsetClass">
    <legend class="fieldset-legend text-base font-semibold mb-3" :class="legendClass">
      {{ legend }}
    </legend>
    <div class="form-control w-full gap-2" :class="labelClass">
      <span v-if="label" class="label-text text-sm text-base-content/70" :class="labelTextClass">
        {{ label }}<br />
      </span>
      <slot />
    </div>
    <p v-if="description" class="fieldset-label text-sm text-base-content/70 mt-2">
      {{ description }}
    </p>
  </fieldset>
</template>

<script setup lang="ts">
import type { PropType } from 'vue'

type ClassValue = string | string[] | Record<string, boolean>

defineProps({
  legend: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    default: undefined,
  },
  description: {
    type: String,
    default: undefined,
  },
  fieldsetClass: {
    type: [String, Array, Object] as PropType<ClassValue>,
    default: undefined,
  },
  legendClass: {
    type: [String, Array, Object] as PropType<ClassValue>,
    default: undefined,
  },
  labelClass: {
    type: [String, Array, Object] as PropType<ClassValue>,
    default: undefined,
  },
  labelTextClass: {
    type: [String, Array, Object] as PropType<ClassValue>,
    default: undefined,
  },
})
</script>
