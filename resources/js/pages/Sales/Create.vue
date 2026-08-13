<template>
  <AppLayout title="Add Sale">
    <!-- Register Not Open Warning Banner -->
    <div v-if="!hasOpenRegister" class="bg-red-600 border-l-4 border-red-900 px-4 py-3 shadow-lg mb-4 mx-4 mt-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <svg class="h-6 w-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
          <div>
            <p class="font-bold text-white">Cash Register Not Open</p>
            <p class="text-sm text-red-100">You must open the cash register before making any sales.</p>
          </div>
        </div>
        <a 
          href="/registers" 
          class="bg-white text-red-600 px-4 py-2 rounded font-bold hover:bg-gray-100 transition-colors"
        >
          Open Register
        </a>
      </div>
    </div>

    <div class="flex flex-col min-h-screen bg-gray-100 dark:bg-gray-900">
      
      <!-- Top Bar: Customer Selection -->
      <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-3 shadow-sm z-20">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
          <div class="w-full md:w-1/2 relative">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Select Customer</label>
            <div class="flex relative">
              <div class="relative w-full">
                <input
                  type="text"
                  v-model="customerSearchQuery"
                  @focus="showCustomerDropdown = true"
                  @input="showCustomerDropdown = true"
                  @blur="setTimeout(() => showCustomerDropdown = false, 200)"
                  placeholder="Search Customer..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                />
                <div 
                  v-if="showCustomerDropdown" 
                  class="absolute z-50 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-b-lg shadow-lg max-h-60 overflow-y-auto mt-1"
                >
                  <div 
                    v-for="customer in filteredCustomers" 
                    :key="customer.id"
                    @click="selectCustomer(customer)"
                    class="px-4 py-2 hover:bg-indigo-50 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-0 text-sm"
                  >
                    <div class="font-medium text-gray-800 dark:text-gray-200">{{ customer.name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ customer.phone }}</div>
                  </div>
                  <div v-if="filteredCustomers.length === 0" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                    No customers found
                  </div>
                </div>
              </div>
              <button
                @click="showCustomerForm = true"
                class="px-3 bg-indigo-600 text-white rounded-r-lg hover:bg-indigo-700 transition-colors"
                title="Add New Customer"
              >
                <ModernIcon name="plus" size="sm" />
              </button>
            </div>
            
            <!-- Walk-in Customer Name Input -->
            <div v-if="isWalkInCustomer" class="mt-2">
              <input 
                v-model="walkInName"
                type="text"
                placeholder="Enter Walk-in Customer Name (for Bill)..."
                class="w-full px-3 py-2 border-2 border-indigo-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-indigo-700 dark:text-white text-sm"
              />
            </div>

            <!-- Invoice Date Input -->
            <div class="mt-2">
               <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Invoice Date (Optional)</label>
               <input 
                 type="date" 
                 v-model="invoiceDate"
                 class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
               />
            </div>
          </div>
          
          <div class="flex gap-3 w-full md:w-auto">
            <div class="bg-orange-600 text-white px-4 py-2 rounded-lg shadow text-sm font-medium flex items-center">
              <ModernIcon name="dollar" size="sm" class="mr-2" />
              Balance: {{ formatCurrency(selectedCustomerInfo?.credit_used || 0) }}
            </div>
            <div class="bg-red-600 text-white px-4 py-2 rounded-lg shadow text-sm font-medium flex items-center">
              <ModernIcon name="clock" size="sm" class="mr-2" />
              Limit: {{ formatCurrency(selectedCustomerInfo?.credit_limit || 0) }}
            </div>
            <div class="bg-green-600 text-white px-4 py-2 rounded-lg shadow text-sm font-medium flex items-center">
              <ModernIcon name="cash" size="sm" class="mr-2" />
              Advance: {{ formatCurrency(selectedCustomerInfo?.advance || 0) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Area -->
      <div class="flex flex-1 flex-col md:flex-row">
        
        <!-- Left Panel: Add Item & Cart -->
        <div class="w-full flex flex-col border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
          
          <!-- Add Item Section -->
          <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            <h2 class="text-lg font-semibold text-indigo-700 dark:text-indigo-400 mb-3 flex items-center">
              <ModernIcon name="shopping-cart" size="sm" class="mr-2" />
              Add Item to Cart
            </h2>
            
            <!-- Search Product (Show only if no product selected) -->
            <div v-if="!selectedProductForCart" class="mb-4 flex gap-2">
              <div class="relative w-full">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <ModernIcon name="search" size="sm" class="text-gray-400" />
                  </div>
                  <input
                    ref="searchInput"
                    v-model="searchQuery"
                    @input="searchProducts"
                    type="text"
                    placeholder="Type product name or scan barcode..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                  />
                  <!-- Dropdown results -->
                  <div v-if="searchQuery.length > 1 && showSearchResults" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                    <div 
                      v-for="product in products" 
                      :key="product.id"
                      @click="selectProductForEdit(product)"
                      class="px-4 py-2 hover:bg-indigo-50 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-0"
                    >
                      <div class="flex justify-between">
                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ product.name }}</span>
                        <span class="text-indigo-600 dark:text-indigo-400">{{ formatCurrency(product.sale_rate) }}</span>
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">Stock: {{ product.current_stock }} | SKU: {{ product.sku }}</div>
                    </div>
                  </div>
              </div>
              
              <button 
                @click="showCustomItemModal = true"
                class="px-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center justify-center"
                title="Add Custom Item"
              >
                <ModernIcon name="plus" size="sm" />
              </button>
            </div>

            <!-- Selected Product Display (Show when product is selected) -->
            <div v-else class="mb-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-3 flex justify-between items-center">
                <div>
                    <h3 class="text-sm font-bold text-indigo-900 dark:text-indigo-100">{{ selectedProductForCart.name }}</h3>
                    <p class="text-xs text-indigo-700 dark:text-indigo-300">
                        Stock: 
                        <span class="font-bold">
                            {{ selectedProductForCart.type === 'panaflex_roll' ? selectedProductForCart.current_stock + ' sq.ft' : selectedProductForCart.current_stock + ' units' }}
                        </span>
                    </p>
                </div>
                <button 
                    @click="clearSelectedProduct"
                    class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-red-200 dark:border-red-700 text-red-600 dark:text-red-400 rounded-md text-xs font-medium hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors flex items-center gap-1"
                >
                    <ModernIcon name="x" size="xs" />
                    Change Product
                </button>
            </div>

            <!-- Item Details Inputs -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
              <!-- Panaflex Fields -->
              <div v-if="selectedProductForCart?.type === 'panaflex_roll'">
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Width (in)</label>
                <input
                  v-model.number="panaflexForm.width"
                  type="number"
                  step="0.1"
                  placeholder="Width in Inches"
                  class="w-full px-3 py-2 border-2 border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-blue-700 dark:text-white"
                />
              </div>
              <div v-if="selectedProductForCart?.type === 'panaflex_roll'">
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Length (m)</label>
                <input
                  v-model.number="panaflexForm.length"
                  type="number"
                  step="0.1"
                  placeholder="Length in Meters"
                  class="w-full px-3 py-2 border-2 border-green-300 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:border-green-700 dark:text-white"
                />
              </div>

              <!-- Standard Fields -->
              <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Rate ({{ selectedProductForCart?.type === 'panaflex_roll' ? 'Per Sq.Ft' : 'Per Unit' }})</label>
                <input
                  v-model.number="cartItemForm.rate"
                  type="number"
                  class="w-full px-3 py-2 border-2 border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:border-purple-700 dark:text-white"
                />
              </div>
              <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Quantity</label>
                <input
                  v-model.number="cartItemForm.qty"
                  type="number"
                  min="1"
                  class="w-full px-3 py-2 border-2 border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:border-orange-700 dark:text-white"
                />
              </div>
              <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Total Price</label>
                <div class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-800 dark:text-white font-bold">
                  {{ formatCurrency(calculatedItemTotal) }}
                </div>
              </div>
              <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Discount (%)</label>
                <div class="relative">
                  <input
                    v-model.number="cartItemForm.discountPercent"
                    type="number"
                    min="0"
                    max="100"
                    class="w-full px-3 py-2 border-2 border-pink-300 rounded-lg focus:ring-2 focus:ring-pink-500 dark:bg-gray-700 dark:border-pink-700 dark:text-white pr-8"
                  />
                  <span class="absolute right-3 top-2 text-gray-500">%</span>
                </div>
              </div>
              
              <!-- Stock Info -->
              <div v-if="selectedProductForCart" class="flex flex-col justify-end pb-2">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Stock Update</div>
                <div class="flex items-center space-x-2 text-sm">
                   <span class="font-medium text-gray-600 dark:text-gray-300" title="Current Stock">{{ selectedProductForCart.current_stock }}</span>
                   <ModernIcon name="arrow-right" size="xs" class="text-gray-400" />
                   <span class="font-bold text-indigo-600 dark:text-indigo-400" title="Stock After Sale">{{ calculatedRemainingStock }}</span>
                   <span class="text-xs text-gray-400">{{ selectedProductForCart.type === 'panaflex_roll' ? 'sq.ft' : 'units' }}</span>
                </div>
              </div>
            </div>

            <!-- Description Field -->
            <div class="mb-4">
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Description (Optional)</label>
                <textarea
                  v-model="cartItemForm.description"
                  placeholder="Enter custom description for invoice..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                  rows="2"
                ></textarea>
            </div>

            <!-- Add Button -->
            <button
              @click="addItemToCart"
              :disabled="!selectedProductForCart"
              class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-3 rounded-lg shadow-sm transition-colors flex justify-center items-center disabled:opacity-50 disabled:cursor-not-allowed"
              :class="{'bg-indigo-600 hover:bg-indigo-700 text-white': selectedProductForCart}"
            >
              <ModernIcon name="shopping-cart" size="sm" class="mr-2" />
              ADD TO CART
            </button>
          </div>

          <!-- Cart Table -->
          <div class="flex-1 overflow-y-auto p-0">
            <table class="w-full text-base text-left">
              <thead class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0">
                <tr>
                  <th class="px-5 py-4">Product</th>
                  <th class="px-5 py-4">Description</th>
                  <th class="px-5 py-4 text-right">Price</th>
                  <th class="px-5 py-4 text-center">Qty</th>
                  <th class="px-5 py-4 text-center">Disc%</th>
                  <th class="px-5 py-4 text-right">Total</th>
                  <th class="px-5 py-4 text-center">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="cart.length === 0">
                  <td colspan="7" class="px-5 py-12 text-center text-gray-500 dark:text-gray-400">
                    <div class="flex flex-col items-center justify-center">
                      <ModernIcon name="shopping-cart" size="xl" class="mb-3 text-gray-300" />
                      <p>Cart is empty. Add items from above.</p>
                    </div>
                  </td>
                </tr>
                <tr 
                  v-for="(item, index) in cart" 
                  :key="index"
                  class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                  <td class="px-5 py-4 font-medium text-gray-900 dark:text-white">
                    {{ item.name }}
                    <div v-if="item.type === 'panaflex_roll'" class="text-sm text-gray-500">
                      {{ item.length }}x{{ item.width }} ({{ item.units_sqft }} sq.ft)
                    </div>
                  </td>
                  <td class="px-5 py-4 text-sm text-gray-500">
                    <span v-if="editingIndex !== index">{{ item.description || '-' }}</span>
                    <input 
                      v-else 
                      v-model="editingItem.description" 
                      type="text" 
                      class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"
                      placeholder="Description..."
                    />
                  </td>
                  <td class="px-5 py-4 text-right">
                    <span v-if="editingIndex !== index">{{ formatCurrency(item.rate) }}</span>
                    <input 
                      v-else 
                      v-model.number="editingItem.rate" 
                      type="number" 
                      step="0.01" 
                      min="0"
                      class="w-24 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-right dark:bg-gray-700 dark:text-white"
                    />
                  </td>
                  <td class="px-5 py-4 text-center">
                    <span v-if="editingIndex !== index">{{ item.qty }}</span>
                    <input 
                      v-else 
                      v-model.number="editingItem.qty" 
                      type="number" 
                      min="1" 
                      step="1"
                      class="w-16 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-center dark:bg-gray-700 dark:text-white"
                    />
                  </td>
                  <td class="px-5 py-4 text-center">
                    <span v-if="editingIndex !== index">{{ item.discountPercent || 0 }}%</span>
                    <div v-else class="flex items-center justify-center">
                      <template v-if="item.type === 'custom'">
                        <span class="text-xs text-gray-400 mr-1">Rs.</span>
                        <input 
                          v-model.number="editingItem.discount" 
                          type="number" 
                          min="0" 
                          step="0.01"
                          class="w-20 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-center dark:bg-gray-700 dark:text-white"
                        />
                      </template>
                      <template v-else>
                        <input 
                          v-model.number="editingItem.discountPercent" 
                          type="number" 
                          min="0" 
                          max="100" 
                          step="1"
                          class="w-16 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 text-center dark:bg-gray-700 dark:text-white"
                        />
                        <span class="ml-1 text-sm">%</span>
                      </template>
                    </div>
                  </td>
                  <td class="px-5 py-4 text-right font-semibold">
                    <span v-if="editingIndex !== index">{{ formatCurrency(item.line_total) }}</span>
                    <span v-else class="text-indigo-600 dark:text-indigo-400 font-bold">{{ formatCurrency(calculatedEditingItemTotal) }}</span>
                  </td>
                  <td class="px-5 py-4 text-center">
                    <div v-if="editingIndex === index" class="flex justify-center gap-2">
                      <button @click="saveCartItem(index)" class="text-green-600 hover:text-green-800" title="Save">
                        <ModernIcon name="check" size="md" />
                      </button>
                      <button @click="cancelEditCartItem" class="text-gray-500 hover:text-gray-700" title="Cancel">
                        <ModernIcon name="x" size="md" />
                      </button>
                    </div>
                    <div v-else class="flex justify-center gap-2">
                      <button @click="startEditCartItem(index, item)" class="text-blue-500 hover:text-blue-700" title="Edit">
                        <ModernIcon name="edit" size="md" />
                      </button>
                      <button @click="removeCartItem(index)" class="text-red-500 hover:text-red-700" title="Delete">
                        <ModernIcon name="trash" size="md" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- System Description Field -->
          <div class="p-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
             <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">System Description (Internal Note - Not on Invoice)</label>
             <textarea 
               v-model="systemDescription"
               rows="2"
               placeholder="Enter internal notes for this sale (Visible in Edit/Show only)..."
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
             ></textarea>
          </div>
        </div>
      </div>

      <!-- Bottom Bar: Payment -->
      <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4 shadow-lg z-30">
        <div class="flex flex-col lg:flex-row items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="text-center">
              <div class="text-xs text-gray-500 uppercase">Items</div>
              <div class="font-bold text-xl">{{ cart.length }}</div>
            </div>
            <div class="text-center">
              <div class="text-xs text-gray-500 uppercase">Total</div>
              <div class="font-bold text-2xl text-indigo-600">{{ formatCurrency(billTotal) }}</div>
            </div>
          </div>

          <div class="flex flex-1 gap-3 w-full lg:w-auto">
            <div class="flex-1">
              <label class="block text-xs text-gray-500 mb-1">Discount</label>
              <input 
                v-model.number="discountAmount"
                type="number" 
                class="w-full px-3 py-2 border-2 border-red-300 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:border-red-700"
              />
            </div>
            <div class="flex-1">
              <label class="block text-xs text-gray-500 mb-1">Received</label>
              <input 
                v-model.number="paidAmount"
                type="number" 
                :disabled="paymentMethod === 'credit'"
                class="w-full px-3 py-2 border-2 border-teal-300 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-teal-700 disabled:bg-gray-200 disabled:cursor-not-allowed"
              />
            </div>
            <div class="flex-1">
              <label class="block text-xs text-gray-500 mb-1">Method</label>
              <select 
                v-model="paymentMethod"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600"
              >
                <option value="cash">Cash</option>
                <option value="credit">Credit</option>
                <option value="bank">Bank Transfer</option>
              </select>
            </div>
          </div>

          <button 
            @click="processPayment"
            :disabled="!hasOpenRegister"
            :title="!hasOpenRegister ? 'Please open a cash register first' : ''"
            class="w-full lg:w-auto bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-8 rounded-lg shadow-lg flex items-center justify-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <ModernIcon name="printer" size="md" class="mr-2" />
            PAY & PRINT
          </button>
        </div>
      </div>

    </div>

    <!-- Custom Item Modal -->
    <div v-if="showCustomItemModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-[60]">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full shadow-xl">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add Custom Item</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
            <input v-model="customItemForm.name" type="text" placeholder="Item name..." class="mt-1 w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description (Optional)</label>
            <textarea 
              v-model="customItemForm.description" 
              rows="4" 
              maxlength="500"
              placeholder="Additional details (Max 500 chars)..." 
              class="mt-1 w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white resize-y"
            ></textarea>
            <div class="text-xs text-gray-500 text-right mt-1">{{ customItemForm.description?.length || 0 }}/500</div>
          </div>
          <div class="grid grid-cols-2 gap-4">
             <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Width (Optional)</label>
                <input v-model.number="customItemForm.width" type="number" step="0.01" min="0" placeholder="0" class="mt-1 w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
             </div>
             <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Height (Optional)</label>
                <input v-model.number="customItemForm.height" type="number" step="0.01" min="0" placeholder="0" class="mt-1 w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
             </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
             <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Price/Rate</label>
                <div class="relative">
                  <span class="absolute left-3 top-2 text-gray-500">PKR</span>
                  <input v-model.number="customItemForm.rate" type="number" step="0.01" min="0" class="mt-1 w-full pl-12 pr-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                </div>
             </div>
             <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label>
                <input v-model.number="customItemForm.qty" type="number" step="1" min="1" class="mt-1 w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
             </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Discount (Amount)</label>
            <div class="relative">
               <span class="absolute left-3 top-2 text-gray-500">PKR</span>
               <input v-model.number="customItemForm.discount" type="number" step="0.01" min="0" class="mt-1 w-full pl-12 pr-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
            </div>
          </div>
          
          <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg flex justify-between items-center text-sm">
             <span class="text-gray-600 dark:text-gray-400">Total:</span>
             <span class="font-bold text-lg text-indigo-600 dark:text-indigo-400">{{ formatCurrency(calculateCustomItemTotal()) }}</span>
          </div>

          <div class="flex gap-2 mt-6">
            <button @click="addCustomItemToCart" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 shadow-lg">Add to Cart</button>
            <button @click="showCustomItemModal = false" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg hover:bg-gray-300">Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Customer Form Modal (Reused) -->
    <div v-if="showCustomerForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full shadow-xl">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add New Customer</h3>
        <div class="space-y-4">
          <input v-model="newCustomer.name" placeholder="Name" class="w-full px-3 py-2 border rounded-lg" />
          <input v-model="newCustomer.phone" placeholder="Phone" class="w-full px-3 py-2 border rounded-lg" />
          <textarea v-model="newCustomer.address" placeholder="Address" class="w-full px-3 py-2 border rounded-lg"></textarea>
          <div class="flex gap-2">
            <button @click="saveNewCustomer" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg">Save</button>
            <button @click="showCustomerForm = false" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg">Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Sale Success Modal -->
    <div v-if="showSuccessModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-[9999]">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-lg w-full shadow-2xl transform transition-all">
        <div class="text-center mb-6">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Sale Completed Successfully!</h3>
          <p class="text-gray-600 dark:text-gray-400 mt-2">
            Invoice: {{ lastSaleResponse?.invoice_no }}
          </p>
        </div>
        
        <div class="grid grid-cols-2 gap-3">
          <button
            @click="previewA4Invoice"
            class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors text-sm"
          >
            Preview A4
          </button>
          <button
            @click="printA4Invoice"
            class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors text-sm"
          >
            Print A4
          </button>
          <button
            @click="preview80mmReceipt"
            class="bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors text-sm"
          >
            Preview 80mm
          </button>
          <button
            @click="print80mmReceipt"
            class="bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors text-sm"
          >
            Print 80mm
          </button>
        </div>
        
        <button
          @click="closeSuccessModal"
          class="w-full mt-4 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 py-2 px-4 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
        >
          Close
        </button>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue'
