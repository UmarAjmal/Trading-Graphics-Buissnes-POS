<template>
  <AppLayout>
    <PageHeader
      :title="isEdit ? 'Edit Product' : 'Add New Product'"
      :subtitle="isEdit ? 'Update product information' : 'Create a new product in your catalog'"
    >
      <div class="flex gap-3">
        <UiButton
          variant="outline"
          @click="$inertia.visit(route('products.index'))"
        >
          Cancel
        </UiButton>
        <UiButton
          variant="primary"
          @click="submitForm"
          :loading="processing"
        >
          {{ isEdit ? 'Update' : 'Create' }} Product
        </UiButton>
      </div>
    </PageHeader>

    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Product Type Selection -->
      <UiCard v-if="!isEdit" title="Product Type" padding="lg">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            @click="form.type = 'simple'"
            :class="[
              'relative rounded-lg border-2 cursor-pointer p-6 transition-colors',
              form.type === 'simple'
                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
            ]"
          >
            <div class="flex items-center">
              <input
                type="radio"
                :value="'simple'"
                v-model="form.type"
                class="sr-only"
              >
              <div class="flex-1">
                <div class="flex items-center">
                  <svg class="w-8 h-8 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m8-8v2m-3 3h6m-6 4h6M8 17h8"/>
                  </svg>
                  <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Simple Product
                  </h3>
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                  Regular products sold by quantity (pieces, packets, liters, etc.)
                </p>
              </div>
              <div
                v-if="form.type === 'simple'"
                class="absolute top-4 right-4 w-5 h-5 bg-primary-500 rounded-full flex items-center justify-center"
              >
                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
              </div>
            </div>
          </div>

          <div
            @click="form.type = 'panaflex_roll'"
            :class="[
              'relative rounded-lg border-2 cursor-pointer p-6 transition-colors',
              form.type === 'panaflex_roll'
                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
            ]"
          >
            <div class="flex items-center">
              <input
                type="radio"
                :value="'panaflex_roll'"
                v-model="form.type"
                class="sr-only"
              >
              <div class="flex-1">
                <div class="flex items-center">
                  <svg class="w-8 h-8 text-purple-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Panaflex Roll
                  </h3>
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                  Panaflex materials sold by square feet with width and length specifications
                </p>
              </div>
              <div
                v-if="form.type === 'panaflex_roll'"
                class="absolute top-4 right-4 w-5 h-5 bg-primary-500 rounded-full flex items-center justify-center"
              >
                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
              </div>
            </div>
          </div>
        </div>
      </UiCard>

      <!-- Product Information -->
      <UiCard title="Product Information" padding="lg">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Product Name -->
          <div class="lg:col-span-2">
            <UiInput
              v-model="form.name"
              label="Product Name"
              required
              :error="errors.name"
              placeholder="Enter product name"
            />
          </div>

          <!-- SKU -->
          <UiInput
            v-model="form.sku"
            label="SKU (Stock Keeping Unit)"
            :error="errors.sku"
            placeholder="Auto-generated if empty"
          >
            <template #suffix>
              <UiButton
                variant="ghost"
                size="sm"
                @click="generateSku"
                :loading="generatingSku"
                type="button"
              >
                Generate
              </UiButton>
            </template>
          </UiInput>

          <!-- Category -->
          <UiSelect
            v-model="form.category_id"
            label="Category"
            required
            :error="errors.category_id"
            :options="categoryOptions"
            placeholder="Select category"
          />

          <!-- Unit (Only for Simple Products) -->
          <UiSelect
            v-if="form.type === 'simple'"
            v-model="form.unit_id"
            label="Unit of Measurement"
            required
            :error="errors.unit_id"
            :options="unitOptions"
            placeholder="Select unit"
          />

          <!-- Description -->
          <div class="lg:col-span-2">
            <UiTextarea
              v-model="form.description"
              label="Description"
              :error="errors.description"
              :rows="3"
              placeholder="Product description (optional)"
            />
          </div>
        </div>
      </UiCard>

      <!-- Pricing Information -->
      <UiCard title="Pricing & Stock" padding="lg">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Cost Price -->
          <UiInput
            v-model="form.cost_price"
            :label="form.type === 'panaflex_roll' ? 'Cost Price (Per Sq. Ft)' : 'Cost Price (PKR)'"
            type="number"
            step="0.01"
            min="0"
            :error="errors.cost_price"
            placeholder="0.00"
          >
            <template #prefix>
              <span class="text-gray-500 text-sm">PKR</span>
            </template>
          </UiInput>

          <!-- Selling Price -->
          <UiInput
            v-if="form.type !== 'panaflex_roll'"
            v-model="form.selling_price"
            label="Selling Price (PKR)"
            type="number"
            step="0.01"
            min="0"
            required
            :error="errors.selling_price"
            placeholder="0.00"
          >
            <template #prefix>
              <span class="text-gray-500 text-sm">PKR</span>
            </template>
          </UiInput>

          <!-- Current Stock -->
          <div>
            <UiInput
              v-model="form.current_stock"
              :label="form.type === 'panaflex_roll' ? 'Opening Stock (Sq. Ft)' : 'Opening Stock'"
              type="number"
              step="0.01"
              min="0"
              :error="errors.current_stock"
              placeholder="0"
            />
            <p class="text-xs text-amber-600 mt-1 flex items-start gap-1">
              <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
              <span><strong>Important:</strong> Only enter stock here if you are NOT creating a Purchase Order for it. If you create a Purchase Order later, stock will be added again.</span>
            </p>
          </div>

          <!-- Minimum Stock -->
          <UiInput
            v-model="form.min_stock"
            :label="form.type === 'panaflex_roll' ? 'Minimum Stock (Sq. Ft)' : 'Minimum Stock Level'"
            type="number"
            step="0.01"
            min="0"
            :error="errors.min_stock"
            placeholder="0"
          />
        </div>

        <!-- Profit Margin Display -->
        <div v-if="form.cost_price && form.selling_price" class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
          <div class="flex items-center justify-between text-sm">
            <span class="font-medium text-green-800 dark:text-green-200">Profit Margin:</span>
            <span class="text-green-600 dark:text-green-400">
              PKR {{ formatNumber(form.selling_price - form.cost_price) }} 
              ({{ Math.round(((form.selling_price - form.cost_price) / form.selling_price) * 100) }}%)
            </span>
          </div>
        </div>
      </UiCard>

      <!-- Panaflex Specifications (Only for Panaflex Roll) -->
      <UiCard v-if="form.type === 'panaflex_roll'" title="Panaflex Specifications" padding="lg">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Width -->
          <UiInput
            v-model="panaflexSpec.roll_width_inch"
            label="Width (Inches)"
            type="number"
            step="0.1"
            min="0"
            required
            :error="errors['panaflex_spec.roll_width_inch']"
            placeholder="0.0"
          >
            <template #suffix>
              <span class="text-gray-500 text-sm">in</span>
            </template>
          </UiInput>

          <!-- Length -->
          <UiInput
            v-model="panaflexSpec.roll_length_meter"
            label="Length (Meters)"
            type="number"
            step="0.1"
            min="0"
            required
            :error="errors['panaflex_spec.roll_length_meter']"
            placeholder="0.0"
          >
            <template #suffix>
              <span class="text-gray-500 text-sm">m</span>
            </template>
          </UiInput>

          <!-- Quality/Grade -->
          <UiSelect
            v-model="panaflexSpec.quality"
            label="Quality/Grade"
            required
            :error="errors['panaflex_spec.quality']"
            :options="qualityOptions"
            placeholder="Select quality"
          />

          <!-- Color -->
          <UiInput
            v-model="panaflexSpec.color"
            label="Color"
            :error="errors['panaflex_spec.color']"
            placeholder="e.g., White, Transparent, Blue"
          />

          <!-- GSM -->
          <UiInput
            v-model="panaflexSpec.gsm"
            label="GSM (Weight)"
            type="number"
            min="0"
            :error="errors['panaflex_spec.gsm']"
            placeholder="e.g., 280, 340, 440"
          >
            <template #suffix>
              <span class="text-gray-500 text-sm">GSM</span>
            </template>
          </UiInput>

          <!-- Finish -->
          <UiSelect
            v-model="panaflexSpec.finish"
            label="Finish Type"
            :error="errors['panaflex_spec.finish']"
            :options="finishOptions"
            placeholder="Select finish"
          />

          <!-- Rate per Square Foot -->
          <UiInput
            v-model="panaflexSpec.rate_per_sqft"
            label="Rate per Square Foot (PKR)"
            type="number"
            step="0.01"
            min="0"
            required
            :error="errors['panaflex_spec.rate_per_sqft']"
            placeholder="0.00"
          >
            <template #prefix>
              <span class="text-gray-500 text-sm">PKR</span>
            </template>
          </UiInput>
        </div>

        <!-- Total Square Feet Display -->
        <div v-if="panaflexSpec.roll_width_inch && panaflexSpec.roll_length_meter" class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
          <div class="flex items-center justify-between text-sm">
            <span class="font-medium text-blue-800 dark:text-blue-200">Total Area:</span>
            <span class="text-blue-600 dark:text-blue-400">
              {{ formatNumber(getTotalSquareFeet()) }} square feet
            </span>
          </div>
        </div>
      </UiCard>

      <!-- Product Status -->
      <UiCard title="Product Status" padding="lg">
        <div class="flex items-center">
          <input
            v-model="form.active"
            type="checkbox"
            class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
          <label class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
            Product is active and available for sale
          </label>
        </div>
      </UiCard>

      <!-- Bottom Action Buttons -->
      <UiCard padding="lg">
        <div class="flex justify-end gap-3">
          <UiButton
            variant="outline"
            @click="$inertia.visit(route('products.index'))"
          >
            Cancel
          </UiButton>
          <UiButton
            variant="primary"
            @click="submitForm"
            :loading="processing"
            type="submit"
          >
            {{ isEdit ? 'Update Product' : 'Save This Product' }}
          </UiButton>
        </div>
      </UiCard>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'

