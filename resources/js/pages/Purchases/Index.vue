<template>
  <AppLayout>
    <PageHeader
      title="Purchases"
      subtitle="Manage purchase orders and inventory procurement"
    >
      <template #actions>
        <div class="flex gap-2">
          <a
            :href="route('purchases.export')"
            class="bg-blue-500 text-white px-3 py-2 rounded-lg hover:bg-blue-600 flex items-center justify-center gap-2 text-sm"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            <span class="hidden sm:inline">Export</span>
          </a>
          <Link
            :href="route('purchases.create')"
            class="bg-primary-600 text-white px-3 py-2 rounded-lg hover:bg-primary-700 flex items-center justify-center gap-2 text-sm"
          >
            <Plus class="w-4 h-4" />
            <span class="hidden sm:inline">New Purchase</span>
          </Link>
        </div>
      </template>
    </PageHeader>

    <UiCard>
      <DataTable
        ref="tableRef"
        :url="route('purchases.datatable')"
        :columns="columns"
        :filters="filters"
      >
        <template #filter>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
              <input
                v-model="filters.search"
                type="text"
                placeholder="Search by PO number, supplier..."
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
              <select
                v-model="filters.status"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white"
              >
                <option value="">All Status</option>
                <option value="draft">Draft</option>
                <option value="pending">Pending</option>
                <option value="ordered">Ordered</option>
                <option value="received">Received</option>
                <option value="partial">Partially Received</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Supplier</label>
              <Combobox v-model="selectedSupplier">
                <div class="relative mt-1">
                  <div
                    class="relative w-full cursor-default overflow-hidden rounded-lg bg-white text-left border border-gray-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75 focus-visible:ring-offset-2 focus-visible:ring-offset-teal-300 sm:text-sm"
                  >
                    <ComboboxInput
                      class="w-full border-none py-2 pl-3 pr-10 text-sm leading-5 text-gray-900 focus:ring-0"
                      :displayValue="(supplier) => supplier?.name"
                      @change="supplierQuery = $event.target.value"
                      placeholder="All Suppliers" 
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
                    @after-leave="supplierQuery = ''"
                  >
                    <ComboboxOptions
                      class="absolute mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm z-50"
                    >
                      <div
                        class="relative cursor-default select-none py-2 px-4 text-gray-700"
                        @click="selectedSupplier = null"
                        :class="{ 'bg-teal-600 text-white': false, 'hover:bg-teal-600 hover:text-white': true }"
                      >
                         All Suppliers
                      </div>
                      <div
                        v-if="filteredSuppliers.length === 0 && supplierQuery !== ''"
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
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Range</label>
              <input
                v-model="filters.date_from"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white"
              />
            </div>
          </div>
        </template>

        <template #actions="{ item }">
          <div class="flex items-center gap-2">
            <!-- View Button -->
            <Link
              :href="route('purchases.show', item.id)"
              class="text-blue-600 hover:text-blue-800 p-1 hover:bg-blue-50 rounded"
              title="View Details"
            >
              <Eye class="w-4 h-4" />
            </Link>
            
            <!-- Edit Button -->
            <Link
              :href="route('purchases.edit', item.id)"
              class="text-green-600 hover:text-green-800 p-1 hover:bg-green-50 rounded"
              title="Edit Purchase"
            >
              <Edit class="w-4 h-4" />
            </Link>
            
            <!-- Receive Button for ordered status -->
            <button
              v-if="item.status === 'ordered'"
              @click="handleReceive(item)"
              class="text-purple-600 hover:text-purple-800 p-1 hover:bg-purple-50 rounded"
              title="Receive Items"
              type="button"
            >
              <Package class="w-4 h-4" />
            </button>

            <!-- Delete Button -->
            <button
              @click="handleDelete(item)"
              class="text-red-600 hover:text-red-800 p-1 hover:bg-red-50 rounded"
              title="Delete Purchase"
              type="button"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </template>
        
        <!-- Status Column with Dropdown -->
        <template #column.status="{ item }">
          <select
            :value="item.status"
            @change="updateStatus(item, $event.target.value)"
            class="text-xs px-2 py-1 border rounded focus:outline-none focus:ring-2 focus:ring-primary"
            :class="{
              'bg-gray-100 text-gray-800 border-gray-300': item.status === 'draft',
              'bg-yellow-100 text-yellow-800 border-yellow-300': item.status === 'pending',
              'bg-blue-100 text-blue-800 border-blue-300': item.status === 'ordered',
              'bg-green-100 text-green-800 border-green-300': item.status === 'received',
              'bg-red-100 text-red-800 border-red-300': item.status === 'cancelled'
            }"
          >
            <option value="draft">Draft</option>
            <option value="pending">Pending</option>
            <option value="ordered">Ordered</option>
            <option value="received">Received</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </template>
      </DataTable>
    </UiCard>

    <!-- Receive Items Modal -->
    <Modal :show="showReceiveModal" @close="closeReceiveModal">
      <div class="p-6">
        <h3 class="text-lg font-semibold mb-4">Receive Purchase Items</h3>
        <div class="space-y-4">
          <div v-for="item in selectedPurchase?.items" :key="item.id" class="border rounded p-4">
            <div class="flex justify-between items-start mb-2">
              <div>
                <h4 class="font-medium">{{ item.product.name }}</h4>
                <p class="text-sm text-gray-500">
                  Ordered: {{ item.quantity }} {{ item.product.unit }}
                </p>
                <p class="text-sm text-gray-500">
                  Received: {{ item.received_quantity || 0 }} {{ item.product.unit }}
                </p>
              </div>
              <div class="text-right">
                <p class="font-medium">PKR {{ item.unit_cost }}</p>
                <p class="text-sm text-gray-500">per {{ item.product.unit }}</p>
              </div>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Receive Qty</label>
                <input
                  v-model.number="item.receiving_quantity"
                  type="number"
                  :max="item.quantity - (item.received_quantity || 0)"
                  min="0"
                  step="0.01"
                  class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-primary"
                />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Batch Code</label>
                <input
                  v-model="item.batch_code"
                  type="text"
                  class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-primary"
                />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Expiry Date</label>
                <input
                  v-model="item.expiry_date"
                  type="date"
                  class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-primary"
                />
              </div>
            </div>
          </div>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button
            @click="closeReceiveModal"
            class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            @click="submitReceiving"
            :disabled="processingReceive"
            class="px-4 py-2 bg-primary text-white rounded hover:bg-primary/90 disabled:opacity-50"
          >
            {{ processingReceive ? 'Processing...' : 'Receive Items' }}
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Combobox, ComboboxInput, ComboboxButton, ComboboxOptions, ComboboxOption, TransitionRoot } from '@headlessui/vue'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import DataTable from '@/components/DataTable.vue'
import Modal from '@/components/Modal.vue'
import { Plus, Eye, Edit, Package, X, Trash2, Check, ChevronsUpDown } from 'lucide-vue-next'

