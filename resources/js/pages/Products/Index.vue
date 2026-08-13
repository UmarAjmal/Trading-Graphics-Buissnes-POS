<template>
  <AppLayout>
    <!-- Page Header -->
    <PageHeader
      title="Product Catalog"
      subtitle="Manage your product inventory with simple items and panaflex rolls"
    >
      <!-- Header Actions -->
      <div class="flex flex-col sm:flex-row gap-3">
        <!-- Import Button -->
        <button
          @click="showImportModal = true"
          class="icon-btn icon-btn--secondary icon-btn--modern text-sm order-3 sm:order-1 group"
        >
          <div class="icon-container">
            <ModernIcon name="upload" class="w-4 h-4" />
          </div>
          Import CSV
        </button>

        <!-- Export Button -->
        <button
          @click="exportProducts"
          :disabled="exporting"
          class="icon-btn icon-btn--secondary icon-btn--modern text-sm order-2 group"
        >
          <div class="icon-container" :class="{ 'animate-pulse': exporting }">
            <ModernIcon name="arrow-down" class="w-4 h-4" />
          </div>
          {{ exporting ? 'Exporting...' : 'Export CSV' }}
        </button>

        <!-- Add Product Button -->
        <button
          @click="$inertia.visit(route('products.create'))"
          class="icon-btn icon-btn--primary icon-btn--gradient text-sm order-1 sm:order-3 group"
        >
          <div class="icon-container">
            <ModernIcon name="plus" class="w-4 h-4" />
          </div>
          Add Product
        </button>
      </div>
    </PageHeader>

    <!-- Quick Add Product Button -->
    <div class="mb-6">
      <button
        @click="$inertia.visit(route('products.create'))"
        class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors flex items-center shadow-lg"
      >
        <ModernIcon name="plus" class="w-5 h-5 mr-2" />
        Add New Product
      </button>
    </div>

    <!-- Filters Card -->
    <UiCard padding="lg" class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Search -->
        <UiInput
          v-model="filters.search"
          placeholder="Search products..."
          :debounce="500"
          @update:modelValue="applyFilters"
        >
          <template #prefix>
            <ModernIcon name="search" class="w-4 h-4 text-gray-400" />
          </template>
        </UiInput>

        <!-- Category Filter -->
        <UiSelect
          v-model="filters.category_id"
          placeholder="All Categories"
          :options="categoryOptions"
          @update:modelValue="applyFilters"
        />

        <!-- Type Filter -->
        <UiSelect
          v-model="filters.type"
          placeholder="All Types"
          :options="typeOptions"
          @update:modelValue="applyFilters"
        />

        <!-- Status Filter -->
        <UiSelect
          v-model="filters.is_active"
          placeholder="All Status"
          :options="statusOptions"
          @update:modelValue="applyFilters"
        />
      </div>

      <!-- Filter Summary -->
      <div v-if="hasActiveFilters" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex flex-wrap gap-2">
          <span class="text-sm text-gray-500 dark:text-gray-400">Active filters:</span>
          
          <span v-if="filters.search" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
            Search: "{{ filters.search }}"
            <button @click="filters.search = ''; applyFilters()" class="ml-1.5 inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-blue-200 dark:hover:bg-blue-800">
              <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
              </svg>
            </button>
          </span>

          <button
            @click="clearFilters"
            class="inline-flex items-center px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            Clear all
          </button>
        </div>
      </div>
    </UiCard>

    <!-- Products Table -->
    <UiCard>
      <!-- Table Header -->
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            Products ({{ tableData.total || 0 }})
          </h3>
          
          <!-- Per Page Selector -->
          <UiSelect
            v-model="filters.per_page"
            :options="perPageOptions"
            size="sm"
            @update:modelValue="applyFilters"
            class="w-24"
          />
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Product
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                SKU
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Category
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Type
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Price
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Stock
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
            <!-- Loading State -->
            <tr v-if="loading">
              <td colspan="8" class="px-6 py-12 text-center">
                <div class="flex items-center justify-center">
                  <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
                  <span class="ml-3 text-sm text-gray-500 dark:text-gray-400">Loading products...</span>
                </div>
              </td>
            </tr>

            <!-- No Data State -->
            <tr v-else-if="!tableData.data?.length">
              <td colspan="8" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                  <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m8-8v2m-3 3h6m-6 4h6M8 17h8"/>
                  </svg>
                  <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No products found</h3>
                  <p class="text-gray-500 dark:text-gray-400 mb-4">Get started by adding your first product.</p>
                  <UiButton
                    variant="primary"
                    size="sm"
                    @click="$inertia.visit(route('products.create'))"
                  >
                    Add First Product
                  </UiButton>
                </div>
              </td>
            </tr>

            <!-- Product Rows -->
            <tr v-for="product in tableData.data" :key="product.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
              <!-- Product Info -->
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <img
                      v-if="product.image_url"
                      class="h-10 w-10 rounded object-cover"
                      :src="product.image_url"
                      :alt="product.name"
                    >
                    <div v-else class="h-10 w-10 rounded bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                      <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m8-8v2m-3 3h6m-6 4h6M8 17h8"/>
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ product.name }}
                    </div>
                    <div v-if="product.description" class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                      {{ product.description }}
                    </div>
                  </div>
                </div>
              </td>

              <!-- SKU -->
              <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-white">
                {{ product.sku }}
              </td>

              <!-- Category -->
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                  {{ product.category?.name || '-' }}
                </span>
              </td>

              <!-- Type -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="product.type === 'simple' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'">
                  {{ product.type === 'simple' ? 'Simple' : 'Panaflex Roll' }}
                </span>
              </td>

              <!-- Price -->
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                <div class="font-medium">PKR {{ formatNumber(product.selling_price) }}</div>
                <div v-if="product.cost_price" class="text-xs text-gray-500 dark:text-gray-400">
                  Cost: PKR {{ formatNumber(product.cost_price) }}
                </div>
              </td>

              <!-- Stock -->
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <div class="flex items-center">
                  <span class="font-medium" :class="getStockColorClass(product.current_stock, product.min_stock)">
                    {{ formatNumber(product.current_stock) }}
                  </span>
                  <span class="ml-1 text-gray-500 dark:text-gray-400">{{ product.unit?.symbol || '' }}</span>
                </div>
                <div v-if="product.min_stock > 0" class="text-xs text-gray-500 dark:text-gray-400">
                  Min: {{ formatNumber(product.min_stock) }}
                </div>
              </td>

              <!-- Status -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="product.active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'">
                  {{ product.active ? 'Active' : 'Inactive' }}
                </span>
              </td>

              <!-- Actions -->
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-2">
                  <!-- View Button -->
                  <UiButton
                    variant="ghost"
                    size="sm"
                    @click="$inertia.visit(route('products.show', product.id))"
                    class="text-blue-600 hover:text-blue-700"
                  >
                    <ModernIcon name="eye" class="w-4 h-4" />
                  </UiButton>

                  <!-- Edit Button -->
                  <UiButton
                    variant="ghost"
                    size="sm"
                    @click="$inertia.visit(route('products.edit', product.id))"
                    class="text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                  >
                    <ModernIcon name="edit" class="w-4 h-4" />
                  </UiButton>

                  <!-- Barcode Button -->
                  <UiButton
                    variant="ghost"
                    size="sm"
                    @click="printBarcode(product)"
                    class="text-purple-600 hover:text-purple-700"
                    title="Print Barcode"
                  >
                    <ModernIcon name="barcode" class="w-4 h-4" />
                  </UiButton>

                  <!-- Delete Button -->
                  <UiButton
                    variant="ghost"
                    size="sm"
                    @click="confirmDelete(product)"
                    class="text-red-600 hover:text-red-700"
                  >
                    <ModernIcon name="trash-can" class="w-4 h-4" />
                  </UiButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="tableData.data?.length && tableData.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div class="flex-1 flex justify-between sm:hidden">
            <UiButton
              variant="outline"
              size="sm"
              @click="goToPage(tableData.current_page - 1)"
              :disabled="tableData.current_page <= 1"
            >
              Previous
            </UiButton>
            <UiButton
              variant="outline"
              size="sm"
              @click="goToPage(tableData.current_page + 1)"
              :disabled="tableData.current_page >= tableData.last_page"
            >
              Next
            </UiButton>
          </div>
          
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700 dark:text-gray-300">
                Showing
                <span class="font-medium">{{ tableData.from || 0 }}</span>
                to
                <span class="font-medium">{{ tableData.to || 0 }}</span>
                of
                <span class="font-medium">{{ tableData.total || 0 }}</span>
                results
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                <!-- Previous Button -->
                <button
                  @click="goToPage(tableData.current_page - 1)"
                  :disabled="tableData.current_page <= 1"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                >
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                </button>

                <!-- Page Numbers -->
                <template v-for="page in pageNumbers" :key="page">
                  <button
                    v-if="page !== '...'"
                    @click="goToPage(page)"
                    :class="[
                      page === tableData.current_page
                        ? 'z-10 bg-primary-50 dark:bg-primary-900 border-primary-500 text-primary-600 dark:text-primary-400'
                        : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700',
                      'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                    ]"
                  >
                    {{ page }}
                  </button>
                  <span
                    v-else
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300"
                  >
                    ...
                  </span>
                </template>

                <!-- Next Button -->
                <button
                  @click="goToPage(tableData.current_page + 1)"
                  :disabled="tableData.current_page >= tableData.last_page"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                >
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                  </svg>
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </UiCard>

    <!-- Import Modal -->
    <UiModal
      v-model="showImportModal"
      title="Import Products from CSV"
      size="lg"
    >
      <div class="space-y-6">
        <!-- Instructions -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4">
          <div class="flex">
            <svg class="flex-shrink-0 w-5 h-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">CSV Import Instructions</h3>
              <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                <p class="mb-2">Your CSV file should have the following columns:</p>
                <ul class="list-disc list-inside space-y-1 text-xs">
                  <li><strong>name</strong> - Product name (required)</li>
                  <li><strong>sku</strong> - Product SKU (optional, auto-generated if empty)</li>
                  <li><strong>type</strong> - simple or panaflex_roll</li>
                  <li><strong>category</strong> - Category name</li>
                  <li><strong>unit</strong> - Unit name</li>
                  <li><strong>cost_price</strong> - Cost price in PKR</li>
                  <li><strong>selling_price</strong> - Selling price in PKR (required)</li>
                  <li><strong>current_stock</strong> - Current stock quantity</li>
                  <li><strong>min_stock</strong> - Minimum stock level</li>
                  <li><strong>description</strong> - Product description</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- File Upload -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Select CSV File
          </label>
          <input
            ref="csvFileInput"
            type="file"
            accept=".csv"
            @change="handleFileSelect"
            class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 dark:file:bg-primary-900 file:text-primary-700 dark:file:text-primary-300 hover:file:bg-primary-100 dark:hover:file:bg-primary-800"
          />
        </div>

        <!-- Options -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <label class="flex items-center">
            <input
              v-model="importOptions.create_categories"
              type="checkbox"
              class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Create new categories</span>
          </label>
          
          <label class="flex items-center">
            <input
              v-model="importOptions.create_units"
              type="checkbox"
              class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Create new units</span>
          </label>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3">
          <UiButton
            variant="outline"
            @click="showImportModal = false"
          >
            Cancel
          </UiButton>
          <UiButton
            variant="primary"
            @click="importProducts"
            :loading="importing"
            :disabled="!selectedFile"
          >
            Import Products
          </UiButton>
        </div>
      </template>
    </UiModal>

    <!-- Delete Confirmation Modal -->
    <UiModal
      v-model="showDeleteModal"
      title="Delete Product"
      size="md"
    >
      <div class="space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Are you sure you want to delete <strong>{{ productToDelete?.name }}</strong>?
          This action cannot be undone.
        </p>
        
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-4">
          <div class="flex">
            <svg class="flex-shrink-0 w-5 h-5 text-red-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Warning</h3>
              <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                This will permanently delete the product and all associated data.
              </p>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3">
          <UiButton
            variant="outline"
            @click="showDeleteModal = false"
          >
            Cancel
          </UiButton>
          <UiButton
            variant="danger"
            @click="deleteProduct"
            :loading="deleting"
          >
            Delete Product
          </UiButton>
        </div>
      </template>
    </UiModal>

    <!-- Barcode Modal -->
    <BarcodeModal
      :is-open="showBarcodeModal"
      :product="selectedProduct"
      @close="showBarcodeModal = false"
      @print="handleBarcodePrint"
    />
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