// Components
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiButton from '@/components/UiButton.vue'
import UiInput from '@/components/UiInput.vue'
import UiSelect from '@/components/UiSelect.vue'
import UiTextarea from '@/components/UiTextarea.vue'
import UiCard from '@/components/UiCard.vue'

// Props
const props = defineProps({
  product: {
    type: Object,
    default: () => null
  },
  categories: {
    type: Array,
    default: () => []
  },
  units: {
    type: Array,
    default: () => []
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

// State
const generatingSku = ref(false)
const processing = ref(false)

// Computed
const isEdit = computed(() => !!props.product)

const categoryOptions = computed(() => {
  return props.categories.map(category => ({
    value: category.id,
    label: category.name
  }))
})

const unitOptions = computed(() => {
  return props.units.map(unit => ({
    value: unit.id,
    label: `${unit.name} (${unit.symbol})`
  }))
})

const qualityOptions = computed(() => [
  { value: 'economy', label: 'Economy' },
  { value: 'standard', label: 'Standard' },
  { value: 'premium', label: 'Premium' },
  { value: 'luxury', label: 'Luxury' }
])

const finishOptions = computed(() => [
  { value: 'matte', label: 'Matte' },
  { value: 'glossy', label: 'Glossy' },
  { value: 'semi_gloss', label: 'Semi-Gloss' },
  { value: 'satin', label: 'Satin' }
])

// Form data
const form = useForm({
  name: props.product?.name || '',
  sku: props.product?.sku || '',
  type: props.product?.type || 'simple',
  category_id: props.product?.category_id || '',
  unit_id: props.product?.unit_id || '',
  description: props.product?.description || '',
  cost_price: props.product?.cost_price || '',
  selling_price: props.product?.selling_price || '',
  current_stock: props.product?.current_stock || '',
  min_stock: props.product?.min_stock || '',
  barcode: props.product?.barcode || '',
  taxable: props.product?.taxable || false,
  active: props.product?.active ?? true,
  image: null
})

// Panaflex specifications
const panaflexSpec = reactive({
  roll_width_inch: props.product?.panaflex_spec?.roll_width_inch || '',
  roll_length_meter: props.product?.panaflex_spec?.roll_length_meter || '',
  rate_per_sqft: props.product?.panaflex_spec?.rate_per_sqft || '',
  quality: props.product?.panaflex_spec?.quality || '',
  color: props.product?.panaflex_spec?.color || '',
  gsm: props.product?.panaflex_spec?.gsm || '',
  finish: props.product?.panaflex_spec?.finish || ''
})

// Watchers
watch(() => panaflexSpec.rate_per_sqft, (newVal) => {
  if (form.type === 'panaflex_roll') {
    form.selling_price = newVal
  }
})

watch(() => form.type, (newVal) => {
  if (newVal === 'panaflex_roll') {
    form.selling_price = panaflexSpec.rate_per_sqft
  }
})

// Methods
const generateSku = async () => {
  if (!form.name) {
    alert('Please enter product name first')
    return
  }

  generatingSku.value = true

  try {
    const { data } = await axios.post('/api/products/generate-sku', {
      name: form.name,
      type: form.type
    })

    if (data.success) {
      form.sku = data.sku
    } else {
      alert('Failed to generate SKU')
    }
  } catch (error) {
    console.error('Failed to generate SKU:', error)
    alert('Failed to generate SKU')
  } finally {
    generatingSku.value = false
  }
}

const getTotalSquareFeet = () => {
  if (!panaflexSpec.roll_width_inch || !panaflexSpec.roll_length_meter) return 0
  const widthFt = parseFloat(panaflexSpec.roll_width_inch) / 12
  const lengthFt = parseFloat(panaflexSpec.roll_length_meter) * 3.28
  return widthFt * lengthFt
}

const formatNumber = (value) => {
  if (value === null || value === undefined) return '0'
  return Number(value).toLocaleString('en-PK', { maximumFractionDigits: 2 })
}

const submitForm = async () => {
  // Prevent double submission
  if (processing.value) {
    console.log('Form submission already in progress...')
    return
  }

  processing.value = true

  try {
    // Prepare form data
    const formData = new FormData()
    
    // Add all form fields manually
    formData.append('name', form.name || '')
    formData.append('sku', form.sku || '')
    formData.append('type', form.type || 'simple')
    formData.append('category_id', form.category_id || '')
    formData.append('unit_id', form.unit_id || '')
    formData.append('description', form.description || '')
    formData.append('cost_price', form.cost_price || '0')
    formData.append('selling_price', form.selling_price || '0')
    formData.append('current_stock', form.current_stock || '0')
    formData.append('min_stock', form.min_stock || '0')
    formData.append('barcode', form.barcode || '')
    formData.append('active', form.active ? '1' : '0')
    
    // Add image file if selected
    if (form.image) {
      formData.append('image', form.image)
    }

    // Add method spoofing for PUT requests with FormData
    if (isEdit.value) {
      formData.append('_method', 'PUT')
    }

    // Add panaflex specifications if applicable
    if (form.type === 'panaflex_roll') {
      formData.append('roll_width_inch', panaflexSpec.roll_width_inch || '0')
      formData.append('roll_length_meter', panaflexSpec.roll_length_meter || '0')
      formData.append('rate_per_sqft', panaflexSpec.rate_per_sqft || '0')
    }

    console.log('Submitting form data:', {
      name: form.name,
      sku: form.sku,
      type: form.type,
      category_id: form.category_id,
      unit_id: form.unit_id,
      description: form.description,
      selling_price: form.selling_price,
      cost_price: form.cost_price,
      current_stock: form.current_stock,
      min_stock: form.min_stock,
      barcode: form.barcode,
      taxable: form.taxable,
      active: form.active,
      panaflexSpec: form.type === 'panaflex_roll' ? panaflexSpec : null
    })

    // Debug FormData contents
    console.log('FormData contents:')
    for (let [key, value] of formData.entries()) {
      console.log(key + ':', value)
    }

    let response
    if (isEdit.value) {
      // Use POST with method spoofing for FormData
      response = await axios.post(`/api/products/${props.product.id}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
    } else {
      response = await axios.post('/api/products', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
    }
    
    console.log('Response:', response.data)
    
    if (response.data.success) {
      alert(isEdit.value ? 'Product updated successfully!' : 'Product created successfully!')
      // Redirect to products index
      window.location.href = route('products.index')
    } else {
      alert('Failed to save product: ' + response.data.message)
    }
  } catch (error) {
    console.error('Save failed:', error)
    console.error('Error response:', error.response?.data)
    
    let errorMessage = 'Failed to save product: '
    
    if (error.response?.data?.errors) {
      // Validation errors
      const errors = error.response.data.errors
      const errorMessages = Object.values(errors).flat()
      errorMessage += errorMessages.join(', ')
    } else if (error.response?.data?.message) {
      // General error message
      errorMessage += error.response.data.message
    } else {
      // Fallback error message
      errorMessage += error.message
    }
    
    alert(errorMessage)
  } finally {
    processing.value = false
  }
}

// Set default unit for simple products
onMounted(() => {
  if (!isEdit.value && form.type === 'simple' && props.units.length > 0) {
    const pcsUnit = props.units.find(unit => unit.symbol === 'PCS')
    if (pcsUnit) {
      form.unit_id = pcsUnit.id
    }
  }
})
</script>