import { formatCurrency } from '@/utils/currency'
import ModernIcon from '@/components/ModernIcon.vue'

const props = defineProps({
  activeRegisterSession: Object,
  hasOpenRegister: Boolean,
  editSale: Object
})

// State
const customers = ref([])
const products = ref([])
const cart = ref([])
const selectedCustomer = ref(null)
const selectedCustomerInfo = ref(null)
const searchQuery = ref('')
const showSearchResults = ref(false)
const selectedCategory = ref('all')
const selectedProductForCart = ref(null)
const showCustomerForm = ref(false)

const editingIndex = ref(null)
const editingItem = ref(null)

// Forms
const cartItemForm = ref({
  qty: 1,
  rate: 0,
  discountPercent: 0,
  description: ''
})

const showCustomItemModal = ref(false)
const customItemForm = ref({
  name: '',
  description: '',
  width: 0,
  height: 0,
  rate: 0,
  qty: 1,
  discount: 0
})

const calculateCustomItemTotal = () => {
  const width = parseFloat(customItemForm.value.width) || 0
  const height = parseFloat(customItemForm.value.height) || 0
  const rate = parseFloat(customItemForm.value.rate) || 0
  const qty = parseFloat(customItemForm.value.qty) || 1
  const discount = parseFloat(customItemForm.value.discount) || 0

  let subtotal = 0
  
  // If both width and height are provided, multiply: Width * Height * Rate * Quantity
  if (width > 0 && height > 0) {
    subtotal = width * height * rate * qty
  } else {
    // Otherwise, standard calculation: Rate * Quantity
    subtotal = rate * qty
  }
  
  // Apply discount
  const total = subtotal - discount
  return total > 0 ? total : 0
}

