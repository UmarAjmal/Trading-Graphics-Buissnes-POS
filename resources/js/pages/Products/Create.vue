<template>
  <AppLayout>
    <div class="p-6">
      <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Add New Product</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Create a new product for your inventory</p>
          </div>
          <Link 
            :href="route('products.index')" 
            class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
          >
            ← Back to Products
          </Link>
        </div>

        <!-- Product Form -->
        <form @submit.prevent="submitForm" class="space-y-6">
          <!-- Basic Information Card -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Basic Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Product Name -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Product Name *
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="Enter product name"
                />
                <div v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name }}</div>
              </div>

              <!-- SKU -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  SKU *
                </label>
                <input
                  v-model="form.sku"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="Enter SKU"
                />
                <div v-if="errors.sku" class="text-red-500 text-sm mt-1">{{ errors.sku }}</div>
              </div>

              <!-- Category -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Category
                </label>
                <select
                  v-model="form.category_id"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                >
                  <option value="">Select Category</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
                <div v-if="errors.category_id" class="text-red-500 text-sm mt-1">{{ errors.category_id }}</div>
              </div>

              <!-- Unit -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Unit
                </label>
                <select
                  v-model="form.unit_id"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                >
                  <option value="">Select Unit</option>
                  <option v-for="unit in units" :key="unit.id" :value="unit.id">
                    {{ unit.name }}
                  </option>
                </select>
                <div v-if="errors.unit_id" class="text-red-500 text-sm mt-1">{{ errors.unit_id }}</div>
              </div>
            </div>

            <!-- Description -->
            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Description
              </label>
              <textarea
                v-model="form.description"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Enter product description"
              ></textarea>
              <div v-if="errors.description" class="text-red-500 text-sm mt-1">{{ errors.description }}</div>
            </div>
          </div>

          <!-- Pricing & Inventory Card -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Pricing & Inventory</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <!-- Price -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Price *
                </label>
                <input
                  v-model="form.price"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="0.00"
                />
                <div v-if="errors.price" class="text-red-500 text-sm mt-1">{{ errors.price }}</div>
              </div>

              <!-- Stock -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Initial Stock
                </label>
                <input
                  v-model="form.stock"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="0"
                />
                <div v-if="errors.stock" class="text-red-500 text-sm mt-1">{{ errors.stock }}</div>
              </div>

              <!-- Low Stock Alert -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Low Stock Alert
                </label>
                <input
                  v-model="form.low_stock_alert"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="5"
                />
                <div v-if="errors.low_stock_alert" class="text-red-500 text-sm mt-1">{{ errors.low_stock_alert }}</div>
              </div>
            </div>
          </div>

          <!-- Product Status Card -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Product Status</h3>
            
            <div class="space-y-4">
              <!-- Active Status -->
              <div class="flex items-center">
                <input
                  v-model="form.is_active"
                  type="checkbox"
                  id="is_active"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-white">
                  Product is active
                </label>
              </div>

              <!-- Track Stock -->
              <div class="flex items-center">
                <input
                  v-model="form.track_stock"
                  type="checkbox"
                  id="track_stock"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label for="track_stock" class="ml-2 block text-sm text-gray-900 dark:text-white">
                  Track stock for this product
                </label>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end gap-3">
            <Link
              :href="route('products.index')"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              Cancel
            </Link>
            <button
              type="submit"
              :disabled="processing"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ processing ? 'Creating...' : 'Create Product' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '../../layouts/AppLayout.vue'

export default {
  name: 'ProductCreate',
  components: {
    AppLayout,
    Link
  },
  props: {
    categories: {
      type: Array,
      default: () => []
    },
    units: {
      type: Array,
      default: () => []
    },
    errors: {
      type: Object,
      default: () => ({})
    }
  },
  setup() {
    const processing = ref(false)
    
    const form = reactive({
      name: '',
      sku: '',
      description: '',
      category_id: '',
      unit_id: '',
      price: '',
      stock: 0,
      low_stock_alert: 5,
      is_active: true,
      track_stock: true
    })

    const submitForm = () => {
      processing.value = true
      
      router.post(route('products.store'), form, {
        onSuccess: () => {
          // Handle success
        },
        onError: () => {
          // Handle errors
        },
        onFinish: () => {
          processing.value = false
        }
      })
    }

    return {
      form,
      processing,
      submitForm
    }
  }
}
</script>