<template>
  <div class="space-y-1">
    <label v-if="label" :for="textareaId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
      {{ label }}
      <span v-if="required" class="text-danger-500">*</span>
    </label>
    
    <textarea
      :id="textareaId"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :readonly="readonly"
      :rows="rows"
      class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:border-primary-500 focus:ring-primary-500 sm:text-sm transition-colors duration-150 resize-none"
      :class="[
        sizeClasses,
        errorClasses,
        disabledClasses
      ]"
      @input="$emit('update:modelValue', $event.target.value)"
      @blur="$emit('blur', $event)"
      @focus="$emit('focus', $event)"
    />
    
    <p v-if="error" class="text-sm text-danger-600 dark:text-danger-400">{{ error }}</p>
    <p v-else-if="hint" class="text-sm text-gray-500 dark:text-gray-400">{{ hint }}</p>
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'UiTextarea',
  props: {
    modelValue: {
      type: [String, Number],
      default: ''
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
      type: String,
      default: null
    },
    rows: {
      type: Number,
      default: 4
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
    },
    readonly: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:modelValue', 'blur', 'focus'],
  setup(props) {
    const textareaId = computed(() => `textarea-${Math.random().toString(36).substr(2, 9)}`)
    
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
      textareaId,
      sizeClasses,
      errorClasses,
      disabledClasses
    }
  }
}
</script>