const addCustomItemToCart = () => {
    if(!customItemForm.value.name || customItemForm.value.rate <= 0 || customItemForm.value.qty <= 0) {
        alert('Please enter valid item name, rate and quantity.')
        return
    }

    const width = parseFloat(customItemForm.value.width) || 0
    const height = parseFloat(customItemForm.value.height) || 0
    const rate = parseFloat(customItemForm.value.rate)
    const qty = parseFloat(customItemForm.value.qty)
    const discount = parseFloat(customItemForm.value.discount || 0)
    
    // Calculate subtotal based on dimensions
    let subtotal = 0
    if (width > 0 && height > 0) {
      subtotal = width * height * rate * qty
    } else {
      subtotal = rate * qty
    }
    
    const total = subtotal - discount

    cart.value.push({
        product_id: null,
        name: customItemForm.value.name,
        type: 'custom',
        rate: rate,
        qty: qty,
        discount: discount,
        discountPercent: subtotal > 0 ? ((discount / subtotal) * 100).toFixed(1) : 0,
        line_total: total,
        width: width > 0 ? width : null,
        height: height > 0 ? height : null,
        length: null,
        units_sqft: (width > 0 && height > 0) ? (width * height) : 0,
        length_unit: null,
        width_unit: null,
        description: customItemForm.value.description || null
    })

    showCustomItemModal.value = false
    customItemForm.value = { name: '', description: '', width: 0, height: 0, rate: 0, qty: 1, discount: 0 }
}

