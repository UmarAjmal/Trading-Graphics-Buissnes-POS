<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <div class="flex items-center gap-2">
            <Link
              :href="route('returns.sales.index')"
              class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
              </svg>
            </Link>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <span class="p-2 bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
              </span>
              Process Sale Return
            </h1>
          </div>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-9">
            Search invoice, choose return quantities, restock inventory batches, and adjust customer ledger
          </p>
        </div>

        <Link
          :href="route('returns.sales.index')"
          class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-sm font-medium transition"
        >
          View All Returns
        </Link>
      </div>

      <!-- Search Invoice Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-3">
          Step 1: Select Original Sale Invoice
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Search input -->
          <div class="md:col-span-2">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
              Enter Invoice Number / Scan Barcode
            </label>
            <div class="flex gap-2">
              <div class="relative flex-1">
                <input
                  v-model="searchQuery"
                  @keyup.enter="searchInvoice"
                  type="text"
                  placeholder="e.g. INV-0001234 or type invoice number..."
                  class="w-full pl-9 pr-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-rose-500 dark:bg-gray-700 dark:text-white"
                />
                <svg class="w-5 h-5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
              <button
                @click="searchInvoice"
                :disabled="searching || !searchQuery.trim()"
                class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition flex items-center gap-2 shrink-0"
              >
                <svg v-if="searching" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>{{ searching ? 'Searching...' : 'Load Invoice' }}</span>
              </button>
            </div>
            <p v-if="searchError" class="text-xs text-red-500 font-medium mt-1">{{ searchError }}</p>
          </div>

          <!-- Quick Select Recent Invoice -->
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
              Or Pick From Recent Invoices
            </label>
            <select
              @change="onRecentSelect($event.target.value)"
              class="w-full py-2.5 px-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-rose-500 dark:bg-gray-700 dark:text-white"
            >
              <option value="">-- Select Recent Invoice --</option>
              <option v-for="s in recentSales" :key="s.id" :value="s.id">
                {{ s.invoice_no }} - {{ s.customer_name }} (PKR {{ formatNumber(s.bill_total) }})
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Loaded Invoice Details & Return Form -->
      <div v-if="loadedSale" class="space-y-6">
        <!-- Invoice Info Banner -->
        <div class="bg-rose-50/50 dark:bg-rose-950/20 rounded-xl border border-rose-200 dark:border-rose-900/50 p-4">
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-sm">
            <div>
              <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Invoice No</span>
              <span class="font-bold text-gray-900 dark:text-white">{{ loadedSale.invoice_no }}</span>
            </div>
            <div>
              <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Sale Date</span>
              <span class="font-medium text-gray-800 dark:text-gray-200">{{ loadedSale.sold_at || '-' }}</span>
            </div>
            <div>
              <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Customer</span>
              <span class="font-semibold text-rose-600 dark:text-rose-400">
                {{ loadedSale.customer?.name || 'Walk-in Customer' }}
              </span>
              <div v-if="loadedSale.customer?.phone" class="text-xs text-gray-500">
                {{ loadedSale.customer.phone }}
              </div>
            </div>
            <div>
              <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Customer Ledger Balance</span>
              <span
                :class="[
                  'font-bold',
                  (loadedSale.customer?.balance || 0) > 0 ? 'text-amber-600' : 'text-emerald-600'
                ]"
              >
                PKR {{ formatNumber(loadedSale.customer?.balance || 0) }}
              </span>
              <div class="text-[10px] text-gray-400">
                {{ (loadedSale.customer?.balance || 0) > 0 ? '(Receivable Debt)' : '(Advance/Clear)' }}
              </div>
            </div>
            <div>
              <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Original Bill Total</span>
              <span class="font-bold text-gray-900 dark:text-white">PKR {{ formatNumber(loadedSale.bill_total) }}</span>
            </div>
            <div>
              <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Payment Type</span>
              <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold uppercase bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                {{ loadedSale.payment_type }}
              </span>
              <div v-if="loadedSale.pending_due > 0" class="text-xs text-red-500 font-semibold mt-0.5">
                Due: PKR {{ formatNumber(loadedSale.pending_due) }}
              </div>
            </div>
          </div>
        </div>

        <form @submit.prevent="submitReturn" class="space-y-6">
          <!-- Step 2: Return Items Table -->
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
              <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                  Step 2: Choose Items and Quantities to Return
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                  Enter quantity/sq.ft to return. Stock will be restored into inventory batches automatically.
                </p>
              </div>
              <button
                type="button"
                @click="returnAllItems"
                class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-medium transition"
              >
                Return All Available
              </button>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  <tr>
                    <th class="py-3 px-4 text-left">Product Item</th>
                    <th class="py-3 px-4 text-center">Original Sold</th>
                    <th class="py-3 px-4 text-center">Already Returned</th>
                    <th class="py-3 px-4 text-center">Available to Return</th>
                    <th class="py-3 px-4 text-center" style="min-width: 290px;">Return Quantity / Dimensions</th>
                    <th class="py-3 px-4 text-right">Unit Rate</th>
                    <th class="py-3 px-4 text-right">Refund Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                  <tr
                    v-for="(item, idx) in returnForm.items"
                    :key="item.sale_item_id"
                    :class="[
                      'transition',
                      item.return_quantity > 0 || item.return_units_sqft > 0
                        ? 'bg-rose-50/40 dark:bg-rose-950/10'
                        : 'hover:bg-gray-50 dark:hover:bg-gray-700/30'
                    ]"
                  >
                    <!-- Product Details -->
                    <td class="py-3 px-4">
                      <div class="font-semibold text-gray-900 dark:text-white">
                        {{ item.product_name }}
                      </div>
                      <div v-if="item.is_panaflex" class="text-xs text-purple-600 dark:text-purple-400 font-medium">
                        Panaflex Roll (Square Feet)
                        <span v-if="item.width_input && item.length_input" class="text-gray-400 font-normal ml-1">
                          (Sold: {{ item.width_input }}{{ item.width_unit }} × {{ item.length_input }}{{ item.length_unit }})
                        </span>
                      </div>
                      <div v-else class="text-xs text-gray-400">
                        Unit: {{ item.unit }}
                      </div>
                      <input
                        v-model="item.note"
                        type="text"
                        placeholder="Item note / defect reason (optional)..."
                        class="mt-1.5 w-full text-xs px-2 py-1 border border-gray-200 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white"
                      />
                    </td>

                    <!-- Original Sold -->
                    <td class="py-3 px-4 text-center text-gray-700 dark:text-gray-300 whitespace-nowrap">
                      <span v-if="item.is_panaflex">
                        {{ formatNumber(item.original_units_sqft) }} sq.ft
                      </span>
                      <span v-else>
                        {{ formatNumber(item.original_quantity) }} {{ item.unit }}
                      </span>
                    </td>

                    <!-- Already Returned -->
                    <td class="py-3 px-4 text-center text-gray-500 dark:text-gray-400 whitespace-nowrap">
                      <span v-if="item.is_panaflex">
                        {{ formatNumber(item.returned_units_sqft) }} sq.ft
                      </span>
                      <span v-else>
                        {{ formatNumber(item.returned_quantity) }} {{ item.unit }}
                      </span>
                    </td>

                    <!-- Available -->
                    <td class="py-3 px-4 text-center font-bold text-gray-900 dark:text-white whitespace-nowrap">
                      <span v-if="item.is_panaflex" class="text-purple-600 dark:text-purple-400">
                        {{ formatNumber(item.remaining_units_sqft) }} sq.ft
                      </span>
                      <span v-else class="text-emerald-600 dark:text-emerald-400">
                        {{ formatNumber(item.remaining_quantity) }} {{ item.unit }}
                      </span>
                    </td>

                    <!-- Return Input -->
                    <td class="py-3 px-4 text-center">
                      <div v-if="!item.can_return" class="text-xs text-gray-400 font-medium italic">
                        Fully Returned
                      </div>

                      <!-- Panaflex Dimension Inputs & Auto Sq.ft Calculation -->
                      <div v-else-if="item.is_panaflex" class="space-y-1.5 py-1">
                        <!-- Dimensions Input Mode -->
                        <div v-if="item.input_mode !== 'direct'" class="flex items-center justify-center gap-1">
                          <!-- Width -->
                          <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 overflow-hidden" title="Width">
                            <input
                              v-model.number="item.return_width_input"
                              @input="onDimensionChange(item)"
                              type="number"
                              step="0.1"
                              placeholder="Width"
                              class="w-14 text-center py-1 px-1 text-xs font-bold border-0 focus:ring-0 dark:bg-gray-700 dark:text-white"
                            />
                            <select
                              v-model="item.return_width_unit"
                              @change="onDimensionChange(item)"
                              class="text-[10px] py-1 px-1 border-0 border-l border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold focus:ring-0 cursor-pointer"
                            >
                              <option value="in">in</option>
                              <option value="ft">ft</option>
                            </select>
                          </div>

                          <span class="text-gray-400 font-bold text-xs">×</span>

                          <!-- Length -->
                          <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 overflow-hidden" title="Length">
                            <input
                              v-model.number="item.return_length_input"
                              @input="onDimensionChange(item)"
                              type="number"
                              step="0.1"
                              placeholder="Length"
                              class="w-14 text-center py-1 px-1 text-xs font-bold border-0 focus:ring-0 dark:bg-gray-700 dark:text-white"
                            />
                            <select
                              v-model="item.return_length_unit"
                              @change="onDimensionChange(item)"
                              class="text-[10px] py-1 px-1 border-0 border-l border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold focus:ring-0 cursor-pointer"
                            >
                              <option value="m">m</option>
                              <option value="ft">ft</option>
                            </select>
                          </div>

                          <span class="text-gray-400 font-bold text-xs">×</span>

                          <!-- Pieces/Qty -->
                          <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 overflow-hidden" title="Pieces / Quantity">
                            <input
                              v-model.number="item.return_pieces"
                              @input="onDimensionChange(item)"
                              type="number"
                              min="1"
                              step="1"
                              placeholder="Qty"
                              class="w-10 text-center py-1 px-1 text-xs font-bold border-0 focus:ring-0 dark:bg-gray-700 dark:text-white"
                            />
                          </div>
                        </div>

                        <!-- Direct Sq.ft Input Mode -->
                        <div v-else class="flex items-center justify-center gap-1">
                          <input
                            v-model.number="item.return_units_sqft"
                            @input="calculateLineTotal(item)"
                            type="number"
                            step="0.01"
                            :min="0"
                            :max="item.remaining_units_sqft"
                            placeholder="Sq.ft"
                            class="w-24 text-center py-1 px-2 text-xs font-bold border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-rose-500 dark:bg-gray-700 dark:text-white"
                          />
                          <span class="text-xs font-medium text-gray-500">sq.ft</span>
                        </div>

                        <!-- Helper pill & actions -->
                        <div class="flex items-center justify-between gap-1 text-[11px] pt-0.5 px-0.5">
                          <div class="flex items-center gap-1 font-semibold text-rose-600 dark:text-rose-400">
                            <span>= {{ formatNumber(item.return_units_sqft || 0) }} sq.ft</span>
                            <span v-if="item.return_units_sqft > item.remaining_units_sqft" class="text-[10px] text-red-500 font-bold">
                              (Exceeds {{ formatNumber(item.remaining_units_sqft) }} max!)
                            </span>
                          </div>

                          <div class="flex items-center gap-1">
                            <button
                              type="button"
                              @click="item.input_mode = (item.input_mode === 'direct' ? 'dimensions' : 'direct')"
                              class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-medium"
                            >
                              {{ item.input_mode === 'direct' ? 'Use Dims' : 'Direct Sq.Ft' }}
                            </button>
                            <button
                              type="button"
                              @click="setMaxQty(item)"
                              class="px-1.5 py-0.5 text-[10px] font-bold bg-rose-100 hover:bg-rose-200 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 rounded"
                              title="Return maximum available"
                            >
                              Max
                            </button>
                            <button
                              v-if="item.return_units_sqft > 0"
                              type="button"
                              @click="clearItemReturn(item)"
                              class="px-1 py-0.5 text-[10px] text-gray-400 hover:text-red-500 font-bold"
                              title="Clear"
                            >
                              ✕
                            </button>
                          </div>
                        </div>
                      </div>

                      <!-- Standard Simple Item Input -->
                      <div v-else class="flex items-center justify-center gap-1.5">
                        <button
                          type="button"
                          @click="decrementQty(item)"
                          class="w-7 h-7 flex items-center justify-center bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-white rounded text-sm font-bold"
                        >
                          -
                        </button>
                        
                        <input
                          v-model.number="item.return_quantity"
                          @input="calculateLineTotal(item)"
                          type="number"
                          step="1"
                          :min="0"
                          :max="item.remaining_quantity"
                          class="w-20 text-center py-1 px-2 text-sm font-bold border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-rose-500 dark:bg-gray-700 dark:text-white"
                        />

                        <button
                          type="button"
                          @click="incrementQty(item)"
                          class="w-7 h-7 flex items-center justify-center bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-white rounded text-sm font-bold"
                        >
                          +
                        </button>
                        <button
                          type="button"
                          @click="setMaxQty(item)"
                          class="px-2 py-1 text-[11px] font-semibold bg-rose-100 hover:bg-rose-200 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 rounded"
                          title="Return maximum available"
                        >
                          Max
                        </button>
                      </div>
                    </td>

                    <!-- Unit Rate -->
                    <td class="py-3 px-4 text-right text-gray-700 dark:text-gray-300 font-medium whitespace-nowrap">
                      PKR {{ formatNumber(item.rate) }}
                    </td>

                    <!-- Line Total -->
                    <td class="py-3 px-4 text-right font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                      PKR {{ formatNumber(item.line_total) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Step 3: Refund Settlement & Reason -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Reason & Adjustments -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-4">
              <h2 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                Step 3: Reason & Settlement Details
              </h2>

              <!-- Reason -->
              <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Reason for Return <span class="text-red-500">*</span>
                </label>
                <textarea
                  v-model="returnForm.reason"
                  rows="3"
                  placeholder="State the reason (e.g. Defective print, damaged sheet, customer changed order, wrong measurements)..."
                  class="w-full text-sm p-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-rose-500 dark:bg-gray-700 dark:text-white"
                  required
                ></textarea>
              </div>

              <!-- Restocking / Adjustments -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                  <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Other Adjustment / Restocking Fee (PKR)
                  </label>
                  <input
                    v-model.number="returnForm.other_adjustments"
                    @input="recalculateTotals"
                    type="number"
                    step="0.01"
                    placeholder="Enter 0 or +/- amount"
                    class="w-full text-sm py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-rose-500 dark:bg-gray-700 dark:text-white"
                  />
                  <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                    Negative (-) for deductions/restocking fee, Positive (+) for goodwill addition.
                  </p>
                </div>

                <div>
                  <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Refund Settlement Method <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="returnForm.refund_type"
                    class="w-full text-sm py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-rose-500 dark:bg-gray-700 dark:text-white font-medium"
                    required
                  >
                    <option value="cash">Cash (Paid out from Cash Drawer)</option>
                    <option value="credit">Credit (Deduct Customer Debt / Store Credit)</option>
                    <option value="bank">Bank Transfer (Refund via Bank Account)</option>
                  </select>
                  <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                    <span v-if="returnForm.refund_type === 'cash'">Records a cash payout disbursement in the system.</span>
                    <span v-else-if="returnForm.refund_type === 'credit'">Directly reduces customer debt in the ledger or creates advance credit.</span>
                    <span v-else-if="returnForm.refund_type === 'bank'">Transfers refund amount to customer bank account.</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Grand Total Summary Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm flex flex-col justify-between space-y-4">
              <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-3">
                  Refund Calculation
                </h3>

                <div class="space-y-2.5 text-sm">
                  <div class="flex justify-between text-gray-600 dark:text-gray-400">
                    <span>Items Subtotal:</span>
                    <span class="font-medium text-gray-900 dark:text-white">PKR {{ formatNumber(returnSubtotal) }}</span>
                  </div>

                  <div v-if="returnForm.other_adjustments != 0" class="flex justify-between text-gray-600 dark:text-gray-400">
                    <span>Adjustment:</span>
                    <span class="font-medium text-gray-900 dark:text-white">PKR {{ formatNumber(returnForm.other_adjustments) }}</span>
                  </div>

                  <div class="pt-3 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <span class="text-base font-bold text-gray-900 dark:text-white">Total Refund:</span>
                    <span class="text-xl font-extrabold text-rose-600 dark:text-rose-400">
                      PKR {{ formatNumber(returnGrandTotal) }}
                    </span>
                  </div>

                  <div class="p-2.5 bg-rose-50 dark:bg-rose-950/20 rounded-lg text-xs text-rose-700 dark:text-rose-300 font-medium">
                    Method: <span class="uppercase font-bold">{{ returnForm.refund_type }}</span>
                  </div>
                </div>
              </div>

              <!-- Submit Button -->
              <div>
                <button
                  type="submit"
                  :disabled="submitting || returnGrandTotal <= 0"
                  class="w-full py-3 px-4 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition flex items-center justify-center gap-2"
                >
                  <svg v-if="submitting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                  </svg>
                  <span>{{ submitting ? 'Processing Sale Return...' : 'Confirm & Process Return' }}</span>
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import axios from 'axios'

