<template>
  <AppLayout>
    <div class="p-6 max-w-7xl mx-auto space-y-6 print:p-0 print:max-w-none print:m-0">
      
      <!-- Action Bar (Hidden in Print) -->
      <div class="flex justify-between items-center print:hidden">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Register Report #{{ session.id }}</h1>
          <p class="text-sm text-gray-500">Detailed breakdown of the session</p>
        </div>
        <div class="flex gap-2">
            <Link :href="route('reports.register.index')" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Back to List
            </Link>
            <button @click="printReport" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2">
                <PrinterIcon class="w-5 h-5" /> Print Report
            </button>
        </div>
      </div>

      <!-- Printable Report Container -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden print:shadow-none print:border-none print:rounded-none">
        
        <!-- Report Header -->
        <div class="p-8 border-b border-gray-200 text-center">
            <h2 class="text-3xl font-bold text-gray-900">DAY END REGISTER REPORT</h2>
            <p class="text-gray-500 mt-1">Al-Raza Graphics & Panaflex</p>
            <div class="mt-4 grid grid-cols-3 gap-4 text-left border p-4 rounded bg-gray-50 print:bg-transparent">
                <div>
                    <span class="block text-xs uppercase text-gray-500 font-semibold">Opened By</span>
                    <span class="font-medium text-lg">{{ session.user ? session.user.name : 'Unknown' }}</span>
                </div>
                <div>
                    <span class="block text-xs uppercase text-gray-500 font-semibold">Opened At</span>
                    <span class="font-medium">{{ formatDate(session.opened_at) }} {{ formatTime(session.opened_at) }}</span>
                </div>
                <div>
                    <span class="block text-xs uppercase text-gray-500 font-semibold">Closed At</span>
                    <span class="font-medium">{{ formatDate(session.closed_at) }} {{ formatTime(session.closed_at) }}</span>
                </div>
            </div>
        </div>

        <div class="p-8 space-y-8">
            
            <!-- Cash Management Summary -->
            <section>
                <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b uppercase tracking-wide">Cash Reconciliation</h3>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-4 bg-gray-50 rounded border print:border-gray-300">
                        <span class="text-sm text-gray-500 block">Opening Cash</span>
                        <span class="text-xl font-bold text-gray-900">{{ formatCurrency(session.opening_cash) }}</span>
                    </div>
                     <div class="p-4 bg-gray-50 rounded border print:border-gray-300">
                        <span class="text-sm text-gray-500 block">Total Sales (All Methods)</span>
                        <span class="text-xl font-bold text-blue-600">{{ formatCurrency(summary.total_sales) }}</span>
                    </div>
                    <div class="p-4 bg-gray-50 rounded border print:border-gray-300">
                        <span class="text-sm text-gray-500 block">Total Expenses</span>
                        <span class="text-xl font-bold text-red-600">{{ formatCurrency(summary.total_expenses) }}</span>
                    </div>
                     <div class="p-4 bg-gray-50 rounded border print:border-gray-300">
                        <span class="text-sm text-gray-500 block">Closing Cash (Actual)</span>
                        <span class="text-xl font-bold text-gray-900">{{ formatCurrency(session.closing_cash) }}</span>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                    <div class="p-2 border rounded">
                        <span class="block text-xs text-gray-500">Expected Cash</span>
                        <span class="font-bold">{{ formatCurrency(session.expected_cash) }}</span>
                    </div>
                     <div class="p-2 border rounded">
                        <span class="block text-xs text-gray-500">Variance/Diff</span>
                        <span class="font-bold" :class="session.cash_difference < 0 ? 'text-red-600' : 'text-green-600'">{{ formatCurrency(session.cash_difference) }}</span>
                    </div>
                    <div class="p-2 border rounded">
                        <span class="block text-xs text-gray-500">Status</span>
                        <span class="font-bold uppercase">{{ session.status }}</span>
                    </div>
                </div>
            </section>

            <!-- Sales Summary -->
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>
                   <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b uppercase tracking-wide">Sales Breakdown</h3>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 print:bg-gray-100">
                            <tr>
                                <th class="p-2 text-left">Payment Method</th>
                                <th class="p-2 text-right">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="(amount, method) in summary.sales_by_method" :key="method">
                                <td class="p-2 capitalize">{{ method.replace('_', ' ') }}</td>
                                <td class="p-2 text-right font-medium">{{ formatCurrency(amount) }}</td>
                            </tr>
                            <tr class="font-bold bg-gray-50 border-t-2 border-gray-300">
                                <td class="p-2">TOTAL SALES</td>
                                <td class="p-2 text-right">{{ formatCurrency(summary.total_sales) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div>
                   <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b uppercase tracking-wide">Drawer Expenses</h3>
                   <table class="w-full text-sm mb-8">
                        <thead class="bg-gray-100 print:bg-gray-100">
                            <tr>
                                <th class="p-2 text-left">Category</th>
                                <th class="p-2 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                             <tr v-for="(data, index) in summary.expense_breakdown" :key="index">
                                <td class="p-2">{{ data.category }}</td>
                                <td class="p-2 text-right font-medium">{{ formatCurrency(data.amount) }}</td>
                            </tr>
                            <tr v-if="!summary.expense_breakdown || Object.keys(summary.expense_breakdown).length === 0">
                                <td colspan="2" class="p-4 text-center text-gray-500">No Expenses Recorded</td>
                            </tr>
                             <tr class="font-bold bg-gray-50 border-t-2 border-gray-300">
                                <td class="p-2">TOTAL DRAWER EXPENSES</td>
                                <td class="p-2 text-right">{{ formatCurrency(summary.total_expenses) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="summary.owner_expenses_total > 0">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b uppercase tracking-wide text-orange-600">Owner Expenses (External)</h3>
                        <table class="w-full text-sm border border-orange-200">
                                <thead class="bg-orange-50 print:bg-orange-50">
                                    <tr>
                                        <th class="p-2 text-left text-orange-800">Category</th>
                                        <th class="p-2 text-right text-orange-800">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-orange-100">
                                    <tr v-for="(data, index) in summary.owner_expenses_breakdown" :key="index">
                                        <td class="p-2">{{ data.category }}</td>
                                        <td class="p-2 text-right font-medium text-orange-700">{{ formatCurrency(data.amount) }}</td>
                                    </tr>
                                    <tr class="font-bold bg-orange-50 border-t-2 border-orange-200">
                                        <td class="p-2 text-orange-900">TOTAL OWNER EXPENSES</td>
                                        <td class="p-2 text-right text-orange-900">{{ formatCurrency(summary.owner_expenses_total) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="text-xs text-orange-600 mt-1">* Not included in Cash Reconciliation</p>
                    </div>
                </div>
            </section>

            <!-- Stock Report -->
             <section>
                 <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b uppercase tracking-wide">Stock / Products Sold</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm divide-y divide-gray-200 border">
                         <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 text-left">Product / Item</th>
                                <th class="p-2 text-center">Qty / Sq.ft</th>
                                <th class="p-2 text-right">Total Revenue</th>
                            </tr>
                        </thead>
                         <tbody>
                            <tr v-for="(item, index) in stock_sold" :key="index" class="hover:bg-gray-50">
                                <td class="p-2 font-medium">{{ item.name }}</td>
                                <td class="p-2 text-center">
                                    <span v-if="item.sqft > 0">{{ Number(item.sqft).toFixed(2) }} Sq.ft</span>
                                    <span v-else>{{ item.qty }}</span>
                                </td>
                                <td class="p-2 text-right">{{ formatCurrency(item.total) }}</td>
                            </tr>
                             <tr v-if="stock_sold.length === 0">
                                <td colspan="3" class="p-4 text-center text-gray-500">No Items Sold</td>
                            </tr>
                         </tbody>
                    </table>
                </div>
            </section>

            <!-- Full Invoice List -->
            <section class="break-before-page">
                 <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b uppercase tracking-wide">Detailed Invoice List</h3>
                 <div class="overflow-x-auto">
                    <table class="w-full text-sm divide-y divide-gray-200 border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 text-left">Invoice #</th>
                                <th class="p-2 text-left">Time</th>
                                <th class="p-2 text-left">Customer</th>
                                <th class="p-2 text-center">Method</th>
                                <th class="p-2 text-right">Bill Total</th>
                                <th class="p-2 text-right">Paid</th>
                                <th class="p-2 text-right">Balance</th>
                            </tr>
                        </thead>
                         <tbody>
                            <tr v-for="sale in session.sales" :key="sale.id" class="hover:bg-gray-50">
                                <td class="p-2 font-medium">#{{ sale.invoice_no }}</td>
                                <td class="p-2 text-gray-500">{{ formatTime(sale.sold_at) }}</td>
                                <td class="p-2">{{ sale.customer ? sale.customer.name : (sale.custom_customer_name || 'Walk-in') }}</td>
                                <td class="p-2 text-center capitalize">
                                    <span class="px-2 py-0.5 rounded text-xs border" 
                                        :class="{
                                            'bg-green-50 border-green-200 text-green-700': sale.payment_type === 'cash',
                                            'bg-blue-50 border-blue-200 text-blue-700': sale.payment_type === 'bank',
                                            'bg-orange-50 border-orange-200 text-orange-700': sale.payment_type === 'credit'
                                        }">
                                        {{ sale.payment_type }}
                                    </span>
                                </td>
                                <td class="p-2 text-right">{{ formatCurrency(sale.bill_total) }}</td>
                                <td class="p-2 text-right">{{ formatCurrency(sale.paid_amount) }}</td>
                                <td class="p-2 text-right font-medium text-gray-400">{{ formatCurrency(sale.grand_total - sale.paid_amount) }}</td>
                            </tr>
                             <tr v-if="session.sales.length === 0">
                                <td colspan="7" class="p-4 text-center text-gray-500">No Sales in this session</td>
                            </tr>
                        </tbody>
                    </table>
                 </div>
            </section>
             
             <!-- Footer Notes -->
             <section v-if="session.closing_notes" class="mt-8 p-4 bg-gray-50 rounded border print:border-gray-200">
                 <h4 class="font-bold text-gray-700 text-xs uppercase mb-1">Closing Notes</h4>
                 <p class="text-gray-900">{{ session.closing_notes }}</p>
             </section>

             <div class="hidden print:block mt-12 pt-8 border-t border-gray-400">
                 <div class="flex justify-between text-sm">
                     <div class="text-center w-32 pt-8 border-t border-gray-900">
                         Checked By
                     </div>
                      <div class="text-center w-32 pt-8 border-t border-gray-900">
                         Approved By
                     </div>
                 </div>
                 <div class="text-center mt-4 text-xs text-gray-400">
                     Printed on {{ formatDate(new Date()) }}
                 </div>
             </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { PrinterIcon } from 'lucide-vue-next'

const props = defineProps({
  session: Object,
  summary: Object,
  stock_sold: Array
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-PK', {
    style: 'currency',
    currency: 'PKR'
  }).format(amount)
}

const formatDate = (date) => {
  if(!date) return '-';
  return new Date(date).toLocaleDateString('en-PK', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatTime = (date) => {
  if(!date) return '';
  return new Date(date).toLocaleTimeString('en-PK', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const printReport = () => {
    window.print()
}
</script>

<style>
@media print {
    /* Hide Layout Elements like sidebar/header if AppLayout doesn't handle it for us with 'no-print' classes */
    /* Assuming AppLayout uses standard Inertia/Jetstream structure which might just need hiding everything else if specific classes aren't there */
    
    /* However, we used 'print:hidden' on the action bar. */
    /* If AppLayout has a sidebar, we probably want to hide it. */
    nav, aside, header {
        display: none !important;
    }

    body {
        background: white;
    }
    
    /* Ensure background colors print */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>