const panaflexForm = ref({
  width: '',
  length: '',
  qty: 1,
  rate: 0
})

const newCustomer = ref({ name: '', phone: '', address: '' })
const walkInName = ref('')
const systemDescription = ref('')
const invoiceDate = ref('')

// Payment State
const discountAmount = ref(0)
const paidAmount = ref(0)
const paymentMethod = ref('cash')

// Success Modal State
const showSuccessModal = ref(false)
const lastSaleResponse = ref(null)

// Customer Search State
const customerSearchQuery = ref('')
const showCustomerDropdown = ref(false)

// Computed
const filteredCustomers = computed(() => customers.value)

const isWalkInCustomer = computed(() => {
  if (!selectedCustomer.value) return false
  const customer = customers.value.find(c => c.id === selectedCustomer.value)
  return customer && customer.name === 'Walk-in Customer'
})

const billTotal = computed(() => {
  const subtotal = cart.value.reduce((sum, item) => sum + item.line_total, 0)
  return subtotal - discountAmount.value
})

const calculatedRemainingStock = computed(() => {
  if (!selectedProductForCart.value) return 0
  const current = parseFloat(selectedProductForCart.value.current_stock) || 0
  let deduction = 0
  
  if (selectedProductForCart.value.type === 'panaflex_roll') {
     deduction = (panaflexCalculation.value?.area_sqft || 0) * (cartItemForm.value.qty || 1)
  } else {
     deduction = cartItemForm.value.qty || 0
  }
  
  return (current - deduction).toFixed(2)
})

