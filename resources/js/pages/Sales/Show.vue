<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto space-y-6">
      
      <!-- Top Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="flex items-center space-x-3">
          <Link
            :href="route('sales.index')"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition"
            title="Back to Sales History"
          >
            <ArrowLeftIcon class="h-6 w-6" />
          </Link>
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
                {{ sale.invoice_no }}
              </h1>
              <span
                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider',
                  sale.payment_type === 'cash' 
                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' 
                    : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300'
                ]"
              >
                {{ sale.payment_type === 'cash' ? 'Cash Sale' : 'Credit Sale' }}
              </span>
              <span
                v-if="hasReturns"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 uppercase tracking-wider"
              >
                Has Returns ({{ sale.returns.length }})
              </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
              Invoice Details & Transaction History
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <!-- Print A4 -->
          <button
            @click="printA4(false)"
            class="inline-flex items-center px-3.5 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl font-semibold text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-sm transition"
          >
            <DocumentTextIcon class="h-4 w-4 mr-1.5 text-blue-600 dark:text-blue-400" />
            Print A4
          </button>

          <!-- Print Thermal 80mm -->
          <button
            @click="print80mm(false)"
            class="inline-flex items-center px-3.5 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl font-semibold text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-sm transition"
          >
            <ReceiptPercentIcon class="h-4 w-4 mr-1.5 text-emerald-600 dark:text-emerald-400" />
            Print 80mm
          </button>

          <!-- Create Return Button -->
          <button
            @click="createReturn"
            :disabled="!canCreateReturn"
            class="inline-flex items-center px-4 py-2 bg-rose-600 hover:bg-rose-700 disabled:opacity-40 text-white rounded-xl font-bold text-xs uppercase tracking-wider shadow-md hover:shadow-lg transition"
          >
            <ArrowUturnLeftIcon class="h-4 w-4 mr-1.5" />
            Process Return
          </button>
        </div>
      </div>

      <!-- Sale Info Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Basic Info -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Sale Information</h3>
            <span class="p-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
              <DocumentTextIcon class="w-4 h-4" />
            </span>
          </div>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500 dark:text-gray-400">Date/Time:</span>
              <span class="font-medium text-gray-900 dark:text-white">{{ formatDate(sale.sold_at) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500 dark:text-gray-400">Cashier:</span>
              <span class="font-medium text-gray-900 dark:text-white">{{ sale.user?.name || 'Unknown' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500 dark:text-gray-400">Payment Type:</span>
              <span class="font-bold text-gray-900 dark:text-white uppercase">{{ sale.payment_type }}</span>
            </div>
          </div>
        </div>

        <!-- Customer Info -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Customer Details</h3>
            <span class="p-1.5 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-lg">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
            </span>
          </div>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500 dark:text-gray-400">Name:</span>
              <span class="font-bold text-gray-900 dark:text-white">{{ sale.customer?.name || 'Walk-in Customer' }}</span>
            </div>
            <div v-if="sale.customer?.phone" class="flex justify-between">
              <span class="text-gray-500 dark:text-gray-400">Phone:</span>
              <span class="font-medium text-gray-900 dark:text-white">{{ sale.customer.phone }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500 dark:text-gray-400">Ledger Balance:</span>
              <span
                :class="[
                  'font-bold',
                  (sale.customer?.balance || 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'
                ]"
              >
                PKR {{ formatAmount(sale.customer?.balance || 0) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Returns Summary -->
        <div
          :class="[
            'rounded-2xl border p-5 shadow-sm space-y-3',
            hasReturns
              ? 'bg-rose-50/50 dark:bg-rose-950/20 border-rose-200 dark:border-rose-900/50'
              : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700'
          ]"
        >
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Returns Status</h3>
            <span
              :class="[
                'p-1.5 rounded-lg',
                hasReturns ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/50 dark:text-rose-300' : 'bg-gray-100 text-gray-400 dark:bg-gray-700'
              ]"
            >
              <ArrowUturnLeftIcon class="w-4 h-4" />
            </span>
          </div>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500 dark:text-gray-400">Total Returns:</span>
              <span class="font-bold text-gray-900 dark:text-white">{{ sale.returns?.length || 0 }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500 dark:text-gray-400">Refunded Amount:</span>
              <span class="font-extrabold text-rose-600 dark:text-rose-400">
                PKR {{ formatAmount(totalReturnedAmount) }}
              </span>
            </div>
            <div class="flex justify-between pt-1 border-t border-gray-200 dark:border-gray-700">
              <span class="text-gray-500 dark:text-gray-400">Net Invoice Bill:</span>
              <span class="font-black text-gray-900 dark:text-white">
                PKR {{ formatAmount(sale.grand_total - totalReturnedAmount) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Financial Totals -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Financial Totals</h3>
            <span class="p-1.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-lg">
              <ReceiptPercentIcon class="w-4 h-4" />
            </span>
          </div>
          <div class="space-y-1.5 text-xs">
            <div class="flex justify-between text-gray-600 dark:text-gray-400">
              <span>Subtotal:</span>
              <span class="font-medium text-gray-900 dark:text-white">PKR {{ formatAmount(sale.subtotal) }}</span>
            </div>
            <div v-if="sale.discount_total > 0" class="flex justify-between text-rose-600">
              <span>Discount:</span>
              <span>-PKR {{ formatAmount(sale.discount_total) }}</span>
            </div>
            <div v-if="sale.tax_total > 0" class="flex justify-between text-gray-600 dark:text-gray-400">
              <span>Tax:</span>
              <span>PKR {{ formatAmount(sale.tax_total) }}</span>
            </div>
            <div v-if="sale.other_charges > 0" class="flex justify-between text-gray-600 dark:text-gray-400">
              <span>Other:</span>
              <span>PKR {{ formatAmount(sale.other_charges) }}</span>
            </div>
            <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700 text-sm">
              <span class="font-bold text-gray-900 dark:text-white">Grand Total:</span>
              <span class="font-black text-gray-900 dark:text-white">PKR {{ formatAmount(sale.grand_total) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- System Description / Internal Note -->
      <div v-if="sale.system_description" class="bg-blue-50/80 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900/40 rounded-2xl p-4 text-sm text-blue-900 dark:text-blue-200">
        <span class="font-bold text-xs uppercase tracking-wider text-blue-700 dark:text-blue-300 block mb-1">
          System Description / Internal Note
        </span>
        <p class="whitespace-pre-wrap">{{ sale.system_description }}</p>
      </div>

      <!-- Section 1: Sale Items (With Exact Return Status & Net Quantities) -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
          <div>
            <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <span>Original Sold Items</span>
              <span class="text-xs px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold">
                {{ sale.sale_items?.length || 0 }} Items
              </span>
            </h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
              Breakdown of sold items, returned quantities, and net remaining quantities
            </p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
              <tr>
                <th class="py-3 px-4 text-left">Product Item</th>
                <th class="py-3 px-4 text-center">Original Sold</th>
                <th class="py-3 px-4 text-center">Returned Qty / Sq.Ft</th>
                <th class="py-3 px-4 text-center">Net Active Sold</th>
                <th class="py-3 px-4 text-right">Unit Rate</th>
                <th class="py-3 px-4 text-right">Original Total</th>
                <th class="py-3 px-4 text-right">Net Bill Total</th>
                <th class="py-3 px-4 text-center">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="(item, idx) in sale.sale_items"
                :key="item.id"
                :class="[
                  'transition',
                  getItemReturnedQty(item) > 0 ? 'bg-rose-50/20 dark:bg-rose-950/10' : 'hover:bg-gray-50 dark:hover:bg-gray-700/30'
                ]"
              >
                <!-- Product Details -->
                <td class="py-3 px-4">
                  <div class="font-bold text-gray-900 dark:text-white">
                    {{ item.product?.name || item.description || 'Custom Item' }}
                  </div>
                  <div v-if="isPanaflexItem(item)" class="text-xs text-purple-600 dark:text-purple-400 font-medium mt-0.5">
                    Panaflex Roll ({{ item.length_input }}{{ item.length_unit || 'm' }} × {{ item.width_input }}{{ item.width_unit || 'in' }})
                  </div>
                  <div v-if="item.description && item.product" class="text-xs text-gray-400 mt-0.5">
                    {{ item.description }}
                  </div>
                </td>

                <!-- Original Sold -->
                <td class="py-3 px-4 text-center whitespace-nowrap text-gray-800 dark:text-gray-200 font-medium">
                  <span v-if="isPanaflexItem(item)">
                    {{ formatAmount(item.units_sqft) }} sq.ft
                  </span>
                  <span v-else>
                    {{ item.quantity }} {{ item.product?.unit?.symbol || 'pcs' }}
                  </span>
                </td>

                <!-- Returned Qty / Sq.ft -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span
                    v-if="getItemReturnedQty(item) > 0"
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800"
                  >
                    -{{ formatAmount(getItemReturnedQty(item)) }} {{ isPanaflexItem(item) ? 'sq.ft' : (item.product?.unit?.symbol || 'pcs') }}
                  </span>
                  <span v-else class="text-xs text-gray-400">-</span>
                </td>

                <!-- Net Active Sold -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span
                    :class="[
                      'font-bold text-sm',
                      getNetSoldQty(item) <= 0 ? 'text-gray-400 line-through' : 'text-emerald-600 dark:text-emerald-400'
                    ]"
                  >
                    {{ formatAmount(getNetSoldQty(item)) }} {{ isPanaflexItem(item) ? 'sq.ft' : (item.product?.unit?.symbol || 'pcs') }}
                  </span>
                </td>

                <!-- Rate -->
                <td class="py-3 px-4 text-right text-gray-700 dark:text-gray-300 font-medium whitespace-nowrap">
                  PKR {{ formatAmount(item.rate) }}
                </td>

                <!-- Original Total -->
                <td class="py-3 px-4 text-right text-gray-500 dark:text-gray-400 whitespace-nowrap">
                  PKR {{ formatAmount(item.line_total) }}
                </td>

                <!-- Net Bill Total -->
                <td class="py-3 px-4 text-right font-bold text-gray-900 dark:text-white whitespace-nowrap">
                  PKR {{ formatAmount(getNetLineTotal(item)) }}
                </td>

                <!-- Status Badge -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span
                    v-if="isItemFullyReturned(item)"
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300"
                  >
                    Fully Returned
                  </span>
                  <span
                    v-else-if="getItemReturnedQty(item) > 0"
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                  >
                    Partially Returned
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300"
                  >
                    Active
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Section 2: Returns History & Detailed Returned Products Breakdown -->
      <div v-if="hasReturns" class="bg-white dark:bg-gray-800 rounded-2xl border border-rose-200 dark:border-rose-900/50 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-rose-100 dark:border-rose-900/40 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-rose-50/50 dark:bg-rose-950/20">
          <div>
            <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <span class="p-1.5 bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 rounded-lg">
                <ArrowUturnLeftIcon class="w-4 h-4" />
              </span>
              <span>Sale Returns History & Returned Products</span>
            </h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
              Complete record of returned products, quantities, dimensions, and financial refund adjustments
            </p>
          </div>
          <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-600 text-white shadow-sm">
            Total Refunded: PKR {{ formatAmount(totalReturnedAmount) }}
          </span>
        </div>

        <div class="p-5 space-y-5">
          <!-- Iterate over each Return record -->
          <div
            v-for="(returnRecord, rIdx) in sale.returns"
            :key="returnRecord.id"
            class="bg-gray-50/80 dark:bg-gray-800/80 rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-4 shadow-sm"
          >
            <!-- Return Header Summary Row -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-gray-200 dark:border-gray-700">
              <div class="flex flex-wrap items-center gap-3">
                <Link
                  :href="route('returns.sales.show', returnRecord.id)"
                  class="font-black text-rose-600 dark:text-rose-400 hover:underline text-base flex items-center gap-1.5"
                >
                  <span class="p-1 bg-rose-100 dark:bg-rose-900/50 rounded">#</span>
                  {{ returnRecord.return_no }}
                </Link>

                <span class="text-xs text-gray-500 dark:text-gray-400">
                  {{ formatDate(returnRecord.returned_at) }}
                </span>

                <span
                  :class="[
                    'inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider',
                    returnRecord.refund_type === 'cash' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' :
                    returnRecord.refund_type === 'credit' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' :
                    'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300'
                  ]"
                >
                  {{ returnRecord.refund_type || 'cash' }}
                </span>

                <span class="text-xs text-gray-500 dark:text-gray-400">
                  By: <strong class="text-gray-800 dark:text-gray-200">{{ returnRecord.user?.name || 'Cashier' }}</strong>
                </span>
              </div>

              <!-- Right Actions: Print & Delete -->
              <div class="flex items-center gap-2">
                <span class="text-sm font-black text-rose-600 dark:text-rose-400 mr-2">
                  PKR {{ formatAmount(returnRecord.grand_total) }}
                </span>

                <button
                  @click="printReturnA4(returnRecord.id)"
                  title="Print A4"
                  class="p-1.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30 transition text-xs flex items-center gap-1 font-medium"
                >
                  <DocumentTextIcon class="w-3.5 h-3.5" />
                  <span>A4</span>
                </button>

                <button
                  @click="printReturn80mm(returnRecord.id)"
                  title="Print Thermal 80mm"
                  class="p-1.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-emerald-600 dark:text-emerald-400 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition text-xs flex items-center gap-1 font-medium"
                >
                  <ReceiptPercentIcon class="w-3.5 h-3.5" />
                  <span>80mm</span>
                </button>

                <!-- Delete / Reverse Return Button -->
                <button
                  @click="confirmDeleteReturn(returnRecord)"
                  title="Delete & Reverse Return"
                  class="p-1.5 bg-white dark:bg-gray-700 border border-red-200 dark:border-red-800/50 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 transition text-xs flex items-center gap-1 font-bold"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                  <span>Delete & Reverse</span>
                </button>
              </div>
            </div>

            <!-- Return Reason Note -->
            <div v-if="returnRecord.reason" class="text-xs text-gray-600 dark:text-gray-300 italic bg-white dark:bg-gray-700/50 p-2.5 rounded-lg border border-gray-200/60 dark:border-gray-600">
              <strong>Return Reason:</strong> {{ returnRecord.reason }}
            </div>

            <!-- Products Returned Sub-table -->
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                <thead class="bg-gray-100/70 dark:bg-gray-700/70 font-semibold text-gray-600 dark:text-gray-300 uppercase">
                  <tr>
                    <th class="py-2.5 px-3 text-left">#</th>
                    <th class="py-2.5 px-3 text-left">Returned Product Item</th>
                    <th class="py-2.5 px-3 text-center">Returned Dimensions / Quantity</th>
                    <th class="py-2.5 px-3 text-right">Return Rate</th>
                    <th class="py-2.5 px-3 text-right">Refund Total</th>
                    <th class="py-2.5 px-3 text-left">Item Note</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr
                    v-for="(rItem, itemIdx) in (returnRecord.items || returnRecord.sale_return_items || [])"
                    :key="rItem.id"
                    class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30"
                  >
                    <td class="py-2.5 px-3 text-gray-400 font-mono">{{ itemIdx + 1 }}</td>
                    
                    <!-- Product Name -->
                    <td class="py-2.5 px-3 font-semibold text-gray-900 dark:text-white">
                      {{ rItem.sale_item?.product?.name || rItem.sale_item?.description || 'Returned Item' }}
                    </td>

                    <!-- Returned Dimensions / Quantity -->
                    <td class="py-2.5 px-3 text-center font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                      <div v-if="rItem.units_sqft > 0">
                        <span>{{ formatAmount(rItem.units_sqft) }} sq.ft</span>
                        <span v-if="rItem.width_input && rItem.length_input" class="block text-[10px] text-gray-400 font-normal">
                          ({{ rItem.width_input }}{{ rItem.width_unit || 'in' }} × {{ rItem.length_input }}{{ rItem.length_unit || 'm' }})
                        </span>
                      </div>
                      <div v-else>
                        {{ rItem.quantity }} {{ rItem.sale_item?.product?.unit?.symbol || 'pcs' }}
                      </div>
                    </td>

                    <!-- Rate -->
                    <td class="py-2.5 px-3 text-right text-gray-600 dark:text-gray-300">
                      PKR {{ formatAmount(rItem.rate) }}
                    </td>

                    <!-- Line Total -->
                    <td class="py-2.5 px-3 text-right font-black text-rose-600 dark:text-rose-400">
                      PKR {{ formatAmount(rItem.line_total) }}
                    </td>

                    <!-- Item Note -->
                    <td class="py-2.5 px-3 text-gray-500 dark:text-gray-400 italic">
                      {{ rItem.note || '-' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  ArrowLeftIcon,
  DocumentTextIcon,
  ReceiptPercentIcon,
  ArrowUturnLeftIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  sale: Object,
})

const hasReturns = computed(() => {
  return Array.isArray(props.sale?.returns) && props.sale.returns.length > 0
})

const totalReturnedAmount = computed(() => {
  if (!props.sale?.returns) return 0
  return props.sale.returns.reduce((total, returnItem) => {
    return total + Math.abs(Number(returnItem.grand_total) || 0)
  }, 0)
})

const isPanaflexItem = (item) => {
  return item.product?.type === 'panaflex_roll'
}

const getItemReturnedQty = (item) => {
  if (!props.sale?.returns) return 0
  let returned = 0
  props.sale.returns.forEach(returnRecord => {
    const items = returnRecord.items || returnRecord.sale_return_items || []
    items.forEach(rItem => {
      if (rItem.sale_item_id === item.id) {
        if (isPanaflexItem(item)) {
          returned += Number(rItem.units_sqft) || 0
        } else {
          returned += Number(rItem.quantity) || 0
        }
      }
    })
  })
  return returned
}

const getNetSoldQty = (item) => {
  if (isPanaflexItem(item)) {
    return Math.max(0, (Number(item.units_sqft) || 0) - getItemReturnedQty(item))
  }
  return Math.max(0, (Number(item.quantity) || 0) - getItemReturnedQty(item))
}

const getNetLineTotal = (item) => {
  const netQty = getNetSoldQty(item)
  return netQty * (Number(item.rate) || 0)
}

const isItemFullyReturned = (item) => {
  return getNetSoldQty(item) <= 0.001
}

const canCreateReturn = computed(() => {
  if (!props.sale?.sale_items) return false
  return props.sale.sale_items.some(item => getNetSoldQty(item) > 0.001)
})

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('en-PK', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatAmount = (amount) => {
  const val = Number(amount) || 0
  return new Intl.NumberFormat('en-PK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(val)
}

const printA4 = (isCopy = false) => {
  const url = route('prints.invoice.a4', props.sale.id) + (isCopy ? '?copy=1' : '')
  window.open(url, '_blank')
}

const print80mm = (isCopy = false) => {
  const url = route('prints.invoice.80mm', props.sale.id) + (isCopy ? '?copy=1' : '')
  window.open(url, '_blank')
}

const printReturnA4 = (returnId) => {
  window.open(route('prints.return.a4', returnId), '_blank')
}

const printReturn80mm = (returnId) => {
  window.open(route('prints.return.80mm', returnId), '_blank')
}

const createReturn = () => {
  router.visit(route('returns.sales.create', { sale_id: props.sale.id }))
}

const confirmDeleteReturn = (returnRecord) => {
  if (confirm(`Are you sure you want to delete and reverse Sale Return #${returnRecord.return_no}?\n\nThis will restore stock and customer balance automatically.`)) {
    router.delete(route('returns.sales.destroy', returnRecord.id))
  }
}
</script>