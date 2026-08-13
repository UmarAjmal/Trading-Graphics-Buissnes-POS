<template>
  <label 
    :for="for"
    :class="labelClasses"
  >
    <slot />
  </label>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  for: String,
  required: Boolean
})

const labelClasses = computed(() => [
  'block text-sm font-medium text-gray-700 dark:text-gray-300',
  props.required && 'after:content-["*"] after:ml-0.5 after:text-red-500'
])
</script>