const props = defineProps({
  initialSale: Object,
  recentSales: Array,
  hasOpenRegister: Boolean,
})

const searchQuery = ref('')
const searching = ref(false)
const searchError = ref('')
const submitting = ref(false)
const loadedSale = ref(props.initialSale || null)

const returnForm = reactive({
  sale_id: props.initialSale?.id || null,
  reason: '',
  refund_type: 'cash',
  other_adjustments: 0,
  items: props.initialSale?.items || [],
})

const onRecentSelect = (saleId) => {
  if (!saleId) return
  searchQuery.value = saleId
  searchInvoice()
}

const searchInvoice = async () => {
  if (!searchQuery.value.toString().trim()) return
  searching.value = true
  searchError.value = ''

  try {
    const res = await axios.get(route('returns.sales.search'), {
      params: { q: searchQuery.value.toString().trim() }
    })
    if (res.data.success && res.data.sale) {
      loadedSale.value = res.data.sale
      returnForm.sale_id = res.data.sale.id
      returnForm.items = res.data.sale.items
      returnForm.other_adjustments = 0
      returnForm.refund_type = res.data.sale.payment_type === 'credit' ? 'credit' : 'cash'
    }
  } catch (err) {
    searchError.value = err.response?.data?.message || 'Invoice not found. Please verify the number.'
  } finally {
    searching.value = false
  }
}

