<template>
  <AppLayout>
    <PageHeader
      :title="purchase?.id ? 'Edit Purchase Order' : 'Create Purchase Order'"
      :subtitle="purchase?.id ? `Edit PO #${purchase.purchase_no || purchase.po_number || ''}` : 'Create a new purchase order'"
    >
      <template #actions>
        <Link
          :href="route('purchases.index')"
          class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2"
        >
          <ArrowLeft class="w-4 h-4" />
          Back to Purchases
        </Link>
      </template>
    </PageHeader>

    <form @submit.prevent="submitForm">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information -->
          <UiCard>
            <CardHeader>
              <h3 class="text-lg font-semibold">Purchase Order Details</h3>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Supplier *</label>
                  <Combobox v-model="selectedSupplier">
                    <div class="relative mt-1">
                      <div
                        class="relative w-full cursor-default overflow-hidden rounded-lg bg-white text-left border border-gray-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75 focus-visible:ring-offset-2 focus-visible:ring-offset-teal-300 sm:text-sm"
                      >
                        <ComboboxInput
                          class="w-full border-none py-2 pl-3 pr-10 text-sm leading-5 text-gray-900 focus:ring-0"
                          :displayValue="(supplier) => supplier?.name"
                          @change="query = $event.target.value"
                          placeholder="Select a supplier..." 
                        />
                        <ComboboxButton
                          class="absolute inset-y-0 right-0 flex items-center pr-2"
                        >
                          <ChevronsUpDown
                            class="h-5 w-5 text-gray-400"
                            aria-hidden="true"
                          />
                        </ComboboxButton>
                      </div>
                      <TransitionRoot
                        leave="transition ease-in duration-100"
                        leaveFrom="opacity-100"
                        leaveTo="opacity-0"
                        @after-leave="query = ''"
                      >
                        <ComboboxOptions
                          class="absolute mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm z-50"
                        >
                          <div
                            v-if="filteredSuppliers.length === 0 && query !== ''"
                            class="relative cursor-default select-none py-2 px-4 text-gray-700"
                          >
                            Nothing found.
                          </div>

                          <ComboboxOption
                            v-for="supplier in filteredSuppliers"
                            as="template"
                            :key="supplier.id"
                            :value="supplier"
                            v-slot="{ selected, active }"
                          >
                            <li
                              class="relative cursor-default select-none py-2 pl-10 pr-4"
                              :class="{
                                'bg-teal-600 text-white': active,
                                'text-gray-900': !active,
                              }"
                            >
                              <span
                                class="block truncate"
                                :class="{ 'font-medium': selected, 'font-normal': !selected }"
                              >
                                {{ supplier.name }}
                              </span>
                              <span
                                v-if="selected"
                                class="absolute inset-y-0 left-0 flex items-center pl-3"
                                :class="{ 'text-white': active, 'text-teal-600': !active }"
                              >
                                <Check class="h-5 w-5" aria-hidden="true" />
                              </span>
                            </li>
                          </ComboboxOption>
                        </ComboboxOptions>
                      </TransitionRoot>
                    </div>
                  </Combobox>
                  <span v-if="errors.supplier_id" class="text-red-500 text-sm">{{ errors.supplier_id }}</span>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Date *</label>
                  <input
                    v-model="form.purchased_at"
                    type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                    required
                  />
                  <span v-if="errors.purchased_at" class="text-red-500 text-sm">{{ errors.purchased_at }}</span>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Expected Delivery Date</label>
                  <input
                    v-model="form.expected_date"
                    type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                  />
                  <span v-if="errors.expected_date" class="text-red-500 text-sm">{{ errors.expected_date }}</span>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                  <select
                    v-model="form.status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                  >
                    <option value="pending">Pending</option>
                    <option value="ordered">Ordered</option>
                    <option value="received">Received</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                  <span v-if="errors.status" class="text-red-500 text-sm">{{ errors.status }}</span>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <textarea
                  v-model="form.notes"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                  placeholder="Any additional notes for this purchase order..."
                ></textarea>
                <span v-if="errors.notes" class="text-red-500 text-sm">{{ errors.notes }}</span>
              </div>
            </CardContent>
          </UiCard>

          <!-- Purchase Items -->
          <UiCard>
            <CardHeader>
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold">Purchase Items</h3>
                <button
                  type="button"
                  @click="showProductModal = true"
                  class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium text-white bg-indigo-600 rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                >
                  <Plus class="w-4 h-4" />
                  Add Product
                </button>
              </div>
            </CardHeader>
            <CardContent>
              <div v-if="form.items.length === 0" class="text-center py-8 text-gray-500">
                No items added yet. Click "Add Product" to start building your purchase order.
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
                  
                  <!-- Panaflex Roll Details (shown only for panaflex products) -->
                  <div v-if="item.product_type === 'panaflex_roll'" class="mt-3 p-3 bg-blue-50 rounded-lg">
                    <h5 class="text-sm font-medium text-blue-900 mb-2">Roll Specifications</h5>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                      <div>
                        <label class="block text-xs font-medium text-blue-700 mb-1">Roll Count *</label>
                        <input
                          v-model.number="item.rolls_count"
                          type="number"
                          step="any"
                          min="0.01"
                          class="w-full px-2 py-1 text-sm border border-blue-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                          placeholder="1.5"
                          required
                          @input="calculatePanaflexQuantity(index)"
                        />
                      </div>
                      <div>
                        <label class="block text-xs font-medium text-blue-700 mb-1">Width (Inches) *</label>
                        <input
                          v-model.number="item.roll_width_inch"
                          type="number"
                          min="1"
                          step="1"
                          class="w-full px-2 py-1 text-sm border border-blue-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                          placeholder="e.g. 120"
                          required
                          @input="calculatePanaflexQuantity(index)"
                        />
                      </div>
                      <div>
                        <label class="block text-xs font-medium text-blue-700 mb-1">Length (Meters) *</label>
                        <input
                          v-model.number="item.roll_length_meter"
                          type="number"
                          min="0.1"
                          step="0.01"
                          class="w-full px-2 py-1 text-sm border border-blue-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                          placeholder="164"
                          required
                          @input="calculatePanaflexQuantity(index)"
                        />
                      </div>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3">
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">Quantity *</label>
                      <input
                        v-model.number="item.quantity"
                        type="number"
                        :min="item.product_type !== 'panaflex_roll' ? 0.01 : null"
                        step="0.01"
                        class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-primary"
                        @input="calculateItemTotal(index)"
                        :required="item.product_type !== 'panaflex_roll'"
                      />
                      <p v-if="item.product_type === 'panaflex_roll'" class="text-[10px] text-orange-600 mt-1 flex items-center gap-1">
                        <span class="font-bold">⚠</span> Auto-calculated. Please verify.
                      </p>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">Unit Cost *</label>
                      <input
                        v-model.number="item.unit_cost"
                        type="number"
                        min="0"
                        step="0.01"
                        class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-primary"
                        @input="calculateItemTotal(index)"
                        required
                      />
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">Total</label>
                      <input
                        :value="formatItemTotal(item)"
                        type="text"
                        readonly
                        class="w-full px-2 py-1 text-sm border border-gray-200 rounded bg-gray-100"
                      />
                    </div>
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
              <h3 class="text-lg font-semibold">Order Summary</h3>
            </CardHeader>
            <CardContent class="space-y-3">
              <div class="flex justify-between">
                <span>Items:</span>
                <span>{{ form.items.length }}</span>
              </div>
              <div class="flex justify-between">
                <span>Subtotal:</span>
                <span>{{ formatCurrency(subtotal) }}</span>
              </div>
              
              <!-- Extra Charges Section -->
              <div class="border-t pt-3 space-y-2">
                <div class="flex justify-between items-center gap-2">
                  <label class="text-sm font-medium text-gray-700">Shipping:</label>
                  <input
                    v-model.number="form.shipping_charges"
                    type="number"
                    min="0"
                    step="0.01"
                    class="w-full sm:w-20 px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-primary text-right"
                    placeholder="0"
                    @input="calculateTotals"
                  />
                </div>
                <div class="flex justify-between items-center gap-2">
                  <label class="text-sm font-medium text-gray-700">Other Charges:</label>
                  <input
                    v-model.number="form.other_charges"
                    type="number"
                    min="0"
                    step="0.01"
                    class="w-full sm:w-20 px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-primary text-right"
                    placeholder="0"
                    @input="calculateTotals"
                  />
                </div>
                <div class="flex justify-between items-center gap-2">
                  <label class="text-sm font-medium text-gray-700">Discount:</label>
                  <input
                    v-model.number="form.discount_total"
                    type="number"
                    min="0"
                    step="0.01"
                    class="w-full sm:w-20 px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-primary text-right"
                    placeholder="0"
                    @input="calculateTotals"
                  />
                </div>
              </div>
              
              <div class="flex justify-between items-center gap-2">
                <div class="flex items-center gap-2">
                  <label class="text-sm font-medium text-gray-700">Tax:</label>
                  <input
                    v-model.number="form.tax_rate"
                    type="number"
                    min="0"
                    max="100"
                    step="0.1"
                    class="w-12 sm:w-16 px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-primary text-right"
                    placeholder="0"
                    @input="calculateTotals"
                  />
                  <span class="text-sm">%</span>
                </div>
                <span>{{ formatCurrency(tax) }}</span>
              </div>
              <hr>
              <div class="flex justify-between font-semibold text-lg">
                <span>Grand Total:</span>
                <span>{{ formatCurrency(total) }}</span>
              </div>
            </CardContent>
          </UiCard>

          <UiCard>
            <CardContent>
              <div class="space-y-3">
                <button
                  type="submit"
                  :disabled="processing || form.items.length === 0"
                  class="w-full inline-flex justify-center items-center gap-2 bg-indigo-600 text-white py-2 px-4 rounded-lg shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ processing ? 'Saving...' : (purchase?.id ? 'Update Purchase Order' : 'Create Purchase Order') }}
                </button>
                <button
                  v-if="!purchase?.id"
                  type="button"
                  @click="saveDraft"
                  :disabled="processing || form.items.length === 0"
                  class="w-full inline-flex justify-center items-center gap-2 bg-gray-100 text-gray-800 py-2 px-4 rounded-lg shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Save as Draft
                </button>
              </div>
            </CardContent>
          </UiCard>
        </div>
      </div>
    </form>

    <!-- Product Selection Modal -->
    <Modal :show="showProductModal" @close="showProductModal = false">
      <div class="p-6 max-w-4xl">
        <h3 class="text-lg font-semibold mb-4">Add Products to Purchase Order</h3>
        
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
              <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <div>
                  <h4 class="font-medium">{{ product.name }}</h4>
                  <p class="text-sm text-gray-500">SKU: {{ product.sku }} | Stock: {{ product.stock_quantity }} {{ product.unit }}</p>
                  <p class="text-sm text-gray-500">Category: {{ product.category?.name }}</p>
                </div>
                <div class="text-left sm:text-right">
                  <p class="font-medium">{{ formatCurrency(product.cost_price) }}</p>
                  <p class="text-xs text-gray-500">Cost Price</p>
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
import { Combobox, ComboboxInput, ComboboxButton, ComboboxOptions, ComboboxOption, TransitionRoot } from '@headlessui/vue'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import CardHeader from '@/components/CardHeader.vue'
import CardContent from '@/components/CardContent.vue'
import Modal from '@/components/Modal.vue'
import { ArrowLeft, Plus, X, Search, Check, ChevronsUpDown } from 'lucide-vue-next'
import { formatCurrency } from '@/utils/currency'

