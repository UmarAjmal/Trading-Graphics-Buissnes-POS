<template>
  <div
    class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft border border-gray-200 dark:border-gray-700 transition-colors duration-150"
    :class="[paddingClasses, hoverClasses]"
  >
    <div v-if="$slots.header || title" class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
      <slot name="header">
        <div class="flex items-center justify-between">
          <h3 v-if="title" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ title }}
          </h3>
          <slot name="actions" />
        </div>
      </slot>
    </div>
    
    <div class="card-body">
      <slot />
    </div>
    
    <div v-if="$slots.footer" class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
      <slot name="footer" />
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'UiCard',
  props: {
    title: {
      type: String,
      default: null
    },
    padding: {
      type: String,
      default: 'md',
      validator: value => ['none', 'sm', 'md', 'lg', 'xl'].includes(value)
    },
    hover: {
      type: Boolean,
      default: false
    }
  },
  setup(props) {
    const paddingClasses = computed(() => {
      const paddings = {
        none: '',
        sm: 'p-2 sm:p-3',
        md: 'p-3 sm:p-4',
        lg: 'p-4 sm:p-6',
        xl: 'p-6 sm:p-8'
      }
      return paddings[props.padding] || paddings.md
    })
    
    const hoverClasses = computed(() => {
      return props.hover ? 'hover:shadow-soft-lg cursor-pointer transition-shadow duration-200' : ''
    })
    
    return {
      paddingClasses,
      hoverClasses
    }
  }
}
</script>