<template>
  <div class="mb-4 lg:mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">
          {{ title }}
        </h1>
        <p v-if="subtitle" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ subtitle }}
        </p>
      </div>
      
      <div v-if="$slots.actions" class="flex items-center">
        <slot name="actions" />
      </div>
    </div>
    
    <nav v-if="breadcrumbs && breadcrumbs.length" class="flex mt-4" aria-label="Breadcrumb">
      <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li v-for="(item, index) in breadcrumbs" :key="index" class="inline-flex items-center">
          <template v-if="index > 0">
            <svg class="w-6 h-6 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
            </svg>
          </template>
          
          <a
            v-if="item.href && index < breadcrumbs.length - 1"
            :href="item.href"
            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
          >
            <component
              v-if="item.icon && index === 0"
              :is="item.icon"
              class="w-4 h-4 mr-2"
            />
            {{ item.name }}
          </a>
          
          <span
            v-else
            class="inline-flex items-center text-sm font-medium text-gray-500 dark:text-gray-400"
          >
            <component
              v-if="item.icon && index === 0"
              :is="item.icon"
              class="w-4 h-4 mr-2"
            />
            {{ item.name }}
          </span>
        </li>
      </ol>
    </nav>
  </div>
</template>

<script>
export default {
  name: 'PageHeader',
  props: {
    title: {
      type: String,
      required: true
    },
    subtitle: {
      type: String,
      default: null
    },
    breadcrumbs: {
      type: Array,
      default: () => []
    }
  }
}
</script>