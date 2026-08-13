<template>
  <Teleport to="body">
    <Transition
      enter-active-class="duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click="handleBackdropClick"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50"></div>
        
        <!-- Modal container -->
        <div class="flex min-h-full items-center justify-center p-4">
          <Transition
            enter-active-class="duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div
              v-if="show"
              :class="[
                'relative bg-white rounded-lg shadow-xl',
                'w-full',
                sizeClasses
              ]"
              @click.stop
            >
              <!-- Header -->
              <div v-if="$slots.header || title" class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <div>
                    <slot name="header">
                      <h3 class="text-lg font-medium text-gray-900">
                        {{ title }}
                      </h3>
                    </slot>
                  </div>
                  <button
                    v-if="closeable"
                    @click="close"
                    type="button"
                    class="text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600"
                  >
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Body -->
              <div class="px-6 py-4">
                <slot></slot>
              </div>

              <!-- Footer -->
              <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                <slot name="footer"></slot>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script>
export default {
  name: 'Modal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: ''
    },
    size: {
      type: String,
      default: 'md',
      validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl', '2xl', 'full'].includes(value)
    },
    closeable: {
      type: Boolean,
      default: true
    },
    closeOnBackdrop: {
      type: Boolean,
      default: true
    }
  },
  emits: ['close'],
  computed: {
    sizeClasses() {
      const sizes = {
        xs: 'max-w-xs',
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        '2xl': 'max-w-2xl',
        full: 'max-w-full mx-4'
      }
      return sizes[this.size]
    }
  },
  methods: {
    close() {
      if (this.closeable) {
        this.$emit('close')
      }
    },
    handleBackdropClick() {
      if (this.closeOnBackdrop) {
        this.close()
      }
    }
  },
  mounted() {
    // Handle escape key
    const handleEscape = (e) => {
      if (e.key === 'Escape' && this.show && this.closeable) {
        this.close()
      }
    }
    
    document.addEventListener('keydown', handleEscape)
    
    // Store cleanup function for unmount
    this._handleEscape = handleEscape
  },
  unmounted() {
    // Cleanup on unmount
    if (this._handleEscape) {
      document.removeEventListener('keydown', this._handleEscape)
    }
  }
}
</script>