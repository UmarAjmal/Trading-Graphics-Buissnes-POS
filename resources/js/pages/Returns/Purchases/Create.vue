<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
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
              <span class="p-2 bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
              </span>
              Process Purchase Return
            </h1>
          </div>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-9">
            Select supplier purchase, choose returned items, deduct stock batches, and update supplier ledger
          </p>
        </div>

        <Link
          :href="route('returns.purchases.index')"
          class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-sm font-medium transition"
        >
          View All Returns
        </Link>
      </div>

      <!-- Search Purchase Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-3">
          Step 1: Select Original Purchase Bill
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Search input -->
          <div class="md:col-span-2">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
              Enter Purchase PO Number
            </label>
            <div class="flex gap-2">
              <div class="relative flex-1">
                <input
                  v-model="searchQuery"
                  @keyup.enter="searchPurchase"
                  type="text"
                  placeholder="e.g. PUR-0000001 or type purchase number..."
                  class="w-full pl-9 pr-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-white"
                />
                <svg class="w-5 h-5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
              <button
                @click="searchPurchase"
                :disabled="searching || !searchQuery.trim()"
                class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition flex items-center gap-2 shrink-0"
              >
                <svg v-if="searching" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>{{ searching ? 'Searching...' : 'Load Purchase' }}</span>
              </button>
            </div>
            <p v-if="searchError" class="text-xs text-red-500 font-medium mt-1">{{ searchError }}</p>
          </div>

          <!-- Quick Select Recent Purchase -->
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
              Or Pick From Recent Purchases
            </label>
            <select
              @change="onRecentSelect($event.target.value)"
              class="w-full py-2.5 px-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-white"
            >
              <option value="">-- Select Recent Purchase --</option>
              <option v-for="p in recentPurchases" :key="p.id" :value="p.id">
                {{ p.purchase_no }} - {{ p.supplier_name }} (PKR {{ formatNumber(p.grand_total) }})
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Loaded Purchase Details & Return Form -->
      <div v-if="loadedPurchase" class="space-y-6">
        <!-- Purchase Info Banner -->
        <div class="bg-amber-50/50 dark:bg-amber-950/20 rounded-xl border border-amber-200 dark:border-amber-900/50 p-4">
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 text-sm">
            <div>
              <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Purchase PO #</span>
              <span class="font-bold text-gray-900 dark:text-white">{{ loadedPurchase.purchase_no }}</span>
            </div>
            <div>
              <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Purchase Date</span>
              <span class="font-medium text-gray-800 dark:text-gray-200">{{ loadedPurchase.purchased_at || '-' }}</span>
            </div>
            <div>
              <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Supplier</span>
              <span class="font-semibold text-amber-600 dark:text-amber-400">
                {{ loadedPurchase.supplier?.name || 'Direct Supplier' }}
              </span>
              <div v-if="loadedPurchase.supplier?.phone" class="text-xs text-gray-500">
                {{ loadedPurchase.supplier.phone }}
              </div>
            </div>
            <div>
              <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Supplier Ledger Balance</span>
              <span
                :class="[
                  'font-bold',
                  (loadedPurchase.supplier?.balance || 0) > 0 ? 'text-red-600' : 'text-emerald-600'
                ]"
              >
                PKR {{ formatNumber(loadedPurchase.supplier?.balance || 0) }}
              </span>
              <div class="text-[10px] text-gray-400">
                {{ (loadedPurchase.supplier?.balance || 0) > 0 ? '(We Owe Supplier)' : '(Advance Paid)' }}
              </div>
            </div>
            <div>
              <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Original Bill Total</span>
              <span class="font-bold text-gray-900 dark:text-white">PKR {{ formatNumber(loadedPurchase.grand_total) }}</span>
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
                  Enter quantity or dimensions (width × length) to return. Stock will be deducted automatically from inventory.
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
                    <th class="py-3 px-4 text-center">Original Purchased</th>
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
                    :key="item.purchase_item_id"
                    :class="[
                      'transition',
                      item.return_quantity > 0 || item.return_units_sqft > 0
                        ? 'bg-amber-50/40 dark:bg-amber-950/10'
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
                          (Purchased: {{ item.width_input }}{{ item.width_unit }} × {{ item.length_input }}{{ item.length_unit }})
                        </span>
                      </div>
                      <div v-else class="text-xs text-gray-400">
                        Unit: {{ item.unit }}
                      </div>
                      <input
                        v-model="item.note"
                        type="text"
                        placeholder="Item return note / reason..."
                        class="mt-1.5 w-full text-xs px-2 py-1 border border-gray-200 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white"
                      />
                    </td>

                    <!-- Original Purchased -->
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
                            class="w-24 text-center py-1 px-2 text-xs font-bold border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-white"
                          />
                          <span class="text-xs font-medium text-gray-500">sq.ft</span>
                        </div>

                        <!-- Helper pill & actions -->
                        <div class="flex items-center justify-between gap-1 text-[11px] pt-0.5 px-0.5">
                          <div class="flex items-center gap-1 font-semibold text-amber-600 dark:text-amber-400">
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
                              class="px-1.5 py-0.5 text-[10px] font-bold bg-amber-100 hover:bg-amber-200 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 rounded"
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
                          class="w-20 text-center py-1 px-2 text-sm font-bold border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-white"
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
                          class="px-2 py-1 text-[11px] font-semibold bg-amber-100 hover:bg-amber-200 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 rounded"
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
                    <td class="py-3 px-4 text-right font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">
                      PKR {{ formatNumber(item.line_total) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Step 3: Settlement & Reason -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Reason & Adjustments -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-4">
              <h2 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                Step 3: Return Reason & Financial Settlement
              </h2>

              <!-- Reason -->
              <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Reason for Returning Goods <span class="text-red-500">*</span>
                </label>
                <textarea
                  v-model="returnForm.reason"
                  rows="3"
                  placeholder="State reason (e.g. Defective rolls, damaged goods, excess supply, wrong specifications sent by supplier)..."
                  class="w-full text-sm p-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-white"
                  required
                ></textarea>
              </div>

              <!-- Adjustments & Refund Type -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                  <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Other Adjustment / Deduction (PKR)
                  </label>
                  <input
                    v-model.number="returnForm.other_adjustments"
                    @input="recalculateTotals"
                    type="number"
                    step="0.01"
                    placeholder="Enter 0 or +/- amount"
                    class="w-full text-sm py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-white"
                  />
                  <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                    Shipping deductions, restocking penalty, or supplier compensations.
                  </p>
                </div>

                <div>
                  <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Settlement Method <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="returnForm.refund_type"
                    class="w-full text-sm py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-white font-medium"
                    required
                  >
                    <option value="credit">Credit (Deduct Our Payable Liability in Supplier Ledger)</option>
                    <option value="cash">Cash Received (Supplier refunded in Cash)</option>
                    <option value="bank">Bank Transfer (Supplier refunded into Bank Account)</option>
                  </select>
                  <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                    <span v-if="returnForm.refund_type === 'credit'">Directly reduces what we owe to the supplier in their ledger balance.</span>
                    <span v-else-if="returnForm.refund_type === 'cash'">Records a cash received transaction from the supplier.</span>
                    <span v-else-if="returnForm.refund_type === 'bank'">Records a bank receipt from the supplier into our bank balance.</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Grand Total Summary Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm flex flex-col justify-between space-y-4">
              <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-3">
                  Return Calculation
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
                    <span class="text-base font-bold text-gray-900 dark:text-white">Total Return:</span>
                    <span class="text-xl font-extrabold text-amber-600 dark:text-amber-400">
                      PKR {{ formatNumber(returnGrandTotal) }}
                    </span>
                  </div>

                  <div class="p-2.5 bg-amber-50 dark:bg-amber-950/20 rounded-lg text-xs text-amber-700 dark:text-amber-300 font-medium">
                    Settlement: <span class="uppercase font-bold">{{ returnForm.refund_type }}</span>
                  </div>
                </div>
              </div>

              <!-- Submit Button -->
              <div>
                <button
                  type="submit"
                  :disabled="submitting || returnGrandTotal <= 0"
                  class="w-full py-3 px-4 bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition flex items-center justify-center gap-2"
                >
                  <svg v-if="submitting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                  </svg>
                  <span>{{ submitting ? 'Processing Purchase Return...' : 'Confirm & Deduct Stock' }}</span>
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
  initialPurchase: Object,
  recentPurchases: Array,
  hasOpenRegister: Boolean,
})

