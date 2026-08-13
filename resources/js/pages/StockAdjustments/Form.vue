<template>
  <AppLayout>
    <PageHeader
      title="Stock Adjustment"
      subtitle="Adjust inventory levels for stock corrections"
    >
      <template #actions>
        <Link
          :href="route('inventory.index')"
          class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2"
        >
          <ArrowLeft class="w-4 h-4" />
          Back to Inventory
        </Link>
      </template>
    </PageHeader>

    <form @submit.prevent="submitForm">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Adjustment Details -->
          <UiCard>
            <CardHeader>
              <h3 class="text-lg font-semibold">Adjustment Details</h3>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Adjustment Type *</label>
                  <select
                    v-model="form.type"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                    required
                  >
                    <option value="">Select Type</option>
                    <option value="increase">Stock Increase</option>
                    <option value="decrease">Stock Decrease</option>
                    <option value="correction">Stock Correction</option>
                  </select>
                  <span v-if="errors.type" class="text-red-500 text-sm">{{ errors.type }}</span>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Reason *</label>
                  <select
                    v-model="form.reason"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                    required
                  >
                    <option value="">Select Reason</option>
                    <option value="damaged">Damaged Goods</option>
                    <option value="expired">Expired Items</option>
                    <option value="theft">Theft/Loss</option>
                    <option value="found">Found Stock</option>
                    <option value="recount">Stock Recount</option>
                    <option value="supplier_error">Supplier Error</option>
                    <option value="other">Other</option>
                  </select>
                  <span v-if="errors.reason" class="text-red-500 text-sm">{{ errors.reason }}</span>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <textarea
                  v-model="form.notes"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                  placeholder="Additional details about this adjustment..."
                ></textarea>
                <span v-if="errors.notes" class="text-red-500 text-sm">{{ errors.notes }}</span>
              </div>
            </CardContent>
          </UiCard>

          <!-- Products to Adjust -->
          <UiCard>
            <CardHeader>
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold">Products to Adjust</h3>
                <button
                  type="button"
                  @click="showProductModal = true"
                  class="bg-primary-600 text-white px-3 py-1 rounded text-sm hover:bg-primary-700 flex items-center gap-1"
                >
                  <Plus class="w-4 h-4" />
                  Add Product
                </button>
              </div>
            </CardHeader>
            <CardContent>
              <div v-if="form.items.length === 0" class="text-center py-8 text-gray-500">
                No products selected. Click "Add Product" to start your adjustment.
              </div>
              <div v-else class="space-y-3">
                <div
                  v-for="(item, index) in form.items"
                  :key="index"
                  class="border rounded-lg p-4 bg-gray-50"
                >
                  <div class="flex justify-between items-start mb-3">
                    <div>
                      <h4 class="font-medium">{{ item.product_name }}</h4>
                      <p class="text-sm text-gray-500">SKU: {{ item.product_sku }}</p>
                      <p class="text-sm text-gray-500">Current Stock: {{ item.current_stock }} {{ item.product_unit }}</p>
                    </div>
                    <button
                      type="button"
                      @click="removeItem(index)"
                      class="text-red-500 hover:text-red-700"
                    >
                      <X class="w-4 h-4" />
                    </button>
                  </div>
                  
                  <div class="grid grid-cols-3 gap-3">
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">Adjustment Quantity *</label>
                      <div class="relative">
                        <input
                          v-model.number="item.quantity"
                          type="number"
                          step="0.01"
                          class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-primary pl-6"
                          @input="calculateNewStock(index)"
                          required
                        />
                        <span class="absolute left-2 top-1.5 text-xs text-gray-500">±</span>
                      </div>
                      <p class="text-xs text-gray-500 mt-1">Use + for increase, - for decrease</p>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">New Stock Level</label>
                      <input
                        :value="item.new_stock !== null ? item.new_stock.toFixed(2) : item.current_stock"
                        type="text"
                        readonly
                        :class="[
                          'w-full px-2 py-1 text-sm border rounded bg-gray-100',
                          item.new_stock < 0 ? 'border-red-300 text-red-600' : 'border-gray-200'
                        ]"
                      />
                    </div>
                    <div v-if="item.has_batches">
                      <label class="block text-xs font-medium text-gray-700 mb-1">Select Batch</label>
                      <select
                        v-model="item.batch_id"
                        class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-primary"
                      >
                        <option value="">Any Batch</option>
                        <option v-for="batch in item.batches" :key="batch.id" :value="batch.id">
                          {{ batch.batch_code }} ({{ batch.quantity }})
                        </option>
                      </select>
                    </div>
                  </div>
                  
                  <!-- Batch Information for decreases -->
                  <div v-if="form.type === 'decrease' && item.quantity < 0 && !item.batch_id" class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded">
                    <p class="text-xs text-yellow-800">
                      <AlertTriangle class="w-3 h-3 inline mr-1" />
                      This will consume stock using FIFO (oldest batches first)
                    </p>
                  </div>
                </div>
              </div>
            </CardContent>
          </UiCard>
        </div>

        <!-- Summary Sidebar -->
        <div class="space-y-6">
          <UiCard>
            <CardHeader>
              <h3 class="text-lg font-semibold">Adjustment Summary</h3>
            </CardHeader>
            <CardContent class="space-y-3">
              <div class="flex justify-between">
                <span>Products:</span>
                <span>{{ form.items.length }}</span>
              </div>
              <div class="flex justify-between">
                <span>Total Adjustments:</span>
                <span>{{ totalAdjustments }}</span>
              </div>
              <div class="flex justify-between">
                <span>Increases:</span>
                <span class="text-green-600">+{{ positiveAdjustments }}</span>
              </div>
              <div class="flex justify-between">
                <span>Decreases:</span>
                <span class="text-red-600">{{ negativeAdjustments }}</span>
              </div>
              <hr>
              <div class="space-y-2">
                <div v-for="item in form.items" :key="item.product_id" class="text-sm">
                  <div class="flex justify-between">
                    <span class="truncate">{{ item.product_name }}</span>
                    <span :class="item.quantity >= 0 ? 'text-green-600' : 'text-red-600'">
                      {{ item.quantity >= 0 ? '+' : '' }}{{ item.quantity }}
                    </span>
                  </div>
                </div>
              </div>
            </CardContent>
          </UiCard>

          <UiCard>
            <CardContent>
              <div class="space-y-3">
                <button
                  type="submit"
                  :disabled="processing || form.items.length === 0 || hasInvalidStock"
                  class="w-full bg-primary-600 text-white py-2 px-4 rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ processing ? 'Processing...' : 'Process Adjustment' }}
                </button>
                <div v-if="hasInvalidStock" class="text-red-500 text-sm text-center">
                  Some adjustments would result in negative stock levels
                </div>
              </div>
            </CardContent>
          </UiCard>
        </div>
      </div>
    </form>

    <!-- Product Selection Modal -->
    <Modal :show="showProductModal" @close="showProductModal = false">
      <div class="p-6 max-w-4xl">
        <h3 class="text-lg font-semibold mb-4">Select Products to Adjust</h3>
        
        <div class="mb-4">
          <input
            v-model="productSearch"
            type="text"
            placeholder="Search products..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
          />
        </div>

        <div class="max-h-96 overflow-y-auto">
          <div class="grid grid-cols-1 gap-2">
            <div
              v-for="product in filteredProducts"
              :key="product.id"
              class="border rounded p-3 hover:bg-gray-50 cursor-pointer"
              @click="addProduct(product)"
            >
              <div class="flex justify-between items-center">
                <div>
                  <h4 class="font-medium">{{ product.name }}</h4>
                  <p class="text-sm text-gray-500">SKU: {{ product.sku }}</p>
                  <p class="text-sm text-gray-500">Current Stock: {{ product.stock_quantity }} {{ product.unit }}</p>
                  <p class="text-sm text-gray-500">Category: {{ product.category?.name }}</p>
                </div>
                <div class="text-right">
                  <p class="font-medium">PKR {{ product.cost_price }}</p>
                  <p class="text-xs text-gray-500">Cost Price</p>
                  <span v-if="product.stock_quantity <= product.min_stock" class="inline-block mt-1 px-2 py-1 bg-red-100 text-red-800 text-xs rounded">
                    Low Stock
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end mt-4">
          <button
            @click="showProductModal = false"
            class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50"
          >
            Close
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import CardHeader from '@/components/CardHeader.vue'
import CardContent from '@/components/CardContent.vue'
import Modal from '@/components/Modal.vue'
import { ArrowLeft, Plus, X, AlertTriangle } from 'lucide-vue-next'

