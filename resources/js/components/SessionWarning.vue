<template>
  <div v-show="show" class="fixed top-4 right-4 z-50">
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 shadow-lg rounded-md max-w-md">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3 flex-1">
          <h3 class="text-sm font-medium text-yellow-800">
            Session Expiring Soon
          </h3>
          <div class="mt-2 text-sm text-yellow-700">
            <p>{{ message }}</p>
            <div v-if="timeRemaining > 0" class="mt-1">
              <strong>Time remaining: {{ formatTimeRemaining }}</strong>
            </div>
          </div>
          <div class="mt-4 flex gap-2">
            <button
              @click="extendSession"
              :disabled="extending"
              class="bg-yellow-400 hover:bg-yellow-500 text-yellow-800 text-xs font-medium px-3 py-1.5 rounded-md transition-colors disabled:opacity-50"
            >
              <span v-if="extending">Extending...</span>
              <span v-else>Extend Session</span>
            </button>
            <button
              @click="dismiss"
              class="bg-white hover:bg-gray-50 text-yellow-800 text-xs font-medium px-3 py-1.5 rounded-md border border-yellow-200 transition-colors"
            >
              Dismiss
            </button>
          </div>
        </div>
        <div class="ml-auto flex-shrink-0">
          <button
            @click="dismiss"
            class="text-yellow-400 hover:text-yellow-600 transition-colors"
          >
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useSessionManagement } from '@/composables/useSessionManagement'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  timeRemaining: {
    type: Number,
    default: 0
  },
  message: {
    type: String,
    default: 'Your session will expire soon. Please extend your session to continue.'
  }
})

const emit = defineEmits(['dismiss', 'extend'])

const extending = ref(false)
const { extendSession: extendSessionComposable } = useSessionManagement()

const formatTimeRemaining = computed(() => {
  const minutes = props.timeRemaining
  if (minutes >= 60) {
    const hours = Math.floor(minutes / 60)
    const remainingMinutes = minutes % 60
    return `${hours}h ${remainingMinutes}m`
  }
  return `${minutes}m`
})

const extendSession = async () => {
  try {
    extending.value = true
    await extendSessionComposable()
    emit('extend')
  } catch (error) {
    console.error('Failed to extend session:', error)
    // You could show an error message here
  } finally {
    extending.value = false
  }
}

const dismiss = () => {
  emit('dismiss')
}
</script>

<style scoped>
/* Animation for smooth appearance */
.v-enter-active, .v-leave-active {
  transition: all 0.3s ease;
}

.v-enter-from, .v-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>