const props = defineProps({
  suppliers: Array
})

const supplierQuery = ref('')
const filteredSuppliers = computed(() =>
  supplierQuery.value === ''
    ? props.suppliers
    : props.suppliers.filter((supplier) =>
        supplier.name
          .toLowerCase()
          .replace(/\s+/g, '')
          .includes(supplierQuery.value.toLowerCase().replace(/\s+/g, ''))
      )
)

const selectedSupplier = computed({
  get: () => props.suppliers.find(s => s.id === filters.value.supplier_id) || null,
  set: (val) => {
    filters.value.supplier_id = val ? val.id : ''
  }
})

const tableRef = ref(null)
const showReceiveModal = ref(false)
const selectedPurchase = ref(null)
const processingReceive = ref(false)

const filters = ref({
  search: '',
  status: '',
  supplier_id: '',
  date_from: ''
})

const columns = [
  {
    key: 'po_number',
    label: 'PO Number',
    sortable: true
  },
  {
    key: 'supplier.name',
    label: 'Supplier',
    sortable: true
  },
  {
    key: 'total_amount',
    label: 'Total Amount',
    sortable: true,
    format: (value) => `PKR ${parseFloat(value).toFixed(2)}`
  },
  {
    key: 'status',
    label: 'Status',
    sortable: true,
    component: 'StatusBadge'
  },
  {
    key: 'order_date',
    label: 'Order Date',
    sortable: true,
    format: (value) => new Date(value).toLocaleDateString()
  },
  {
    key: 'expected_date',
    label: 'Expected Date',
    sortable: true,
    format: (value) => value ? new Date(value).toLocaleDateString() : '-'
  },
  {
    key: 'actions',
    label: 'Actions',
    sortable: false
  }
]