const props = defineProps({
  purchase: Object,
  suppliers: Array,
  products: Array,
  errors: Object
})

const query = ref('')
const filteredSuppliers = computed(() =>
  query.value === ''
    ? props.suppliers
    : props.suppliers.filter((supplier) =>
        supplier.name
          .toLowerCase()
          .replace(/\s+/g, '')
          .includes(query.value.toLowerCase().replace(/\s+/g, ''))
      )
)

const selectedSupplier = computed({
  get: () => props.suppliers.find(s => s.id === form.supplier_id) || null,
  set: (val) => {
    form.supplier_id = val ? val.id : ''
  }
})

const processing = ref(false)
const showProductModal = ref(false)
const productSearch = ref('')

const form = useForm({
  supplier_id: props.purchase?.supplier_id || '',
  purchased_at: props.purchase?.purchased_at ? props.purchase.purchased_at.split('T')[0] : new Date().toISOString().split('T')[0],
  expected_date: props.purchase?.expected_date ? props.purchase.expected_date.split('T')[0] : '',
  subtotal: props.purchase?.subtotal || 0,
  discount_total: props.purchase?.discount_total || 0,
  tax_total: props.purchase?.tax_total || 0,
  tax_rate: props.purchase?.tax_rate || 0,
  other_charges: props.purchase?.other_charges || 0,
  shipping_charges: props.purchase?.shipping_charges || 0,
  grand_total: props.purchase?.grand_total || 0,
  status: props.purchase?.status || 'pending',
  notes: props.purchase?.notes || '',
  items: (() => {
    const items = props.purchase?.purchaseItems?.map(item => ({
      id: item.id, // Keep original item ID for update tracking
      product_id: item.product_id,
      product_name: item.product?.name || '',
      product_sku: item.product?.sku || '',
      product_unit: item.product?.unit || {},
      product_type: item.product?.type || 'standard',
      current_stock: item.product?.stock_quantity || 0,
      quantity: parseFloat(item.quantity) || 0,
      unit_cost: parseFloat(item.rate) || 0,
      // Panaflex roll details
      rolls_count: item.rolls_count || 1,
      roll_width_inch: item.roll_width_inch || null,
      roll_length_meter: item.roll_length_meter || null
    })) || []
    
    console.log('Purchase form initialized with items:', items)
    return items
  })()
})

