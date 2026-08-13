<template>
  <AppLayout>
    <PageHeader
      title="Inventory Management"
      subtitle="Monitor stock levels and manage inventory operations"
    >
      <template #actions>
        <div class="flex gap-2">
          <button
            @click="showImportModal = true"
            class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 flex items-center justify-center gap-2 text-sm"
          >
            <Upload class="w-4 h-4" />
            <span class="hidden sm:inline">Import Products</span>
          </button>
          <a
            :href="route('products.export')"
            class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 flex items-center justify-center gap-2 text-sm"
          >
            <Download class="w-4 h-4" />
            <span class="hidden sm:inline">Export Products</span>
          </a>
          <Link
            :href="route('stock-adjustments.create')"
            class="bg-yellow-500 text-white px-3 py-2 rounded-lg hover:bg-yellow-600 flex items-center justify-center gap-2 text-sm"
          >
            <Settings class="w-4 h-4" />
            <span class="hidden sm:inline">Stock Adjustment</span>
          </Link>
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

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-6 mb-6">
      <UiCard class="cursor-pointer hover:shadow-lg transition-all duration-300 hover:-translate-y-1" @click="clearFilters">
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total Products</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.total_products }}</p>
            </div>
            <div class="stat-icon stat-icon--blue">
              <Package class="w-7 h-7" />
            </div>
          </div>
        </CardContent>
      </UiCard>
      
      <UiCard class="cursor-pointer hover:shadow-lg transition-all duration-300 hover:-translate-y-1" @click="filterLowStock">
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Low Stock Items</p>
              <p class="text-2xl font-bold text-red-600">{{ stats.low_stock_products }}</p>
            </div>
            <div class="stat-icon stat-icon--amber">
              <AlertTriangle class="w-7 h-7" />
            </div>
          </div>
        </CardContent>
      </UiCard>
      
      <UiCard class="cursor-pointer hover:shadow-lg transition-all duration-300 hover:-translate-y-1" @click="filterOutOfStock">
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Out of Stock</p>
              <p class="text-2xl font-bold text-red-600">{{ stats.out_of_stock_products }}</p>
            </div>
            <div class="stat-icon stat-icon--red">
              <XCircle class="w-7 h-7" />
            </div>
          </div>
        </CardContent>
      </UiCard>
      
      <UiCard class="hover:shadow-lg transition-all duration-300">
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total Stock Value</p>
              <p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats.total_stock_value) }}</p>
            </div>
            <div class="stat-icon stat-icon--green">
              <Coins class="w-7 h-7" />
            </div>
          </div>
        </CardContent>
      </UiCard>
    </div>

    <UiCard>
      <DataTable
        ref="tableRef"
        :url="route('inventory.datatable')"
        :columns="columns"
        :filters="filters"
      >
        <template #filter>
          <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
              <input
                v-model="filters.search"
                type="text"
                placeholder="Search by name, SKU..."
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
              <select
                v-model="filters.category_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="">All Categories</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Stock Status</label>
              <select
                v-model="filters.stock_status"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="">All Stock Levels</option>
                <option value="in_stock">In Stock</option>
                <option value="low_stock">Low Stock</option>
                <option value="out_of_stock">Out of Stock</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
              <select
                v-model="filters.supplier_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="">All Suppliers</option>
                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                  {{ supplier.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Has Batches</label>
              <select
                v-model="filters.has_batches"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="">All Products</option>
                <option value="yes">With Batches</option>
                <option value="no">Without Batches</option>
              </select>
            </div>
          </div>
        </template>

        <template #column.stock_quantity="{ item }">
          <div class="flex flex-col">
            <span class="font-medium">{{ item.stock_quantity }}</span>
          </div>
        </template>

        <template #column.min_stock="{ item }">
          <div class="flex flex-col">
            <span class="font-medium">{{ item.min_stock }}</span>
          </div>
        </template>

        <template #actions="{ item }">
          <div class="flex items-center gap-2">
            <button
              @click="showStockHistory(item)"
              class="action-icon action-icon--blue"
              title="Stock History"
            >
              <History class="w-4 h-4" />
            </button>
            <button
              @click="showBatches(item)"
              class="action-icon action-icon--green"
              title="View Batches"
            >
              <Layers class="w-4 h-4" />
            </button>
            <Link
              :href="route('stock-adjustments.create', { product_id: item.id })"
              class="action-icon action-icon--amber"
              title="Adjust Stock"
            >
              <Settings class="w-4 h-4" />
            </Link>
            <Link
              :href="route('purchases.create', { product_id: item.id })"
              class="action-icon action-icon--purple"
              title="Create Purchase Order"
            >
              <ShoppingCart class="w-4 h-4" />
            </Link>
          </div>
        </template>
      </DataTable>
    </UiCard>

    <!-- Stock History Modal -->
    <Modal :show="showHistoryModal" @close="closeHistoryModal">
      <div class="p-6 max-w-4xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Stock History - {{ selectedProduct?.name }}</h3>
          <button @click="closeHistoryModal" class="text-gray-400 hover:text-gray-600">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <div class="max-h-96 overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left">Date</th>
                <th class="px-3 py-2 text-left">Type</th>
                <th class="px-3 py-2 text-left">Quantity</th>
                <th class="px-3 py-2 text-left">Balance</th>
                <th class="px-3 py-2 text-left">Reference</th>
                <th class="px-3 py-2 text-left">Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="move in stockMoves" :key="move.id" class="border-b">
                <td class="px-3 py-2">{{ formatDate(move.created_at) }}</td>
                <td class="px-3 py-2">
                  <span :class="getTypeColor(move.type)" class="px-2 py-1 rounded-full text-xs">
                    {{ move.type }}
                  </span>
                </td>
                <td class="px-3 py-2" :class="move.quantity > 0 ? 'text-green-600' : 'text-red-600'">
                  {{ move.quantity > 0 ? '+' : '' }}{{ move.quantity }}
                </td>
                <td class="px-3 py-2">{{ move.balance_after }}</td>
                <td class="px-3 py-2">{{ move.reference || '-' }}</td>
                <td class="px-3 py-2">{{ move.notes || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </Modal>

    <!-- Batches Modal -->
    <Modal :show="showBatchesModal" @close="closeBatchesModal">
      <div class="p-6 max-w-4xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Stock Batches - {{ selectedProduct?.name }}</h3>
          <button @click="closeBatchesModal" class="text-gray-400 hover:text-gray-600">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <div class="max-h-96 overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left">Batch Code</th>
                <th class="px-3 py-2 text-left">Quantity</th>
                <th class="px-3 py-2 text-left">Cost</th>
                <th class="px-3 py-2 text-left">Expiry Date</th>
                <th class="px-3 py-2 text-left">Purchase Date</th>
                <th class="px-3 py-2 text-left">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="batch in stockBatches" :key="batch.id" class="border-b">
                <td class="px-3 py-2 font-mono">{{ batch.batch_code }}</td>
                <td class="px-3 py-2">{{ batch.quantity }}</td>
                <td class="px-3 py-2">{{ formatCurrency(batch.unit_cost) }}</td>
                <td class="px-3 py-2" :class="isExpiringSoon(batch.expiry_date) ? 'text-red-600' : ''">
                  {{ batch.expiry_date ? formatDate(batch.expiry_date) : '-' }}
                </td>
                <td class="px-3 py-2">{{ formatDate(batch.created_at) }}</td>
                <td class="px-3 py-2">
                  <span :class="getBatchStatusColor(batch)" class="px-2 py-1 rounded-full text-xs">
                    {{ getBatchStatus(batch) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </Modal>

    <!-- Import Modal -->
    <Modal :show="showImportModal" @close="showImportModal = false">
      <div class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Import Products</h3>
          <button @click="showImportModal = false" class="text-gray-400 hover:text-gray-600">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <form @submit.prevent="importFile" enctype="multipart/form-data">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Select Excel/CSV File
            </label>
            <input
              type="file"
              ref="fileInput"
              accept=".xlsx,.csv"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            />
            <p class="text-sm text-gray-500 mt-1">
              Supported formats: Excel (.xlsx), CSV (.csv)
            </p>
          </div>
          
          <div class="flex justify-end gap-3">
            <button
              type="button"
              @click="showImportModal = false"
              class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="importing"
              class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 disabled:opacity-50"
            >
              {{ importing ? 'Importing...' : 'Import' }}
            </button>
          </div>
        </form>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import CardContent from '@/components/CardContent.vue'
import DataTable from '@/components/DataTable.vue'
import Modal from '@/components/Modal.vue'
import { formatCurrency } from '@/utils/currency'
import { 
  Plus, Settings, Package, AlertTriangle, XCircle, Coins,
  History, Layers, ShoppingCart, X, Upload, Download
} from 'lucide-vue-next'

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      total_products: 0,
      low_stock_products: 0,
      out_of_stock_products: 0,
      total_stock_value: 0
    })
  },
  categories: {
    type: Array,
    default: () => []
  },
  suppliers: {
    type: Array,
    default: () => []
  }
})

const tableRef = ref(null)
const showHistoryModal = ref(false)
const showBatchesModal = ref(false)
const showImportModal = ref(false)
const fileInput = ref(null)
const importing = ref(false)
const selectedProduct = ref(null)
const stockMoves = ref([])
const stockBatches = ref([])

const filters = ref({
  search: '',
  category_id: '',
  stock_status: '',
  supplier_id: '',
  has_batches: ''
})

const formatNumber = (value) => {
  if (value === null || value === undefined) return '0'
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(value)
}

// Auto-refresh data when component mounts
onMounted(() => {
  // Reload page data to ensure fresh inventory information
  if (tableRef.value?.reload) {
    tableRef.value.reload()
  }
})

const columns = [
  {
    key: 'sku',
    label: 'SKU',
    sortable: true
  },
  {
    key: 'name',
    label: 'Product Name',
    sortable: true
  },
  {
    key: 'category.name',
    label: 'Category',
    sortable: true
  },
  {
    key: 'type',
    label: 'Type',
    sortable: true
  },
  {
    key: 'stock_quantity',
    label: 'Current Stock',
    sortable: true,
    component: 'StockLevel'
  },
  {
    key: 'min_stock',
    label: 'Min Stock',
    sortable: true
  },
  {
    key: 'stock_status',
    label: 'Status',
    sortable: true,
    component: 'StockStatus'
  },
  {
    key: 'stock_value',
    label: 'Stock Value',
    sortable: true,
    format: (value) => formatCurrency(parseFloat(value || 0))
  },
  {
    key: 'available_batches',
    label: 'Batches',
    sortable: true
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

const showStockHistory = async (product) => {
  selectedProduct.value = product
  
  try {
    const response = await fetch(route('api.inventory.history', product.id))
    stockMoves.value = await response.json()
    showHistoryModal.value = true
  } catch (error) {
    console.error('Failed to load stock history:', error)
  }
}

const closeHistoryModal = () => {
  showHistoryModal.value = false
  selectedProduct.value = null
  stockMoves.value = []
}

const showBatches = async (product) => {
  selectedProduct.value = product
  
  try {
    const response = await fetch(route('api.inventory.batches', product.id))
    stockBatches.value = await response.json()
    showBatchesModal.value = true
  } catch (error) {
    console.error('Failed to load stock batches:', error)
  }
}

const closeBatchesModal = () => {
  showBatchesModal.value = false
  selectedProduct.value = null
  stockBatches.value = []
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

const getTypeColor = (type) => {
  const colors = {
    'purchase': 'bg-green-100 text-green-800',
    'sale': 'bg-red-100 text-red-800',
    'adjustment': 'bg-blue-100 text-blue-800',
    'return': 'bg-yellow-100 text-yellow-800'
  }
  return colors[type] || 'bg-gray-100 text-gray-800'
}

const isExpiringSoon = (expiryDate) => {
  if (!expiryDate) return false
  const expiry = new Date(expiryDate)
  const today = new Date()
  const diffTime = expiry - today
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays <= 30 && diffDays >= 0
}

const getBatchStatus = (batch) => {
  if (batch.quantity <= 0) return 'Consumed'
  if (isExpiringSoon(batch.expiry_date)) return 'Expiring Soon'
  return 'Active'
}

const getBatchStatusColor = (batch) => {
  const status = getBatchStatus(batch)
  const colors = {
    'Active': 'bg-green-100 text-green-800',
    'Expiring Soon': 'bg-yellow-100 text-yellow-800',
    'Consumed': 'bg-gray-100 text-gray-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const importFile = () => {
  if (!fileInput.value?.files[0]) return
  
  importing.value = true
  const formData = new FormData()
  formData.append('file', fileInput.value.files[0])
  
  router.post(route('products.import'), formData, {
    onSuccess: () => {
      showImportModal.value = false
      importing.value = false
      if (tableRef.value) {
        tableRef.value.refresh()
      }
    },
    onError: () => {
      importing.value = false
    },
    onFinish: () => {
      importing.value = false
    }
  })
}

// Filter functions for stat cards
const filterLowStock = () => {
  filters.value.stock_status = 'low_stock'
  filters.value.search = ''
  filters.value.category_id = ''
  filters.value.supplier_id = ''
  filters.value.has_batches = ''
}

const filterOutOfStock = () => {
  filters.value.stock_status = 'out_of_stock'
  filters.value.search = ''
  filters.value.category_id = ''
  filters.value.supplier_id = ''
  filters.value.has_batches = ''
}

const clearFilters = () => {
  filters.value.stock_status = ''
  filters.value.search = ''
  filters.value.category_id = ''
  filters.value.supplier_id = ''
  filters.value.has_batches = ''
}
</script>

<style scoped>
.stat-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
  background: #f8fafc;
  color: #1f2937;
}
.stat-icon--blue { background: linear-gradient(145deg, #eff6ff, #dbeafe); color: #1d4ed8; }
.stat-icon--amber { background: linear-gradient(145deg, #fff7ed, #ffedd5); color: #c2410c; }
.stat-icon--red { background: linear-gradient(145deg, #fef2f2, #fee2e2); color: #b91c1c; }
.stat-icon--green { background: linear-gradient(145deg, #ecfdf3, #d1fae5); color: #047857; }

.action-icon {
  width: 34px;
  height: 34px;
  border-radius: 9999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  color: #0f172a;
  border: 1px solid #e2e8f0;
  transition: all 150ms ease;
  box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08);
}
.action-icon:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
}
.action-icon--blue { background: #eef2ff; border-color: #c7d2fe; color: #4338ca; }
.action-icon--green { background: #ecfdf3; border-color: #bbf7d0; color: #047857; }
.action-icon--amber { background: #fff7ed; border-color: #fed7aa; color: #c2410c; }
.action-icon--purple { background: #f3e8ff; border-color: #e9d5ff; color: #7c3aed; }
</style>
