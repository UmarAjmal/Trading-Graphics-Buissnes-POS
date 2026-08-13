<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeModal"></div>
    
    <!-- Modal -->
    <div class="flex min-h-full items-center justify-center p-4">
      <div class="relative w-full max-w-md transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 shadow-xl transition-all">
        <!-- Header -->
        <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              Print Barcode Labels
            </h3>
            <button
              type="button"
              @click="closeModal"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Body -->
        <div class="px-6 py-4">
          <div class="space-y-4">
            <!-- Product Info -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
              <h4 class="font-medium text-gray-900 dark:text-white">{{ product.name }}</h4>
              <p class="text-sm text-gray-600 dark:text-gray-300">SKU: {{ product.sku }}</p>
            </div>

            <!-- Quantity Input -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Number of Labels
              </label>
              <input
                v-model="form.quantity"
                type="number"
                min="1"
                max="500"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Enter quantity"
                @input="updateLayout"
              />
              <p v-if="errors.quantity" class="mt-1 text-sm text-red-600">{{ errors.quantity }}</p>
            </div>

            <!-- Layout Options -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Page Layout
              </label>
              <div class="grid grid-cols-1 gap-2">
                <label class="flex items-center p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                       :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-900/20': form.layout === 'auto' }">
                  <input
                    v-model="form.layout"
                    type="radio"
                    value="auto"
                    class="text-blue-600 focus:ring-blue-500"
                  />
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">Auto Adjust (Recommended)</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ getAutoLayoutDescription() }}</div>
                  </div>
                </label>

                <label class="flex items-center p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                       :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-900/20': form.layout === '3x8' }">
                  <input
                    v-model="form.layout"
                    type="radio"
                    value="3x8"
                    class="text-blue-600 focus:ring-blue-500"
                  />
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">3 × 8 Layout</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">24 labels per page</div>
                  </div>
                </label>

                <label class="flex items-center p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                       :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-900/20': form.layout === '2x12' }">
                  <input
                    v-model="form.layout"
                    type="radio"
                    value="2x12"
                    class="text-blue-600 focus:ring-blue-500"
                  />
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">2 × 12 Layout</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">24 labels per page (larger size)</div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Preview Info -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
              <div class="flex items-start">
                <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="ml-2">
                  <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Print Preview</p>
                  <p class="text-sm text-blue-700 dark:text-blue-300">{{ getPrintPreview() }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">
          <div class="flex justify-end space-x-3">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="printBarcode"
              :disabled="!isFormValid"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H9.5a2 2 0 01-2-2v-1a2 2 0 00-2-2H3a2 2 0 00-2 2v9a2 2 0 002 2h2" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h4l-2 2m0-4l2 2" />
              </svg>
              Print Labels
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

// Props
const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  product: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['close', 'print'])

// Form data
const form = ref({
  quantity: 1,
  layout: 'auto'
})

// Errors
const errors = ref({})

// Computed
const isFormValid = computed(() => {
  return form.value.quantity >= 1 && form.value.quantity <= 500 && !errors.value.quantity
})

// Methods
const closeModal = () => {
  emit('close')
  resetForm()
}

const resetForm = () => {
  form.value = {
    quantity: 1,
    layout: 'auto'
  }
  errors.value = {}
}

const validateQuantity = () => {
  errors.value.quantity = null
  
  if (!form.value.quantity || form.value.quantity < 1) {
    errors.value.quantity = 'Quantity must be at least 1'
  } else if (form.value.quantity > 500) {
    errors.value.quantity = 'Quantity cannot exceed 500'
  }
}

const updateLayout = () => {
  validateQuantity()
  
  // Auto-suggest layout based on quantity
  if (form.value.layout === 'auto') {
    // Keep auto selected
  }
}

const getAutoLayoutDescription = () => {
  if (!form.value.quantity) return 'Best layout will be chosen automatically'
  
  if (form.value.quantity <= 12) {
    return `${form.value.quantity <= 8 ? '2×4' : '2×6'} layout for better readability`
  } else if (form.value.quantity <= 24) {
    return '3×8 layout (standard)'
  } else {
    return 'Multiple pages with 3×8 layout'
  }
}

const getPrintPreview = () => {
  if (!form.value.quantity) return ''
  
  let layout = form.value.layout
  
  // Auto-determine layout
  if (layout === 'auto') {
    if (form.value.quantity <= 12) {
      layout = '2x6'
    } else {
      layout = '3x8'
    }
  }
  
  const labelsPerPage = layout === '2x12' ? 24 : (layout === '2x6' ? 12 : 24)
  const pages = Math.ceil(form.value.quantity / labelsPerPage)
  
  return `${form.value.quantity} labels on ${pages} page${pages > 1 ? 's' : ''} (${layout} layout)`
}

const printBarcode = () => {
  if (!isFormValid.value) return
  
  let layout = form.value.layout
  
  // Auto-determine layout
  if (layout === 'auto') {
    if (form.value.quantity <= 12) {
      layout = '2x6'
    } else {
      layout = '3x8'
    }
  }
  
  emit('print', {
    quantity: form.value.quantity,
    layout: layout
  })
  
  closeModal()
}

// Watch for prop changes
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    resetForm()
  }
})
</script>