<template>
  <AppLayout>
    <PageHeader
      :title="product.name"
      subtitle="Product Details"
    >
      <div class="flex gap-3">
        <UiButton
          variant="outline"
          @click="$inertia.visit(route('products.index'))"
        >
          Back to Products
        </UiButton>
        <UiButton
          variant="primary"
          @click="$inertia.visit(route('products.edit', product.id))"
        >
          Edit Product
        </UiButton>
      </div>
    </PageHeader>

    <div class="max-w-4xl mx-auto">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Product Image -->
        <div class="lg:col-span-1">
          <UiCard padding="lg">
            <div class="aspect-square bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
              <img
                v-if="product.image_url"
                :src="product.image_url"
                :alt="product.name"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              </div>
            </div>
          </UiCard>
        </div>

        <!-- Product Details -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information -->
          <UiCard title="Basic Information" padding="lg">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Name</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ product.name }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">SKU</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ product.sku }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                  :class="product.type === 'simple' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'">
                  {{ product.type === 'simple' ? 'Simple Product' : 'Panaflex Roll' }}
                </span>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="product.active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'">
                  {{ product.active ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <div v-if="product.category">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ product.category.name }}</p>
              </div>
              <div v-if="product.unit">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unit</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ product.unit.name }}</p>
              </div>
              <div v-if="product.barcode">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Barcode</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ product.barcode }}</p>
              </div>
            </div>
            
            <div v-if="product.description" class="mt-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ product.description }}</p>
            </div>
          </UiCard>

          <!-- Pricing Information -->
          <UiCard title="Pricing Information" padding="lg">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Selling Price</label>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">PKR {{ formatNumber(product.selling_price) }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cost Price</label>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">PKR {{ formatNumber(product.cost_price) }}</p>
              </div>
            </div>
          </UiCard>

          <!-- Stock Information -->
          <UiCard title="Stock Information" padding="lg">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Current Stock</label>
                <p class="mt-1 text-lg font-semibold" 
                  :class="product.current_stock > product.min_stock ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                  {{ formatNumber(product.type === 'panaflex_roll' ? product.current_stock_ft : product.current_stock) }}
                  <span class="text-sm font-normal text-gray-500">{{ product.unit?.name || (product.type === 'panaflex_roll' ? 'sq.ft' : 'pcs') }}</span>
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Minimum Stock</label>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                  {{ formatNumber(product.min_stock) }}
                  <span class="text-sm font-normal text-gray-500">{{ product.unit?.name || (product.type === 'panaflex_roll' ? 'meters' : 'pcs') }}</span>
                </p>
              </div>
            </div>
          </UiCard>

          <!-- Panaflex Specifications (if applicable) -->
          <UiCard v-if="product.type === 'panaflex_roll' && product.panaflex_spec" title="Panaflex Specifications" padding="lg">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Roll Width</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ product.panaflex_spec.roll_width_ft }} ft</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Roll Length</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ product.panaflex_spec.roll_length_ft }} ft</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rate per Sq Ft</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">PKR {{ formatNumber(product.panaflex_spec.rate_per_sqft) }}</p>
              </div>
              <div v-if="product.panaflex_spec.quality">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quality</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ product.panaflex_spec.quality }}</p>
              </div>
              <div v-if="product.panaflex_spec.color">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Color</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ product.panaflex_spec.color }}</p>
              </div>
              <div v-if="product.panaflex_spec.gsm">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">GSM</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ product.panaflex_spec.gsm }}</p>
              </div>
              <div v-if="product.panaflex_spec.finish">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Finish</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white capitalize">{{ product.panaflex_spec.finish }}</p>
              </div>
            </div>
          </UiCard>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiButton from '@/components/UiButton.vue'
import UiCard from '@/components/UiCard.vue'

// Props
const props = defineProps({
  product: {
    type: Object,
    required: true
  }
})

// Helper functions
const formatNumber = (value) => {
  if (value === null || value === undefined) return '0'
  return Number(value).toLocaleString('en-PK', { 
    minimumFractionDigits: 0,
    maximumFractionDigits: 2 
  })
}
</script>