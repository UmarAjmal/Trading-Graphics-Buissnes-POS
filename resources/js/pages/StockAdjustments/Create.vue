<template>
  <AppLayout title="Stock Adjustment">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <h2 class="text-xl font-semibold mb-6">Create Stock Adjustment</h2>
            
            <form @submit.prevent="submit" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Product *</label>
                  <select v-model="form.product_id" required
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Select Product</option>
                    <option v-for="product in products" :key="product.id" :value="product.id">
                      {{ product.name }} ({{ product.sku }}) - Stock: {{ product.stock_quantity }}
                    </option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Adjustment Quantity *</label>
                  <input v-model.number="form.delta" type="number" step="0.01" required
                         placeholder="Use negative for decrease (e.g., -10)"
                         class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                  <p class="mt-1 text-xs text-gray-500">Positive to add, negative to reduce stock</p>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Reason *</label>
                  <select v-model="form.reason" required
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Select Reason</option>
                    <option value="damage">Damage</option>
                    <option value="shrinkage">Shrinkage</option>
                    <option value="correction">Stock Correction</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                  <textarea v-model="form.notes" rows="3" 
                           placeholder="Add any additional details..."
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
              </div>
              
              <div class="flex justify-end space-x-3">
                <button type="button" @click="$inertia.visit('/inventory')" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                  Cancel
                </button>
                <button type="submit" :disabled="processing"
                        class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">
                  {{ processing ? 'Processing...' : 'Create Adjustment' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  products: {
    type: Array,
    default: () => []
  }
})

const processing = ref(false)

const form = reactive({
  product_id: '',
  delta: '',
  reason: '',
  notes: ''
})

const submit = () => {
  processing.value = true
  
  router.post('/stock-adjustments', form, {
    onFinish: () => processing.value = false,
    onSuccess: () => {
      // Reset form
      Object.assign(form, {
        product_id: '',
        delta: '',
        reason: '',
        notes: ''
      })
    },
    onError: (errors) => {
      console.error('Stock adjustment errors:', errors)
    }
  })
}
</script>