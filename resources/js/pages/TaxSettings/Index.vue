<template>
  <AppLayout>
    <PageHeader
      title="Tax Configuration"
      subtitle="Configure tax rates, GST settings, and tax calculation rules"
    />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Tax Settings Form -->
      <UiCard>
        <div class="p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            General Tax Settings
          </h3>
          
          <form @submit.prevent="updateTaxSettings" class="space-y-4">
            <!-- Tax Enabled -->
            <div class="flex items-center justify-between">
              <div>
                <UiLabel>Enable Tax Calculation</UiLabel>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Apply taxes to all sales transactions
                </p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input
                  v-model="taxForm.enabled"
                  type="checkbox"
                  class="sr-only peer"
                >
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
              </label>
            </div>

            <!-- Default Tax Rate -->
            <div>
              <UiLabel for="default_rate">Default Tax Rate (%)</UiLabel>
              <UiInput
                id="default_rate"
                v-model.number="taxForm.default_rate"
                type="number"
                step="0.01"
                min="0"
                max="100"
                placeholder="18.00"
              />
            </div>

            <!-- Tax Type -->
            <div>
              <UiLabel for="tax_type">Tax Type</UiLabel>
              <select
                id="tax_type"
                v-model="taxForm.type"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="inclusive">Inclusive (price includes tax)</option>
                <option value="exclusive">Exclusive (tax added to price)</option>
              </select>
            </div>

            <!-- Tax Display -->
            <div>
              <UiLabel for="display_type">Tax Display on Receipt</UiLabel>
              <select
                id="display_type"
                v-model="taxForm.display_type"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="separate">Show as separate line</option>
                <option value="inclusive">Include in item price</option>
                <option value="both">Show both itemized and total</option>
              </select>
            </div>

            <!-- Tax Number -->
            <div>
              <UiLabel for="tax_number">Business Tax Number</UiLabel>
              <UiInput
                id="tax_number"
                v-model="taxForm.tax_number"
                type="text"
                placeholder="Enter GST/VAT/TIN number"
              />
            </div>

            <UiButton
              type="submit"
              variant="primary"
              :loading="taxForm.processing"
              class="w-full"
            >
              Save Tax Settings
            </UiButton>
          </form>
        </div>
      </UiCard>

      <!-- Tax Rates Management -->
      <UiCard>
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              Tax Rates
            </h3>
            <UiButton
              @click="addTaxRate"
              variant="primary"
              size="sm"
            >
              Add Rate
            </UiButton>
          </div>

          <!-- Tax Rates List -->
          <div class="space-y-3">
            <div
              v-for="(rate, index) in taxRates"
              :key="index"
              class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
            >
              <div class="flex-1 grid grid-cols-2 gap-3">
                <UiInput
                  v-model="rate.name"
                  placeholder="Tax name (e.g., GST, VAT)"
                  size="sm"
                />
                <div class="flex items-center gap-2">
                  <UiInput
                    v-model.number="rate.rate"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    placeholder="Rate %"
                    size="sm"
                  />
                  <UiButton
                    @click="removeTaxRate(index)"
                    variant="danger"
                    size="sm"
                  >
                    ×
                  </UiButton>
                </div>
              </div>
            </div>

            <div v-if="taxRates.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
              No custom tax rates configured. Using default rate.
            </div>
          </div>

          <UiButton
            @click="saveTaxRates"
            variant="outline"
            :loading="ratesForm.processing"
            class="w-full mt-4"
          >
            Save Tax Rates
          </UiButton>
        </div>
      </UiCard>

      <!-- Tax Calculation Preview -->
      <UiCard class="lg:col-span-2">
        <div class="p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Tax Calculation Preview
          </h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Sample Calculation -->
            <div>
              <UiLabel>Sample Item Price</UiLabel>
              <UiInput
                v-model.number="previewPrice"
                type="number"
                step="0.01"
                min="0"
                placeholder="1000.00"
                class="mb-4"
              />
              
              <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
                <div class="flex justify-between">
                  <span>Base Price:</span>
                  <span>{{ formatCurrency(calculatedPreview.basePrice) }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Tax ({{ taxForm.default_rate }}%):</span>
                  <span>{{ formatCurrency(calculatedPreview.taxAmount) }}</span>
                </div>
                <div class="flex justify-between font-semibold border-t pt-2">
                  <span>Total Price:</span>
                  <span>{{ formatCurrency(calculatedPreview.totalPrice) }}</span>
                </div>
              </div>
            </div>

            <!-- Tax Information -->
            <div>
              <h4 class="font-medium text-gray-900 dark:text-white mb-3">Current Configuration</h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span>Tax Calculation:</span>
                  <span class="font-medium">{{ taxForm.enabled ? 'Enabled' : 'Disabled' }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Default Rate:</span>
                  <span class="font-medium">{{ taxForm.default_rate }}%</span>
                </div>
                <div class="flex justify-between">
                  <span>Tax Type:</span>
                  <span class="font-medium capitalize">{{ taxForm.type }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Display Type:</span>
                  <span class="font-medium capitalize">{{ taxForm.display_type.replace('_', ' ') }}</span>
                </div>
                <div v-if="taxForm.tax_number" class="flex justify-between">
                  <span>Tax Number:</span>
                  <span class="font-medium">{{ taxForm.tax_number }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </UiCard>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import UiButton from '@/components/UiButton.vue'
import UiInput from '@/components/UiInput.vue'
import UiLabel from '@/components/UiLabel.vue'
import { formatCurrency } from '@/utils/currency'

// Props
const props = defineProps({
  settings: {
    type: Object,
    default: () => ({
      enabled: true,
      default_rate: 18.00,
      type: 'exclusive',
      display_type: 'separate',
      tax_number: ''
    })
  },
  rates: {
    type: Array,
    default: () => []
  }
})

// Tax settings form
const taxForm = useForm({
  enabled: props.settings.enabled,
  default_rate: props.settings.default_rate,
  type: props.settings.type,
  display_type: props.settings.display_type,
  tax_number: props.settings.tax_number
})

// Tax rates
const taxRates = ref([...props.rates])

// Rates form for saving
const ratesForm = useForm({
  rates: taxRates
})

// Preview calculation
const previewPrice = ref(1000)

const calculatedPreview = computed(() => {
  const price = previewPrice.value || 0
  const rate = taxForm.default_rate || 0
  
  if (!taxForm.enabled) {
    return {
      basePrice: price,
      taxAmount: 0,
      totalPrice: price
    }
  }

  if (taxForm.type === 'inclusive') {
    // Price includes tax
    const basePrice = price / (1 + rate / 100)
    const taxAmount = price - basePrice
    return {
      basePrice,
      taxAmount,
      totalPrice: price
    }
  } else {
    // Tax is added to price
    const taxAmount = price * (rate / 100)
    return {
      basePrice: price,
      taxAmount,
      totalPrice: price + taxAmount
    }
  }
})

// Methods
const updateTaxSettings = () => {
  taxForm.post(route('settings.tax.update'), {
    preserveScroll: true
  })
}

const addTaxRate = () => {
  taxRates.value.push({
    name: '',
    rate: 0
  })
}

const removeTaxRate = (index) => {
  taxRates.value.splice(index, 1)
}

const saveTaxRates = () => {
  ratesForm.rates = taxRates.value
  ratesForm.post(route('settings.tax.rates.update'), {
    preserveScroll: true
  })
}
</script>