const calculateLineTotal = (item) => {
  if (item.is_panaflex) {
    if (item.return_units_sqft > item.remaining_units_sqft) {
      item.return_units_sqft = item.remaining_units_sqft
    }
    item.line_total = (item.return_units_sqft || 0) * (item.rate || 0)
  } else {
    if (item.return_quantity > item.remaining_quantity) {
      item.return_quantity = item.remaining_quantity
    }
    item.line_total = (item.return_quantity || 0) * (item.rate || 0)
  }
}

const onDimensionChange = (item) => {
  if (!item.return_width_input || !item.return_length_input) {
    item.return_units_sqft = 0
  } else {
    const w = Number(item.return_width_input) || 0
    const l = Number(item.return_length_input) || 0
    const qty = Number(item.return_pieces) || 1

    const lFt = (item.return_length_unit === 'm') ? (l * 3.28) : l
    const wFt = (item.return_width_unit === 'in') ? (w / 12) : w

    const sqft = Math.round((lFt * wFt * qty) * 100) / 100
    item.return_units_sqft = sqft
  }
  calculateLineTotal(item)
}

const clearItemReturn = (item) => {
  item.return_units_sqft = 0
  item.return_quantity = 0
  item.return_width_input = null
  item.return_length_input = null
  item.return_pieces = 1
  calculateLineTotal(item)
}