const filteredProducts = computed(() => {
  if (!productSearch.value) return props.products
  
  return props.products.filter(product =>
    product.name.toLowerCase().includes(productSearch.value.toLowerCase()) ||
    product.sku.toLowerCase().includes(productSearch.value.toLowerCase())
  )
})

const toNumber = (value) => {
  const parsed = parseFloat(value)
  return Number.isFinite(parsed) ? parsed : 0
}

const toNullableDecimal = (value) => {
  const parsed = parseFloat(value)
  return Number.isFinite(parsed) ? parsed : null
}

const toPositiveDecimalOrNull = (value) => {
  const parsed = parseFloat(value)
  return Number.isFinite(parsed) && parsed > 0 ? parsed : null
}

const getItemQuantity = (item) => toNumber(item.quantity)
const getItemUnitCost = (item) => toNumber(item.unit_cost)
const getItemLineTotal = (item) => getItemQuantity(item) * getItemUnitCost(item)
const formatItemTotal = (item) => getItemLineTotal(item).toFixed(2)

const subtotal = computed(() => {
  const sub = form.items.reduce((sum, item) => sum + getItemLineTotal(item), 0)
  form.subtotal = sub
  return sub
})

const tax = computed(() => {
  const taxRate = toNumber(form.tax_rate) / 100
  const taxableAmount = Math.max(subtotal.value - toNumber(form.discount_total), 0)
  const taxAmount = taxableAmount * taxRate
  form.tax_total = taxAmount
  return taxAmount
})