const props = defineProps({
  products: Array,
  selectedProduct: Object,
  errors: Object
})

const processing = ref(false)
const showProductModal = ref(false)
const productSearch = ref('')

const form = useForm({
  type: '',
  reason: '',
  notes: '',
  items: props.selectedProduct ? [{
    product_id: props.selectedProduct.id,
    product_name: props.selectedProduct.name,
    product_sku: props.selectedProduct.sku,
    product_unit: props.selectedProduct.unit,
    current_stock: props.selectedProduct.stock_quantity,
    quantity: 0,
    new_stock: props.selectedProduct.stock_quantity,
    batch_id: null,
    has_batches: false,
    batches: []
  }] : []
})

const filteredProducts = computed(() => {
  if (!productSearch.value) return props.products
  
  return props.products.filter(product =>
    product.name.toLowerCase().includes(productSearch.value.toLowerCase()) ||
    product.sku.toLowerCase().includes(productSearch.value.toLowerCase())
  )
})

const totalAdjustments = computed(() => {
  return form.items.reduce((sum, item) => sum + Math.abs(item.quantity || 0), 0)
})

const positiveAdjustments = computed(() => {
  return form.items.reduce((sum, item) => sum + Math.max(0, item.quantity || 0), 0)
})

const negativeAdjustments = computed(() => {
  return form.items.reduce((sum, item) => sum + Math.min(0, item.quantity || 0), 0)
})

