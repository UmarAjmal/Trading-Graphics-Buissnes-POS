import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/index.esm.js';
import { setGlobalCurrency } from './utils/currency';

// Import components for global registration
import AppLayout from './layouts/AppLayout.vue';
import UiButton from './components/UiButton.vue';
import UiInput from './components/UiInput.vue';
import UiSelect from './components/UiSelect.vue';
import UiTextarea from './components/UiTextarea.vue';
import UiCard from './components/UiCard.vue';
import UiModal from './components/UiModal.vue';
import UiToast from './components/UiToast.vue';
import PageHeader from './components/PageHeader.vue';
import SidebarItem from './components/SidebarItem.vue';

// Set default currency
setGlobalCurrency('PKR');

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const page = resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue'));
        return page;
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);
        
        // Register components globally
        app.component('AppLayout', AppLayout);
        app.component('UiButton', UiButton);
        app.component('UiInput', UiInput);
        app.component('UiSelect', UiSelect);
        app.component('UiTextarea', UiTextarea);
        app.component('UiCard', UiCard);
        app.component('UiModal', UiModal);
        app.component('UiToast', UiToast);
        app.component('PageHeader', PageHeader);
        app.component('SidebarItem', SidebarItem);
        
        return app.mount(el);
    },
    progress: {
        // Enable progress indicator for navigation
        delay: 250,
        color: '#4f46e5',
        showSpinner: true,
    },
})

// Add global error handler for debugging navigation issues
window.addEventListener('error', (e) => {
    console.error('Navigation error:', e.error)
})

// Add Inertia event listeners for debugging
document.addEventListener('inertia:start', (event) => {
    console.log('Navigation starting to:', event.detail.visit.url)
})

document.addEventListener('inertia:finish', (event) => {
    console.log('Navigation completed to:', event.detail.visit.url)
    if (event.detail.page && event.detail.page.component) {
        console.log('Current page component:', event.detail.page.component)
    }
})

document.addEventListener('inertia:success', (event) => {
    console.log('Navigation successful to:', event.detail.page.url)
    
    // Update CSRF token after successful navigation
    if (window.updateCSRFToken) {
        window.updateCSRFToken()
    }
})

document.addEventListener('inertia:error', (event) => {
    // Safely access event details with null checks
    const page = event.detail?.page
    const url = page?.url || 'unknown'
    const errors = page?.props?.errors || {}
    
    console.error('Navigation error to:', url, errors)
    
    // If it's a 419 error, try to refresh the CSRF token
    if (errors['419']) {
        console.warn('419 CSRF error detected, refreshing page...')
        window.location.reload()
    }
});

// Fallback for non-Inertia pages
const appElement = document.getElementById('app');
if (appElement && !appElement.hasAttribute('data-page')) {
    const app = createApp(Dashboard);
    app.component('AppLayout', AppLayout);
    app.component('Dashboard', Dashboard);
    app.component('UiButton', UiButton);
    app.component('UiInput', UiInput);
    app.component('UiSelect', UiSelect);
    app.component('UiTextarea', UiTextarea);
    app.component('UiCard', UiCard);
    app.component('UiModal', UiModal);
    app.component('UiToast', UiToast);
    app.component('PageHeader', PageHeader);
    app.component('SidebarItem', SidebarItem);
    app.mount('#app');
}
