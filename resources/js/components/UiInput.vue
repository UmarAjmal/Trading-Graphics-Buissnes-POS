<template>
  <div class="space-y-1">
    <label v-if="label" :for="inputId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
      {{ label }}
      <span v-if="required" class="text-danger-500">*</span>
    </label>
    
    <div class="relative">
      <input
        :id="inputId"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :readonly="readonly"
        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:border-primary-500 focus:ring-primary-500 sm:text-sm transition-colors duration-150"
        :class="[
          sizeClasses,
          errorClasses,
          disabledClasses
        ]"
        @input="$emit('update:modelValue', $event.target.value)"
        @blur="$emit('blur', $event)"
        @focus="$emit('focus', $event)"
      />
      
      <div v-if="$slots.suffix || suffixIcon" class="absolute inset-y-0 right-0 flex items-center pr-3">
        <slot name="suffix">
          <component v-if="suffixIcon" :is="suffixIcon" class="h-5 w-5 text-gray-400" />
        </slot>
      </div>
    </div>
    
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
  name: 'UiInput',
  props: {
    modelValue: {
      type: [String, Number],
      default: ''
    },
    type: {
      type: String,
      default: 'text'
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
    },
    readonly: {
      type: Boolean,
      default: false
    },
    suffixIcon: {
      type: String,
      default: null
    }
  },
  emits: ['update:modelValue', 'blur', 'focus'],
  setup(props) {
    const inputId = computed(() => `input-${Math.random().toString(36).substr(2, 9)}`)
    
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
      inputId,
      sizeClasses,
      errorClasses,
      disabledClasses
    }
  }
}
</script>