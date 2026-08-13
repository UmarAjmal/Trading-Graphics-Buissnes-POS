<template>
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('sales.index')"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <ArrowLeftIcon class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ sale.invoice_no }}</h1>
                        <p class="text-sm text-gray-600">Sale Details</p>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex space-x-3 sm:mt-0">
                <button
                    @click="printA4(false)"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <DocumentTextIcon class="h-4 w-4 mr-2" />
                    Print A4
                </button>
                <button
                    @click="print80mm(false)"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <ReceiptPercentIcon class="h-4 w-4 mr-2" />
                    Print 80mm
                </button>
                <button
                    @click="createReturn"
                    :disabled="!canCreateReturn"
                    class="inline-flex items-center px-4 py-2 bg-rose-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-rose-700 focus:bg-rose-700 active:bg-rose-900 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                >
                    <ArrowUturnLeftIcon class="h-4 w-4 mr-2" />
                    Create Return
                </button>
            </div>
        </div>

        <!-- Sale Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Basic Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Sale Information</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Date/Time:</span>
                        <span class="text-sm font-medium">{{ formatDate(sale.sold_at) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Cashier:</span>
                        <span class="text-sm font-medium">{{ sale.user?.name || 'Unknown' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Payment:</span>
                        <span
                            :class="[
                                'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                                sale.payment_type === 'cash' 
                                    ? 'bg-emerald-100 text-emerald-800' 
                                    : 'bg-indigo-100 text-indigo-800'
                            ]"
                        >
                            {{ sale.payment_type === 'cash' ? 'Cash' : 'Credit' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Customer</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Name:</span>
                        <span class="text-sm font-medium">{{ sale.customer?.name || 'Walk-in Customer' }}</span>
                    </div>
                    <div v-if="sale.customer?.phone" class="flex justify-between">
                        <span class="text-sm text-gray-600">Phone:</span>
                        <span class="text-sm font-medium">{{ sale.customer.phone }}</span>
                    </div>
                    <div v-if="sale.customer?.address" class="flex justify-between">
                        <span class="text-sm text-gray-600">Address:</span>
                        <span class="text-sm font-medium text-right">{{ sale.customer.address }}</span>
                    </div>
                </div>
            </div>

            <!-- Returns Summary -->
            <div v-if="sale.returns && sale.returns.length > 0" class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Returns</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Total Returns:</span>
                        <span class="text-sm font-medium">{{ sale.returns.length }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Returned Amount:</span>
                        <span class="text-sm font-medium text-rose-600">
                            PKR {{ formatAmount(Math.abs(totalReturnedAmount)) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Remaining:</span>
                        <span class="text-sm font-medium">
                            PKR {{ formatAmount(sale.grand_total - Math.abs(totalReturnedAmount)) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Totals -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Totals</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Subtotal:</span>
                        <span class="text-sm font-medium">PKR {{ formatAmount(sale.subtotal) }}</span>
                    </div>
                    <div v-if="sale.discount_total > 0" class="flex justify-between">
                        <span class="text-sm text-gray-600">Discount:</span>
                        <span class="text-sm font-medium text-rose-600">-PKR {{ formatAmount(sale.discount_total) }}</span>
                    </div>
                    <div v-if="sale.tax_total > 0" class="flex justify-between">
                        <span class="text-sm text-gray-600">Tax:</span>
                        <span class="text-sm font-medium">PKR {{ formatAmount(sale.tax_total) }}</span>
                    </div>
                    <div v-if="sale.other_charges > 0" class="flex justify-between">
                        <span class="text-sm text-gray-600">Other:</span>
                        <span class="text-sm font-medium">PKR {{ formatAmount(sale.other_charges) }}</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-200">
                        <span class="text-sm font-medium text-gray-900">Grand Total:</span>
                        <span class="text-sm font-bold text-gray-900">PKR {{ formatAmount(sale.grand_total) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Description / Internal Note -->
        <div v-if="sale.system_description" class="bg-blue-50 border border-blue-200 rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-sm font-medium text-blue-800 mb-2">System Description / Internal Note</h3>
            <p class="text-sm text-blue-900 whitespace-pre-wrap">{{ sale.system_description }}</p>
        </div>

        <!-- Sale Items -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Sale Items</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Product
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Units/Qty
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Rate
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Line Total
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="item in sale.sale_items" :key="item.id">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ item.product?.name || 'Custom Item' }}</div>
                                    <div v-if="item.description" class="text-sm text-gray-500">{{ item.description }}</div>
                                    <div v-if="isPanaflexItem(item)" class="text-xs text-gray-400 mt-1">
                                        {{ item.length_input }}{{ item.length_unit }} × {{ item.width_input }}{{ item.width_unit }} × {{ item.quantity }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm text-gray-900">
                                    <span v-if="isPanaflexItem(item)">
                                        {{ formatAmount(item.units_sqft) }} sq.ft
                                    </span>
                                    <span v-else>
                                        {{ item.quantity }} {{ item.product?.unit?.symbol || 'pcs' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-gray-900">
                                PKR {{ formatAmount(item.rate) }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-gray-900 font-medium">
                                PKR {{ formatAmount(item.line_total) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span v-if="getItemReturnedQuantity(item) > 0"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800"
                                >
                                    Partially Returned
                                </span>
                                <span v-else-if="isItemFullyReturned(item)"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                                >
                                    Fully Returned
                                </span>
                                <span v-else
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                >
                                    Active
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Returns History -->
        <div v-if="sale.returns && sale.returns.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Returns History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Return No
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Processed By
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Reason
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="returnItem in sale.returns" :key="returnItem.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ returnItem.return_no }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatDate(returnItem.returned_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ returnItem.user?.name || 'Unknown' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-rose-600 text-right font-medium">
                                PKR {{ formatAmount(Math.abs(returnItem.grand_total)) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="max-w-xs truncate" :title="returnItem.reason">
                                    {{ returnItem.reason }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <button
                                        @click="printReturnA4(returnItem.id)"
                                        title="Print Return A4"
                                        class="text-gray-400 hover:text-gray-600"
                                    >
                                        <DocumentTextIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="printReturn80mm(returnItem.id)"
                                        title="Print Return 80mm"
                                        class="text-gray-400 hover:text-gray-600"
                                    >
                                        <ReceiptPercentIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
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

const totalReturnedAmount = computed(() => {
    if (!props.sale.returns) return 0
    return props.sale.returns.reduce((total, returnItem) => total + returnItem.grand_total, 0)
})

const canCreateReturn = computed(() => {
    const hasReturnableItems = props.sale.sale_items.some(item => {
        if (isPanaflexItem(item)) {
            return getRemainingUnits(item) > 0
        } else {
            return getRemainingQuantity(item) > 0
        }
    })
    
    return hasReturnableItems
})

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('en-PK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-PK').format(amount)
}

const isPanaflexItem = (item) => {
    return item.product?.type === 'panaflex_roll'
}


const getItemReturnedQuantity = (item) => {
    if (!props.sale.returns) return 0
    
    let returned = 0
    props.sale.returns.forEach(returnRecord => {
        if (returnRecord.items) {
            returnRecord.items.forEach(returnItem => {
                if (returnItem.sale_item_id === item.id) {
                    returned += isPanaflexItem(item) ? returnItem.units_sqft : returnItem.quantity
                }
            })
        }
    })
    
    return returned
}

const getRemainingQuantity = (item) => {
    return item.quantity - getItemReturnedQuantity(item)
}

const getRemainingUnits = (item) => {
    return item.units_sqft - getItemReturnedQuantity(item)
}

const isItemFullyReturned = (item) => {
    if (isPanaflexItem(item)) {
        return getRemainingUnits(item) <= 0
    } else {
        return getRemainingQuantity(item) <= 0
    }
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
    router.visit(route('sales.return.create', props.sale.id))
}
</script>