const incrementQty = (item) => {
  if (item.is_panaflex) {
    item.return_units_sqft = Math.min(item.remaining_units_sqft, (item.return_units_sqft || 0) + 1)
  } else {
    item.return_quantity = Math.min(item.remaining_quantity, (item.return_quantity || 0) + 1)
  }
  calculateLineTotal(item)
}

const decrementQty = (item) => {
  if (item.is_panaflex) {
    item.return_units_sqft = Math.max(0, (item.return_units_sqft || 0) - 1)
  } else {
    item.return_quantity = Math.max(0, (item.return_quantity || 0) - 1)
  }
  calculateLineTotal(item)
}

const setMaxQty = (item) => {
  if (item.is_panaflex) {
    item.return_units_sqft = item.remaining_units_sqft
    item.return_width_input = item.width_input || null
    item.return_width_unit = item.width_unit || 'in'
    item.return_length_input = item.length_input || null
    item.return_length_unit = item.length_unit || 'm'
    item.return_pieces = item.original_quantity || 1
  } else {
    item.return_quantity = item.remaining_quantity
  }
  calculateLineTotal(item)
}

const returnAllItems = () => {
  returnForm.items.forEach(item => {
    if (item.can_return) {
      setMaxQty(item)
    }
  })
}

const returnSubtotal = computed(() => {
  return returnForm.items.reduce((acc, it) => acc + (it.line_total || 0), 0)
})

