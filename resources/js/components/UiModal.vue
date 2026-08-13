<template>
  <teleport to="body">
    <transition
      enter-active-class="duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isVisible"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click="handleBackdropClick"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        
        <!-- Modal container -->
        <div class="flex min-h-full items-center justify-center p-4">
          <transition
            enter-active-class="duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div
              v-if="isVisible"
              class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-xl transition-all w-full"
              :class="sizeClasses"
              @click.stop
            >
              <!-- Header -->
              <div v-if="$slots.header || title || closable" class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <slot name="header">
                  <h3 v-if="title" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ title }}
                  </h3>
                </slot>
                
                <button
                  v-if="closable"
                  @click="closeModal"
                  class="rounded-md text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                  <span class="sr-only">Close</span>
                  <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              
              <!-- Body -->
              <div class="p-6">
                <slot />
              </div>
              
              <!-- Footer -->
              <div v-if="$slots.footer" class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-700">
                <slot name="footer" />
              </div>
            </div>
          </transition>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script>
import { computed, onMounted, onUnmounted } from 'vue'

export default {
  name: 'UiModal',
  props: {
    modelValue: {
      type: Boolean,
      default: false
    },
    visible: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: null
    },
    size: {
      type: String,
      default: 'md',
      validator: value => ['xs', 'sm', 'md', 'lg', 'xl', '2xl', 'full'].includes(value)
    },
    closable: {
      type: Boolean,
      default: true
    },
    closeOnBackdrop: {
      type: Boolean,
      default: true
    }
  },
  emits: ['close', 'update:modelValue'],
  setup(props, { emit }) {
    const isVisible = computed(() => props.modelValue || props.visible)
    
    const sizeClasses = computed(() => {
      const sizes = {
        xs: 'max-w-xs',
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        '2xl': 'max-w-2xl',
        full: 'max-w-full mx-4'
      }
      return sizes[props.size] || sizes.md
    })
    
    const handleBackdropClick = () => {
      if (props.closeOnBackdrop && props.closable) {
        closeModal()
      }
    }
    
    const closeModal = () => {
      emit('close')
      emit('update:modelValue', false)
    }
    
    const handleEscape = (event) => {
      if (event.key === 'Escape' && isVisible.value && props.closable) {
        closeModal()
      }
    }
    
    onMounted(() => {
      document.addEventListener('keydown', handleEscape)
    })
    
    onUnmounted(() => {
      document.removeEventListener('keydown', handleEscape)
    })
    
    return {
      isVisible,
      sizeClasses,
      handleBackdropClick,
      closeModal
    }
  }
}
</script>