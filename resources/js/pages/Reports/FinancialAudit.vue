<template>
  <AppLayout>
    <PageHeader
      title="Executive Financial & Audit Report"
      subtitle="Complete business audit: Sales, Purchases, Cash In/Out, Receivables, Payables, Expenses & Profitability"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2">
          <Link
            :href="route('reports.index')"
            class="ghost-btn inline-flex items-center gap-1.5 text-sm"
          >
            <ModernIcon name="arrow-left" size="sm" />
            <span>All Reports</span>
          </Link>

          <a
            :href="route('reports.financial-audit.export-pdf', exportParams)"
            class="primary-soft-btn inline-flex items-center gap-1.5 text-sm"
            target="_blank"
          >
            <ModernIcon name="file-text" size="sm" />
            <span>Export PDF</span>
          </a>

          <a
            :href="route('reports.financial-audit.export-excel', exportParams)"
            class="secondary-btn inline-flex items-center gap-1.5 text-sm bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800"
          >
            <ModernIcon name="table" size="sm" />
            <span>Excel (.xlsx)</span>
          </a>

          <a
            :href="route('reports.financial-audit.export-csv', exportParams)"
            class="secondary-btn inline-flex items-center gap-1.5 text-sm"
          >
            <ModernIcon name="download" size="sm" />
            <span>CSV</span>
          </a>

          <button
            @click="printReport"
            class="secondary-btn inline-flex items-center gap-1.5 text-sm"
          >
            <ModernIcon name="printer" size="sm" />
            <span>Print</span>
          </button>
        </div>
      </template>
    </PageHeader>

    <!-- Period Presets & Date Filters Bar -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <!-- Quick Preset Buttons -->
        <div class="flex flex-wrap items-center gap-1.5">
          <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mr-1">Period:</span>
          
          <button
            v-for="p in presetButtons"
            :key="p.id"
            @click="setPreset(p.id)"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-150"
            :class="filters.period === p.id 
              ? 'bg-blue-600 text-white shadow-sm ring-2 ring-blue-600/20' 
              : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
          >
            {{ p.label }}
          </button>
        </div>

        <!-- Custom Date Range Inputs -->
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500 dark:text-gray-400">From:</span>
            <input
              type="date"
              v-model="filters.start_date"
              class="form-input text-xs py-1.5 px-2.5 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
              @change="filters.period = 'custom'"
            />
          </div>

          <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500 dark:text-gray-400">To:</span>
            <input
              type="date"
              v-model="filters.end_date"
              class="form-input text-xs py-1.5 px-2.5 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
              @change="filters.period = 'custom'"
            />
          </div>

          <button
            @click="applyFilters"
            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-sm transition"
          >
            Apply
          </button>

          <button
            @click="resetFilters"
            class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg transition"
          >
            Reset
          </button>
        </div>
      </div>

      <!-- Active Period Info Pill -->
      <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex flex-wrap items-center justify-between text-xs text-gray-500 dark:text-gray-400">
        <div class="flex items-center gap-2">
          <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
          <span>Showing Data for: <strong>{{ audit.period_info.start_formatted }}</strong> to <strong>{{ audit.period_info.end_formatted }}</strong> ({{ audit.period_info.days_count }} Days)</span>
        </div>
        <div>
          <span>Last Refreshed: <strong>{{ currentTime }}</strong></span>
        </div>
      </div>
    </div>

    <!-- Executive Business Position Highlight Card (Clean, High-Contrast & Ultra-Readable) -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
      <!-- Header Bar -->
      <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 pb-5 border-b border-gray-100 dark:border-gray-700 mb-5">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-extrabold bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-300 uppercase tracking-wider">
              EXECUTIVE AUDIT SUMMARY
            </span>
            <span class="text-xs font-bold text-gray-500 dark:text-gray-400">کاروبار کی مالیاتی پوزیشن ایک نظر میں</span>
          </div>
          <h2 class="text-xl lg:text-2xl font-black text-gray-900 dark:text-gray-100 tracking-tight">
            Business Financial Health & Audit Overview
          </h2>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            Clear summary of Profitability, Cash Flow, Market Receivables/Payables, and Inventory Assets
          </p>
        </div>

        <!-- Net Profit Featured Pill -->
        <div class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-950/40 px-4 py-2.5 rounded-xl border border-emerald-200 dark:border-emerald-800/80 shadow-xs">
          <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-lg shadow-sm">
            💰
          </div>
          <div>
            <div class="text-[11px] font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">Net Profit (خالص کمائی)</div>
            <div 
              class="text-xl lg:text-2xl font-black"
              :class="audit.profit.is_profitable ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-400'"
            >
              Rs {{ formatNumber(audit.profit.net_profit) }}
            </div>
          </div>
        </div>
      </div>

      <!-- 4 High-Contrast Structured Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Profit Summary -->
        <div class="bg-emerald-50/60 dark:bg-gray-800/90 rounded-xl p-4 border-2 border-emerald-200 dark:border-emerald-800/80 hover:shadow-md transition">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-black uppercase text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
              <span>📈</span>
              <span>1. حقیقی کمائی (Profit)</span>
            </span>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-200/80 dark:bg-emerald-900 text-emerald-900 dark:text-emerald-200">
              {{ audit.profit.net_margin }}% Margin
            </span>
          </div>

          <div class="text-lg font-black text-emerald-700 dark:text-emerald-300 mb-2">
            Rs {{ formatNumber(audit.profit.net_profit) }}
          </div>

          <div class="space-y-1.5 text-xs text-gray-700 dark:text-gray-300 border-t border-emerald-200/60 dark:border-emerald-800/40 pt-2.5">
            <div class="flex justify-between items-center">
              <span class="text-gray-600 dark:text-gray-400">کل فروخت (Net Sales):</span>
              <span class="font-bold text-gray-900 dark:text-gray-100">Rs {{ formatNumber(audit.sales.net_sales) }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600 dark:text-gray-400">مجموعی منافع (Gross):</span>
              <span class="font-bold text-emerald-600 dark:text-emerald-400">Rs {{ formatNumber(audit.profit.gross_profit) }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600 dark:text-gray-400">اخراجات وضع کیے:</span>
              <span class="font-bold text-rose-600 dark:text-rose-400">- Rs {{ formatNumber(audit.expenses.total) }}</span>
            </div>
          </div>
        </div>

        <!-- 2. Cash Flow Summary -->
        <div class="bg-blue-50/60 dark:bg-gray-800/90 rounded-xl p-4 border-2 border-blue-200 dark:border-blue-800/80 hover:shadow-md transition">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-black uppercase text-blue-800 dark:text-blue-300 flex items-center gap-1.5">
              <span>💵</span>
              <span>2. کیش فلو (Cash In / Out)</span>
            </span>
            <span 
              class="text-[10px] font-bold px-2 py-0.5 rounded-full"
              :class="audit.cash_flow.is_positive_cashflow 
                ? 'bg-emerald-200 dark:bg-emerald-900 text-emerald-900 dark:text-emerald-200' 
                : 'bg-rose-200 dark:bg-rose-900 text-rose-900 dark:text-rose-200'"
            >
              {{ audit.cash_flow.is_positive_cashflow ? 'Surplus' : 'Deficit' }}
            </span>
          </div>

          <div 
            class="text-lg font-black mb-2"
            :class="audit.cash_flow.is_positive_cashflow ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-400'"
          >
            {{ audit.cash_flow.is_positive_cashflow ? '+' : '' }}Rs {{ formatNumber(audit.cash_flow.net_cash_flow) }}
          </div>

          <div class="space-y-1.5 text-xs text-gray-700 dark:text-gray-300 border-t border-blue-200/60 dark:border-blue-800/40 pt-2.5">
            <div class="flex justify-between items-center">
              <span class="text-gray-600 dark:text-gray-400">کل وصولی (Cash In):</span>
              <span class="font-bold text-emerald-600 dark:text-emerald-400">+ Rs {{ formatNumber(audit.cash_flow.cash_in_total) }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600 dark:text-gray-400">سپلائرز کو دیا:</span>
              <span class="font-bold text-gray-900 dark:text-gray-100">Rs {{ formatNumber(audit.cash_flow.cash_out_supplier_total) }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600 dark:text-gray-400">کل کیش آؤٹ:</span>
              <span class="font-bold text-rose-600 dark:text-rose-400">- Rs {{ formatNumber(audit.cash_flow.total_outflow) }}</span>
            </div>
          </div>
        </div>

        <!-- 3. Market Position Summary -->
        <div class="bg-amber-50/60 dark:bg-gray-800/90 rounded-xl p-4 border-2 border-amber-200 dark:border-amber-800/80 hover:shadow-md transition">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-black uppercase text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
              <span>⚖️</span>
              <span>3. مارکیٹ پوزیشن (Lena/Dena)</span>
            </span>
            <span 
              class="text-[10px] font-bold px-2 py-0.5 rounded-full"
              :class="audit.market.is_surplus 
                ? 'bg-emerald-200 dark:bg-emerald-900 text-emerald-900 dark:text-emerald-200' 
                : 'bg-rose-200 dark:bg-rose-900 text-rose-900 dark:text-rose-200'"
            >
              {{ audit.market.is_surplus ? 'Net Receivable' : 'Net Payable' }}
            </span>
          </div>

          <div 
            class="text-lg font-black mb-2"
            :class="audit.market.is_surplus ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-400'"
          >
            {{ audit.market.is_surplus ? '+' : '' }}Rs {{ formatNumber(audit.market.net_market_balance) }}
          </div>

          <div class="space-y-1.5 text-xs text-gray-700 dark:text-gray-300 border-t border-amber-200/60 dark:border-amber-800/40 pt-2.5">
            <div class="flex justify-between items-center">
              <span class="text-gray-600 dark:text-gray-400">گاہکوں سے لینا ہے:</span>
              <span class="font-bold text-blue-600 dark:text-blue-400">Rs {{ formatNumber(audit.market.total_receivables) }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600 dark:text-gray-400">سپلائرز کو دینا ہے:</span>
              <span class="font-bold text-amber-600 dark:text-amber-400">Rs {{ formatNumber(audit.market.total_payables) }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600 dark:text-gray-400">صافی بقایا بیلنس:</span>
              <span class="font-bold text-emerald-600 dark:text-emerald-400">+ Rs {{ formatNumber(audit.market.net_market_balance) }}</span>
            </div>
          </div>
        </div>

        <!-- 4. Stock Asset Summary -->
        <div class="bg-purple-50/60 dark:bg-gray-800/90 rounded-xl p-4 border-2 border-purple-200 dark:border-purple-800/80 hover:shadow-md transition">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-black uppercase text-purple-800 dark:text-purple-300 flex items-center gap-1.5">
              <span>🏢</span>
              <span>4. گودام اسٹاک (Stock Asset)</span>
            </span>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-200 dark:bg-purple-900 text-purple-900 dark:text-purple-200">
              {{ audit.stock.total_items }} Items
            </span>
          </div>

          <div class="text-lg font-black text-purple-700 dark:text-purple-300 mb-2">
            Rs {{ formatNumber(audit.stock.total_value) }}
          </div>

          <div class="space-y-1.5 text-xs text-gray-700 dark:text-gray-300 border-t border-purple-200/60 dark:border-purple-800/40 pt-2.5">
            <div class="flex justify-between items-center">
              <span class="text-gray-600 dark:text-gray-400">پروڈکٹ کوانٹٹی:</span>
              <span class="font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(audit.stock.total_qty) }} Qty</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600 dark:text-gray-400">رول لمبائی:</span>
              <span class="font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(audit.stock.total_meters) }} Meters</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600 dark:text-gray-400">خرید لاگت مالیت:</span>
              <span class="font-bold text-purple-600 dark:text-purple-400">Rs {{ formatNumber(audit.stock.total_value) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 10 Primary Executive KPI Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
      <!-- 1. Net Profit Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 relative overflow-hidden group hover:border-emerald-500 transition duration-200">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Net Profit (خالص نفع)</span>
          <span 
            class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold"
            :class="audit.profit.is_profitable ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'"
          >
            ₹
          </span>
        </div>
        <div 
          class="text-xl font-black mb-1"
          :class="audit.profit.is_profitable ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
        >
          Rs {{ formatNumber(audit.profit.net_profit) }}
        </div>
        <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
          <span>Net Margin: <strong class="text-gray-700 dark:text-gray-300">{{ audit.profit.net_margin }}%</strong></span>
          <span>Avg: Rs {{ formatNumber(audit.profit.profit_per_day) }}/d</span>
        </div>
      </div>

      <!-- 2. Gross Profit Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 relative overflow-hidden group hover:border-blue-500 transition duration-200">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gross Profit (مجموعی منافع)</span>
          <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 flex items-center justify-center text-xs font-bold">
            📈
          </span>
        </div>
        <div class="text-xl font-black text-blue-600 dark:text-blue-400 mb-1">
          Rs {{ formatNumber(audit.profit.gross_profit) }}
        </div>
        <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
          <span>Margin: <strong class="text-gray-700 dark:text-gray-300">{{ audit.profit.gross_margin }}%</strong></span>
          <span>COGS: Rs {{ formatNumber(audit.sales.net_cogs) }}</span>
        </div>
      </div>

      <!-- 3. Net Sales Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 relative overflow-hidden group hover:border-indigo-500 transition duration-200">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Sales (کل فروخت)</span>
          <span class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 flex items-center justify-center text-xs font-bold">
            🛒
          </span>
        </div>
        <div class="text-xl font-black text-indigo-600 dark:text-indigo-400 mb-1">
          Rs {{ formatNumber(audit.sales.net_sales) }}
        </div>
        <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
          <span>{{ audit.sales.count }} Invoices</span>
          <span>Avg: Rs {{ formatNumber(audit.sales.avg_order_value) }}</span>
        </div>
      </div>

      <!-- 4. Total Purchases Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 relative overflow-hidden group hover:border-amber-500 transition duration-200">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Purchases (خریداری)</span>
          <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 flex items-center justify-center text-xs font-bold">
            📦
          </span>
        </div>
        <div class="text-xl font-black text-amber-600 dark:text-amber-400 mb-1">
          Rs {{ formatNumber(audit.purchases.total) }}
        </div>
        <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
          <span>{{ audit.purchases.count }} Bills</span>
          <span>Cash: Rs {{ formatNumber(audit.purchases.cash_purchases) }}</span>
        </div>
      </div>

      <!-- 5. Operating Expenses Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 relative overflow-hidden group hover:border-rose-500 transition duration-200">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expenses (اخراجات)</span>
          <span class="w-7 h-7 rounded-lg bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 flex items-center justify-center text-xs font-bold">
            🧾
          </span>
        </div>
        <div class="text-xl font-black text-rose-600 dark:text-rose-400 mb-1">
          Rs {{ formatNumber(audit.expenses.total) }}
        </div>
        <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
          <span>{{ audit.expenses.count }} Vouchers</span>
          <span>{{ audit.expenses.by_category.length }} Categories</span>
        </div>
      </div>

      <!-- 6. Cash In (Customer Receipts) Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 relative overflow-hidden group hover:border-emerald-500 transition duration-200">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cash In (کل وصولی)</span>
          <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 flex items-center justify-center text-xs font-bold">
            📥
          </span>
        </div>
        <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mb-1">
          Rs {{ formatNumber(audit.cash_flow.cash_in_total) }}
        </div>
        <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
          <span>💵 Galla: {{ formatNumber(audit.cash_flow.cash_in_galla) }}</span>
          <span>🏦 Bank: {{ formatNumber(audit.cash_flow.cash_in_bank) }}</span>
        </div>
      </div>

      <!-- 7. Cash Out (Suppliers + Expenses) Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 relative overflow-hidden group hover:border-rose-500 transition duration-200">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cash Out (کل ادائیگی)</span>
          <span class="w-7 h-7 rounded-lg bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 flex items-center justify-center text-xs font-bold">
            📤
          </span>
        </div>
        <div class="text-xl font-black text-rose-600 dark:text-rose-400 mb-1">
          Rs {{ formatNumber(audit.cash_flow.total_outflow) }}
        </div>
        <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
          <span>Parties: {{ formatNumber(audit.cash_flow.cash_out_supplier_total) }}</span>
          <span>Exp: {{ formatNumber(audit.expenses.total) }}</span>
        </div>
      </div>

      <!-- 8. Market Receivables (Customer Lena) Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 relative overflow-hidden group hover:border-blue-500 transition duration-200">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Receivables (لینا ہے)</span>
          <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 flex items-center justify-center text-xs font-bold">
            👥
          </span>
        </div>
        <div class="text-xl font-black text-blue-600 dark:text-blue-400 mb-1">
          Rs {{ formatNumber(audit.market.total_receivables) }}
        </div>
        <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
          <span>{{ audit.market.receivable_customers_count }} Customers</span>
          <span class="text-blue-600 dark:text-blue-400 font-semibold">Market Asset</span>
        </div>
      </div>

      <!-- 9. Market Payables (Supplier Dena) Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 relative overflow-hidden group hover:border-amber-500 transition duration-200">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Payables (دینا ہے)</span>
          <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 flex items-center justify-center text-xs font-bold">
            🏭
          </span>
        </div>
        <div class="text-xl font-black text-amber-600 dark:text-amber-400 mb-1">
          Rs {{ formatNumber(audit.market.total_payables) }}
        </div>
        <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
          <span>{{ audit.market.payable_suppliers_count }} Suppliers</span>
          <span class="text-amber-600 dark:text-amber-400 font-semibold">Liability</span>
        </div>
      </div>

      <!-- 10. Godown Stock Valuation Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 relative overflow-hidden group hover:border-purple-500 transition duration-200">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock Value (اسٹاک مالیت)</span>
          <span class="w-7 h-7 rounded-lg bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 flex items-center justify-center text-xs font-bold">
            🏢
          </span>
        </div>
        <div class="text-xl font-black text-purple-600 dark:text-purple-400 mb-1">
          Rs {{ formatNumber(audit.stock.total_value) }}
        </div>
        <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
          <span>{{ audit.stock.total_items }} Items</span>
          <span>{{ formatNumber(audit.stock.total_qty) }} Qty</span>
        </div>
      </div>
    </div>

    <!-- Interactive Navigation Tabs for Detailed Audit -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
      <!-- Tabs Header -->
      <div class="flex flex-wrap border-b border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-900/40 px-4 pt-3 gap-2">
        <button
          v-for="t in tabs"
          :key="t.id"
          @click="activeTab = t.id"
          class="px-4 py-2.5 text-xs font-bold rounded-t-lg transition-all duration-150 flex items-center gap-2 border-b-2"
          :class="activeTab === t.id
            ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400 shadow-sm'
            : 'text-gray-600 dark:text-gray-400 border-transparent hover:text-gray-900 dark:hover:text-gray-200 hover:bg-white/50 dark:hover:bg-gray-800/50'"
        >
          <span>{{ t.icon }}</span>
          <span>{{ t.label }}</span>
          <span v-if="t.badge" class="px-1.5 py-0.5 text-[10px] rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300">
            {{ t.badge }}
          </span>
        </button>
      </div>

      <div class="p-5">
        <!-- TAB 1: Daily Timeline Audit -->
        <div v-if="activeTab === 'timeline'">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <span>📅</span>
                <span>Daily Audit Timeline Breakdown (روزانہ آڈٹ تفصیل)</span>
              </h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">Day-by-day sales, purchases, cash flow, expenses & profitability performance</p>
            </div>

            <div class="relative max-w-xs w-full">
              <input
                type="text"
                v-model="timelineSearch"
                placeholder="Search date or day..."
                class="form-input text-xs py-1.5 pl-8 pr-3 rounded-lg w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
              />
              <span class="absolute left-2.5 top-2 text-gray-400 text-xs">🔍</span>
            </div>
          </div>

          <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-left text-xs border-collapse">
              <thead class="bg-gray-50 dark:bg-gray-700/60 uppercase font-semibold text-gray-600 dark:text-gray-300">
                <tr>
                  <th class="px-4 py-3">Date (Day)</th>
                  <th class="px-4 py-3 text-right">Net Sales</th>
                  <th class="px-4 py-3 text-right">Purchases</th>
                  <th class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400">Cash In (Rec)</th>
                  <th class="px-4 py-3 text-right text-rose-600 dark:text-rose-400">Cash Out (Paid)</th>
                  <th class="px-4 py-3 text-right text-rose-500">Expenses</th>
                  <th class="px-4 py-3 text-right">Gross Profit</th>
                  <th class="px-4 py-3 text-right">Net Profit</th>
                  <th class="px-4 py-3 text-right">Net Cashflow</th>
                  <th class="px-4 py-3 text-center">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                <tr 
                  v-for="day in filteredTimeline" 
                  :key="day.date"
                  class="hover:bg-blue-50/40 dark:hover:bg-gray-700/40 transition"
                >
                  <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">
                    {{ day.formatted_date }} <span class="text-xs text-gray-500 font-normal">({{ day.day_name }})</span>
                  </td>
                  <td class="px-4 py-2.5 text-right font-medium">Rs {{ formatNumber(day.sales) }}</td>
                  <td class="px-4 py-2.5 text-right text-gray-600 dark:text-gray-300">Rs {{ formatNumber(day.purchases) }}</td>
                  <td class="px-4 py-2.5 text-right font-medium text-emerald-600 dark:text-emerald-400">Rs {{ formatNumber(day.cash_in) }}</td>
                  <td class="px-4 py-2.5 text-right font-medium text-rose-600 dark:text-rose-400">Rs {{ formatNumber(day.cash_out) }}</td>
                  <td class="px-4 py-2.5 text-right text-rose-500">Rs {{ formatNumber(day.expenses) }}</td>
                  <td class="px-4 py-2.5 text-right font-semibold" :class="day.gross_profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600'">
                    Rs {{ formatNumber(day.gross_profit) }}
                  </td>
                  <td class="px-4 py-2.5 text-right font-bold" :class="day.is_profitable ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600'">
                    Rs {{ formatNumber(day.net_profit) }}
                  </td>
                  <td class="px-4 py-2.5 text-right font-medium" :class="day.net_cash_flow >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                    {{ day.net_cash_flow >= 0 ? '+' : '' }}Rs {{ formatNumber(day.net_cash_flow) }}
                  </td>
                  <td class="px-4 py-2.5 text-center">
                    <span 
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                      :class="day.is_profitable 
                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' 
                        : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'"
                    >
                      {{ day.is_profitable ? 'Profitable' : 'Deficit' }}
                    </span>
                  </td>
                </tr>

                <tr v-if="filteredTimeline.length === 0">
                  <td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                    No transactions recorded for this period.
                  </td>
                </tr>
              </tbody>

              <!-- Footer Totals -->
              <tfoot v-if="filteredTimeline.length > 0" class="bg-gray-100 dark:bg-gray-700 font-bold text-gray-900 dark:text-gray-100">
                <tr>
                  <td class="px-4 py-3 uppercase">Total / Summary</td>
                  <td class="px-4 py-3 text-right">Rs {{ formatNumber(audit.sales.net_sales) }}</td>
                  <td class="px-4 py-3 text-right">Rs {{ formatNumber(audit.purchases.total) }}</td>
                  <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400">Rs {{ formatNumber(audit.cash_flow.cash_in_total) }}</td>
                  <td class="px-4 py-3 text-right text-rose-600 dark:text-rose-400">Rs {{ formatNumber(audit.cash_flow.total_outflow) }}</td>
                  <td class="px-4 py-3 text-right text-rose-500">Rs {{ formatNumber(audit.expenses.total) }}</td>
                  <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400">Rs {{ formatNumber(audit.profit.gross_profit) }}</td>
                  <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400">Rs {{ formatNumber(audit.profit.net_profit) }}</td>
                  <td class="px-4 py-3 text-right" :class="audit.cash_flow.is_positive_cashflow ? 'text-emerald-600' : 'text-rose-600'">
                    {{ audit.cash_flow.is_positive_cashflow ? '+' : '' }}Rs {{ formatNumber(audit.cash_flow.net_cash_flow) }}
                  </td>
                  <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 font-bold">
                      Audit Complete
                    </span>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- TAB 2: Income Statement (P&L) -->
        <div v-if="activeTab === 'pnl'">
          <div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-slate-900 to-blue-950 text-white p-4">
              <h3 class="text-base font-bold flex items-center gap-2">
                <span>📊</span>
                <span>Income Statement (Profit & Loss / نفع و نقصان)</span>
              </h3>
              <p class="text-xs text-blue-200">Accounting P&L breakdown for the period {{ audit.period_info.start_formatted }} to {{ audit.period_info.end_formatted }}</p>
            </div>

            <div class="p-6 divide-y divide-gray-100 dark:divide-gray-700 text-xs">
              <!-- Revenue Section -->
              <div class="pb-4">
                <h4 class="font-bold text-gray-900 dark:text-gray-100 uppercase tracking-wider mb-3 text-xs text-blue-600 dark:text-blue-400">
                  1. Sales Revenue (فروخت آمدن)
                </h4>
                <div class="space-y-2">
                  <div class="flex justify-between items-center text-gray-600 dark:text-gray-300">
                    <span>Gross Sales ({{ audit.sales.count }} Invoices)</span>
                    <span class="font-semibold">Rs {{ formatNumber(audit.sales.gross_sales) }}</span>
                  </div>
                  <div class="flex justify-between items-center text-rose-600 dark:text-rose-400">
                    <span>Less: Sales Returns ({{ audit.sales.returns_count }} Returns)</span>
                    <span>- Rs {{ formatNumber(audit.sales.returns_total) }}</span>
                  </div>
                  <div class="flex justify-between items-center pt-2 border-t border-gray-100 dark:border-gray-700 font-bold text-gray-900 dark:text-gray-100 text-sm">
                    <span>Net Sales Revenue (خالص فروخت)</span>
                    <span class="text-blue-600 dark:text-blue-400">Rs {{ formatNumber(audit.sales.net_sales) }}</span>
                  </div>
                </div>
              </div>

              <!-- COGS Section -->
              <div class="py-4">
                <h4 class="font-bold text-gray-900 dark:text-gray-100 uppercase tracking-wider mb-3 text-xs text-amber-600 dark:text-amber-400">
                  2. Cost of Goods Sold (COGS / بیچے گئے مال کی لاگت)
                </h4>
                <div class="space-y-2">
                  <div class="flex justify-between items-center text-gray-600 dark:text-gray-300">
                    <span>Product Purchase Rate & Materials Cost</span>
                    <span>Rs {{ formatNumber(audit.sales.net_cogs) }}</span>
                  </div>
                  <div class="flex justify-between items-center pt-2 border-t border-gray-100 dark:border-gray-700 font-bold text-emerald-600 dark:text-emerald-400 text-sm">
                    <span>GROSS PROFIT (مجموعی منافع)</span>
                    <span>Rs {{ formatNumber(audit.profit.gross_profit) }} ({{ audit.profit.gross_margin }}%)</span>
                  </div>
                </div>
              </div>

              <!-- Operating Expenses Section -->
              <div class="py-4">
                <h4 class="font-bold text-gray-900 dark:text-gray-100 uppercase tracking-wider mb-3 text-xs text-rose-600 dark:text-rose-400">
                  3. Operating Expenses (کاروباری اخراجات)
                </h4>
                <div class="space-y-2">
                  <div 
                    v-for="cat in audit.expenses.by_category" 
                    :key="cat.name"
                    class="flex justify-between items-center text-gray-600 dark:text-gray-300"
                  >
                    <span>{{ cat.name }} ({{ cat.percentage }}%)</span>
                    <span class="text-rose-600 dark:text-rose-400 font-medium">Rs {{ formatNumber(cat.amount) }}</span>
                  </div>
                  <div class="flex justify-between items-center pt-2 border-t border-gray-100 dark:border-gray-700 font-bold text-rose-600 dark:text-rose-400 text-sm">
                    <span>Total Operating Expenses (کل اخراجات)</span>
                    <span>Rs {{ formatNumber(audit.expenses.total) }}</span>
                  </div>
                </div>
              </div>

              <!-- Net Profit Bottom Line -->
              <div class="pt-4">
                <div class="bg-blue-50 dark:bg-blue-950/40 p-4 rounded-xl border border-blue-200 dark:border-blue-800 flex justify-between items-center">
                  <div>
                    <div class="text-sm font-black text-gray-900 dark:text-gray-100 uppercase">
                      NET PROFIT / (LOSS) (صافی منافع / اصل بچت)
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Net Profit Margin: {{ audit.profit.net_margin }}% | Daily Avg: Rs {{ formatNumber(audit.profit.profit_per_day) }}</div>
                  </div>
                  <div 
                    class="text-2xl font-black"
                    :class="audit.profit.is_profitable ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                  >
                    Rs {{ formatNumber(audit.profit.net_profit) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 3: Cash Flow Audit -->
        <div v-if="activeTab === 'cash_flow'">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Inflows -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
              <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700 mb-4">
                <h4 class="font-bold text-sm text-emerald-600 dark:text-emerald-400 flex items-center gap-2">
                  <span>📥</span>
                  <span>Cash Inflows (وصولیاں / Payments In)</span>
                </h4>
                <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold">Inflows</span>
              </div>

              <div class="space-y-3 text-xs">
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <div>
                    <div class="font-bold text-gray-800 dark:text-gray-200">💵 Cash In Hand (کیش گلہ وصولی)</div>
                    <div class="text-[11px] text-gray-500">Counter collections & physical cash received</div>
                  </div>
                  <div class="font-black text-emerald-600 text-sm">
                    Rs {{ formatNumber(audit.cash_flow.cash_in_galla) }}
                  </div>
                </div>

                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <div>
                    <div class="font-bold text-gray-800 dark:text-gray-200">🏦 Bank Transfers & Online (بینک وصولی)</div>
                    <div class="text-[11px] text-gray-500">Online bank deposits, cheques, mobile banking</div>
                  </div>
                  <div class="font-black text-emerald-600 text-sm">
                    Rs {{ formatNumber(audit.cash_flow.cash_in_bank) }}
                  </div>
                </div>

                <div class="flex justify-between items-center p-3 bg-emerald-50 dark:bg-emerald-950/40 rounded-lg border border-emerald-200 dark:border-emerald-800 font-bold">
                  <span class="text-emerald-800 dark:text-emerald-300 uppercase">Total Cash In (کل وصولی)</span>
                  <span class="text-emerald-600 dark:text-emerald-400 text-base">Rs {{ formatNumber(audit.cash_flow.cash_in_total) }}</span>
                </div>
              </div>
            </div>

            <!-- Outflows -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
              <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700 mb-4">
                <h4 class="font-bold text-sm text-rose-600 dark:text-rose-400 flex items-center gap-2">
                  <span>📤</span>
                  <span>Cash Outflows (ادائگیاں / Payments Out)</span>
                </h4>
                <span class="text-xs px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-bold">Outflows</span>
              </div>

              <div class="space-y-3 text-xs">
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <div>
                    <div class="font-bold text-gray-800 dark:text-gray-200">💵 Supplier Payments (Cash Galla)</div>
                    <div class="text-[11px] text-gray-500">Physical cash disbursed to vendors</div>
                  </div>
                  <div class="font-black text-rose-600 text-sm">
                    Rs {{ formatNumber(audit.cash_flow.cash_out_supplier_galla) }}
                  </div>
                </div>

                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <div>
                    <div class="font-bold text-gray-800 dark:text-gray-200">🏦 Supplier Payments (Bank)</div>
                    <div class="text-[11px] text-gray-500">Bank transfers paid to suppliers</div>
                  </div>
                  <div class="font-black text-rose-600 text-sm">
                    Rs {{ formatNumber(audit.cash_flow.cash_out_supplier_bank) }}
                  </div>
                </div>

                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <div>
                    <div class="font-bold text-gray-800 dark:text-gray-200">🧾 Operating Expenses Paid (اخراجات)</div>
                    <div class="text-[11px] text-gray-500">Rent, bills, staff, maintenance & consumables</div>
                  </div>
                  <div class="font-black text-rose-600 text-sm">
                    Rs {{ formatNumber(audit.expenses.total) }}
                  </div>
                </div>

                <div class="flex justify-between items-center p-3 bg-rose-50 dark:bg-rose-950/40 rounded-lg border border-rose-200 dark:border-rose-800 font-bold">
                  <span class="text-rose-800 dark:text-rose-300 uppercase">Total Cash Out (کل ادائیگی)</span>
                  <span class="text-rose-600 dark:text-rose-400 text-base">Rs {{ formatNumber(audit.cash_flow.total_outflow) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 4: Expenses Analysis -->
        <div v-if="activeTab === 'expenses'">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
              <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                <span>🧾</span>
                <span>Expense Category Distribution (اخراجات کی مدیں)</span>
              </h3>

              <div class="space-y-4">
                <div 
                  v-for="cat in audit.expenses.by_category" 
                  :key="cat.name"
                  class="space-y-1.5"
                >
                  <div class="flex justify-between text-xs font-semibold text-gray-700 dark:text-gray-300">
                    <span>{{ cat.name }}</span>
                    <span>Rs {{ formatNumber(cat.amount) }} ({{ cat.percentage }}%)</span>
                  </div>
                  <div class="w-full h-2.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                    <div 
                      class="h-full bg-gradient-to-r from-rose-500 to-amber-500 rounded-full"
                      :style="{ width: cat.percentage + '%' }"
                    ></div>
                  </div>
                </div>

                <div v-if="audit.expenses.by_category.length === 0" class="text-center py-8 text-gray-500 text-xs">
                  No expenses recorded in this period.
                </div>
              </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200 dark:border-gray-700 p-5 flex flex-col justify-between">
              <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Expense Summary</h4>
                <div class="text-3xl font-black text-rose-600 mb-1">
                  Rs {{ formatNumber(audit.expenses.total) }}
                </div>
                <div class="text-xs text-gray-500 mb-4">Total across {{ audit.expenses.count }} vouchers</div>

                <div class="p-3 bg-white dark:bg-gray-700 rounded-lg text-xs space-y-2">
                  <div class="flex justify-between text-gray-600 dark:text-gray-300">
                    <span>% of Net Sales:</span>
                    <strong class="text-rose-600">{{ audit.sales.net_sales > 0 ? (audit.expenses.total / audit.sales.net_sales * 100).toFixed(1) : 0 }}%</strong>
                  </div>
                  <div class="flex justify-between text-gray-600 dark:text-gray-300">
                    <span>Daily Average:</span>
                    <strong>Rs {{ formatNumber(audit.expenses.total / (audit.period_info.days_count || 1)) }}/day</strong>
                  </div>
                </div>
              </div>

              <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <Link 
                  :href="route('reports.expenses')"
                  class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg text-center block transition"
                >
                  View Full Expense Ledger →
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 5: Market Position & Stock Assets -->
        <div v-if="activeTab === 'market'">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Market Balances -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
              <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                <span>⚖️</span>
                <span>Market Lena / Dena (Receivables vs Payables)</span>
              </h3>

              <div class="space-y-3 text-xs">
                <div class="flex justify-between items-center p-3.5 bg-blue-50 dark:bg-blue-950/30 rounded-lg border border-blue-200 dark:border-blue-800">
                  <div>
                    <div class="font-bold text-blue-900 dark:text-blue-200">Customer Receivables (مارکیٹ سے لینا ہے)</div>
                    <div class="text-[11px] text-gray-500">{{ audit.market.receivable_customers_count }} customers with outstanding balances</div>
                  </div>
                  <div class="text-lg font-black text-blue-600 dark:text-blue-400">
                    Rs {{ formatNumber(audit.market.total_receivables) }}
                  </div>
                </div>

                <div class="flex justify-between items-center p-3.5 bg-amber-50 dark:bg-amber-950/30 rounded-lg border border-amber-200 dark:border-amber-800">
                  <div>
                    <div class="font-bold text-amber-900 dark:text-amber-200">Supplier Payables (سپلائرز کو دینا ہے)</div>
                    <div class="text-[11px] text-gray-500">{{ audit.market.payable_suppliers_count }} suppliers with payable balances</div>
                  </div>
                  <div class="text-lg font-black text-amber-600 dark:text-amber-400">
                    Rs {{ formatNumber(audit.market.total_payables) }}
                  </div>
                </div>

                <div 
                  class="flex justify-between items-center p-4 rounded-xl border font-bold text-sm"
                  :class="audit.market.is_surplus ? 'bg-emerald-50 border-emerald-300 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200' : 'bg-rose-50 border-rose-300 text-rose-900 dark:bg-rose-950/40 dark:text-rose-200'"
                >
                  <span>NET MARKET POSITION (صافی بقایا)</span>
                  <span class="text-lg font-black">
                    {{ audit.market.is_surplus ? '+' : '' }}Rs {{ formatNumber(audit.market.net_market_balance) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Stock Valuation -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
              <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                <span>🏢</span>
                <span>Godown Stock Assets (اسٹاک مالیت)</span>
              </h3>

              <div class="space-y-3 text-xs">
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <span class="text-gray-600 dark:text-gray-300">Total Products in Inventory:</span>
                  <span class="font-bold">{{ formatNumber(audit.stock.total_items) }} Items</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <span class="text-gray-600 dark:text-gray-300">Standard Product Quantity:</span>
                  <span class="font-bold">{{ formatNumber(audit.stock.total_qty) }} Units</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <span class="text-gray-600 dark:text-gray-300">Panaflex & Roll Length:</span>
                  <span class="font-bold">{{ formatNumber(audit.stock.total_meters) }} Meters</span>
                </div>
                <div class="flex justify-between items-center p-4 bg-purple-50 dark:bg-purple-950/40 rounded-xl border border-purple-200 dark:border-purple-800 font-bold text-sm">
                  <span class="text-purple-900 dark:text-purple-200">TOTAL STOCK VALUATION</span>
                  <span class="text-lg font-black text-purple-600 dark:text-purple-400">Rs {{ formatNumber(audit.stock.total_value) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import ModernIcon from '@/Components/ModernIcon.vue'

const props = defineProps({
  audit: {
    type: Object,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({
      period: 'monthly',
      start_date: '',
      end_date: ''
    })
  }
})

const activeTab = ref('timeline')
const timelineSearch = ref('')
const currentTime = ref(new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }))

const filters = ref({
  period: props.filters.period || 'monthly',
  start_date: props.filters.start_date || '',
  end_date: props.filters.end_date || ''
})

const presetButtons = [
  { id: 'today', label: 'Today' },
  { id: 'yesterday', label: 'Yesterday' },
  { id: 'this_week', label: 'This Week' },
  { id: 'last_7_days', label: '7 Days' },
  { id: '15_days', label: '15 Days' },
  { id: 'this_month', label: 'This Month' },
  { id: 'last_month', label: 'Last Month' },
  { id: 'this_year', label: 'This Year' },
]

const tabs = [
  { id: 'timeline', label: 'Daily Timeline Audit', icon: '📅', badge: computed(() => props.audit.timeline?.length || 0) },
  { id: 'pnl', label: 'Income Statement (P&L)', icon: '📊' },
  { id: 'cash_flow', label: 'Cash Flow Analysis', icon: '💵' },
  { id: 'expenses', label: 'Expenses Breakdown', icon: '🧾' },
  { id: 'market', label: 'Market & Stock Position', icon: '⚖️' },
]

const filteredTimeline = computed(() => {
  if (!props.audit.timeline) return []
  if (!timelineSearch.value) return props.audit.timeline

  const q = timelineSearch.value.toLowerCase()
  return props.audit.timeline.filter(day => 
    day.date.toLowerCase().includes(q) ||
    day.day_name.toLowerCase().includes(q) ||
    day.formatted_date.toLowerCase().includes(q)
  )
})

const exportParams = computed(() => ({
  period: filters.value.period,
  start_date: filters.value.start_date || undefined,
  end_date: filters.value.end_date || undefined,
}))

const formatNumber = (val) => {
  const num = Number(val || 0)
  return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const applyFilters = () => {
  router.get(route('reports.financial-audit'), {
    period: filters.value.period,
    start_date: filters.value.start_date || undefined,
    end_date: filters.value.end_date || undefined,
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

const resetFilters = () => {
  filters.value.period = 'monthly'
  filters.value.start_date = ''
  filters.value.end_date = ''
  applyFilters()
}

const setPreset = (presetId) => {
  filters.value.period = presetId
  filters.value.start_date = ''
  filters.value.end_date = ''
  applyFilters()
}

const printReport = () => {
  window.print()
}
</script>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>
