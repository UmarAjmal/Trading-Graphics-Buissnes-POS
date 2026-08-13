<template>
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center space-x-3 mb-4">
                <Link
                    :href="route('sales.show', sale.id)"
                    class="text-gray-400 hover:text-gray-600"
                >
                    <ArrowLeftIcon class="h-6 w-6" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Create Return</h1>
                    <p class="text-sm text-gray-600">Return for Invoice {{ sale.invoice_no }}</p>
                </div>
            </div>

            <!-- Sale Info -->
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Customer:</span>
                        <span class="ml-2 font-medium">{{ sale.customer?.name || 'Walk-in Customer' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Date:</span>
                        <span class="ml-2 font-medium">{{ formatDate(sale.sold_at) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Total:</span>
                        <span class="ml-2 font-medium">PKR {{ formatAmount(sale.grand_total) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <form @submit.prevent="submitReturn">
            <!-- Return Reason -->
            <div class="mb-6 bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Return Details</h3>
                <div class="mb-4">
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                        Reason for Return <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="reason"
                        v-model="form.reason"
                        rows="3"
                        placeholder="Reason for return (damage, wrong size, customer request...)"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :class="{ 'border-red-300': errors.reason }"
                        required
                    ></textarea>
                    <p v-if="errors.reason" class="mt-1 text-sm text-red-600">{{ errors.reason }}</p>
                </div>

                <div>
                    <label for="other_adjustments" class="block text-sm font-medium text-gray-700 mb-2">
                        Other Adjustments (PKR)
                    </label>
                    <input
                        id="other_adjustments"
                        v-model.number="form.other_adjustments"
                        type="number"
                        step="0.01"
                        placeholder="Restocking fee (-) or goodwill compensation (+)"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    <p class="mt-1 text-xs text-gray-500">
                        Enter negative values for restocking fees, positive for goodwill adjustments
                    </p>
                </div>
            </div>

            <!-- Return Items -->
            <div class="mb-6 bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Items to Return</h3>
                    <p class="text-sm text-gray-600">Select quantities/units to return for each item</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Product
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Original
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Available
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Return
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rate
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Refund Amount
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(item, index) in returnableItems" :key="item.id">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ item.product_name }}</div>
                                        <div class="text-sm text-gray-500">{{ item.description }}</div>
                                        <div v-if="item.is_panaflex" class="text-xs text-gray-400 mt-1">
                                            Panaflex calculation by square feet
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-900">
                                    <div v-if="item.is_panaflex">
                                        {{ formatAmount(item.original_units_sqft) }} sq.ft
                                    </div>
                                    <div v-else>
                                        {{ item.original_quantity }} pcs
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-900">
                                    <div v-if="item.is_panaflex">
                                        {{ formatAmount(item.remaining_units_sqft) }} sq.ft
                                    </div>
                                    <div v-else>
                                        {{ item.remaining_quantity }} pcs
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div v-if="item.is_panaflex">
                                        <input
                                            v-model.number="form.items[index].return_units_sqft"
                                            type="number"
                                            step="0.01"
                                            :max="item.remaining_units_sqft"
                                            min="0"
                                            placeholder="0.00"
                                            class="w-24 text-center rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            @input="calculateLineTotal(index)"
                                        />
                                        <div class="text-xs text-gray-500 mt-1">sq.ft</div>
                                    </div>
                                    <div v-else>
                                        <input
                                            v-model.number="form.items[index].return_quantity"
                                            type="number"
                                            :max="item.remaining_quantity"
                                            min="0"
                                            placeholder="0"
                                            class="w-20 text-center rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            @input="calculateLineTotal(index)"
                                        />
                                        <div class="text-xs text-gray-500 mt-1">pcs</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-900">
                                    PKR {{ formatAmount(item.rate) }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <span :class="calculateLineTotal(index) < 0 ? 'text-rose-600' : 'text-gray-900'">
                                        PKR {{ formatAmount(Math.abs(calculateLineTotal(index))) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Item Notes -->
                <div class="px-6 py-4 bg-gray-50">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Item Notes (Optional)</h4>
                    <div class="space-y-3">
                        <div v-for="(item, index) in returnableItems" :key="`note-${item.id}`">
                            <label :for="`note-${index}`" class="block text-xs font-medium text-gray-700 mb-1">
                                {{ item.product_name }}
                            </label>
                            <input
                                :id="`note-${index}`"
                                v-model="form.items[index].note"
                                type="text"
                                placeholder="Optional note about this item return..."
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Return Summary -->
            <div class="mb-6 bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Return Summary</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Subtotal:</span>
                        <span class="text-sm font-medium text-rose-600">
                            PKR {{ formatAmount(Math.abs(returnSubtotal)) }}
                        </span>
                    </div>
                    <div v-if="form.other_adjustments !== 0" class="flex justify-between">
                        <span class="text-sm text-gray-600">Other Adjustments:</span>
                        <span 
                            class="text-sm font-medium"
                            :class="form.other_adjustments >= 0 ? 'text-green-600' : 'text-rose-600'"
                        >
                            {{ form.other_adjustments >= 0 ? '+' : '' }}PKR {{ formatAmount(Math.abs(form.other_adjustments)) }}
                        </span>
                    </div>
                    <div class="flex justify-between pt-3 border-t border-gray-200">
                        <span class="text-base font-medium text-gray-900">Refund Total:</span>
                        <span class="text-base font-bold text-rose-600">
                            PKR {{ formatAmount(Math.abs(returnGrandTotal)) }}
                        </span>
                    </div>
                </div>

                <div v-if="hasReturnItems" class="mt-4 p-3 bg-blue-50 rounded-md">
                    <p class="text-sm text-blue-800">
                        <span class="font-medium">{{ returnItemCount }}</span> item(s) will be returned.
                        <span v-if="sale.payment_type === 'credit'" class="block mt-1">
                            This credit sale's outstanding balance will be reduced by the refund amount.
                        </span>
                    </p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-4">
                <Link
                    :href="route('sales.show', sale.id)"
                    class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Cancel
                </Link>
                <button
                    type="submit"
                    :disabled="!hasReturnItems || processing"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-rose-600 hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 disabled:opacity-25 disabled:cursor-not-allowed"
                >
                    <span v-if="processing">Processing...</span>
                    <span v-else>Create Return</span>
                </button>
            </div>
        </form>

        <!-- Error Messages -->
        <div v-if="Object.keys(errors).length > 0" class="mt-4 bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                        There were errors with your submission
                    </h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    sale: Object,
    returnableItems: Array,
    errors: {
        type: Object,
        default: () => ({})
    }
})

const processing = ref(false)

const form = reactive({
    reason: '',
    other_adjustments: 0,
    items: props.returnableItems.map(item => ({
        sale_item_id: item.id,
        return_quantity: 0,
        return_units_sqft: 0,
        note: ''
    }))
})

const returnSubtotal = computed(() => {
    return form.items.reduce((total, formItem, index) => {
        const item = props.returnableItems[index]
        let lineTotal = 0
        
        if (item.is_panaflex) {
            lineTotal = (formItem.return_units_sqft || 0) * item.rate
        } else {
            lineTotal = (formItem.return_quantity || 0) * item.rate
        }
        
        return total + lineTotal
    }, 0) * -1 // Make negative for refund
})

const returnGrandTotal = computed(() => {
    return returnSubtotal.value + (form.other_adjustments || 0)
})

const hasReturnItems = computed(() => {
    return form.items.some((formItem, index) => {
        const item = props.returnableItems[index]
        if (item.is_panaflex) {
            return (formItem.return_units_sqft || 0) > 0
        } else {
            return (formItem.return_quantity || 0) > 0
        }
    })
})

const returnItemCount = computed(() => {
    return form.items.filter((formItem, index) => {
        const item = props.returnableItems[index]
        if (item.is_panaflex) {
            return (formItem.return_units_sqft || 0) > 0
        } else {
            return (formItem.return_quantity || 0) > 0
        }
    }).length
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

const calculateLineTotal = (index) => {
    const formItem = form.items[index]
    const item = props.returnableItems[index]
    
    if (item.is_panaflex) {
        return (formItem.return_units_sqft || 0) * item.rate * -1
    } else {
        return (formItem.return_quantity || 0) * item.rate * -1
    }
}

const submitReturn = () => {
    if (!hasReturnItems.value) {
        alert('Please specify quantities/units to return for at least one item.')
        return
    }

    processing.value = true

    // Filter out items with no return quantity/units
    const itemsToReturn = form.items.filter((formItem, index) => {
        const item = props.returnableItems[index]
        if (item.is_panaflex) {
            return (formItem.return_units_sqft || 0) > 0
        } else {
            return (formItem.return_quantity || 0) > 0
        }
    })

    router.post(route('sales.return.store', props.sale.id), {
        reason: form.reason,
        other_adjustments: form.other_adjustments || 0,
        items: itemsToReturn
    }, {
        onFinish: () => {
            processing.value = false
        }
    })
}
</script>