<script setup>
import { computed } from 'vue'

/* -------------------------------------------------------------------------- */
/*                                 PROPS                                      */
/* -------------------------------------------------------------------------- */
const props = defineProps({
  /** Visual style of the wrapper */
  variant: {
    type: String,
    default: 'icon-btn',
    validator: (value) => ['icon-btn', 'icon-fab', 'icon-glass', 'icon-btn--gradient'].includes(value)
  },

  /** Icon colour */
  color: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'accent', 'secondary', 'gradient'].includes(value)
  },

  /** Width / height (px) – Tailwind uses `w-6 h-6` etc. */
  size: {
    type: [String, Number],
    default: 16,
  },

  /** Optional animation */
  animation: {
    type: String,
    default: undefined,
    validator: (value) => !value || ['spin', 'pulse', 'bounce', 'float'].includes(value)
  },

  /** Show a tiny spinner inside */
  loading: { type: Boolean, default: false },

  /** Show a badge (red dot) */
  badge: { type: Boolean, default: false },

  /** Tiny "micro" animation (used for sidebar child icons) */
  microAnimation: { type: Boolean, default: false },

  /** SVG viewBox – default works for Heroicons */
  viewBox: { type: String, default: '0 0 24 24' },

  /** Rotate the icon (degrees) */
  rotation: { type: [String, Number], default: 0 },
})

const emit = defineEmits(['click'])

/* -------------------------------------------------------------------------- */
/*                              COMPUTED STYLES                               */
/* -------------------------------------------------------------------------- */
const wrapperClasses = computed(() => {
  const base = 'inline-flex items-center justify-center relative transition-all duration-150 cursor-pointer'

  const variantMap = {
    'icon-btn': 'p-1 rounded hover:bg-gray-50 dark:hover:bg-gray-800/50',
    'icon-fab': 'p-2 rounded-full shadow-sm hover:shadow-md bg-white dark:bg-gray-800',
    'icon-glass': 'p-1 rounded backdrop-blur-sm bg-white/20 dark:bg-gray-900/20',
    'icon-btn--gradient': 'p-1.5 rounded bg-gradient-to-r from-primary-500 to-primary-600 text-white',
  }

  return `${base} ${variantMap[props.variant]}`
})

const iconClasses = computed(() => {
  const base = 'transition-colors duration-150'

  // size → Tailwind class (smaller, more minimal)
  const sizeMap = {
    12: 'w-3 h-3',
    14: 'w-3.5 h-3.5',
    16: 'w-4 h-4',
    18: 'w-4.5 h-4.5',
    20: 'w-5 h-5',
    24: 'w-5 h-5', // Smaller
    28: 'w-5 h-5', // Smaller
    32: 'w-6 h-6', // Much smaller
    36: 'w-6 h-6', // Smaller
    40: 'w-6 h-6', // Smaller
  }
  const sizeCls = typeof props.size === 'number' ? sizeMap[props.size] ?? `w-[${Math.floor(props.size * 0.8)}px] h-[${Math.floor(props.size * 0.8)}px]` : props.size

  // colour - more subtle
  const colourMap = {
    primary: 'text-gray-700 dark:text-gray-300',
    accent: 'text-gray-600 dark:text-gray-400',
    secondary: 'text-gray-500 dark:text-gray-500',
    gradient: 'text-primary-600 dark:text-primary-400',
  }

  // minimal animations
  const animMap = {
    spin: 'animate-spin',
    pulse: 'animate-pulse',
    bounce: '',
    float: '',
  }

  const parts = [
    base,
    sizeCls,
    colourMap[props.color],
    props.animation ? animMap[props.animation] : '',
    props.microAnimation ? 'hover:scale-105' : '',
  ]

  return parts.filter(Boolean).join(' ')
})

const rotationStyle = computed(() => ({
  '--icon-rotation': `${props.rotation}deg`,
}))

const handleClick = (event) => {
  if (!props.loading) {
    emit('click', event)
  }
}
</script>

<template>
  <!-- Wrapper -->
  <div
    :class="wrapperClasses"
    :style="rotationStyle"
    @click="handleClick"
  >
    <!-- Badge -->
    <div
      v-if="badge"
      class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full ring-2 ring-white dark:ring-gray-900"
    />

    <!-- Loading spinner (covers the slot) -->
    <svg
      v-if="loading"
      :class="iconClasses"
      viewBox="0 0 24 24"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
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

    <!-- Real icon (slot) -->
    <div
      v-else
      :class="iconClasses"
      style="transform: rotate(var(--icon-rotation, 0deg)); shape-rendering: crispEdges; image-rendering: pixelated; -webkit-font-smoothing: none;"
    >
      <slot />
    </div>
  </div>
</template>

<style scoped>
/* -------------------------------------------------------------------------- */
/*                               ANIMATIONS                                   */
/* -------------------------------------------------------------------------- */
@keyframes spin {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50%      { opacity: .5; }
}
@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50%      { transform: translateY(-6px); }
}
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50%      { transform: translateY(-4px); }
}
@keyframes micro {
  0%   { transform: scale(1); }
  50%  { transform: scale(1.15); }
  100% { transform: scale(1); }
}

.animate-spin   { animation: spin 1s linear infinite; }
.animate-pulse  { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
.animate-bounce { animation: bounce 1s infinite; }
.animate-float  { animation: float 3s ease-in-out infinite; }
.animate-micro  { animation: micro 0.4s ease-in-out; }

/* Minimal crisp icons */
svg, path {
  shape-rendering: crispEdges;
  image-rendering: pixelated;
  stroke-width: 1.5;
}

/* Remove all heavy styling */
.icon-container {
  image-rendering: pixelated;
  -webkit-font-smoothing: none;
}
</style>