const calculatedItemTotal = computed(() => {
  const rate = parseFloat(cartItemForm.value.rate) || 0
  const qty = parseFloat(cartItemForm.value.qty) || 0
  
  if (selectedProductForCart.value?.type === 'panaflex_roll') {
    const area = panaflexCalculation.value?.area_sqft || 0
    return rate * area * qty
  }
  
  return rate * qty
})

const panaflexCalculation = ref(null)

// Methods
const selectCustomer = (customer) => {
  selectedCustomer.value = customer.id
  customerSearchQuery.value = customer.name
  showCustomerDropdown.value = false
  updateCustomerInfo()
}

const searchProducts = async () => {
  if (searchQuery.value.length < 2) {
    showSearchResults.value = false
    return
  }
  try {
    const response = await axios.get('/api/products/search', { params: { q: searchQuery.value } })
    products.value = response.data
    showSearchResults.value = true
  } catch (e) {
    console.error(e)
  }
}

const selectProductForEdit = (product) => {
  selectedProductForCart.value = product
  cartItemForm.value.rate = product.sale_rate
  cartItemForm.value.qty = 1
  cartItemForm.value.discountPercent = 0
  cartItemForm.value.description = ''
  showSearchResults.value = false
  searchQuery.value = product.name

  if (product.type === 'panaflex_roll') {
    panaflexForm.value.rate = product.sale_rate
    panaflexForm.value.qty = 1
    // Initialize width/length if needed, or clear them
    panaflexForm.value.width = ''
    panaflexForm.value.length = ''
  }
}