// Watch filters and refresh table
watch(filters, () => {
  if (tableRef.value) {
    tableRef.value.refresh()
  }
}, { deep: true })

const handleReceive = (purchase) => {
  console.log('🎯 RECEIVE BUTTON CLICKED!')
  console.log('Purchase data:', purchase)
  alert(`Receive button clicked for Purchase ID: ${purchase.id}`)
  receivePurchase(purchase)
}

const handleCancel = (purchase) => {
  console.log('🎯 CANCEL BUTTON CLICKED!')
  console.log('Purchase data:', purchase)
  alert(`Cancel button clicked for Purchase ID: ${purchase.id}`)
  confirmCancel(purchase)
}

const receivePurchase = (purchase) => {
  console.log('receivePurchase called with:', purchase)
  selectedPurchase.value = purchase
  // Initialize receiving quantities
  if (purchase.items && Array.isArray(purchase.items)) {
    console.log('Purchase items found:', purchase.items.length)
    purchase.items.forEach(item => {
      item.receiving_quantity = item.quantity - (item.received_quantity || 0)
      item.batch_code = ''
      item.expiry_date = ''
    })
  } else {
    console.error('Purchase items not found or not an array:', purchase)
    alert('Error: Purchase items not loaded properly. Please refresh the page.')
    return
  }
  console.log('Opening receive modal...')
  showReceiveModal.value = true
}

const closeReceiveModal = () => {
  showReceiveModal.value = false
  selectedPurchase.value = null
}

const submitReceiving = async () => {
  console.log('submitReceiving called')
  processingReceive.value = true
  
  try {
    const items = selectedPurchase.value.items
      .filter(item => item.receiving_quantity > 0)
      .map(item => ({
        purchase_item_id: item.id,
        quantity: item.receiving_quantity,
        batch_code: item.batch_code,
        expiry_date: item.expiry_date || null
      }))

    console.log('Submitting items:', items)
    console.log('Purchase ID:', selectedPurchase.value.id)

    await router.post(route('purchases.receive', selectedPurchase.value.id), {
      items
    }, {
      onSuccess: (response) => {
        console.log('Receive success:', response)
        closeReceiveModal()
        tableRef.value.refresh()
      },
      onError: (errors) => {
        console.error('Receive errors:', errors)
      }
    })
  } catch (error) {
    console.error('Submit error:', error)
  } finally {
    processingReceive.value = false
  }
}

const confirmCancel = (purchase) => {
  console.log('confirmCancel called with:', purchase)
  if (confirm(`Are you sure you want to cancel purchase order ${purchase.po_number}?`)) {
    console.log('Cancelling purchase ID:', purchase.id)
    router.patch(route('purchases.cancel', purchase.id), {}, {
      onSuccess: (response) => {
        console.log('Cancel success:', response)
        tableRef.value.refresh()
      },
      onError: (errors) => {
        console.error('Cancel errors:', errors)
      }
    })
  }
}

const handleDelete = (purchase) => {
  if (confirm(`Are you sure you want to delete purchase ${purchase.po_number}? This will reverse any stock additions and supplier ledger entries.`)) {
    router.delete(route('purchases.destroy', purchase.id), {
      onSuccess: () => {
        tableRef.value.refresh()
      }
    })
  }
}

const updateStatus = (purchase, newStatus) => {
  if (confirm(`Change status from "${purchase.status}" to "${newStatus}"?`)) {
    router.patch(route('purchases.update-status', purchase.id), {
      status: newStatus
    }, {
      onSuccess: () => {
        tableRef.value.refresh()
      },
      onError: (errors) => {
        console.error('Status update errors:', errors)
        alert('Failed to update status. Please try again.')
      }
    })
  } else {
    // Reset select to original value
    tableRef.value.refresh()
  }
}

</script>