// Route helper
const route = window.route

// Components
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiButton from '@/components/UiButton.vue'
import UiInput from '@/components/UiInput.vue'
import UiSelect from '@/components/UiSelect.vue'
import UiCard from '@/components/UiCard.vue'
import UiModal from '@/components/UiModal.vue'
import BarcodeModal from '@/components/BarcodeModal.vue'
import ModernIcon from '@/components/ModernIcon.vue'

// Props
const props = defineProps({
  categories: {
    type: Array,
    default: () => []
  },
  units: {
    type: Array,
    default: () => []
  }
})

// State
const loading = ref(false)
const exporting = ref(false)
const importing = ref(false)
const deleting = ref(false)

const showImportModal = ref(false)
const showDeleteModal = ref(false)
const showBarcodeModal = ref(false)
const selectedFile = ref(null)
const productToDelete = ref(null)
const selectedProduct = ref(null)

const tableData = ref({
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0
})

const filters = reactive({
  search: '',
  category_id: '',
  type: '',
  is_active: '',
  page: 1,
  per_page: 15
})

const importOptions = reactive({
  create_categories: true,
  create_units: true
})

// Computed
const categoryOptions = computed(() => {
  return props.categories.map(category => ({
    value: category.id,
    label: category.name
  }))
})

const typeOptions = computed(() => [
  { value: 'simple', label: 'Simple Product' },
  { value: 'panaflex_roll', label: 'Panaflex Roll' }
])

