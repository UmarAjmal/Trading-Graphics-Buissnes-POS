<template>
  <div
    v-if="visible"
    class="fixed inset-0 flex items-end justify-center px-4 py-6 pointer-events-none sm:p-6 sm:items-start sm:justify-end z-50"
  >
    <transition
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div class="max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
        <div class="p-4">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <component :is="iconComponent" class="h-6 w-6" :class="iconColorClass" />
            </div>
            <div class="ml-3 w-0 flex-1 pt-0.5">
              <p v-if="title" class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ title }}
              </p>
              <p class="text-sm text-gray-500 dark:text-gray-400" :class="{ 'mt-1': title }">
                {{ message }}
              </p>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
              <button
                @click="$emit('close')"
                class="bg-white dark:bg-gray-800 rounded-md inline-flex text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                <span class="sr-only">Close</span>
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>
          </div>
        </div>
        
        <!-- Progress bar for auto-dismiss -->
        <div v-if="duration > 0" class="h-1 bg-gray-200 dark:bg-gray-700">
          <div 
            class="h-1 transition-all ease-linear" 
            :class="progressColorClass"
            :style="{ width: progressWidth + '%' }"
          ></div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import { computed, ref, onMounted, onUnmounted } from 'vue'

// Icon components
const CheckCircleIcon = {
  template: `
    <svg fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
    </svg>
  `
}

const ExclamationCircleIcon = {
  template: `
    <svg fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
    </svg>
  `
}

const ExclamationTriangleIcon = {
  template: `
    <svg fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
    </svg>
  `
}

const InformationCircleIcon = {
  template: `
    <svg fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
    </svg>
  `
}

export default {
  name: 'UiToast',
  props: {
    visible: {
      type: Boolean,
      default: true
    },
    type: {
      type: String,
      default: 'info',
      validator: value => ['success', 'error', 'warning', 'info'].includes(value)
    },
    title: {
      type: String,
      default: ''
    },
    message: {
      type: String,
      required: true
    },
    duration: {
      type: Number,
      default: 5000
    }
  },
  emits: ['close'],
  setup(props, { emit }) {
    const progressWidth = ref(100)
    let progressInterval = null
    
    const iconComponent = computed(() => {
      const icons = {
        success: CheckCircleIcon,
        error: ExclamationCircleIcon,
        warning: ExclamationTriangleIcon,
        info: InformationCircleIcon
      }
      return icons[props.type] || InformationCircleIcon
    })
    
    const iconColorClass = computed(() => {
      const colors = {
        success: 'text-success-400',
        error: 'text-danger-400',
        warning: 'text-warning-400',
        info: 'text-blue-400'
      }
      return colors[props.type] || colors.info
    })
    
    const progressColorClass = computed(() => {
      const colors = {
        success: 'bg-success-400',
        error: 'bg-danger-400',
        warning: 'bg-warning-400',
        info: 'bg-blue-400'
      }
      return colors[props.type] || colors.info
    })
    
    const startProgress = () => {
      if (props.duration > 0) {
        const interval = 50 // Update every 50ms
        const decrement = (interval / props.duration) * 100
        
        progressInterval = setInterval(() => {
          progressWidth.value -= decrement
          if (progressWidth.value <= 0) {
            clearInterval(progressInterval)
            emit('close')
          }
        }, interval)
      }
    }
    
    onMounted(() => {
      startProgress()
    })
    
    onUnmounted(() => {
      if (progressInterval) {
        clearInterval(progressInterval)
      }
    })
    
    return {
      iconComponent,
      iconColorClass,
      progressColorClass,
      progressWidth
    }
  }
}
</script>