const total = computed(() => {
  const totalAmount =
    subtotal.value +
    tax.value +
    toNumber(form.other_charges) +
    toNumber(form.shipping_charges) -
    toNumber(form.discount_total)
  form.grand_total = totalAmount
  return totalAmount
})

const addProduct = (product) => {
  // Always add new product entry (Allow duplicates for multiple variations)
  const newItem = {
    product_id: product.id,
    product_name: product.name,
    product_sku: product.sku,
    product_unit: product.unit,
    product_type: product.type,
    current_stock: product.stock_quantity,
    quantity: 1,
    unit_cost: product.cost_price || 0
  }
  
  // Add panaflex roll defaults if it's a panaflex product
  if (product.type === 'panaflex_roll') {
    newItem.rolls_count = 1
    newItem.roll_width_inch = product.panaflex_spec?.roll_width_inch || product.panaflexSpec?.roll_width_inch || null
    newItem.roll_length_meter = product.panaflex_spec?.roll_length_meter || product.panaflexSpec?.roll_length_meter || null
    
    // Automatically calculate initial quantity if specs are loaded
    const rolls = newItem.rolls_count
    const width = newItem.roll_width_inch
    const length = newItem.roll_length_meter
    if (rolls > 0 && width > 0 && length > 0) {
      const widthFt = width / 12
      const lengthFt = length * 3.28
      newItem.quantity = parseFloat((widthFt * lengthFt * rolls).toFixed(2))
    } else {
      newItem.quantity = 0
    }
  }
  
  form.items.push(newItem)
  
  showProductModal.value = false
  productSearch.value = ''
}