const addItemToCart = () => {
  if (!selectedProductForCart.value) return

  const product = selectedProductForCart.value
  const form = cartItemForm.value
  
  // Stock Validation
  const currentStock = parseFloat(product.current_stock) || 0
  let requiredQty = 0

  if (product.type === 'panaflex_roll') {
    const area = panaflexCalculation.value?.area_sqft || 0
    requiredQty = area * form.qty
  } else {
    requiredQty = parseFloat(form.qty) || 0
  }

  if (requiredQty > currentStock) {
    alert(`Insufficient Stock!\nAvailable: ${currentStock}\nRequired: ${requiredQty}`)
    return
  }

  // Calculate line total
  let gross = 0
  if (product.type === 'panaflex_roll') {
    const area = panaflexCalculation.value?.area_sqft || 0
    gross = form.rate * area * form.qty
  } else {
    gross = form.rate * form.qty
  }

  const discount = (gross * form.discountPercent) / 100
  const total = gross - discount

  cart.value.push({
    product_id: product.id,
    name: product.name,
    description: form.description || null,
    type: product.type,
    rate: form.rate,
    qty: form.qty,
    discountPercent: form.discountPercent,
    discount: discount,
    line_total: total,
    // Panaflex specific
    ...(product.type === 'panaflex_roll' ? {
      width: panaflexForm.value.width,
      length: panaflexForm.value.length,
      units_sqft: panaflexCalculation.value?.area_sqft || 0,
      length_unit: 'm',
      width_unit: 'in'
    } : {})
  })

  // Reset Form Fields BUT KEEP PRODUCT SELECTED AND RATE PRESERVED
  // We keep the rate the user just used, so they don't have to re-type it for the next item of the same product.
  cartItemForm.value = { 
    qty: 1, 
    rate: form.rate, 
    discountPercent: 0, 
    description: '' 
  }
  
  // Only reset dimensions if it's a panaflex
  if (product.type === 'panaflex_roll') {
    panaflexForm.value.width = ''
    panaflexForm.value.length = ''
    panaflexCalculation.value = null
    // Sync the rate for panaflex form too, just in case
    panaflexForm.value.rate = form.rate;
  }
  
  // Play Sound (Optional - can add later)
}

const clearSelectedProduct = () => {
  selectedProductForCart.value = null
  searchQuery.value = ''
  cartItemForm.value = { qty: 1, rate: 0, discountPercent: 0, description: '' }
  panaflexForm.value = { width: '', length: '', qty: 1, rate: 0 }
  panaflexCalculation.value = null
  
  // Focus back on search input (next tick)
  setTimeout(() => {
    const searchInput = document.querySelector('input[placeholder="Type product name or scan barcode..."]')
    if (searchInput) searchInput.focus()
  }, 100)
}

const removeCartItem = (index) => {
  cart.value.splice(index, 1)
  if (editingIndex.value === index) {
    cancelEditCartItem()
  }
}

const startEditCartItem = (index, item) => {
  editingIndex.value = index
  editingItem.value = JSON.parse(JSON.stringify(item))
}

const cancelEditCartItem = () => {
  editingIndex.value = null
  editingItem.value = null
}

const saveCartItem = (index) => {
  if (!editingItem.value) return
  
  const item = editingItem.value
  let gross = 0
  let discount = 0
  
  if (item.type === 'panaflex_roll') {
    const rate = parseFloat(item.rate) || 0
    const qty = parseFloat(item.qty) || 0
    const area = parseFloat(item.units_sqft) || 0
    gross = rate * area * qty
    const discountPercent = parseFloat(item.discountPercent) || 0
    discount = (gross * discountPercent) / 100
    item.discount = discount
    item.line_total = parseFloat((gross - discount).toFixed(2))
  } else if (item.type === 'simple') {
    const rate = parseFloat(item.rate) || 0
    const qty = parseFloat(item.qty) || 0
    gross = rate * qty
    const discountPercent = parseFloat(item.discountPercent) || 0
    discount = (gross * discountPercent) / 100
    item.discount = discount
    item.line_total = parseFloat((gross - discount).toFixed(2))
  } else if (item.type === 'custom') {
    const rate = parseFloat(item.rate) || 0
    const qty = parseFloat(item.qty) || 0
    const width = parseFloat(item.width) || 0
    const height = parseFloat(item.height) || 0
    if (width > 0 && height > 0) {
      gross = width * height * rate * qty
    } else {
      gross = rate * qty
    }
    discount = parseFloat(item.discount) || 0
    item.discountPercent = gross > 0 ? parseFloat(((discount / gross) * 100).toFixed(1)) : 0
    item.line_total = parseFloat((gross - discount).toFixed(2))
  }
  
  // Stock Validation
  if (item.product_id) {
    const product = products.value.find(p => p.id === item.product_id)
    if (product) {
      const currentStock = parseFloat(product.current_stock) || 0
      let requiredQty = 0
      if (item.type === 'panaflex_roll') {
        requiredQty = (item.units_sqft || 0) * (item.qty || 1)
      } else {
        requiredQty = parseFloat(item.qty) || 0
      }
      if (requiredQty > currentStock) {
        alert(`Insufficient Stock!\nAvailable: ${currentStock}\nRequired: ${requiredQty}`)
        return
      }
    }
  }

  cart.value[index] = item
  cancelEditCartItem()
}

