<template>
  <AppLayout>
    <PageHeader
      title="Expenses"
      subtitle="Track and manage your business expenses"
    >
      <template #actions>
        <div class="flex space-x-2">
          <Link
            :href="route('expense-categories.index')"
            class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors flex items-center"
          >
            Categories
          </Link>
          <button
            @click="openCreateModal"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Expense
          </button>
        </div>
      </template>
    </PageHeader>

    <!-- Flash Messages -->
    <div v-if="$page.props.flash?.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
      <span class="block sm:inline">{{ $page.props.flash.success }}</span>
    </div>
    <div v-if="$page.props.flash?.error" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
      <span class="block sm:inline">{{ $page.props.flash.error }}</span>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6" v-if="summary">
      <UiCard class="border-l-4 border-l-blue-500">
        <div class="p-4 flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Expenses</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(summary.total) }}</p>
          </div>
          <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
        </div>
      </UiCard>

      <UiCard class="border-l-4 border-l-yellow-500">
        <div class="p-4 flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cash Drawer</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(summary.drawer) }}</p>
          </div>
          <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
          </div>
        </div>
      </UiCard>

      <UiCard class="border-l-4 border-l-purple-500">
        <div class="p-4 flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Owner / External</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(summary.external) }}</p>
          </div>
          <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
          </div>
        </div>
      </UiCard>
    </div>

    <!-- Filters -->
    <UiCard class="mb-6">
      <div class="p-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1 dark:text-gray-300">Start Date</label>
          <input v-model="filters.start_date" type="date" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 dark:text-gray-300">End Date</label>
          <input v-model="filters.end_date" type="date" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 dark:text-gray-300">Category</label>
          <div class="relative">
            <button 
                @click="showCategoryDropdown = !showCategoryDropdown" 
                class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md py-2 px-3 text-left shadow-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm dark:text-gray-100 flex justify-between items-center h-[42px]"
            >
                <span class="block truncate">{{ selectedCategoriesLabel }}</span>
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
                </span>
            </button>

            <!-- Backdrop to detect clicks outside -->
            <div v-if="showCategoryDropdown" @click="showCategoryDropdown = false" class="fixed inset-0 z-10 cursor-default"></div>

            <div v-if="showCategoryDropdown" class="absolute z-20 mt-1 w-full bg-white dark:bg-gray-700 shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                <div 
                    v-for="cat in categories" 
                    :key="cat.id" 
                    class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-white"
                    @click.stop="toggleCategory(cat.id)"
                >
                <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            :checked="filters.category_ids.includes(cat.id)"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded mr-3 pointer-events-none"
                        >
                        <span class="block truncate">{{ cat.name }}</span>
                </div>
                </div>
            </div>
          </div>
        </div>
        <div class="flex items-end">
          <button @click="applyFilters" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 w-full">Filter</button>
        </div>
      </div>
    </UiCard>

    <UiCard>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Category</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Description</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Source</th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="expense in expenses.data" :key="expense.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ formatDate(expense.date) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ expense.category?.name }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ expense.description || '-' }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span :class="expense.payment_source === 'drawer' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                  {{ expense.payment_source === 'drawer' ? 'Cash Drawer' : 'External/Owner' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(expense.amount) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button @click="openEditModal(expense)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">Edit</button>
                <button @click="deleteExpense(expense)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Delete</button>
              </td>
            </tr>
            <tr v-if="expenses.data.length === 0">
              <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No expenses found.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="p-4 border-t border-gray-200 dark:border-gray-700" v-if="expenses.links">
        <!-- Pagination Component Here (Simplified) -->
        <div class="flex justify-between items-center">
            <span class="text-sm text-gray-700 dark:text-gray-400">
                Showing {{ expenses.from }} to {{ expenses.to }} of {{ expenses.total }} results
            </span>
            <div class="flex space-x-1">
                <Link v-for="(link, k) in expenses.links" :key="k" :href="link.url" v-html="link.label" :class="['px-3 py-1 border rounded text-sm', link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700']" :disabled="!link.url" />
            </div>
        </div>
      </div>
    </UiCard>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="closeModal">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-96 max-w-full mx-4">
        <h3 class="text-lg font-semibold mb-4 dark:text-gray-100">{{ isEditing ? 'Edit Expense' : 'Add Expense' }}</h3>
        <form @submit.prevent="submitForm">
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2 dark:text-gray-200">Date <span class="text-red-500">*</span></label>
            <input
              v-model="form.date"
              type="date"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2 dark:text-gray-200">Category <span class="text-red-500">*</span></label>
            <select v-model="form.expense_category_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="" disabled>Select Category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2 dark:text-gray-200">Amount <span class="text-red-500">*</span></label>
            <input
              v-model="form.amount"
              type="number"
              step="0.01"
              min="0"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2 dark:text-gray-200">Payment Source <span class="text-red-500">*</span></label>
            <div class="flex space-x-4 mt-2">
                <label class="inline-flex items-center">
                    <input type="radio" v-model="form.payment_source" value="drawer" class="form-radio text-blue-600">
                    <span class="ml-2 dark:text-gray-300">Cash Drawer</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" v-model="form.payment_source" value="external" class="form-radio text-blue-600">
                    <span class="ml-2 dark:text-gray-300">Owner/External</span>
                </label>
            </div>
            <p class="text-xs text-gray-500 mt-1" v-if="form.payment_source === 'drawer'">
                Amount will be deducted from the current active register session.
            </p>
            <p class="text-xs text-gray-500 mt-1" v-else>
                Amount will NOT affect the register balance but will be counted in Profit/Loss.
            </p>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2 dark:text-gray-200">Description</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
          </div>
          <div class="flex space-x-3">
            <button
              type="submit"
              :disabled="form.processing"
              class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
            >
              {{ isEditing ? 'Update' : 'Add Expense' }}
            </button>
            <button
              type="button"
              @click="closeModal"
              class="flex-1 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 py-2 px-4 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useForm, router, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import { formatCurrency } from '@/utils/currency'

const props = defineProps({
  expenses: Object,
  categories: Array,
  filters: Object,
  summary: Object
})

const showModal = ref(false)
const isEditing = ref(false)
const editingId = ref(null)
const showCategoryDropdown = ref(false)

// Handle initialization of category_ids from props (legacy or new)
const initialCategoryIds = []
if (props.filters.category_ids) {
    if (Array.isArray(props.filters.category_ids)) {
        props.filters.category_ids.forEach(id => initialCategoryIds.push(parseInt(id)))
    } else {
         // handle if it comes as string
         Object.values(props.filters.category_ids).forEach(id => initialCategoryIds.push(parseInt(id)))
    }
} else if (props.filters.category_id) {
    initialCategoryIds.push(parseInt(props.filters.category_id))
}

const filters = reactive({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    category_ids: initialCategoryIds
})

const selectedCategoriesLabel = computed(() => {
    if (filters.category_ids.length === 0) return 'All Categories';
    if (filters.category_ids.length === props.categories.length) return 'All Categories';
    
    if (filters.category_ids.length === 1) {
        const cat = props.categories.find(c => c.id === filters.category_ids[0]);
        return cat ? cat.name : '1 Selected';
    }
    
    return `${filters.category_ids.length} Selected`;
})

const toggleCategory = (id) => {
    const index = filters.category_ids.indexOf(id);
    if (index === -1) {
        filters.category_ids.push(id);
    } else {
        filters.category_ids.splice(index, 1);
    }
}

const form = useForm({
  date: new Date().toISOString().substr(0, 10),
  expense_category_id: '',
  amount: '',
  payment_source: 'drawer',
  description: ''
})

const openCreateModal = () => {
  isEditing.value = false
  editingId.value = null
  form.reset()
  form.date = new Date().toISOString().substr(0, 10)
  form.payment_source = 'drawer'
  showModal.value = true
}

const openEditModal = (expense) => {
  isEditing.value = true
  editingId.value = expense.id
  form.date = expense.date
  form.expense_category_id = expense.expense_category_id
  form.amount = expense.amount
  form.payment_source = expense.payment_source
  form.description = expense.description
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  form.reset()
}

const submitForm = () => {
  if (isEditing.value) {
    form.put(route('expenses.update', editingId.value), {
      onSuccess: () => closeModal()
    })
  } else {
    form.post(route('expenses.store'), {
      onSuccess: () => closeModal()
    })
  }
}

const deleteExpense = (expense) => {
  if (confirm('Are you sure you want to delete this expense?')) {
    router.delete(route('expenses.destroy', expense.id))
  }
}

const applyFilters = () => {
    router.get(route('expenses.index'), filters, { preserveState: true })
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString()
}
</script>