const statusOptions = computed(() => [
  { value: '1', label: 'Active' },
  { value: '0', label: 'Inactive' }
])

const perPageOptions = computed(() => [
  { value: 10, label: '10' },
  { value: 15, label: '15' },
  { value: 25, label: '25' },
  { value: 50, label: '50' },
  { value: 100, label: '100' }
])

const hasActiveFilters = computed(() => {
  return filters.search || filters.category_id || filters.type || filters.is_active
})

const pageNumbers = computed(() => {
  const pages = []
  const current = tableData.value.current_page
  const last = tableData.value.last_page
  
  if (last <= 7) {
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    pages.push(1)
    
    if (current > 4) {
      pages.push('...')
    }
    
    const start = Math.max(2, current - 1)
    const end = Math.min(last - 1, current + 1)
    
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
    
    if (current < last - 3) {
      pages.push('...')
    }
    
    if (last > 1) {
      pages.push(last)
    }
  }
  
  return pages
})

// Methods
const loadTableData = async () => {
  loading.value = true
  
  try {
    const params = new URLSearchParams()
    Object.keys(filters).forEach(key => {
      if (filters[key] !== '' && filters[key] !== null) {
        params.append(key, filters[key])
      }
    })
    
    const { data } = await axios.get(`/api/products/table?${params}`)
    
    if (data.success) {
      tableData.value = data.data
    }
  } catch (error) {
    console.error('Failed to load products:', error)
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  filters.page = 1
  loadTableData()
}

const clearFilters = () => {
  Object.assign(filters, {
    search: '',
    category_id: '',
    type: '',
    is_active: '',
    page: 1,
    per_page: 15
  })
  loadTableData()
}

const goToPage = (page) => {
  if (page >= 1 && page <= tableData.value.last_page) {
    filters.page = page
    loadTableData()
  }
}

const exportProducts = async () => {
  exporting.value = true
  
  try {
    window.open('/products/export', '_blank')
  } catch (error) {
    console.error('Failed to export products:', error)
  } finally {
    exporting.value = false
  }
}

const handleFileSelect = (event) => {
  selectedFile.value = event.target.files[0]
}

const importProducts = async () => {
  if (!selectedFile.value) return
  
  importing.value = true
  
  try {
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    formData.append('create_categories', importOptions.create_categories ? '1' : '0')
    formData.append('create_units', importOptions.create_units ? '1' : '0')
    
    const { data } = await axios.post('/products/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    if (data.success) {
      showImportModal.value = false
      selectedFile.value = null
      // Reset file input
      if (this.$refs.csvFileInput) {
        this.$refs.csvFileInput.value = ''
      }
      
      // Show success message and reload
      alert(`Import completed successfully!\n\nCreated: ${data.summary.created}\nUpdated: ${data.summary.updated}\nSkipped: ${data.summary.skipped}`)
      loadTableData()
    } else {
      alert(`Import failed: ${data.message}`)
    }
  } catch (error) {
    console.error('Import failed:', error)
    alert('Import failed. Please check the file format and try again.')
  } finally {
    importing.value = false
  }
}

const printBarcode = (product) => {
  selectedProduct.value = product
  showBarcodeModal.value = true
}

const handleBarcodePrint = ({ quantity, layout }) => {
  if (!selectedProduct.value) return
  
  const url = `/products/${selectedProduct.value.id}/barcode?quantity=${quantity}&layout=${layout}`
  window.open(url, '_blank')
}

const confirmDelete = (product) => {
  productToDelete.value = product
  showDeleteModal.value = true
}

const deleteProduct = async () => {
  if (!productToDelete.value) return
  
  deleting.value = true
  
  try {
    const response = await axios.delete(`/api/products/${productToDelete.value.id}`)
    
    if (response.data.success) {
      showDeleteModal.value = false
      productToDelete.value = null
      loadTableData()
      alert('Product deleted successfully!')
    } else {
      alert('Failed to delete product: ' + response.data.message)
    }
  } catch (error) {
    console.error('Failed to delete product:', error)
    alert('Failed to delete product: ' + (error.response?.data?.message || error.message))
  } finally {
    deleting.value = false
  }
}

const formatNumber = (value) => {
  if (value === null || value === undefined) return '0'
  return Number(value).toLocaleString('en-PK', { maximumFractionDigits: 2 })
}

const getStockColorClass = (current, min) => {
  if (current <= 0) return 'text-red-600 dark:text-red-400'
  if (current <= min) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-gray-900 dark:text-white'
}

// Lifecycle
onMounted(() => {
  loadTableData()
})

// Watch for filter changes with debounce
watch(() => filters.search, () => {
  // The debounce is handled by the UiInput component
}, { immediate: false })
</script>