const calculatedEditingItemTotal = computed(() => {
  if (!editingItem.value) return 0
  const item = editingItem.value
  let gross = 0
  
  if (item.type === 'panaflex_roll') {
    const rate = parseFloat(item.rate) || 0
    const qty = parseFloat(item.qty) || 0
    const area = parseFloat(item.units_sqft) || 0
    gross = rate * area * qty
    const discountPercent = parseFloat(item.discountPercent) || 0
    return gross - (gross * discountPercent) / 100
  } else if (item.type === 'simple') {
    const rate = parseFloat(item.rate) || 0
    const qty = parseFloat(item.qty) || 0
    gross = rate * qty
    const discountPercent = parseFloat(item.discountPercent) || 0
    return gross - (gross * discountPercent) / 100
  } else if (item.type === 'custom') {
    const rate = parseFloat(item.rate) || 0
    const qty = parseFloat(item.qty) || 0
    const width = parseFloat(item.width) || 0
    const height = parseFloat(item.height) || 0
    if (width > 0 && height > 0) {
      gross = width * height * rate * qty
    } else {
      gross = rate * qty
    }
    const discount = parseFloat(item.discount) || 0
    return gross - discount
  }
  return 0
})

const updateCustomerInfo = async () => {
  if (!selectedCustomer.value) {
    selectedCustomerInfo.value = null
    return
  }
  try {
    const response = await axios.get(`/api/customers/${selectedCustomer.value}/pos-info`)
    selectedCustomerInfo.value = response.data
  } catch (e) {
    console.error(e)
  }
}

const loadCustomers = async (query = '', isInitialLoad = false) => {
  try {
    const response = await axios.get('/api/customers/search', { params: { q: query } })
    customers.value = response.data
    
    if (isInitialLoad) {
      // Set default to Walk-in Customer
      const walkIn = customers.value.find(c => c.name === 'Walk-in Customer')
      if (walkIn) {
        selectedCustomer.value = walkIn.id
        customerSearchQuery.value = walkIn.name
        // Don't fetch info for walk-in to keep UI clean, or fetch if needed
        // updateCustomerInfo() 
      } else if (customers.value.length > 0) {
        selectedCustomer.value = customers.value[0].id
        customerSearchQuery.value = customers.value[0].name
        updateCustomerInfo()
      }
    }
  } catch (e) {
    console.error('Failed to load customers', e)
  }
}

let searchTimeout = null
watch(customerSearchQuery, (newQuery) => {
  // Avoid searching if the query matches the selected customer's name (prevent loop on selection)
  const selected = customers.value.find(c => c.id === selectedCustomer.value)
  if (selected && selected.name === newQuery) return

  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadCustomers(newQuery)
  }, 300)
})

// Success Modal Methods
const closeSuccessModal = () => {
  showSuccessModal.value = false
  lastSaleResponse.value = null
  
  // Clear cart and reset form after closing modal
  cart.value = []
  paidAmount.value = 0
  discountAmount.value = 0
  systemDescription.value = ''
  
  // Reset to Walk-in Customer
  const walkIn = customers.value.find(c => c.name === 'Walk-in Customer')
  if (walkIn) {
    selectedCustomer.value = walkIn.id
  } else {
    selectedCustomer.value = ''
  }
  
  walkInName.value = ''
  selectedCustomerInfo.value = null
}

const previewA4Invoice = () => {
  if (lastSaleResponse.value?.preview_urls?.a4) {
    window.open(lastSaleResponse.value.preview_urls.a4, '_blank')
  }
}

const printA4Invoice = () => {
  if (lastSaleResponse.value?.printable_urls?.a4) {
    window.open(lastSaleResponse.value.printable_urls.a4, '_blank')
  }
}

const preview80mmReceipt = () => {
  if (lastSaleResponse.value?.preview_urls?.['80mm']) {
    window.open(lastSaleResponse.value.preview_urls['80mm'], '_blank')
  }
}

const print80mmReceipt = () => {
  if (lastSaleResponse.value?.printable_urls?.['80mm']) {
    window.open(lastSaleResponse.value.printable_urls['80mm'], '_blank')
  }
}

