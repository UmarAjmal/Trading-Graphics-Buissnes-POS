<template>
  <AppLayout>
    <PageHeader
      title="Reports & Analytics"
      subtitle="View and export comprehensive business reports"
    />

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Primary Executive Financial Audit Card -->
      <div 
        class="md:col-span-3 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-700 rounded-xl shadow-lg hover:shadow-xl p-6 text-white cursor-pointer transition-all duration-300 hover:-translate-y-1 relative overflow-hidden"
        @click="navigateToReport('financial-audit')"
      >
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-white/20 backdrop-blur-sm mb-2 text-white border border-white/20">
              <span>⭐</span>
              <span>ALL-IN-ONE BUSINESS AUDIT</span>
            </div>
            <h3 class="text-2xl font-black text-white">Executive Financial & Audit Report (مکمل مالیاتی و آڈٹ سمری)</h3>
            <p class="text-blue-100 text-sm mt-1 max-w-2xl">
              Complete consolidated business audit: Net Profit, Gross Profit, Sales, Purchases, Cash In/Out (Galla & Bank), Market Receivables & Payables, Expenses, and Godown Stock Value with Daily/Weekly/15-Day/Monthly/Yearly audit timelines.
            </p>
          </div>
          <div class="flex items-center gap-2 bg-white text-blue-900 font-bold px-5 py-2.5 rounded-xl shadow-md text-sm whitespace-nowrap self-start md:self-auto hover:bg-blue-50 transition">
            <span>View Executive Audit</span>
            <span>→</span>
          </div>
        </div>
      </div>

      <!-- Report Cards -->
      <ReportCard
        title="Sales Report"
        description="View detailed sales analytics, customer insights, and revenue trends"
        color="blue"
        @click="navigateToReport('sales')"
      />
      <ReportCard
        title="Purchase Report"
        description="Track purchase orders, supplier performance, and inventory costs"
        color="green"
        @click="navigateToReport('purchases')"
      />
      <ReportCard
        title="Profit Report"
        description="Analyze profit margins, expenses, and overall business profitability"
        color="purple"
        @click="navigateToReport('profit')"
      />
      <ReportCard
        title="Customer Reports"
        description="View customer ledger with sales, payments, and outstanding balances"
        color="blue"
        @click="navigateToReport('customers')"
      />
      <ReportCard
        title="Receivables Report"
        description="Overview of all customer balances, receivables, and advances"
        color="indigo"
        @click="navigateToReport('receivables')"
      />
      <ReportCard
        title="Supplier Reports"
        description="Track supplier ledger with purchases, payments, and prepayments"
        color="green"
        @click="navigateToReport('suppliers')"
      />
      <ReportCard
        title="Payables Report"
        description="Overview of all supplier balances, payables, and prepaid advances"
        color="amber"
        @click="navigateToReport('payables')"
      />
      <ReportCard
        title="Receipt Report (Cash In)"
        description="Comprehensive customer collections, cash receipts, and inflow audit"
        color="emerald"
        @click="navigateToReport('receipts')"
      />
      <ReportCard
        title="Payment Report (Cash Out)"
        description="Comprehensive supplier payments, cash disbursements, and outflow audit"
        color="rose"
        @click="navigateToReport('payments')"
      />
      <ReportCard
        title="All Parties Ledger"
        description="Combined summary of all Customers and Suppliers with balances"
        color="purple"
        @click="navigateToReport('all-parties-ledger')"
      />
    </div>

    <!-- Features -->
    <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📊 Available Features</h3>
      <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <FeatureItem text="Multiple filter options (Daily, Weekly, Monthly, Yearly, Custom Range)" />
        <FeatureItem text="Visual charts and graphs for trend analysis" />
        <FeatureItem text="Export to PDF, Excel, and CSV formats" />
        <FeatureItem text="Detailed transaction tables with complete data" />
      </ul>
    </div>
  </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'

const navigateToReport = (type) => {
  router.visit(`/reports/${type}`)
}
</script>

<!-- Simple reusable components -->
<script>
export default {
  components: {
    ReportCard: {
      props: ['title', 'description', 'color'],
      emits: ['click'],
      template: `
        <div
          class="bg-white dark:bg-gray-800 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer border-2 border-transparent hover:-translate-y-1"
          :class="'hover:border-' + color + '-500'"
          @click="$emit('click')"
        >
          <div class="p-6">
            <div class="flex justify-between items-center mb-4">
              <div class="icon-container" :class="'icon-container--gradient-' + color">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19c-5 0-8-3-8-8s4-8 9-8 8 3 8 8-3 8-8 8zm0-13a1 1 0 011 1v1h1a1 1 0 010 2h-2a1 1 0 01-1-1V8a1 1 0 011-1z"/>
                </svg>
              </div>
              <div class="icon-container icon-container--sm">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>
            <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-gray-100">{{ title }}</h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">{{ description }}</p>
            <div class="flex justify-between text-sm">
              <span :class="'text-' + color + '-600 dark:text-' + color + '-400 font-medium'">View Report</span>
              <span class="text-gray-500 dark:text-gray-400">→</span>
            </div>
          </div>
        </div>
      `
    },
    FeatureItem: {
      props: ['text'],
      template: `
        <li class="flex items-start">
          <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <span class="text-sm text-gray-700 dark:text-gray-300">{{ text }}</span>
        </li>
      `
    }
  }
}
</script>
