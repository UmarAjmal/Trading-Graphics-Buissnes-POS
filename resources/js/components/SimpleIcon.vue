<template>
  <div 
    :class="[
      'inline-flex items-center justify-center transition-all duration-200',
      variantClasses,
      sizeClasses,
      colorClasses,
      animationClasses
    ]"
    :style="rotationStyle"
    @click="handleClick"
  >
    <!-- Badge -->
    <div
      v-if="badge"
      class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full ring-2 ring-white dark:ring-gray-900 z-10"
    />

    <!-- Loading spinner -->
    <svg
      v-if="loading"
      :class="iconSizeClasses"
      viewBox="0 0 24 24"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      class="animate-spin"
    >
      <circle
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="2"
        class="opacity-25"
      />
      <path
        d="M4 12a8 8 0 018-8"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        class="opacity-75"
      />
    </svg>

    <!-- Icon slot -->
    <div
      v-else-if="$slots.default"
      :class="iconSizeClasses"
      style="shape-rendering: crispEdges; image-rendering: pixelated; -webkit-font-smoothing: none;"
    >
      <slot />
    </div>

    <!-- Direct icon component -->
    <component
      v-else-if="icon"
      :is="icon"
      :class="iconSizeClasses"
      style="shape-rendering: crispEdges; image-rendering: pixelated; -webkit-font-smoothing: none; stroke-width: 1.5;"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'simple',
    validator: (value) => ['simple', 'btn', 'fab', 'glass', 'gradient'].includes(value)
  },
  color: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'accent', 'secondary', 'success', 'danger', 'warning'].includes(value)
  },
  size: {
    type: [String, Number],
    default: 16,
  },
  animation: {
    type: String,
    validator: (value) => !value || ['spin', 'pulse', 'bounce', 'float', 'micro'].includes(value)
  },
  loading: { type: Boolean, default: false },
  badge: { type: Boolean, default: false },
  rotation: { type: [String, Number], default: 0 },
  icon: { type: [Object, Function], default: null }
})

const emit = defineEmits(['click'])

const variantClasses = computed(() => {
  const variants = {
    simple: 'cursor-pointer',
    btn: 'p-1 rounded hover:bg-gray-50 dark:hover:bg-gray-800/30 cursor-pointer',
    fab: 'p-2 rounded-full shadow-sm bg-white dark:bg-gray-800 cursor-pointer',
    glass: 'p-1 rounded backdrop-blur-sm bg-white/10 dark:bg-gray-900/10 cursor-pointer',
    gradient: 'p-1.5 rounded bg-gradient-to-r from-primary-500 to-primary-600 text-white cursor-pointer'
  }
  return variants[props.variant] || variants.simple
})

const sizeClasses = computed(() => {
  const containerSizes = {
    12: 'w-4 h-4',
    14: 'w-4 h-4',
    16: 'w-4.5 h-4.5',
    18: 'w-5 h-5',
    20: 'w-5 h-5', 
    24: 'w-6 h-6',
    28: 'w-6 h-6',
    32: 'w-6 h-6', // Smaller
    36: 'w-7 h-7',
    40: 'w-7 h-7'  // Smaller
  }
  return containerSizes[props.size] || 'w-4 h-4'
})

const iconSizeClasses = computed(() => {
  const iconSizes = {
    12: 'w-3 h-3',
    14: 'w-3.5 h-3.5',
    16: 'w-4 h-4',
    18: 'w-4 h-4',
    20: 'w-4 h-4', // Smaller
    24: 'w-4.5 h-4.5', // Smaller
    28: 'w-5 h-5', // Smaller
    32: 'w-5 h-5', // Much smaller
    36: 'w-5.5 h-5.5', // Smaller
    40: 'w-6 h-6'  // Smaller
  }
  return iconSizes[props.size] || 'w-4 h-4'
})

const colorClasses = computed(() => {
  if (props.variant === 'gradient') return 'text-white'
  
  const colors = {
    primary: 'text-gray-700 dark:text-gray-300',
    accent: 'text-gray-600 dark:text-gray-400',
    secondary: 'text-gray-500 dark:text-gray-500',
    success: 'text-gray-600 dark:text-gray-400',
    danger: 'text-gray-600 dark:text-gray-400',
    warning: 'text-gray-600 dark:text-gray-400'
  }
  return colors[props.color] || colors.primary
})

const animationClasses = computed(() => {
  if (!props.animation) return ''
  
  const animations = {
    spin: 'animate-spin',
    pulse: 'animate-pulse', 
    bounce: 'animate-bounce',
    float: 'animate-float',
    micro: 'animate-micro'
  }
  return animations[props.animation] || ''
})

const rotationStyle = computed(() => ({
  '--icon-rotation': `${props.rotation}deg`,
  transform: props.rotation ? `rotate(${props.rotation}deg)` : undefined
}))

const handleClick = (event) => {
  if (!props.loading) {
    emit('click', event)
  }
}
</script>