<template>
  <component
    :is="tag"
    :href="href"
    :disabled="disabled"
    :type="type"
    class="inline-flex items-center justify-center font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-150"
    :class="[sizeClasses, variantClasses, disabledClasses]"
  >
    <slot name="icon-left" />
    <slot />
    <slot name="icon-right" />
  </component>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'UiButton',
  props: {
    variant: {
      type: String,
      default: 'primary',
      validator: value => ['primary', 'secondary', 'danger', 'ghost', 'outline'].includes(value)
    },
    size: {
      type: String,
      default: 'md',
      validator: value => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
    },
    disabled: {
      type: Boolean,
      default: false
    },
    href: {
      type: String,
      default: null
    },
    type: {
      type: String,
      default: 'button'
    }
  },
  setup(props) {
    const tag = computed(() => props.href ? 'a' : 'button')
    
    const sizeClasses = computed(() => {
      const sizes = {
        xs: 'px-2.5 py-1.5 text-xs',
        sm: 'px-3 py-2 text-sm',
        md: 'px-4 py-2 text-sm',
        lg: 'px-4 py-2 text-base',
        xl: 'px-6 py-3 text-base'
      }
      return sizes[props.size] || sizes.md
    })
    
    const variantClasses = computed(() => {
      const variants = {
        primary: 'bg-primary-600 hover:bg-primary-700 focus:ring-primary-500 text-white',
        secondary: 'bg-gray-200 hover:bg-gray-300 focus:ring-gray-500 text-gray-900 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-white dark:focus:ring-gray-400',
        danger: 'bg-danger-600 hover:bg-danger-700 focus:ring-danger-500 text-white',
        ghost: 'bg-transparent hover:bg-gray-100 focus:ring-gray-500 text-gray-700 dark:hover:bg-gray-800 dark:text-gray-300',
        outline: 'bg-transparent border border-gray-300 hover:bg-gray-50 focus:ring-gray-500 text-gray-700 dark:border-gray-600 dark:hover:bg-gray-800 dark:text-gray-300'
      }
      return variants[props.variant] || variants.primary
    })
    
    const disabledClasses = computed(() => {
      return props.disabled ? 'opacity-50 cursor-not-allowed' : ''
    })
    
    return {
      tag,
      sizeClasses,
      variantClasses,
      disabledClasses
    }
  }
}
</script>