const hasInvalidStock = computed(() => {
  return form.items.some(item => item.new_stock < 0)
})

const addProduct = async (product) => {
  // Check if product already exists
  const existingIndex = form.items.findIndex(item => item.product_id === product.id)
  
  if (existingIndex >= 0) {
    showProductModal.value = false
    return
  }

  // Load batches for this product if needed
  let batches = []
  let hasBatches = false
  
  try {
    const response = await fetch(route('inventory.batches', product.id))
    batches = await response.json()
    hasBatches = batches.length > 0
  } catch (error) {
    console.error('Failed to load batches:', error)
  }

  // Add new product
  form.items.push({
    product_id: product.id,
    product_name: product.name,
    product_sku: product.sku,
    product_unit: product.unit,
    current_stock: product.stock_quantity,
    quantity: 0,
    new_stock: product.stock_quantity,
    batch_id: null,
    has_batches: hasBatches,
    batches: batches
  })
  
  showProductModal.value = false
  productSearch.value = ''
}

const removeItem = (index) => {
  form.items.splice(index, 1)
}

const calculateNewStock = (index) => {
  const item = form.items[index]
  item.new_stock = item.current_stock + (item.quantity || 0)
}

const submitForm = () => {
  processing.value = true
  
  const submitData = {
    ...form.data(),
    items: form.items.map(item => ({
      product_id: item.product_id,
      quantity: item.quantity,
      batch_id: item.batch_id || null
    }))
  }

  form.post(route('stock-adjustments.store'), {
    data: submitData,
    onFinish: () => processing.value = false
  })
}

// Initialize new stock calculation for existing items
form.items.forEach((item, index) => {
  calculateNewStock(index)
})
</script>