const searchQuery = ref('')
const searching = ref(false)
const searchError = ref('')
const submitting = ref(false)
const loadedPurchase = ref(props.initialPurchase || null)

const returnForm = reactive({
  purchase_id: props.initialPurchase?.id || null,
  reason: '',
  refund_type: 'credit',
  other_adjustments: 0,
  items: props.initialPurchase?.items || [],
})

const onRecentSelect = (purchaseId) => {
  if (!purchaseId) return
  searchQuery.value = purchaseId
  searchPurchase()
}

const searchPurchase = async () => {
  if (!searchQuery.value.toString().trim()) return
  searching.value = true
  searchError.value = ''

  try {
    const res = await axios.get(route('returns.purchases.search'), {
      params: { q: searchQuery.value.toString().trim() }
    })
    if (res.data.success && res.data.purchase) {
      loadedPurchase.value = res.data.purchase
      returnForm.purchase_id = res.data.purchase.id
      returnForm.items = res.data.purchase.items
      returnForm.other_adjustments = 0
      returnForm.refund_type = 'credit'
    }
  } catch (err) {
    searchError.value = err.response?.data?.message || 'Purchase bill not found. Please check PO number.'
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
    item.return_width_input = item.width_input || item.roll_width_inch || null
    item.return_width_unit = item.width_unit || 'in'
    item.return_length_input = item.length_input || item.roll_length_meter || null
    item.return_length_unit = item.length_unit || 'm'
    item.return_pieces = item.rolls_count || 1
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
    alert('Please enter a return quantity or dimensions/sq.ft for at least one item.')
    return
  }

  if (!returnForm.reason.trim()) {
    alert('Please provide a reason for the purchase return.')
    return
  }

  submitting.value = true

  router.post(route('returns.purchases.store'), {
    purchase_id: returnForm.purchase_id,
    reason: returnForm.reason,
    refund_type: returnForm.refund_type,
    other_adjustments: returnForm.other_adjustments,
    items: returnForm.items.map(it => ({
      purchase_item_id: it.purchase_item_id,
      return_quantity: it.return_quantity || 0,
      return_units_sqft: it.return_units_sqft || 0,
      length_input: it.return_length_input || null,
      length_unit: it.return_length_unit || null,
      width_input: it.return_width_input || null,
      width_unit: it.return_width_unit || null,
      return_pieces: it.return_pieces || 1,
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
