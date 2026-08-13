<template>
  <AppLayout>
    <PageHeader
      :title="`Purchase Order #${purchase.purchase_no}`"
      :subtitle="`View purchase order details`"
    >
      <template #actions>
        <div class="flex gap-2">
          <Link
            :href="route('purchases.index')"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2"
          >
            <ArrowLeft class="w-4 h-4" />
            Back
          </Link>
          <Link
            :href="route('purchases.edit', purchase.id)"
            class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 flex items-center gap-2"
          >
            <Edit class="w-4 h-4" />
            Edit
          </Link>
        </div>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Details -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Purchase Information -->
        <UiCard>
          <CardHeader>
            <h3 class="text-lg font-semibold">Purchase Information</h3>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-gray-500">PO Number</label>
                <p class="text-base font-semibold">{{ purchase.purchase_no }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">Status</label>
                <span
                  class="inline-block px-3 py-1 text-xs font-semibold rounded-full"
                  :class="{
                    'bg-yellow-100 text-yellow-800': purchase.status === 'pending',
                    'bg-blue-100 text-blue-800': purchase.status === 'ordered',
                    'bg-green-100 text-green-800': purchase.status === 'received',
                    'bg-red-100 text-red-800': purchase.status === 'cancelled'
                  }"
                >
                  {{ purchase.status.charAt(0).toUpperCase() + purchase.status.slice(1) }}
                </span>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">Supplier</label>
                <p class="text-base font-semibold">{{ purchase.supplier?.name }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">Purchase Date</label>
                <p class="text-base">{{ formatDate(purchase.purchased_at) }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">Expected Date</label>
                <p class="text-base">{{ purchase.expected_date ? formatDate(purchase.expected_date) : 'N/A' }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">Created By</label>
                <p class="text-base">{{ purchase.user?.name || 'N/A' }}</p>
              </div>
            </div>
          </CardContent>
        </UiCard>

        <!-- Purchase Items -->
        <UiCard>
          <CardHeader>
            <h3 class="text-lg font-semibold">Purchase Items</h3>
          </CardHeader>
          <CardContent>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantity</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Cost</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Received</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Line Total</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="item in purchase.purchaseItems" :key="item.id">
                    <td class="px-4 py-3 whitespace-nowrap">
                      <div class="font-medium text-gray-900">{{ item.product?.name }}</div>
                      <div v-if="item.product?.type === 'panaflex_roll'" class="text-xs text-gray-500">
                        {{ item.rolls_count }} rolls × {{ item.roll_width_inch }}" × {{ item.roll_length_meter }}m
                      </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                      {{ item.product?.sku }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                      {{ item.quantity }} {{ item.product?.unit?.abbreviation }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                      PKR {{ formatNumber(item.rate) }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                      {{ item.received_quantity || 0 }} {{ item.product?.unit?.abbreviation }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                      PKR {{ formatNumber(item.line_total) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </CardContent>
        </UiCard>

        <!-- Notes -->
        <UiCard v-if="purchase.notes">
          <CardHeader>
            <h3 class="text-lg font-semibold">Notes</h3>
          </CardHeader>
          <CardContent>
            <p class="text-gray-700 whitespace-pre-wrap">{{ purchase.notes }}</p>
          </CardContent>
        </UiCard>
      </div>

      <!-- Summary Sidebar -->
      <div class="lg:col-span-1">
        <UiCard>
          <CardHeader>
            <h3 class="text-lg font-semibold">Purchase Summary</h3>
          </CardHeader>
          <CardContent class="space-y-3">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Subtotal:</span>
              <span class="font-semibold">PKR {{ formatNumber(purchase.subtotal) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Discount:</span>
              <span class="font-semibold text-red-600">- PKR {{ formatNumber(purchase.discount_total || 0) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Tax:</span>
              <span class="font-semibold">PKR {{ formatNumber(purchase.tax_total || 0) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Other Charges:</span>
              <span class="font-semibold">PKR {{ formatNumber(purchase.other_charges || 0) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Shipping:</span>
              <span class="font-semibold">PKR {{ formatNumber(purchase.shipping_charges || 0) }}</span>
            </div>
            <div class="border-t pt-3 flex justify-between text-base">
              <span class="font-bold">Grand Total:</span>
              <span class="font-bold text-primary">PKR {{ formatNumber(purchase.grand_total) }}</span>
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
import CardContent from '@/components/CardContent.vue'
import { ArrowLeft, Edit } from 'lucide-vue-next'

const props = defineProps({
  purchase: Object
})

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatNumber = (number) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(number || 0)
}
</script>
