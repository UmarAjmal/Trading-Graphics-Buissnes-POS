<template>
  <div class="card-header px-6 py-4 border-b border-gray-200 bg-gray-50">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <!-- Icon slot -->
        <div v-if="$slots.icon" class="flex-shrink-0">
          <slot name="icon"></slot>
        </div>
        
        <!-- Title and subtitle -->
        <div>
          <h3 v-if="title || $slots.title" class="text-lg font-medium text-gray-900">
            <slot name="title">{{ title }}</slot>
          </h3>
          <p v-if="subtitle || $slots.subtitle" class="text-sm text-gray-600 mt-1">
            <slot name="subtitle">{{ subtitle }}</slot>
          </p>
        </div>
      </div>
      
      <!-- Actions slot -->
      <div v-if="$slots.actions" class="flex items-center space-x-2">
        <slot name="actions"></slot>
      </div>
    </div>
    
    <!-- Additional content slot -->
    <div v-if="$slots.default" class="mt-4">
      <slot></slot>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CardHeader',
  props: {
    title: {
      type: String,
      default: ''
    },
    subtitle: {
      type: String,
      default: ''
    }
  }
}
</script>