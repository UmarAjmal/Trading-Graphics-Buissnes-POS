<template>
  <AppLayout>
    <PageHeader
      :title="supplier.name"
      subtitle="Supplier Details and Purchase History"
    >
      <template #actions>
        <div class="flex gap-2">
          <Link
            :href="route('suppliers.edit', supplier.id)"
            class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 flex items-center gap-2"
          >
            <Edit class="w-4 h-4" />
            Edit Supplier
          </Link>
          <Link
            :href="route('purchases.create', { supplier_id: supplier.id })"
            class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 flex items-center gap-2"
          >
            <Plus class="w-4 h-4" />
            New Purchase
          </Link>
        </div>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Supplier Information -->
      <div class="lg:col-span-1">
        <UiCard>
          <CardHeader>
            <CardTitle>Supplier Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <label class="text-sm font-medium text-gray-500">Name</label>
              <p class="text-lg font-semibold">{{ supplier.name }}</p>
            </div>
            
            <div v-if="supplier.contact_person">
              <label class="text-sm font-medium text-gray-500">Contact Person</label>
              <p>{{ supplier.contact_person }}</p>
            </div>
            
            <div v-if="supplier.email">
              <label class="text-sm font-medium text-gray-500">Email</label>
              <p>{{ supplier.email }}</p>
            </div>
            
            <div v-if="supplier.phone">
              <label class="text-sm font-medium text-gray-500">Phone</label>
              <p>{{ supplier.phone }}</p>
            </div>
            
            <div v-if="supplier.whatsapp">
              <label class="text-sm font-medium text-gray-500">WhatsApp</label>
              <p>{{ supplier.whatsapp }}</p>
            </div>
            
            <div v-if="supplier.address">
              <label class="text-sm font-medium text-gray-500">Address</label>
              <p>{{ supplier.address }}</p>
            </div>
            
            <div>
              <label class="text-sm font-medium text-gray-500">Status</label>
              <span :class="supplier.is_active ? 'text-green-600' : 'text-red-600'" class="font-medium">
                {{ supplier.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            
            <div>
              <label class="text-sm font-medium text-gray-500">Added Date</label>
              <p>{{ new Date(supplier.created_at).toLocaleDateString() }}</p>
            </div>
          </CardContent>
        </UiCard>
      </div>

      <!-- Purchase History -->
      <div class="lg:col-span-2">
        <UiCard>
          <CardHeader>
            <CardTitle>Recent Purchase Orders</CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="supplier.purchases && supplier.purchases.length > 0" class="space-y-4">
              <div
                v-for="purchase in supplier.purchases"
                :key="purchase.id"
                class="border rounded-lg p-4 hover:bg-gray-50"
              >
                <div class="flex justify-between items-start">
                  <div>
                    <h4 class="font-medium">PO #{{ purchase.po_number || purchase.id }}</h4>
                    <p class="text-sm text-gray-600">
                      {{ new Date(purchase.purchased_at || purchase.created_at).toLocaleDateString() }}
                    </p>
                  </div>
                  <div class="text-right">
                    <p class="font-semibold text-lg">{{ formatCurrency(purchase.total_amount) }}</p>
                    <span
                      :class="{
                        'bg-green-100 text-green-800': purchase.status === 'completed',
                        'bg-yellow-100 text-yellow-800': purchase.status === 'pending',
                        'bg-blue-100 text-blue-800': purchase.status === 'received',
                        'bg-gray-100 text-gray-800': purchase.status === 'cancelled'
                      }"
                      class="px-2 py-1 rounded-full text-xs font-medium"
                    >
                      {{ purchase.status }}
                    </span>
                  </div>
                </div>
                <div class="mt-2">
                  <Link
                    :href="route('purchases.show', purchase.id)"
                    class="text-blue-600 hover:text-blue-800 text-sm"
                  >
                    View Details →
                  </Link>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              <Package class="w-12 h-12 mx-auto mb-4 text-gray-300" />
              <p>No purchase orders yet</p>
              <Link
                :href="route('purchases.create', { supplier_id: supplier.id })"
                class="mt-2 text-blue-600 hover:text-blue-800"
              >
                Create first purchase order
              </Link>
            </div>
          </CardContent>
        </UiCard>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import CardHeader from '@/components/CardHeader.vue'
import CardTitle from '@/components/CardTitle.vue'
import CardContent from '@/components/CardContent.vue'
import { Edit, Plus, Package } from 'lucide-vue-next'
import { formatCurrency } from '@/utils/currency'

// Route helper
const route = window.route

// Props
defineProps({
  supplier: {
    type: Object,
    required: true
  }
})
</script>