const processPayment = async () => {
  if (cart.value.length === 0) return alert('Cart is empty')
  
  try {
    let response;
    const payload = {
      items: cart.value,
      discount_total: discountAmount.value,
      payment_type: paymentMethod.value,
      customer_id: selectedCustomer.value === 'walk-in' ? null : selectedCustomer.value,
      custom_customer_name: isWalkInCustomer.value ? walkInName.value : null,
      advance_used: 0, // Simplified for now
      paid_amount: paidAmount.value, // Send the actual amount user entered
      system_description: systemDescription.value,
      invoice_date: invoiceDate.value || null,
    };

    if (props.editSale) {
        response = await axios.put(route('pos.update', props.editSale.id), payload);
    } else {
        response = await axios.post('/api/pos/checkout', payload);
    }
    
    if (response.data.success) {
      lastSaleResponse.value = response.data
      showSuccessModal.value = true
    }
  } catch (e) {
    alert('Payment Failed: ' + (e.response?.data?.message || e.message))
  }
}

// Watchers for Panaflex Calculation
// Watch for Panaflex calculations
watch([() => panaflexForm.value.width, () => panaflexForm.value.length], async () => {
  if (selectedProductForCart.value?.type === 'panaflex_roll' && panaflexForm.value.width && panaflexForm.value.length) {
     try {
        const response = await axios.post('/api/pos/calc', {
          product_id: selectedProductForCart.value.id,
          type: 'panaflex_roll',
          length: panaflexForm.value.length,
          length_unit: 'm',
          width: panaflexForm.value.width,
          width_unit: 'in',
          qty: 1, // Always 1 to get per-piece area
          rate: cartItemForm.value.rate
        })
        panaflexCalculation.value = {
          area_sqft: response.data.units_sqft,
          line_total: response.data.units_sqft * cartItemForm.value.rate
        }
     } catch (e) {
       console.error(e)
     }
  }
})

// Watch payment method to reset paid amount if credit
watch(paymentMethod, (newMethod) => {
  if (newMethod === 'credit') {
    paidAmount.value = 0
  }
})

const initializeEditMode = () => {
  const sale = props.editSale
  
  // Set Customer
  if (sale.customer_id) {
    selectedCustomer.value = sale.customer_id
    if (sale.customer) {
        customerSearchQuery.value = sale.customer.name
    }
    updateCustomerInfo()
  } else {
    // Walk-in
    const walkIn = customers.value.find(c => c.name === 'Walk-in Customer')
    if (walkIn) {
        selectedCustomer.value = walkIn.id
    }
    walkInName.value = sale.customer_name || ''
  }
  
  // Set Items
  cart.value = sale.sale_items.map(item => {
    const rate = parseFloat(item.rate) || 0
    const qty = parseFloat(item.quantity) || 0
    const type = item.product ? item.product.type : 'custom'
    const discount = parseFloat(item.discount || 0)
    
    let gross = 0
    if (type === 'panaflex_roll') {
      const unitsSqFt = parseFloat(item.units_sqft) || 0
      gross = rate * unitsSqFt * qty
    } else {
      gross = rate * qty
    }
    
    const discountPercent = gross > 0 ? Math.round((discount / gross) * 100) : 0
    
    return {
      product_id: item.product_id,
      name: item.product ? item.product.name : (item.description || 'Custom Item'),
      description: item.description,
      type,
      rate,
      qty,
      discountPercent,
      discount,
      line_total: parseFloat(item.line_total),
      width: item.width_input,
      length: item.length_input,
      units_sqft: item.units_sqft,
      length_unit: item.length_unit || 'ft',
      width_unit: item.width_unit || 'ft'
    }
  })
  
  // Set Financials
  discountAmount.value = parseFloat(sale.discount_total)
  paidAmount.value = sale.payments.reduce((sum, p) => sum + parseFloat(p.amount), 0)
  systemDescription.value = sale.system_description || ''
  
  if (sale.invoice_date) {
      // Ensure date format is YYYY-MM-DD for input type="date"
      invoiceDate.value = sale.invoice_date.toString().split('T')[0]
  }

  // Payment Method
  if (sale.payment_type) {
      paymentMethod.value = sale.payment_type
  } else if (sale.payments && sale.payments.length > 0) {
      paymentMethod.value = sale.payments[0].payment_method
  } else {
      paymentMethod.value = 'credit'
  }
}

onMounted(async () => {
  await loadCustomers('', true)
  // Load initial products for catalog
  searchProducts() 
  
  if (props.editSale) {
    initializeEditMode()
  }
})

const resetCustomerForm = () => {
  newCustomer.value = {
    name: '',
    phone: '',
    address: ''
  }
}

const saveNewCustomer = async () => {
  if (!newCustomer.value.name) {
    alert('Please fill in required fields (Name is mandatory)')
    return
  }

  try {
    const response = await axios.post('/api/customers', {
      name: newCustomer.value.name,
      phone: newCustomer.value.phone,
      address: newCustomer.value.address
    })

    if (response.data.success) {
      customers.value.push(response.data.data)
      selectedCustomer.value = response.data.data.id
      customerSearchQuery.value = response.data.data.name
      showCustomerForm.value = false
      resetCustomerForm()
      alert('Customer added successfully!')
    }
  } catch (error) {
    console.error('Failed to save customer:', error)
    alert(error.response?.data?.message || 'Failed to save customer')
  }
}

</script>
