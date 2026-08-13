<template>
  <AppLayout title="POS">
    <!-- Top Bar -->
    <div class="sticky top-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
      <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">POS</h1>
        
        <div class="flex items-center space-x-2">
          <button 
            @click="clearCart" 
            class="px-3 py-2 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
          >
            New Sale
          </button>
          
          <button 
            @click="showScanner = true" 
            class="p-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
            title="Open Scanner"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
          
          <button 
            @click="showHelp = true" 
            class="p-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
            title="Help (Keyboard Shortcuts)"
          >
            <ModernIcon name="help-circle" size="sm" />
          </button>
        </div>
      </div>
    </div>

    <!-- Register Not Open Warning Banner -->
    <div v-if="!hasOpenRegister" class="bg-red-600 border-l-4 border-red-900 px-4 py-3 shadow-lg">
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
          class="bg-white text-red-600 px-4 py-2 rounded-lg font-semibold hover:bg-red-50 transition-colors ml-4"
        >
          Open Register
        </a>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex flex-col lg:flex-row min-h-screen bg-gray-50 dark:bg-gray-900">
      <!-- Products Panel (Right) -->
      <div class="order-2 lg:order-2 lg:w-1/2 p-4 flex-1 min-h-96">
        <!-- Search -->
        <div class="mb-4">
          <div class="relative max-w-md">
            <input
              ref="searchInput"
              v-model="searchQuery"
              @input="searchProducts"
              type="text"
              placeholder="Scan barcode or type product name... (F2)"
              class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
              <ModernIcon name="search" size="sm" class="text-gray-400" />
            </div>
          </div>
        </div>

        <!-- Product Type Tabs -->
        <div class="mb-4">
          <div class="flex space-x-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg">
            <button
              v-for="tab in productTabs"
              :key="tab.value"
              @click="activeTab = tab.value"
              :class="[
                'flex-1 px-3 py-2 text-sm font-medium rounded-md transition-colors',
                activeTab === tab.value
                  ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-sm'
                  : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'
              ]"
            >
              {{ tab.label }}
            </button>
          </div>
        </div>

        <!-- Products Grid (Scrollable) -->
        <div class="overflow-y-auto lg:max-h-[calc(100vh-200px)] max-h-none">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="product in filteredProducts"
            :key="product.id"
            class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow cursor-pointer"
            @click="addToCart(product)"
          >
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <h3 class="font-medium text-gray-900 dark:text-white text-sm">{{ product.name }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ product.category_name }}</p>
                <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 mt-2">
                  {{ formatCurrency(product.sale_rate) }}
                  <span class="text-xs text-gray-500">{{ product.type === 'panaflex_roll' ? '/sq.ft' : `/${product.unit_symbol}` }}</span>
                </p>
                <p v-if="product.type === 'panaflex_roll'" class="text-xs text-gray-500 mt-1">
                  Roll: {{ product.roll_width_inch }}" × {{ product.roll_length_meter }}m
                </p>
              </div>
              <button
                @click.stop="addToCart(product)"
                class="p-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
              </button>
            </div>
          </div>
        </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
          <p class="text-gray-500 dark:text-gray-400">Loading products...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredProducts.length === 0" class="text-center py-12">
          <ModernIcon name="search" size="lg" class="text-gray-400 mx-auto mb-4" />
          <p class="text-gray-500 dark:text-gray-400">No products found</p>
          <p class="text-xs text-gray-400 mt-2">Total products: {{ products.length }}</p>
        </div>
      </div>

      <!-- Cart Panel (Left) -->
      <div class="order-1 lg:order-1 lg:w-1/2 w-full bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col h-auto lg:h-screen">
        <!-- Cart Header -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Cart ({{ cart.length }})</h2>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 min-h-[240px] max-h-[60vh] md:max-h-[55vh] lg:max-h-[60vh] overflow-y-auto p-4 space-y-4">
          <div v-if="cart.length === 0" class="text-center py-8">
            <ModernIcon name="shopping-bag" size="lg" class="text-gray-400 mx-auto mb-4" />
            <p class="text-gray-500 dark:text-gray-400">Cart is empty</p>
          </div>

          <!-- Cart Items -->
          <div
            v-for="(item, index) in cart"
            :key="index"
            class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600"
          >
            <div class="flex justify-between items-start mb-2">
              <div class="flex-1">
                <h4 class="font-medium text-gray-900 dark:text-white text-sm">{{ item.name }}</h4>
                <!-- Panaflex dimensions display -->
                <div v-if="item.type === 'panaflex_roll'" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  {{ item.length }}{{ item.length_unit }} × {{ item.width }}{{ item.width_unit }} × {{ item.qty }}
                  <br>
                  <span class="font-medium">{{ formatCurrency(item.units_sqft) }} sq.ft</span>
                </div>
                <!-- Description Input -->
                <div class="mt-1">
                  <input 
                    v-model="item.description" 
                    placeholder="Description..." 
                    class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 dark:bg-gray-600 dark:border-gray-500 dark:text-gray-200"
                  />
                </div>
              </div>
              <button
                @click="removeCartItem(index)"
                class="text-red-500 hover:text-red-700 p-1"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
            
            <div class="grid grid-cols-3 gap-2 text-sm">
              <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400">Qty</label>
                <input
                  v-model.number="item.qty"
                  @input="updateCartItem(index, item)"
                  type="number"
                  min="1"
                  class="w-full px-2 py-1 border border-gray-300 rounded dark:bg-gray-600 dark:border-gray-500 dark:text-white text-xs"
                />
              </div>
              
              <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400">Rate</label>
                <input
                  v-model.number="item.rate"
                  @input="updateCartItem(index, item)"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-2 py-1 border border-gray-300 rounded dark:bg-gray-600 dark:border-gray-500 dark:text-white text-xs"
                />
              </div>
              
              <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400">Total</label>
                <div class="px-2 py-1 bg-gray-100 dark:bg-gray-600 rounded text-xs font-medium">
                  {{ formatCurrency(item.line_total) }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Cart Footer -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex-shrink-0">
          <!-- Customer Selection -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Customer</label>
            <div class="flex space-x-2">
              <select
                v-model="selectedCustomer"
                @change="updateCustomerInfo"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
              >
                <option value="">Select Customer</option>
                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                  {{ customer.name }} ({{ customer.phone }})
                </option>
              </select>
              <button
                @click="showCustomerForm = true"
                class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                title="Add New Customer"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Invoice Date Picker (Optional) -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Invoice Date (Optional)</label>
            <input 
              type="date" 
              v-model="invoiceDate"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            />
          </div>

          <!-- Walk-in Customer Name Input -->
          <div v-if="isWalkInCustomer" class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Walk-in Customer Name (for Bill)</label>
            <input 
              v-model="walkInName"
              type="text"
              placeholder="Enter name to print on bill..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            />
          </div>

          <!-- Customer Info Display -->
          <div v-if="selectedCustomerInfo && selectedCustomer !== 'walk-in'" class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">Customer Information</h4>
            <div class="space-y-1 text-xs">
              <div class="flex justify-between">
                <span class="text-blue-700 dark:text-blue-300">Name:</span>
                <span class="font-medium text-blue-900 dark:text-blue-100">{{ selectedCustomerInfo.name }}</span>
              </div>
              <div v-if="selectedCustomerInfo.advance > 0" class="flex justify-between">
                <span class="text-blue-700 dark:text-blue-300">Advance:</span>
                <span class="font-medium text-green-600 dark:text-green-400">PKR {{ parseFloat(selectedCustomerInfo.advance || 0).toFixed(2) }}</span>
              </div>
              <div v-if="selectedCustomerInfo.credit_limit > 0" class="flex justify-between">
                <span class="text-blue-700 dark:text-blue-300">Credit Limit:</span>
                <span class="font-medium text-blue-900 dark:text-blue-100">PKR {{ parseFloat(selectedCustomerInfo.credit_limit || 0).toFixed(2) }}</span>
              </div>
              <div v-if="selectedCustomerInfo.credit_limit > 0" class="flex justify-between">
                <span class="text-blue-700 dark:text-blue-300">Credit Used:</span>
                <span 
                  :class="[
                    'font-medium',
                    selectedCustomerInfo.credit_status === 'exceeded' 
                      ? 'text-red-600 dark:text-red-400' 
                      : selectedCustomerInfo.credit_status === 'warning'
                        ? 'text-yellow-600 dark:text-yellow-400'
                        : 'text-green-600 dark:text-green-400'
                  ]"
                >
                  PKR {{ parseFloat(selectedCustomerInfo.credit_used || 0).toFixed(2) }}
                </span>
              </div>
              <div v-if="selectedCustomerInfo.credit_limit > 0" class="flex justify-between">
                <span class="text-blue-700 dark:text-blue-300">Available Credit:</span>
                <span 
                  :class="[
                    'font-medium',
                    selectedCustomerInfo.available_credit <= 0 
                      ? 'text-red-600 dark:text-red-400' 
                      : selectedCustomerInfo.available_credit <= (selectedCustomerInfo.credit_limit * 0.1)
                        ? 'text-yellow-600 dark:text-yellow-400'
                        : 'text-green-600 dark:text-green-400'
                  ]"
                >
                  PKR {{ parseFloat(selectedCustomerInfo.available_credit || 0).toFixed(2) }}
                </span>
              </div>
              <div v-if="selectedCustomerInfo.credit_status === 'exceeded'" class="mt-2 p-2 bg-red-100 dark:bg-red-900/20 border border-red-300 dark:border-red-700 rounded">
                <div class="flex items-center text-red-700 dark:text-red-300">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="text-xs font-medium">CREDIT LIMIT EXCEEDED!</span>
                </div>
              </div>
              <div v-else-if="selectedCustomerInfo.credit_status === 'warning'" class="mt-2 p-2 bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded">
                <div class="flex items-center text-yellow-700 dark:text-yellow-300">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="text-xs font-medium">Credit Near Limit</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Validation with Step-by-Step Breakdown -->
          <div v-if="selectedCustomerInfo && selectedCustomer !== 'walk-in' && cart.length > 0" class="mb-4">
            <!-- RED ALERT for Credit Limit Exceeded -->
            <div v-if="paymentValidation && !paymentValidation.valid" class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border-2 border-red-300 dark:border-red-700">
              <div class="flex items-start">
                <svg class="w-6 h-6 text-red-600 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                  <h5 class="text-base font-bold text-red-800 dark:text-red-200 mb-2">🚫 CREDIT LIMIT EXCEEDED!</h5>
                  <p class="text-sm text-red-700 dark:text-red-300 mb-3 font-medium">{{ paymentValidation.message }}</p>
                  
                  <!-- Step-by-Step Breakdown -->
                  <details class="bg-white dark:bg-gray-800 rounded border border-red-200 dark:border-red-600">
                    <summary class="p-2 text-xs font-semibold text-gray-700 dark:text-gray-300 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                      Payment Flow Breakdown (Click to expand)
                    </summary>
                    <div class="p-2 pt-0 space-y-1 max-h-32 overflow-y-auto">
                      <div v-for="step in paymentValidation.steps" :key="step" class="text-xs text-gray-600 dark:text-gray-400">
                        {{ step }}
                      </div>
                    </div>
                  </details>
                </div>
              </div>
            </div>
            
            <!-- GREEN SUCCESS for Advance Only Payment -->
            <div v-else-if="paymentValidation && paymentValidation.valid && paymentValidation.paymentType === 'advance_only'" class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
              <div class="flex items-start">
                <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <div>
                  <h5 class="text-sm font-medium text-green-800 dark:text-green-200">✅ Advance Payment Available</h5>
                  <p class="text-xs text-green-700 dark:text-green-300 mt-1">{{ paymentValidation.message }}</p>
                </div>
              </div>
            </div>
            
            <!-- BLUE INFO for Mixed Payment (Advance + Credit) -->
            <div v-else-if="paymentValidation && paymentValidation.valid && paymentValidation.paymentType === 'advance_plus_credit'" class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
              <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div>
                  <h5 class="text-sm font-medium text-blue-800 dark:text-blue-200">💰 Mixed Payment Required</h5>
                  <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">{{ paymentValidation.message }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Totals -->
          <div class="space-y-3 mb-4">
            <!-- Subtotal -->
            <div class="flex justify-between items-center">
              <span class="font-medium text-gray-900 dark:text-white">Gross Bill:</span>
              <span class="font-semibold text-lg">{{ formatCurrency(subtotal) }}</span>
            </div>
            
            <!-- Adjustments Row -->
            <div class="grid grid-cols-2 gap-3 py-2 border-t border-gray-200 dark:border-gray-700">
              <!-- Discount -->
              <div class="text-center">
                <label class="block text-xs text-gray-500 mb-1">Discount %</label>
                <div class="flex items-center justify-center space-x-1">
                  <input
                    v-model.number="discountPercent"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    placeholder="0"
                    class="w-12 px-1 py-1 text-xs border border-gray-300 rounded text-center dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                  />
                  <span class="text-xs text-gray-500">%</span>
                </div>
                <div class="text-xs text-red-600 mt-1">-{{ formatCurrency(discountAmount) }}</div>
              </div>
              
              <!-- Tax -->
              <div class="text-center">
                <label class="block text-xs text-gray-500 mb-1">Tax %</label>
                <div class="flex items-center justify-center space-x-1">
                  <input
                    v-model.number="taxPercent"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    placeholder="0"
                    class="w-12 px-1 py-1 text-xs border border-gray-300 rounded text-center dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                  />
                  <span class="text-xs text-gray-500">%</span>
                </div>
                <div class="text-xs text-blue-600 mt-1">+{{ formatCurrency(taxAmount) }}</div>
              </div>
            </div>
            
            <!-- Additional Charges (Collapsible) -->
            <div v-if="utilitiesCharges > 0 || otherCharges > 0 || showExtraCharges" class="space-y-2 pt-2 border-t border-gray-200 dark:border-gray-700">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Utilities/Rent:</span>
                <input
                  v-model.number="utilitiesCharges"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0"
                  class="w-16 px-2 py-1 text-xs border border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-right"
                />
              </div>
              
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Other Charges:</span>
                <input
                  v-model.number="otherCharges"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0"
                  class="w-16 px-2 py-1 text-xs border border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-right"
                />
              </div>
            </div>
            
            <button v-if="!showExtraCharges && utilitiesCharges === 0 && otherCharges === 0" 
              @click="showExtraCharges = true"
              class="text-xs text-blue-600 hover:text-blue-800">
              + Add Extra Charges
            </button>
            
            <!-- Bill Total -->
            <div class="flex justify-between items-center py-2 border-t border-gray-200 dark:border-gray-700">
              <span class="font-medium text-gray-900 dark:text-white">Bill Total:</span>
              <span class="font-semibold text-blue-600">{{ formatCurrency(billTotal) }}</span>
            </div>
            
            <!-- Customer Balance Info (Only for non-walk-in customers) -->
            <div v-if="selectedCustomerInfo && selectedCustomer !== 'walk-in'" class="space-y-1 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600">Previous Balance:</span>
                <span class="text-red-600">{{ formatCurrency(selectedCustomerInfo.credit_used || 0) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Advance Available:</span>
                <span class="text-green-600">{{ formatCurrency(selectedCustomerInfo.advance || 0) }}</span>
              </div>
            </div>
            
            <!-- Grand Total -->
            <div class="flex justify-between items-center py-2 border-t-2 border-gray-300 dark:border-gray-600">
              <span class="font-bold text-gray-900 dark:text-white">Grand Total:</span>
              <span class="font-bold text-xl text-indigo-600">{{ formatCurrency(grandTotal) }}</span>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="space-y-2 pb-20 lg:pb-0">
            <!-- Cash Payment Button -->
            <button
              @click="processCashPayment"
              :disabled="cart.length === 0"
              class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
              </svg>
              {{ editSale ? 'Update Sale (Cash)' : 'Cash Payment (F4)' }}
            </button>
            
            <!-- Credits Payment Button -->
            <button
              @click="processCreditPayment"
              :disabled="cart.length === 0 || !selectedCustomer || selectedCustomer === 'walk-in'"
              class="w-full bg-orange-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2-4h10a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6z" />
              </svg>
              {{ editSale ? 'Update Sale (Credit)' : 'Credits (Udhaar) (F5)' }}
            </button>
            
            <!-- Info message for credits -->
            <p v-if="!selectedCustomer || selectedCustomer === 'walk-in'" class="text-xs text-orange-600 dark:text-orange-400 text-center">
              Select a customer to enable credits payment
            </p>
            
            <button
              @click="clearCart"
              :disabled="cart.length === 0"
              class="w-full bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 py-2 px-4 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Clear (ESC)
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Panaflex Form Modal -->
    <div v-if="showPanaflexForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Add Panaflex Item: {{ selectedProduct?.name }}
        </h3>
        
        <!-- Roll Information -->
        <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg mb-4">
          <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">Roll Information</h4>
          <div class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
            <div>Roll Size: {{ (selectedProduct?.roll_width_inch / 12).toFixed(1) }}' × {{ (selectedProduct?.roll_length_meter * 3.28).toFixed(1) }}'</div>
            <div>Available Stock: {{ (selectedProduct?.current_stock || 0).toFixed(2) }} sq.ft</div>
            <div>Rate: PKR {{ selectedProduct?.rate_per_sqft || 0 }}/sq.ft</div>
          </div>
        </div>
        
        <div class="space-y-4">
          <!-- Width Input -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Width (Inches)</label>
            <div class="flex space-x-2">
              <input
                v-model.number="panaflexForm.width"
                type="number"
                step="0.1"
                min="0"
                placeholder="Enter width in inches"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                @input="validateWidth"
              />
            </div>
            <div v-if="widthError" class="text-red-500 text-xs mt-1">{{ widthError }}</div>
            <div v-else-if="panaflexForm.width" class="text-green-500 text-xs mt-1">
              ✓ Width fits within roll ({{ (selectedProduct?.roll_width_inch).toFixed(1) }}" available)
            </div>
          </div>
          
          <!-- Length Input -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Length (Meters)</label>
            <div class="flex space-x-2">
              <input
                v-model.number="panaflexForm.length"
                type="number"
                step="0.1"
                min="0"
                placeholder="Enter length in meters"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
              />
            </div>
          </div>
          
          <!-- Quantity -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
            <input
              v-model.number="panaflexForm.qty"
              type="number"
              min="1"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            />
          </div>
          
          <!-- Rate -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rate per sq.ft (PKR)</label>
            <input
              v-model.number="panaflexForm.rate"
              type="number"
              step="0.01"
              min="0"
              :placeholder="selectedProduct?.rate_per_sqft || '0.00'"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            />
          </div>
          
          <!-- Calculation Display -->
          <div v-if="panaflexCalculation" class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
            <h4 class="text-sm font-medium text-green-900 dark:text-green-100 mb-2">Calculation</h4>
            <div class="text-xs text-green-700 dark:text-green-300 space-y-1">
              <div>Area: {{ panaflexCalculation.area_sqft }} sq.ft</div>
              <div>Meters Used: {{ panaflexCalculation.meters_consumed }}m</div>
              <div>Rate: PKR {{ panaflexForm.rate }}/sq.ft</div>
              <div class="font-semibold">Total: PKR {{ panaflexCalculation.line_total }}</div>
              <div class="text-red-600 dark:text-red-400 font-medium">
                Remaining Stock: {{ ((selectedProduct?.current_stock || 0) - (panaflexCalculation?.area_sqft || 0)).toFixed(2) }} sq.ft
              </div>
            </div>
          </div>
        </div>
        
        <div class="flex space-x-3 mt-6">
          <button
            @click="addPanaflexToCart"
            class="flex-1 bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition-colors"
          >
            Add to Cart
          </button>
          <button
            @click="showPanaflexForm = false; selectedProduct = null"
            class="flex-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 py-2 px-4 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>

    <!-- Help Modal -->
    <div v-if="showHelp" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Keyboard Shortcuts</h3>
        
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Focus Search:</span>
            <span class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">F2</span>
          </div>
          
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Process Payment:</span>
            <span class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">F4</span>
          </div>
          
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Clear Cart/Cancel:</span>
            <span class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">ESC</span>
          </div>
        </div>
        
        <button
          @click="showHelp = false"
          class="mt-4 w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition-colors"
        >
          Close
        </button>
      </div>
    </div>

    <!-- Scanner Modal -->
    <div v-if="showScanner" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Barcode Scanner</h3>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Scan or Enter Barcode
          </label>
          <input
            ref="barcodeInput"
            type="text"
            v-model="barcodeValue"
            @keyup.enter="searchByBarcode"
            placeholder="Scan barcode or enter manually..."
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            autofocus
          />
        </div>
        
        <div class="flex gap-2">
          <button
            @click="searchByBarcode"
            class="flex-1 bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition-colors"
          >
            Search Product
          </button>
          <button
            @click="showScanner = false; barcodeValue = ''"
            class="flex-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 py-2 px-4 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>

    <!-- Customer Form Modal -->
    <div v-if="showCustomerForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add New Customer</h3>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Customer Name *
            </label>
            <input
              type="text"
              v-model="newCustomer.name"
              placeholder="Enter customer name..."
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Phone Number *
            </label>
            <input
              type="tel"
              v-model="newCustomer.phone"
              placeholder="Enter phone number..."
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Address
            </label>
            <textarea
              v-model="newCustomer.address"
              placeholder="Enter address (optional)..."
              rows="2"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            ></textarea>
          </div>
        </div>
        
        <div class="flex gap-2 mt-6">
          <button
            @click="saveNewCustomer"
            class="flex-1 bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition-colors"
            :disabled="!newCustomer.name || !newCustomer.phone"
          >
            Save Customer
          </button>
          <button
            @click="showCustomerForm = false; resetCustomerForm()"
            class="flex-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 py-2 px-4 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>

    <!-- Sale Success Modal -->
    <div v-if="showSuccessModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-lg w-full">
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
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue'
import { formatCurrency } from '@/utils/currency'
import ModernIcon from '@/components/ModernIcon.vue'

// Props from backend
const props = defineProps({
  activeRegisterSession: Object,
  hasOpenRegister: Boolean
})

// Reactive data
const searchQuery = ref('')
const activeTab = ref('all')
const products = ref([])
const cart = ref([])
const loading = ref(false)
const searchInput = ref(null)

// Totals
const discountPercent = ref(0)
const taxPercent = ref(0)
const otherCharges = ref(0)
const utilitiesCharges = ref(0)
const paidAmount = ref(0)
const showExtraCharges = ref(false)

// Modals
const showHelp = ref(false)
const showScanner = ref(false)
const showCustomerForm = ref(false)
const showPanaflexForm = ref(false)
const showSuccessModal = ref(false)
const selectedProduct = ref(null)
const lastSaleResponse = ref(null)

// Panaflex form data
const panaflexForm = ref({
  length: '',
  length_unit: 'm',
  width: '',
  width_unit: 'in',
  qty: 1,
  rate: 0
})

// Panaflex calculation and validation
const panaflexCalculation = ref(null)
const widthError = ref('')
const customers = ref([])
const selectedCustomer = ref('')
const selectedCustomerInfo = ref(null)
const walkInName = ref('')
const invoiceDate = ref('')

// Scanner
const barcodeInput = ref(null)
const barcodeValue = ref('')

// New Customer Form
const newCustomer = ref({
  name: '',
  phone: '',
  address: ''
})

// Product tabs
const productTabs = [
  { label: 'All', value: 'all' },
  { label: 'Panaflex', value: 'panaflex_roll' },
  { label: 'Simple', value: 'simple' }
]

// Computed properties
const filteredProducts = computed(() => {
  return products.value.filter(product => {
    if (activeTab.value === 'all') return true
    return product.type === activeTab.value
  })
})

const isWalkInCustomer = computed(() => {
  if (!selectedCustomer.value) return false
  const customer = customers.value.find(c => c.id === selectedCustomer.value)
  return customer && customer.name === 'Walk-in Customer'
})

const subtotal = computed(() => {
  return cart.value.reduce((sum, item) => {
    const lineTotal = parseFloat(item.line_total) || 0
    return sum + lineTotal
  }, 0)
})

const discountAmount = computed(() => {
  const subtotalVal = parseFloat(subtotal.value) || 0
  const discountPercentVal = parseFloat(discountPercent.value) || 0
  return (subtotalVal * discountPercentVal) / 100
})

const taxAmount = computed(() => {
  const subtotalVal = parseFloat(subtotal.value) || 0
  const taxPercentVal = parseFloat(taxPercent.value) || 0
  return (subtotalVal * taxPercentVal) / 100
})

const billTotal = computed(() => {
  const subtotalVal = parseFloat(subtotal.value) || 0
  const discountVal = parseFloat(discountAmount.value) || 0
  const taxVal = parseFloat(taxAmount.value) || 0
  const utilitiesVal = parseFloat(utilitiesCharges.value) || 0
  const otherVal = parseFloat(otherCharges.value) || 0
  
  return subtotalVal - discountVal + taxVal + utilitiesVal + otherVal
})

const grandTotal = computed(() => {
  const billTotalVal = parseFloat(billTotal.value) || 0
  const previousBalance = selectedCustomerInfo.value && selectedCustomer.value !== 'walk-in' 
    ? parseFloat(selectedCustomerInfo.value.credit_used) || 0 
    : 0
  
  return billTotalVal + previousBalance
})

const currentBalance = computed(() => {
  const grandTotalVal = parseFloat(grandTotal.value) || 0
  const paidVal = parseFloat(paidAmount.value) || 0
  
  return paidVal - grandTotalVal
})

const paymentValidation = computed(() => {
  if (!selectedCustomerInfo.value || selectedCustomer.value === 'walk-in' || cart.value.length === 0) {
    return null
  }
  
  return validateCustomerPayment()
})

// Watch for changes in panaflex form to auto-calculate
watch([
  () => panaflexForm.value.length,
  () => panaflexForm.value.length_unit,
  () => panaflexForm.value.width,
  () => panaflexForm.value.width_unit,
  () => panaflexForm.value.qty,
  () => panaflexForm.value.rate
], async () => {
  // Only calculate if we have the required values
  if (selectedProduct.value && 
      panaflexForm.value.length && 
      panaflexForm.value.width && 
      panaflexForm.value.rate > 0) {
    
    // Validate width first
    const jobWidthInches = panaflexForm.value.width_unit === 'ft' 
      ? panaflexForm.value.width * 12 
      : panaflexForm.value.width
    
    const rollWidthInches = selectedProduct.value.roll_width_inch || 0
    
    // Validation removed
    widthError.value = ''
      
    // Calculate the area
    try {
        const result = await calculatePanaflexArea()
        if (result) {
          panaflexCalculation.value = {
            area_sqft: result.units_sqft,
            meters_consumed: result.meters_hint || 0,
            line_total: result.units_sqft * panaflexForm.value.rate
          }
        }
      } catch (error) {
        console.error('Calculation error:', error)
        panaflexCalculation.value = null
      }
  } else {
    panaflexCalculation.value = null
  }
}, { deep: true })

// Methods
const searchProducts = async () => {
  if (searchQuery.value.length < 2 && searchQuery.value.length > 0) return
  
  loading.value = true
  try {
    const response = await axios.get('/api/products/search', {
      params: {
        q: searchQuery.value,
        type: activeTab.value
      }
    })
    products.value = response.data
  } catch (error) {
    console.error('Search failed:', error)
  } finally {
    loading.value = false
  }
}

const addToCart = (product) => {
  if (product.type === 'panaflex_roll') {
    // Show Panaflex form modal
    showPanaflexForm.value = true
    selectedProduct.value = product
    panaflexForm.value.rate = product.rate_per_sqft || 0
  } else {
    // Check if product already exists in cart
    const existingItemIndex = cart.value.findIndex(item => 
      item.product_id === product.id && item.type === product.type
    )
    
    if (existingItemIndex !== -1) {
      // Product exists, increase quantity
      const existingItem = cart.value[existingItemIndex]
      existingItem.qty += 1
      updateCartItem(existingItemIndex, existingItem)
    } else {
      // Product doesn't exist, add new item
      const rate = product.sale_rate || product.price || 0
      const cartItem = {
        product_id: product.id,
        name: product.name,
        type: product.type,
        qty: 1,
        rate: rate,
        discount: 0,
        tax: 0,
        description: '',
        line_total: rate,
      }
      
      cart.value.push(cartItem)
    }
  }
}

const updateCartItem = (index, item) => {
  if (item.type === 'panaflex_roll') {
    // For Panaflex items, recalculate area and line total
    const units = parseFloat(item.units_sqft) || 0
    const rate = parseFloat(item.rate) || 0
    const discount = parseFloat(item.discount) || 0
    const tax = parseFloat(item.tax) || 0
    item.line_total = (units * rate) - discount + tax
  } else {
    const qty = parseFloat(item.qty) || 0
    const rate = parseFloat(item.rate) || 0
    const discount = parseFloat(item.discount) || 0
    const tax = parseFloat(item.tax) || 0
    item.line_total = (qty * rate) - discount + tax
  }
  cart.value[index] = item
}

const removeCartItem = (index) => {
  cart.value.splice(index, 1)
}

const clearCart = () => {
  cart.value = []
  discountPercent.value = 0
  taxPercent.value = 0
  otherCharges.value = 0
  utilitiesCharges.value = 0
  paidAmount.value = 0
  showExtraCharges.value = false
}

const processCashPayment = async () => {
  if (cart.value.length === 0) return
  
  // Determine customer ID (if customer is selected and not walk-in)
  const customerId = selectedCustomer.value && selectedCustomer.value !== 'walk-in' 
    ? selectedCustomer.value 
    : null
  
  // **CRITICAL FIX: Refresh customer info to get latest balance (especially after cash vouchers)**
  if (customerId) {
    console.log('Fetching fresh customer balance before cash payment...')
    await updateCustomerInfo()
  }
  
  try {
    const url = props.editSale ? `/pos/update/${props.editSale.id}` : '/api/pos/checkout'
    const method = props.editSale ? 'put' : 'post'
    
    const response = await axios[method](url, {
      items: cart.value,
      discount_total: discountAmount.value,
      tax_total: taxAmount.value,
      utilities_charges: utilitiesCharges.value,
      other_charges: otherCharges.value,
      payment_type: 'cash',
      customer_id: customerId,
      custom_customer_name: isWalkInCustomer.value ? walkInName.value : null,
      invoice_date: invoiceDate.value,
      notes: props.editSale ? props.editSale.notes : ''
    })
    
    if (response.data.success) {
      // Store the response data for later use
      lastSaleResponse.value = response.data
      
      // Show success message and buttons for print/preview options
      showSuccessModal.value = true
    }
  } catch (error) {
    console.error('Cash payment failed:', error)
    alert('Cash payment failed: ' + (error.response?.data?.message || error.message))
  }
}

const validateCustomerPayment = () => {
  if (!selectedCustomerInfo.value || selectedCustomer.value === 'walk-in') {
    return { valid: true, message: '', steps: [] }
  }
  
  const customer = selectedCustomerInfo.value
  const currentBillTotal = billTotal.value
  
  // Step 1: Check advance balance first
  const advanceBalance = parseFloat(customer.advance) || 0
  
  let steps = []
  steps.push(`Step 1: Bill Amount = PKR ${currentBillTotal.toFixed(2)}`)
  steps.push(`Step 2: Advance Balance = PKR ${advanceBalance.toFixed(2)}`)
  
  // Step 2: Calculate remaining bill after advance deduction
  let remainingBill = Math.max(0, currentBillTotal - advanceBalance)
  
  if (advanceBalance >= currentBillTotal) {
    // Case: Full payment can be made from advance
    steps.push(`Step 3: Full payment from advance (PKR ${currentBillTotal.toFixed(2)} - PKR ${advanceBalance.toFixed(2)} = PKR 0.00 remaining)`)
    
    return {
      valid: true,
      message: '✅ Payment successful! Full amount deducted from advance balance.',
      paymentType: 'advance_only',
      steps: steps,
      advanceUsed: currentBillTotal,
      creditUsed: 0,
      remainingBill: 0
    }
  } else {
    // Case: Partial or no advance available
    const advanceUsed = advanceBalance
    steps.push(`Step 3: After advance deduction = PKR ${currentBillTotal.toFixed(2)} - PKR ${advanceUsed.toFixed(2)} = PKR ${remainingBill.toFixed(2)} remaining`)
    
    // Step 4: Check credit limit for remaining amount
    const creditUsed = parseFloat(customer.credit_used) || 0
    const creditLimit = parseFloat(customer.credit_limit) || 0
    const availableCredit = Math.max(0, creditLimit - creditUsed)
    
    steps.push(`Step 4: Credit Check - Limit: PKR ${creditLimit.toFixed(2)}, Used: PKR ${creditUsed.toFixed(2)}, Available: PKR ${availableCredit.toFixed(2)}`)
    
    // Step 5: Decision based on remaining bill vs available credit
    if (remainingBill <= availableCredit) {
      // Success: Can pay remaining from credit
      steps.push(`Step 5: ✅ Remaining PKR ${remainingBill.toFixed(2)} can be covered by available credit`)
      
      return {
        valid: true,
        message: `✅ Payment approved! PKR ${advanceUsed.toFixed(2)} from advance + PKR ${remainingBill.toFixed(2)} on credit.`,
        paymentType: 'advance_plus_credit',
        steps: steps,
        advanceUsed: advanceUsed,
        creditUsed: remainingBill,
        remainingBill: remainingBill
      }
    } else {
      // Failure: Credit limit exceeded
      const shortage = remainingBill - availableCredit
      steps.push(`Step 5: ❌ Credit limit exceeded! Need PKR ${remainingBill.toFixed(2)} but only PKR ${availableCredit.toFixed(2)} available`)
      
      return {
        valid: false,
        message: `🚫 CREDIT LIMIT EXCEEDED! You can only take PKR ${availableCredit.toFixed(2)} more credit. Short by PKR ${shortage.toFixed(2)}.`,
        paymentType: 'credit_exceeded',
        steps: steps,
        advanceUsed: advanceUsed,
        creditUsed: availableCredit,
        remainingBill: remainingBill,
        shortage: shortage
      }
    }
  }
}

const processCreditPayment = async () => {
  if (cart.value.length === 0) return
  
  // Check if customer is selected and not walk-in
  if (!selectedCustomer.value || isWalkInCustomer.value) {
    alert('Please select a customer for credit payment!')
    return
  }
  
  // **CRITICAL FIX: Refresh customer info to get latest balance after any cash vouchers/payments**
  console.log('Fetching fresh customer balance before credit payment...')
  await updateCustomerInfo()
  
  // Get detailed payment validation
  const validation = validateCustomerPayment()
  
  if (!validation.valid) {
    // Show RED ALERT with step-by-step breakdown
    let alertMessage = `🚫 CREDIT LIMIT EXCEEDED!\n\n${validation.message}\n\n`
    alertMessage += `Payment Flow Breakdown:\n`
    validation.steps.forEach(step => {
      alertMessage += `• ${step}\n`
    })
    alertMessage += `\nDo you still want to proceed? This will exceed the customer's credit limit.`
    
    const proceed = confirm(alertMessage)
    if (!proceed) {
      return
    }
  } else {
    // Show success confirmation with payment breakdown
    let confirmMessage = `💰 PAYMENT CONFIRMATION\n\n${validation.message}\n\n`
    confirmMessage += `Payment Breakdown:\n`
    validation.steps.forEach(step => {
      confirmMessage += `• ${step}\n`
    })
    confirmMessage += `\nProceed with this credit payment?`
    
    const proceed = confirm(confirmMessage)
    if (!proceed) {
      return
    }
  }
  
  // Calculate advance used
  const currentBillTotal = billTotal.value
  const advanceBalance = parseFloat(selectedCustomerInfo.value?.advance || 0)
  const advanceUsed = Math.min(currentBillTotal, advanceBalance)
  
  try {
    const url = props.editSale ? `/pos/update/${props.editSale.id}` : '/api/pos/checkout'
    const method = props.editSale ? 'put' : 'post'

    const response = await axios[method](url, {
      items: cart.value,
      discount_total: discountAmount.value,
      tax_total: taxAmount.value,
      utilities_charges: utilitiesCharges.value,
      other_charges: otherCharges.value,
      payment_type: 'credit',
      customer_id: selectedCustomer.value,
      advance_used: advanceUsed,
      invoice_date: invoiceDate.value,
      notes: props.editSale ? props.editSale.notes : ''
    })
    
    if (response.data.success) {
      // Store the response data for later use
      lastSaleResponse.value = response.data
      
      // Show success message and buttons for print/preview options
      showSuccessModal.value = true
    }
  } catch (error) {
    console.error('Credit payment failed:', error)
    alert('Credit payment failed: ' + (error.response?.data?.message || error.message))
  }
}

const calculatePanaflexArea = async () => {
  if (!selectedProduct.value || !panaflexForm.value.length || !panaflexForm.value.width) return
  
  try {
    const response = await axios.post('/api/pos/calc', {
      product_id: selectedProduct.value.id,
      type: 'panaflex_roll',
      length: panaflexForm.value.length,
      length_unit: panaflexForm.value.length_unit,
      width: panaflexForm.value.width,
      width_unit: panaflexForm.value.width_unit,
      qty: panaflexForm.value.qty,
      rate: panaflexForm.value.rate
    })
    
    return response.data
  } catch (error) {
    console.error('Calculation failed:', error)
    return null
  }
}

// Validate width against roll width
const validateWidth = () => {
  // The watcher will handle the calculation automatically
  return
}

// Load customers for selection
const loadCustomers = async () => {
  try {
    const response = await axios.get('/api/customers/search')
    customers.value = response.data
    
    // Auto-select Walk-in Customer
    const walkIn = customers.value.find(c => c.name === 'Walk-in Customer')
    if (walkIn) {
      selectedCustomer.value = walkIn.id
    }
  } catch (error) {
    console.error('Failed to load customers:', error)
  }
}

// Update customer info when customer is selected
const updateCustomerInfo = async () => {
  if (!selectedCustomer.value || isWalkInCustomer.value) {
    selectedCustomerInfo.value = null
    return
  }

  try {
    const response = await axios.get(`/api/customers/${selectedCustomer.value}/pos-info`)
    selectedCustomerInfo.value = response.data
  } catch (error) {
    console.error('Failed to load customer info:', error)
    selectedCustomerInfo.value = null
  }
}

const addPanaflexToCart = async () => {
  const calculation = await calculatePanaflexArea()
  
  if (!calculation || !calculation.valid_width) {
    alert('Width exceeds roll width or calculation failed!')
    return
  }
  
  const cartItem = {
    product_id: selectedProduct.value.id,
    name: selectedProduct.value.name,
    type: 'panaflex_roll',
    qty: panaflexForm.value.qty,
    rate: panaflexForm.value.rate,
    discount: 0,
    tax: 0,
    description: '',
    length: panaflexForm.value.length,
    length_unit: panaflexForm.value.length_unit,
    width: panaflexForm.value.width,
    width_unit: panaflexForm.value.width_unit,
    units_sqft: calculation.units_sqft,
    line_total: calculation.units_sqft * panaflexForm.value.rate
  }
  
  cart.value.push(cartItem)
  
  // Reset form and close modal
  panaflexForm.value = {
    length: '',
    length_unit: 'ft',
    width: '',
    width_unit: 'ft',
    qty: 1,
    rate: 0
  }
  showPanaflexForm.value = false
  selectedProduct.value = null
}

// Scanner functions
const searchByBarcode = async () => {
  if (!barcodeValue.value.trim()) {
    alert('Please enter a barcode')
    return
  }

  try {
    loading.value = true
    const response = await axios.get(`/api/pos/products?q=${barcodeValue.value}`)
    
    if (response.data.length > 0) {
      const product = response.data[0]
      if (product.type === 'simple') {
        addToCart(product)
      } else {
        selectedProduct.value = product
        showPanaflexForm.value = true
      }
      showScanner.value = false
      barcodeValue.value = ''
    } else {
      alert('Product not found with this barcode')
    }
  } catch (error) {
    console.error('Barcode search failed:', error)
    alert('Failed to search product')
  } finally {
    loading.value = false
  }
}

// Customer functions
const saveNewCustomer = async () => {
  if (!newCustomer.value.name || !newCustomer.value.phone) {
    alert('Please fill in required fields')
    return
  }

  try {
    console.log('Saving customer:', newCustomer.value)
    
    const response = await axios.post('/api/customers', {
      name: newCustomer.value.name,
      phone: newCustomer.value.phone,
      address: newCustomer.value.address
    })

    console.log('Customer save response:', response.data)

    if (response.data.success) {
      customers.value.push(response.data.data)
      selectedCustomer.value = response.data.data.id
      showCustomerForm.value = false
      resetCustomerForm()
      alert('Customer added successfully!')
    }
  } catch (error) {
    console.error('Failed to save customer:', error)
    console.error('Error response:', error.response?.data)
    const errorMessage = error.response?.data?.message || 'Failed to save customer'
    alert(errorMessage)
  }
}

// Success Modal Methods
const closeSuccessModal = () => {
  showSuccessModal.value = false
  lastSaleResponse.value = null
  
  // Clear cart and reset form after closing modal
  cart.value = []
  
  // Reset to Walk-in Customer
  const walkIn = customers.value.find(c => c.name === 'Walk-in Customer')
  if (walkIn) {
    selectedCustomer.value = walkIn.id
  } else {
    selectedCustomer.value = ''
  }
  
  walkInName.value = ''
  
  discountPercent.value = 0
  taxPercent.value = 0
  utilitiesCharges.value = 0
  otherCharges.value = 0
  paidAmount.value = 0
  showExtraCharges.value = false
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

const resetCustomerForm = () => {
  newCustomer.value = {
    name: '',
    phone: '',
    address: ''
  }
}

// Keyboard shortcuts enhancement
const handleKeydown = (e) => {
  if (e.key === 'F2') {
    e.preventDefault()
    nextTick(() => {
      if (searchInput.value) {
        searchInput.value.focus()
      }
    })
  } else if (e.key === 'F4') {
    e.preventDefault()
    if (cart.value.length > 0) {
      processCashPayment()
    }
  } else if (e.key === 'F5') {
    e.preventDefault()
    if (cart.value.length > 0 && selectedCustomer.value && selectedCustomer.value !== 'walk-in') {
      processCreditPayment()
    } else if (cart.value.length > 0) {
      alert('Please select a customer for credit payment!')
    }
  } else if (e.key === 'Escape') {
    e.preventDefault()
    if (showHelp.value) {
      showHelp.value = false
    } else if (showPanaflexForm.value) {
      showPanaflexForm.value = false
      selectedProduct.value = null
    } else {
      clearCart()
    }
  }
}

// Lifecycle
onMounted(async () => {
  await searchProducts() // Load initial products
  await loadCustomers() // Load customers for selection
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>
