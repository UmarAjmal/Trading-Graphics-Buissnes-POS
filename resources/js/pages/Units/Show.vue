<template>
  <AppLayout>
    <PageHeader
      :title="`Unit: ${unit.name}`"
      subtitle="View unit details and associated products"
    >
      <template #actions>
        <div class="flex gap-2">
          <Link
            :href="route('units.edit', unit.id)"
            class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 flex items-center gap-2"
          >
            <Edit class="w-4 h-4" />
            Edit Unit
          </Link>
          <Link
            :href="route('units.index')"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2"
          >
            <ArrowLeft class="w-4 h-4" />
            Back to Units
          </Link>
        </div>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Unit Details -->
      <UiCard title="Unit Information">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ unit.name }}</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Code</label>
            <p class="text-gray-900 dark:text-white font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded inline-block">{{ unit.code }}</p>
          </div>
          
          <div v-if="unit.symbol">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Symbol</label>
            <p class="text-gray-900 dark:text-white">{{ unit.symbol }}</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Products Count</label>
            <p class="text-gray-900 dark:text-white">{{ unit.products?.length || 0 }} products</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Created</label>
            <p class="text-gray-900 dark:text-white">{{ new Date(unit.created_at).toLocaleDateString() }}</p>
          </div>
        </div>
      </UiCard>

      <!-- Associated Products -->
      <UiCard title="Associated Products" v-if="unit.products && unit.products.length > 0">
        <div class="space-y-3">
          <div
            v-for="product in unit.products"
            :key="product.id"
            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
          >
            <div>
              <h4 class="font-medium text-gray-900 dark:text-white">{{ product.name }}</h4>
              <p class="text-sm text-gray-600 dark:text-gray-400">SKU: {{ product.sku }}</p>
            </div>
            <div class="text-right">
              <p class="font-semibold text-primary">{{ product.selling_price }} PKR</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">Stock: {{ product.stock_quantity }}</p>
            </div>
          </div>
        </div>
        
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
          <Link
            :href="route('products.index', { unit: unit.id })"
            class="text-primary hover:text-primary/80 text-sm font-medium"
          >
            View all products with this unit →
          </Link>
        </div>
      </UiCard>
      
      <!-- No Products Message -->
      <UiCard title="Associated Products" v-else>
        <div class="text-center py-8">
          <div class="text-gray-400 mb-2">
            <Package class="w-12 h-12 mx-auto" />
          </div>
          <p class="text-gray-600 dark:text-gray-400">No products are using this unit yet.</p>
        </div>
      </UiCard>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import { Edit, ArrowLeft, Package } from 'lucide-vue-next'

// Route helper
const route = window.route

// Props
const props = defineProps({
  unit: {
    type: Object,
    required: true
  }
})
</script>