const removeItem = (index) => {
  form.items.splice(index, 1)
}

const calculatePanaflexQuantity = (index) => {
  const item = form.items[index]
  if (item.product_type === 'panaflex_roll') {
    const rolls = parseFloat(item.rolls_count) || 0
    const width = parseFloat(item.roll_width_inch) || 0
    const length = parseFloat(item.roll_length_meter) || 0
    
    // Convert Width (Inches) and Length (Meters) to feet, then multiply by rolls count to find total SqFt
    if (rolls > 0 && width > 0 && length > 0) {
      const widthFt = width / 12
      const lengthFt = length * 3.28
      const totalSqFt = widthFt * lengthFt * rolls
      item.quantity = parseFloat(totalSqFt.toFixed(2))
      // Trigger reactivity for totals
      calculateItemTotal(index)
    }
  }
}

const calculateItemTotal = (index) => {
  // This function can be used for real-time calculations if needed
  // Currently handled by computed properties
}

const calculateTotals = () => {
  // Force reactivity update
  subtotal.value
  tax.value
  total.value
}

const mapItemsForSubmission = () => {
  return form.items.map(item => {
    const isPanaflex = item.product_type === 'panaflex_roll'
    const normalizedQuantity = getItemQuantity(item)
    const payloadQuantity = isPanaflex
      ? (normalizedQuantity > 0 ? normalizedQuantity : null)
      : normalizedQuantity
    const rate = getItemUnitCost(item)
    const lineTotal = parseFloat(getItemLineTotal(item).toFixed(2))

    console.log('Mapping item:', {
      product_id: item.product_id,
      unit_cost: item.unit_cost,
      quantity: item.quantity,
      rate,
      lineTotal,
      isPanaflex
    })

    return {
      product_id: item.product_id,
      quantity: payloadQuantity,
      rate,
      line_total: lineTotal,
      rolls_count: isPanaflex ? toPositiveDecimalOrNull(item.rolls_count) : null,
      roll_width_inch: isPanaflex ? toNullableDecimal(item.roll_width_inch) : null,
      roll_length_meter: isPanaflex ? toNullableDecimal(item.roll_length_meter) : null
    }
  })
}

const submitForm = () => {
  processing.value = true
  
  // Update totals before submitting
  total.value // This triggers the computed to update form values

  const mappedItems = mapItemsForSubmission()

  console.log('Submitting purchase data:', {
    items: mappedItems,
    subtotal: form.subtotal,
    grand_total: form.grand_total
  })

  if (props.purchase?.id) {
    form
      .transform(data => ({
        ...data,
        items: mappedItems
      }))
      .put(route('purchases.update', props.purchase.id), {
        onFinish: () => processing.value = false,
        onSuccess: () => {
          console.log('Purchase updated successfully')
        },
        onError: (errors) => {
        console.error('Purchase update errors:', errors)
        processing.value = false
      }
    })
  } else {
    form
      .transform(data => ({
        ...data,
        items: mappedItems
      }))
      .post(route('purchases.store'), {
        onFinish: () => processing.value = false,
        onError: (errors) => {
          console.error('Purchase store errors:', errors)
          processing.value = false
        }
      })
  }
}

const saveDraft = () => {
  processing.value = true
  
  // Set form status to draft and update totals
  form.status = 'draft'
  total.value // This triggers the computed to update form values

  const mappedItems = mapItemsForSubmission()

  form
    .transform(data => ({
      ...data,
      items: mappedItems
    }))
    .post(route('purchases.store'), {
      onFinish: () => processing.value = false
    })
}
</script>
