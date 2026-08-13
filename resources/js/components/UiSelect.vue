<template>
  <div class="space-y-1">
    <label v-if="label" :for="selectId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
      {{ label }}
      <span v-if="required" class="text-danger-500">*</span>
    </label>
    
    <select
      :id="selectId"
      :value="modelValue"
      :required="required"
      :disabled="disabled"
      class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500 sm:text-sm transition-colors duration-150"
      :class="[
        sizeClasses,
        errorClasses,
        disabledClasses
      ]"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
      <option 
        v-for="option in normalizedOptions" 
        :key="option.value" 
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>
    
    <p v-if="error" class="text-sm text-danger-600 dark:text-danger-400">
      <template v-if="Array.isArray(error)">{{ error[0] }}</template>
      <template v-else>{{ error }}</template>
    </p>
    <p v-else-if="hint" class="text-sm text-gray-500 dark:text-gray-400">{{ hint }}</p>
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'UiSelect',
  props: {
    modelValue: {
      type: [String, Number],
      default: ''
    },
    options: {
      type: Array,
      required: true
    },
    label: {
      type: String,
      default: null
    },
    placeholder: {
      type: String,
      default: null
    },
    hint: {
      type: String,
      default: null
    },
    error: {
      type: [String, Array],
      default: null
    },
    size: {
      type: String,
      default: 'md',
      validator: value => ['sm', 'md', 'lg'].includes(value)
    },
    required: {
      type: Boolean,
      default: false
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:modelValue'],
  setup(props) {
    const selectId = computed(() => `select-${Math.random().toString(36).substr(2, 9)}`)
    
    const normalizedOptions = computed(() => {
      return props.options.map(option => {
        if (typeof option === 'string') {
          return { value: option, label: option }
        }
        return option
      })
    })
    
    const sizeClasses = computed(() => {
      const sizes = {
        sm: 'py-1.5 px-3 text-sm',
        md: 'py-2 px-3 text-sm',
        lg: 'py-2.5 px-4 text-base'
      }
      return sizes[props.size] || sizes.md
    })
    
    const errorClasses = computed(() => {
      return props.error
        ? 'border-danger-300 focus:border-danger-500 focus:ring-danger-500'
        : ''
    })
    
    const disabledClasses = computed(() => {
      return props.disabled
        ? 'opacity-50 cursor-not-allowed bg-gray-50 dark:bg-gray-800'
        : ''
    })
    
    return {
      selectId,
      normalizedOptions,
      sizeClasses,
      errorClasses,
      disabledClasses
    }
  }
}
</script>