<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto space-y-6">
      <!-- Page Header & Actions -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <div class="flex items-center gap-2">
            <Link
              :href="route('returns.purchases.index')"
              class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
              </svg>
            </Link>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
              Purchase Return #{{ purchaseReturn.return_no }}
            </h1>
            <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 uppercase">
              Stock Deducted & Settled
            </span>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-9">
            Processed on {{ formatDate(purchaseReturn.returned_at) }} at {{ formatTime(purchaseReturn.returned_at) }} by {{ purchaseReturn.user?.name || 'Admin' }}
          </p>
        </div>

        <!-- Print Action Buttons -->
        <div class="flex items-center gap-2">
          <a
            :href="route('prints.purchase-return.a4', purchaseReturn.id)"
            target="_blank"
            class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium flex items-center gap-2 shadow-sm transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print A4 Memo
          </a>
          <a
            :href="route('prints.purchase-return.80mm', purchaseReturn.id)"
            target="_blank"
            class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium flex items-center gap-2 shadow-sm transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Print Thermal (80mm)
          </a>

          <button
            type="button"
            @click="deleteReturn"
            class="px-3.5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium flex items-center gap-2 shadow-sm transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Delete & Reverse
          </button>
        </div>
      </div>

      <!-- Overview Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Return Details -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
          <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
            Return Info
          </h3>
          <div class="space-y-1.5 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Return No:</span>
              <span class="font-bold text-gray-900 dark:text-white">{{ purchaseReturn.return_no }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Purchase PO:</span>
              <span class="font-semibold text-amber-600 dark:text-amber-400">{{ purchaseReturn.purchase?.purchase_no || 'Direct Return' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Original Total:</span>
              <span class="font-medium text-gray-900 dark:text-white">PKR {{ formatNumber(purchaseReturn.purchase?.grand_total) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Processed By:</span>
              <span class="text-gray-800 dark:text-gray-200">{{ purchaseReturn.user?.name || 'Admin' }}</span>
            </div>
          </div>
        </div>

        <!-- Supplier Details -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
          <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
            Supplier Information
          </h3>
          <div class="space-y-1.5 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Name:</span>
              <span class="font-bold text-gray-900 dark:text-white">{{ purchaseReturn.supplier?.name || 'Direct Supplier' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Phone:</span>
              <span class="text-gray-800 dark:text-gray-200">{{ purchaseReturn.supplier?.phone || '-' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Current Ledger:</span>
              <span class="font-bold text-gray-900 dark:text-white">
                PKR {{ formatNumber(purchaseReturn.supplier?.balance || 0) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Settlement Details -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
          <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
            Refund Settlement
          </h3>
          <div class="space-y-1.5 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Method:</span>
              <span class="inline-block px-2 py-0.5 rounded text-xs font-bold uppercase bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                {{ purchaseReturn.refund_type }}
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Stock Deducted:</span>
              <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Yes (Batches Deducted)</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Total Return:</span>
              <span class="font-extrabold text-amber-600 dark:text-amber-400 text-base">
                PKR {{ formatNumber(purchaseReturn.grand_total) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Reason Banner -->
      <div class="bg-amber-50/50 dark:bg-amber-950/20 rounded-xl border border-amber-200 dark:border-amber-900/50 p-4">
        <span class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider block mb-1">
          Return Reason / Notes:
        </span>
        <p class="text-sm text-gray-800 dark:text-gray-200 font-medium">
          {{ purchaseReturn.reason }}
        </p>
      </div>

      <!-- Returned Items Table -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
            Returned Items Breakdown
          </h2>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
              <tr>
                <th class="py-3 px-4 text-left">Product Item</th>
                <th class="py-3 px-4 text-center">Returned Qty / Rolls</th>
                <th class="py-3 px-4 text-right">Unit Rate</th>
                <th class="py-3 px-4 text-right">Line Total</th>
                <th class="py-3 px-4 text-left">Note</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm">
              <tr v-for="item in purchaseReturn.items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                <td class="py-3 px-4">
                  <div class="font-semibold text-gray-900 dark:text-white">
                    {{ item.product?.name || 'Product' }}
                  </div>
                  <div v-if="item.product?.type === 'panaflex_roll'" class="text-xs text-purple-600 dark:text-purple-400">
                    Panaflex Roll
                    <span v-if="item.width_input && item.length_input" class="text-gray-500 font-normal ml-1">
                      ({{ item.width_input }}{{ item.width_unit || 'in' }} × {{ item.length_input }}{{ item.length_unit || 'm' }})
                    </span>
                  </div>
                </td>
                <td class="py-3 px-4 text-center font-bold text-gray-900 dark:text-white whitespace-nowrap">
                  <span v-if="item.product?.type === 'panaflex_roll'">
                    <span v-if="item.units_sqft > 0">{{ formatNumber(item.units_sqft) }} sq.ft</span>
                    <span v-else>{{ formatNumber(item.rolls_count) }} Rolls</span>
                  </span>
                  <span v-else>
                    {{ formatNumber(item.quantity) }} {{ item.product?.unit?.symbol || 'pcs' }}
                  </span>
                </td>
                <td class="py-3 px-4 text-right text-gray-700 dark:text-gray-300 whitespace-nowrap">
                  PKR {{ formatNumber(item.rate) }}
                </td>
                <td class="py-3 px-4 text-right font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">
                  PKR {{ formatNumber(item.line_total) }}
                </td>
                <td class="py-3 px-4 text-xs text-gray-500 dark:text-gray-400">
                  {{ item.note || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Totals Footer -->
        <div class="p-5 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-200 dark:border-gray-700 flex justify-end">
          <div class="w-72 space-y-2 text-sm">
            <div class="flex justify-between text-gray-600 dark:text-gray-400">
              <span>Items Subtotal:</span>
              <span class="font-medium text-gray-900 dark:text-white">PKR {{ formatNumber(purchaseReturn.subtotal) }}</span>
            </div>
            <div v-if="purchaseReturn.other_adjustments != 0" class="flex justify-between text-gray-600 dark:text-gray-400">
              <span>Adjustments:</span>
              <span class="font-medium text-gray-900 dark:text-white">PKR {{ formatNumber(purchaseReturn.other_adjustments) }}</span>
            </div>
            <div class="pt-2 border-t border-gray-200 dark:border-gray-600 flex justify-between items-center text-base font-bold text-gray-900 dark:text-white">
              <span>Total Return:</span>
              <span class="text-xl font-extrabold text-amber-600 dark:text-amber-400">
                PKR {{ formatNumber(purchaseReturn.grand_total) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  purchaseReturn: Object,
})

const deleteReturn = () => {
  if (confirm(`Are you sure you want to delete and reverse Purchase Return #${props.purchaseReturn.return_no}? This will restock the returned goods back to inventory and restore our payable liability in the supplier ledger.`)) {
    router.delete(route('returns.purchases.destroy', props.purchaseReturn.id))
  }
}

const formatNumber = (num) => {
  const val = Number(num) || 0
  return val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (d) => {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatTime = (d) => {
  if (!d) return ''
  return new Date(d).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}
</script>