const returnGrandTotal = computed(() => {
  return Math.max(0, returnSubtotal.value + (Number(returnForm.other_adjustments) || 0))
})

const submitReturn = () => {
  const hasItems = returnForm.items.some(it => (it.return_quantity || 0) > 0 || (it.return_units_sqft || 0) > 0)
  if (!hasItems) {
    alert('Please enter a return quantity or sq.ft for at least one item.')
    return
  }

  if (!returnForm.reason.trim()) {
    alert('Please provide a reason for the return.')
    return
  }

  submitting.value = true

  router.post(route('returns.sales.store'), {
    sale_id: returnForm.sale_id,
    reason: returnForm.reason,
    refund_type: returnForm.refund_type,
    other_adjustments: returnForm.other_adjustments,
    items: returnForm.items.map(it => ({
      sale_item_id: it.sale_item_id,
      return_quantity: it.return_quantity || 0,
      return_units_sqft: it.return_units_sqft || 0,
      length_input: it.return_length_input || null,
      length_unit: it.return_length_unit || null,
      width_input: it.return_width_input || null,
      width_unit: it.return_width_unit || null,
      note: it.note,
    }))
  }, {
    onFinish: () => {
      submitting.value = false
    }
  })
}

const formatNumber = (num) => {
  const val = Number(num) || 0
  return val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>
