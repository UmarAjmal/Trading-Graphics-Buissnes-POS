<template>
  <AppLayout>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Register Reports</h1>
          <p class="text-sm text-gray-500">View closed register sessions details</p>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Session ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opened By</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opened At</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Closed At</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Opening Cash</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Closing Cash</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Difference</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="session in sessions.data" :key="session.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                  #{{ session.id }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ session.user ? session.user.name : 'Unknown' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(session.opened_at) }}
                  <span class="block text-xs uppercase">{{ formatTime(session.opened_at) }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(session.closed_at) }}
                  <span class="block text-xs uppercase">{{ formatTime(session.closed_at) }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                  {{ formatCurrency(session.opening_cash) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                  {{ formatCurrency(session.closing_cash) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold"
                    :class="session.cash_difference < 0 ? 'text-red-600' : (session.cash_difference > 0 ? 'text-green-600' : 'text-gray-400')">
                  {{ formatCurrency(session.cash_difference) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                  <Link :href="route('reports.register.show', session.id)" class="text-indigo-600 hover:text-indigo-900 inline-flex items-center gap-1">
                    <EyeIcon class="w-5 h-5" />
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination -->
        <div v-if="sessions.links && sessions.links.length > 3" class="px-6 py-4 border-t border-gray-200">
            <!-- Reuse generic pagination if available, or simple links -->
            <div class="flex gap-1 justify-center">
                <Link v-for="(link, k) in sessions.links" :key="k"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="px-3 py-1 rounded border"
                    :class="link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                    :preserve-scroll="true"
                />
            </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { EyeIcon } from 'lucide-vue-next'

const props = defineProps({
  sessions: Object
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
</script>
